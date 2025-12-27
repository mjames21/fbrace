<?php

namespace App\Livewire\Manage;

use App\Models\Alliance;
use App\Models\Candidate;
use App\Models\CandidateAlliancePolicy;
use App\Models\DelegateCandidateStatus;
use Livewire\Component;

class Alliances extends Component
{
    public ?int $editingId = null;

    public ?int $from_candidate_id = null;
    public ?int $to_candidate_id = null;

    public int $weight_percent = 25;
    public bool $is_active = true;
    public ?string $notes = null;

    // Policy (per source candidate)
    public string $policy_mode = 'split'; // split|exclusive
    public int $policy_max_total_weight_percent = 100;

    public function mount(): void
    {
        $this->is_active = true;
        $this->weight_percent = 25;
        $this->policy_mode = 'split';
        $this->policy_max_total_weight_percent = 100;
    }

    public function updatedFromCandidateId(): void
    {
        $this->loadPolicyForSource();
    }

    public function createNew(): void
    {
        $this->reset([
            'editingId',
            'from_candidate_id',
            'to_candidate_id',
            'weight_percent',
            'is_active',
            'notes',
            'policy_mode',
            'policy_max_total_weight_percent',
        ]);

        $this->is_active = true;
        $this->weight_percent = 25;
        $this->policy_mode = 'split';
        $this->policy_max_total_weight_percent = 100;

        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function edit(int $id): void
    {
        $a = Alliance::query()->findOrFail($id);

        $this->editingId = (int) $a->id;
        $this->from_candidate_id = (int) $a->from_candidate_id;
        $this->to_candidate_id = (int) $a->to_candidate_id;
        $this->weight_percent = (int) round(((float) $a->weight) * 100);
        $this->is_active = (bool) $a->is_active;
        $this->notes = $a->notes;

        $this->loadPolicyForSource();

        $this->resetErrorBag();
        $this->resetValidation();
    }

    private function loadPolicyForSource(): void
    {
        if (!$this->from_candidate_id) {
            $this->policy_mode = 'split';
            $this->policy_max_total_weight_percent = 100;
            return;
        }

        $p = CandidateAlliancePolicy::query()
            ->where('candidate_id', $this->from_candidate_id)
            ->first();

        $this->policy_mode = $p?->mode ?: 'split';
        $this->policy_max_total_weight_percent = $p?->max_total_weight_percent ?: 100;
    }

    private function sumActivePercentForSource(int $fromCandidateId, ?int $excludeId = null): float
    {
        $q = Alliance::query()
            ->where('from_candidate_id', $fromCandidateId)
            ->where('is_active', true);

        if ($excludeId) {
            $q->where('id', '!=', $excludeId);
        }

        return ((float) $q->sum('weight')) * 100.0;
    }

    private function validatePolicyAndWeights(int $fromId, int $weightPercent, bool $isActive): bool
    {
        $mode = $this->policy_mode;
        $max = max(1, min(100, (int) $this->policy_max_total_weight_percent));

        if (!in_array($mode, ['split', 'exclusive'], true)) {
            $this->addError('policy_mode', 'Invalid policy mode.');
            return false;
        }

        if ($weightPercent < 0 || $weightPercent > 100) {
            $this->addError('weight_percent', 'Weight must be between 0 and 100.');
            return false;
        }

        if ($mode === 'split') {
            $otherSum = $this->sumActivePercentForSource($fromId, $this->editingId);
            $proposed = $otherSum + ($isActive ? $weightPercent : 0);

            if ($proposed > $max + 0.0001) {
                $this->addError(
                    'weight_percent',
                    "Split mode: total active weights for this source would be {$proposed}%, exceeding max {$max}%."
                );
                return false;
            }
        }

        return true;
    }

    public function save(): void
    {
        $data = $this->validate([
            'from_candidate_id' => ['required', 'integer', 'exists:candidates,id'],
            'to_candidate_id' => ['required', 'integer', 'exists:candidates,id'],
            'weight_percent' => ['required', 'integer', 'min:0', 'max:100'],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],

            'policy_mode' => ['required', 'string'],
            'policy_max_total_weight_percent' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $fromId = (int) $data['from_candidate_id'];
        $toId = (int) $data['to_candidate_id'];

        if ($fromId === $toId) {
            $this->addError('to_candidate_id', 'Target candidate must be different from source candidate.');
            return;
        }

        if (!$this->validatePolicyAndWeights($fromId, (int) $data['weight_percent'], (bool) $data['is_active'])) {
            return;
        }

        CandidateAlliancePolicy::updateOrCreate(
            ['candidate_id' => $fromId],
            [
                'mode' => $this->policy_mode,
                'max_total_weight_percent' => (int) $this->policy_max_total_weight_percent,
            ]
        );

        $alliance = Alliance::updateOrCreate(
            ['id' => $this->editingId],
            [
                'from_candidate_id' => $fromId,
                'to_candidate_id' => $toId,
                'weight' => ((int) $data['weight_percent']) / 100,
                'is_active' => (bool) $data['is_active'],
                'notes' => $data['notes'] ? trim($data['notes']) : null,
            ]
        );

        if ($this->policy_mode === 'exclusive' && $alliance->is_active) {
            Alliance::query()
                ->where('from_candidate_id', $fromId)
                ->where('id', '!=', $alliance->id)
                ->where('is_active', true)
                ->update(['is_active' => false]);
        }

        session()->flash('status', $this->editingId ? 'Alliance updated.' : 'Alliance created.');
        $this->createNew();
    }

    public function toggleActive(int $id): void
    {
        $a = Alliance::query()->findOrFail($id);

        $this->from_candidate_id = (int) $a->from_candidate_id;
        $this->loadPolicyForSource();

        $turningOn = !$a->is_active;

        if ($turningOn) {
            $pct = (int) round(((float) $a->weight) * 100);
            if (!$this->validatePolicyAndWeights((int) $a->from_candidate_id, $pct, true)) {
                return;
            }
        }

        $a->is_active = $turningOn;
        $a->save();

        if ($turningOn && $this->policy_mode === 'exclusive') {
            Alliance::query()
                ->where('from_candidate_id', (int) $a->from_candidate_id)
                ->where('id', '!=', $a->id)
                ->where('is_active', true)
                ->update(['is_active' => false]);
        }

        $this->dispatch('notify', message: 'Alliance updated.');
    }

    /**
     * @return array<int,array{source_id:int,source:string,mode:string,max:int,active_targets:int,active_total:int}>
     */
    private function buildPolicySummary(): array
    {
        $candidates = Candidate::query()
            ->orderByDesc('is_active')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'is_active']);

        $policies = CandidateAlliancePolicy::query()
            ->get(['candidate_id', 'mode', 'max_total_weight_percent'])
            ->keyBy('candidate_id');

        $activeAgg = Alliance::query()
            ->where('is_active', true)
            ->selectRaw('from_candidate_id, count(*) as active_targets, round(sum(weight) * 100) as active_total')
            ->groupBy('from_candidate_id')
            ->get()
            ->keyBy('from_candidate_id');

        $rows = [];
        foreach ($candidates as $c) {
            $p = $policies->get($c->id);
            $a = $activeAgg->get($c->id);

            $rows[] = [
                'source_id' => (int) $c->id,
                'source' => $c->name . ($c->is_active ? '' : ' (inactive)'),
                'mode' => $p?->mode ?: 'split',
                'max' => (int) ($p?->max_total_weight_percent ?: 100),
                'active_targets' => (int) ($a->active_targets ?? 0),
                'active_total' => (int) ($a->active_total ?? 0),
            ];
        }

        return $rows;
    }

    /**
     * @return array<int,array{candidate:string,for:int,indicative:int,against:int,direct:float,spillover:float,total:float}>
     */
    private function buildAlliancePreview(): array
    {
        $candidates = Candidate::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name']);

        $stats = [];
        foreach ($candidates as $c) {
            $counts = DelegateCandidateStatus::query()
                ->selectRaw("
                    sum(case when stance = 'for' then 1 else 0 end) as yes_count,
                    sum(case when stance = 'indicative' then 1 else 0 end) as maybe_count,
                    sum(case when stance = 'against' then 1 else 0 end) as no_count
                ")
                ->where('candidate_id', $c->id)
                ->first();

            $for = (int) ($counts->yes_count ?? 0);
            $indicative = (int) ($counts->maybe_count ?? 0);
            $against = (int) ($counts->no_count ?? 0);
            $direct = ($for * 1.0) + ($indicative * 0.5);

            $stats[(int) $c->id] = [
                'candidate' => $c->name,
                'for' => $for,
                'indicative' => $indicative,
                'against' => $against,
                'direct' => $direct,
                'spillover' => 0.0,
                'total' => $direct,
            ];
        }

        $alliances = Alliance::query()
            ->where('is_active', true)
            ->whereIn('from_candidate_id', array_keys($stats))
            ->whereIn('to_candidate_id', array_keys($stats))
            ->get(['from_candidate_id', 'to_candidate_id', 'weight']);

        foreach ($alliances as $a) {
            $fromId = (int) $a->from_candidate_id;
            $toId = (int) $a->to_candidate_id;
            $w = max(0.0, min(1.0, (float) $a->weight));

            $stats[$toId]['spillover'] += $stats[$fromId]['direct'] * $w;
        }

        foreach ($stats as $id => $row) {
            $stats[$id]['total'] = $row['direct'] + $row['spillover'];
        }

        $rows = array_values($stats);
        usort($rows, fn ($a, $b) => $b['total'] <=> $a['total']);

        return $rows;
    }

    public function render()
    {
        $candidates = Candidate::query()
            ->with('alliancePolicy')
            ->orderByDesc('is_active')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'is_active']);

        $alliances = Alliance::query()
            ->with(['fromCandidate:id,name', 'toCandidate:id,name'])
            ->orderByDesc('is_active')
            ->orderBy('from_candidate_id')
            ->orderBy('to_candidate_id')
            ->get();

        return view('livewire.manage.alliances', [
            'candidates' => $candidates,
            'alliances' => $alliances,
            'policySummary' => $this->buildPolicySummary(),
            'preview' => $this->buildAlliancePreview(),
        ])->layout('layouts.app');
    }
}
