<?php
// app/Livewire/Board/DelegateDrawer.php

namespace App\Livewire\Board;

use App\Models\Alliance;
use App\Models\Candidate;
use App\Models\Delegate;
use App\Models\DelegateCandidateStatus;
use App\Models\Group;
use App\Models\Guarantor;
use App\Models\Interaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

class DelegateDrawer extends Component
{
    public int $delegateId;
    public int $candidateId;

    // Delegate profile (shared across candidates)
    public ?string $phonePrimary = null;
    public ?string $phoneSecondary = null;
    public ?int $guarantorId = null;
    public ?string $notes = null;

    public ?string $category = null;

    // ONLY ONE group
    public ?int $groupId = null;

    // Interaction (logged from drawer)
    public string $interactionType = 'note';
    public ?string $interactionOutcome = null; // select option
    public ?string $interactionNotes = null;
    public ?string $nextStepAt = null; // datetime-local string

    public function mount(int $delegateId, int $candidateId): void
    {
        $this->delegateId  = $delegateId;
        $this->candidateId = $candidateId;

        $d = Delegate::query()->with('groups')->findOrFail($this->delegateId);

        $this->phonePrimary   = $d->phone_primary ?? null;
        $this->phoneSecondary = $d->phone_secondary ?? null;
        $this->guarantorId    = $d->guarantor_id ? (int) $d->guarantor_id : null;

        $this->notes    = $d->notes ?? ($d->internal_notes ?? null);
        $this->category = $d->category ?? null;

        // pick first group if multiple exist
        $this->groupId = $d->groups?->first()?->id ? (int) $d->groups->first()->id : null;
    }

    /**
     * ONE SAVE BUTTON: updates delegate meta + (optionally) logs interaction if user filled it.
     */
    public function save(): void
    {
        $data = $this->validate([
            'phonePrimary'        => ['nullable', 'string', 'max:32'],
            'phoneSecondary'      => ['nullable', 'string', 'max:32'],
            'guarantorId'         => ['nullable', 'integer'],
            'notes'               => ['nullable', 'string'],
            'category'            => ['nullable', 'string', 'max:255'],
            'groupId'             => ['nullable', 'integer'],

            'interactionType'     => ['required', 'string', 'max:50'],
            'interactionOutcome'  => ['nullable', 'string', 'max:255'],
            'interactionNotes'    => ['nullable', 'string'],
            'nextStepAt'          => ['nullable', 'date'],
        ]);

        // 1) Save delegate meta
        $gid = $data['guarantorId'] ?? null;
        if ($gid === 0) $gid = null;

        $payload = [
            'phone_primary'   => $data['phonePrimary'] ?? null,
            'phone_secondary' => $data['phoneSecondary'] ?? null,
            'guarantor_id'    => $gid,
            'category'        => $data['category'] ?? null,
        ];

        $delegate = Delegate::query()->findOrFail($this->delegateId);

        if (Schema::hasColumn($delegate->getTable(), 'notes')) {
            $payload['notes'] = $data['notes'] ?? null;
        } elseif (Schema::hasColumn($delegate->getTable(), 'internal_notes')) {
            $payload['internal_notes'] = $data['notes'] ?? null;
        }

        Delegate::query()->whereKey($this->delegateId)->update($payload);

        // group: sync single group (or none)
        $g = $data['groupId'] ?? null;
        if ($g) {
            $delegate->groups()->sync([(int) $g]);
        } else {
            $delegate->groups()->sync([]);
        }

        // 2) Optionally log interaction (only if notes or outcome or next step was provided)
        $shouldLog = !empty(trim((string) ($data['interactionNotes'] ?? '')))
            || !empty(trim((string) ($data['interactionOutcome'] ?? '')))
            || !empty($data['nextStepAt']);

        if ($shouldLog) {
            $interactionPayload = [
                'user_id'      => Auth::id(),
                'delegate_id'  => $this->delegateId,
                'type'         => $data['interactionType'],
                'outcome'      => $data['interactionOutcome'] ?? null,
                'notes'        => $data['interactionNotes'] ?? null,
                'next_step_at' => !empty($data['nextStepAt']) ? Carbon::parse($data['nextStepAt']) : null,
            ];

            Interaction::create($interactionPayload);

            // Clear interaction inputs after save
            $this->interactionOutcome = null;
            $this->interactionNotes = null;
            $this->nextStepAt = null;
        }

        $this->dispatch('notify', message: $shouldLog ? 'Saved + Interaction logged.' : 'Saved.');
        $this->dispatch('refresh-board');
    }

    public function setStance(string $stance): void
    {
        $stance = strtolower(trim($stance));
        if (!in_array($stance, ['for', 'indicative', 'against'], true)) return;

        $row = DelegateCandidateStatus::query()->firstOrNew([
            'delegate_id'  => $this->delegateId,
            'candidate_id' => $this->candidateId,
        ]);

        if (!$row->exists && empty($row->confidence)) {
            $row->confidence = 50;
        }

        $row->stance = $stance;
        $row->save();

        $this->dispatch('notify', message: 'Status updated.');
        $this->dispatch('refresh-board');
    }

    public function updateConfidence(mixed $value): void
    {
        $confidence = max(0, min(100, (int) $value));

        $row = DelegateCandidateStatus::query()->firstOrCreate(
            ['delegate_id' => $this->delegateId, 'candidate_id' => $this->candidateId],
            ['stance' => 'indicative', 'confidence' => 50]
        );

        $row->confidence = $confidence;
        $row->save();

        $this->dispatch('notify', message: 'Confidence updated.');
        $this->dispatch('refresh-board');
    }

    /**
     * @return array{total: float, rows: array<int,array{source:string,weight:int,stance:string,confidence:int,contribution:float}>}
     */
    private function spilloverDetails(): array
    {
        $incoming = Alliance::query()
            ->where('is_active', true)
            ->where('to_candidate_id', $this->candidateId)
            ->get(['from_candidate_id', 'weight']);

        if ($incoming->isEmpty()) return ['total' => 0.0, 'rows' => []];

        $weights = [];
        foreach ($incoming as $a) {
            $weights[(int) $a->from_candidate_id] = max(0.0, min(1.0, (float) $a->weight));
        }

        $sourceIds = array_keys($weights);

        $sourceNames = Candidate::query()
            ->whereIn('id', $sourceIds)
            ->pluck('name', 'id')
            ->map(fn ($v) => (string) $v)
            ->all();

        $statuses = DelegateCandidateStatus::query()
            ->where('delegate_id', $this->delegateId)
            ->whereIn('candidate_id', $sourceIds)
            ->get(['candidate_id', 'stance', 'confidence']);

        $total = 0.0;
        $rows = [];

        foreach ($statuses as $s) {
            $sourceId = (int) $s->candidate_id;
            $w = $weights[$sourceId] ?? 0.0;
            if ($w <= 0.0) continue;

            $stanceVal = match ($s->stance) {
                'for' => 1.0,
                'indicative' => 0.5,
                default => 0.0,
            };

            $confInt = (int) ($s->confidence ?? 50);
            $conf = max(0.0, min(100.0, (float) $confInt)) / 100.0;

            $contrib = $w * $stanceVal * $conf;
            if ($contrib <= 0.0) continue;

            $total += $contrib;

            $rows[] = [
                'source' => $sourceNames[$sourceId] ?? ('Candidate ' . $sourceId),
                'weight' => (int) round($w * 100),
                'stance' => (string) $s->stance,
                'confidence' => $confInt,
                'contribution' => $contrib,
            ];
        }

        usort($rows, fn ($a, $b) => $b['contribution'] <=> $a['contribution']);
        return ['total' => $total, 'rows' => $rows];
    }

    public function render()
    {
        $delegate = Delegate::query()
            ->with(['district.region', 'groups', 'guarantor'])
            ->findOrFail($this->delegateId);

        $candidate = Candidate::query()->find($this->candidateId);

        $status = DelegateCandidateStatus::query()
            ->where('delegate_id', $this->delegateId)
            ->where('candidate_id', $this->candidateId)
            ->first();

        $spill = $this->spilloverDetails();

        $guarantors = Guarantor::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name']);

        $groups = Group::query()->orderBy('name')->get(['id', 'name']);

        $categories = Delegate::query()
            ->select('category')
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->map(fn ($v) => (string) $v)
            ->all();

        $recentInteractions = Interaction::query()
            ->with(['user:id,name'])
            ->where('delegate_id', $this->delegateId)
            ->latest()
            ->limit(10)
            ->get(['id', 'user_id', 'delegate_id', 'type', 'outcome', 'notes', 'next_step_at', 'created_at']);

        $outcomes = [
            'supportive' => 'Supportive',
            'neutral' => 'Neutral',
            'against' => 'Against',
            'needs_follow_up' => 'Needs follow-up',
            'no_answer' => 'No answer',
            'meeting_set' => 'Meeting set',
        ];

        $types = [
            'note' => 'Note',
            'call' => 'Call',
            'visit' => 'Visit',
            'meeting' => 'Meeting',
            'whatsapp' => 'WhatsApp',
            'sms' => 'SMS',
        ];

        return view('livewire.board.delegate-drawer', [
            'delegate' => $delegate,
            'candidate' => $candidate,
            'status' => $status,
            'spill' => $spill,
            'guarantors' => $guarantors,
            'groups' => $groups,
            'categories' => $categories,
            'recentInteractions' => $recentInteractions,
            'outcomes' => $outcomes,
            'types' => $types,
        ]);
    }
}
