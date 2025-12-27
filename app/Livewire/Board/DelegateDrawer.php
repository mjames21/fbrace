<?php

namespace App\Livewire\Board;

use App\Models\Alliance;
use App\Models\Candidate;
use App\Models\Delegate;
use App\Models\DelegateCandidateStatus;
use Livewire\Component;

class DelegateDrawer extends Component
{
    public int $delegateId;
    public int $candidateId;

    public function mount(int $delegateId, int $candidateId): void
    {
        $this->delegateId = $delegateId;
        $this->candidateId = $candidateId;
    }

   public function setStance(string $stance, int $confidence = 60): void
{
    $stance = strtolower(trim($stance));
    $confidence = max(0, min(100, (int) $confidence));

    if (!in_array($stance, DelegateCandidateStatus::STANCES, true)) {
        $this->dispatch('notify', message: 'Invalid stance.');
        return;
    }

    DelegateCandidateStatus::updateOrCreate(
        ['delegate_id' => $this->delegateId, 'candidate_id' => $this->candidateId],
        ['stance' => $stance, 'confidence' => $confidence]
    );

    $this->dispatch('notify', message: 'Status updated.');
}


    public function updateConfidence(mixed $value): void
    {
        $confidence = max(0, min(100, (int) $value));

        $row = DelegateCandidateStatus::firstOrCreate(
            ['delegate_id' => $this->delegateId, 'candidate_id' => $this->candidateId],
            ['stance' => 'indicative', 'confidence' => 50]
        );

        $row->confidence = $confidence;
        $row->save();

        $this->dispatch('notify', message: 'Confidence updated.');
    }

    /**
     * Spillover contributors for THIS delegate into the current candidate.
     *
     * @return array{total: float, rows: array<int,array{source:string,weight:int,stance:string,confidence:int,contribution:float}>}
     */
    private function spilloverDetails(): array
    {
        $incoming = Alliance::query()
            ->where('is_active', true)
            ->where('to_candidate_id', $this->candidateId)
            ->get(['from_candidate_id', 'weight']);

        if ($incoming->isEmpty()) {
            return ['total' => 0.0, 'rows' => []];
        }

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
                'source' => $sourceNames[$sourceId] ?? ('Candidate '.$sourceId),
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
            ->with(['district.region', 'groups'])
            ->findOrFail($this->delegateId);

        $candidate = Candidate::query()->find($this->candidateId);

        $status = DelegateCandidateStatus::query()
            ->where('delegate_id', $this->delegateId)
            ->where('candidate_id', $this->candidateId)
            ->first();

        $spill = $this->spilloverDetails();

        return view('livewire.board.delegate-drawer', [
            'delegate' => $delegate,
            'candidate' => $candidate,
            'status' => $status,
            'spill' => $spill,
        ]);
    }
}
