<?php

namespace App\Livewire\Dashboard;

use App\Models\Alliance;
use App\Models\Candidate;
use App\Models\Delegate;
use App\Models\DelegateCandidateStatus;
use App\Models\District;
use App\Models\Group;
use App\Models\Region;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use Livewire\Component;

class HorseRace extends Component
{
    public bool $includeSpillover = true;

    // 🟡 value: 0.00 - 1.00 (UI limits typical band)
    public float $indicativeWeight = 0.50;

    // Winner threshold
    public bool $autoTarget = true;
    public int $targetDelegates = 0;

    // Filters (same idea as board)
    #[Url(as: 'region')]
    public ?int $regionId = null;

    #[Url(as: 'district')]
    public ?int $districtId = null;

    #[Url(as: 'group')]
    public ?int $groupId = null;

    #[Url(as: 'category')]
    public ?string $category = null;

    public function mount(): void
    {
        $this->recomputeTarget();
    }

    public function updatedIndicativeWeight(): void
    {
        $this->indicativeWeight = max(0.0, min(1.0, (float) $this->indicativeWeight));
    }

    public function updatedAutoTarget(): void
    {
        $this->recomputeTarget();
    }

    public function updatedRegionId(): void
    {
        $this->districtId = null;
        $this->recomputeTarget();
    }

    public function updatedDistrictId(): void
    {
        $this->recomputeTarget();
    }

    public function updatedGroupId(): void
    {
        $this->recomputeTarget();
    }

    public function updatedCategory(): void
    {
        $this->recomputeTarget();
    }

    private function recomputeTarget(): void
    {
        if (!$this->autoTarget) {
            $this->targetDelegates = max(0, (int) $this->targetDelegates);
            return;
        }

        $total = $this->delegatesInScopeCount();
        $this->targetDelegates = $total > 0 ? ((int) floor($total / 2) + 1) : 0;
    }

    private function delegatesScopeQuery(): Builder
    {
        return Delegate::query()
            ->when($this->category, fn (Builder $q) => $q->where('category', $this->category))
             ->where('is_active', true)
            ->when($this->districtId, fn (Builder $q) => $q->where('district_id', $this->districtId))
            ->when(
                $this->regionId,
                fn (Builder $q) => $q->whereHas('district', fn (Builder $d) => $d->where('region_id', $this->regionId))
            )
            ->when(
                $this->groupId,
                fn (Builder $q) => $q->whereHas('groups', fn (Builder $g) => $g->where('groups.id', $this->groupId))
            );
    }

    private function delegatesInScopeCount(): int
    {
        return (int) $this->delegatesScopeQuery()->count();
    }

    /**
     * Aggregate stance counts per candidate for delegates IN SCOPE.
     *
     * NOTE: pivot table is assumed "delegate_group" (Laravel default for Delegate<->Group).
     * If yours differs, change $pivot below.
     *
     * @param  array<int,int>  $candidateIds
     * @return array<int,array{for:int,indicative:int,against:int}>
     */
  // app/Livewire/Dashboard/HorseRace.php
// Replace ONLY the stanceCountsByCandidate() method with this version.

private function stanceCountsByCandidate(array $candidateIds): array
{
    if (empty($candidateIds)) return [];

    $pivot = 'delegate_group'; // change if your pivot name differs
    $table = (new \App\Models\DelegateCandidateStatus())->getTable(); // e.g. delegate_candidate_status

    $q = \App\Models\DelegateCandidateStatus::query()
        ->selectRaw("
            {$table}.candidate_id as candidate_id,
            sum(case when {$table}.stance = 'for' then 1 else 0 end) as for_count,
            sum(case when {$table}.stance = 'indicative' then 1 else 0 end) as indicative_count,
            sum(case when {$table}.stance = 'against' then 1 else 0 end) as against_count
        ")
        ->join('delegates', 'delegates.id', '=', "{$table}.delegate_id")
        ->leftJoin('districts', 'districts.id', '=', 'delegates.district_id')
        ->whereIn("{$table}.candidate_id", $candidateIds)
        ->when($this->category, fn ($x) => $x->where('delegates.category', $this->category))
        ->when($this->districtId, fn ($x) => $x->where('delegates.district_id', $this->districtId))
        ->when($this->regionId, fn ($x) => $x->where('districts.region_id', $this->regionId))
        ->when($this->groupId, function ($x) use ($pivot) {
            $x->join($pivot, "{$pivot}.delegate_id", '=', 'delegates.id')
              ->where("{$pivot}.group_id", $this->groupId);
        })
        ->groupBy("{$table}.candidate_id");

    $rows = $q->get();

    $out = [];
    foreach ($rows as $r) {
        $cid = (int) $r->candidate_id;
        $out[$cid] = [
            'for' => (int) ($r->for_count ?? 0),
            'indicative' => (int) ($r->indicative_count ?? 0),
            'against' => (int) ($r->against_count ?? 0),
        ];
    }

    return $out;
}


    /**
     * @return array<int,array{
     *   candidate:string,for:int,indicative:int,against:int,
     *   direct:float,spillover:float,total:float,
     *   remaining:float,percent_to_target:float
     * }>
     */
    private function buildRows(): array
    {
        $iw = max(0.0, min(1.0, (float) $this->indicativeWeight));

        $candidates = Candidate::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name']);

        $candidateIds = $candidates->pluck('id')->map(fn ($v) => (int) $v)->all();
        $counts = $this->stanceCountsByCandidate($candidateIds);

        $stats = [];
        foreach ($candidates as $c) {
            $cid = (int) $c->id;
            $for = (int) ($counts[$cid]['for'] ?? 0);
            $indicative = (int) ($counts[$cid]['indicative'] ?? 0);
            $against = (int) ($counts[$cid]['against'] ?? 0);

            $direct = ($for * 1.0) + ($indicative * $iw);

            $stats[$cid] = [
                'candidate' => $c->name,
                'for' => $for,
                'indicative' => $indicative,
                'against' => $against,
                'direct' => $direct,
                'spillover' => 0.0,
                'total' => $direct,
                'remaining' => 0.0,
                'percent_to_target' => 0.0,
            ];
        }

        if ($this->includeSpillover && !empty($stats)) {
            $alliances = Alliance::query()
                ->where('is_active', true)
                ->whereIn('from_candidate_id', array_keys($stats))
                ->whereIn('to_candidate_id', array_keys($stats))
                ->get(['from_candidate_id', 'to_candidate_id', 'weight']);

            foreach ($alliances as $a) {
                $fromId = (int) $a->from_candidate_id;
                $toId = (int) $a->to_candidate_id;
                $w = max(0.0, min(1.0, (float) $a->weight));

                $stats[$toId]['spillover'] += ($stats[$fromId]['direct'] ?? 0.0) * $w;
            }
        }

        $target = max(0, (int) $this->targetDelegates);

        foreach ($stats as $id => $row) {
            $total = $row['direct'] + ($this->includeSpillover ? $row['spillover'] : 0.0);
            $stats[$id]['total'] = $total;

            if (!$this->includeSpillover) {
                $stats[$id]['spillover'] = 0.0;
            }

            $remaining = $target > 0 ? max(0.0, $target - $total) : 0.0;
            $pct = $target > 0 ? min(1.0, $total / $target) : 0.0;

            $stats[$id]['remaining'] = $remaining;
            $stats[$id]['percent_to_target'] = $pct;
        }

        $rows = array_values($stats);
        usort($rows, fn ($a, $b) => $b['total'] <=> $a['total']);

        return $rows;
    }

    public function render()
    {
        $regions = Region::query()->orderBy('name')->get(['id', 'name']);
        $groups = Group::query()->orderBy('name')->get(['id', 'name']);

        $districts = District::query()
            ->when($this->regionId, fn (Builder $q) => $q->where('region_id', $this->regionId))
            ->orderBy('name')
            ->get(['id', 'name', 'region_id']);

        $categories = Delegate::query()
            ->select('category')
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->all();

        $scopeTotalDelegates = $this->delegatesInScopeCount();

        return view('livewire.dashboard.horse-race', [
            'rows' => $this->buildRows(),
            'regions' => $regions,
            'districts' => $districts,
            'groups' => $groups,
            'categories' => $categories,
            'scopeTotalDelegates' => $scopeTotalDelegates,
            'includeSpillover' => $this->includeSpillover,
            'indicativeWeight' => $this->indicativeWeight,
            'targetDelegates' => $this->targetDelegates,
            'autoTarget' => $this->autoTarget,
        ])->layout('layouts.app');
    }
}
