<?php
// FILE: app/Livewire/Board/DelegateBoard.php

namespace App\Livewire\Board;

use App\Models\Candidate;
use App\Models\Delegate;
use App\Models\DelegateCandidateStatus;
use App\Models\District;
use App\Models\Group;
use App\Models\Guarantor;
use App\Models\Region;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class DelegateBoard extends Component
{
    use WithPagination;

    protected $listeners = [
        'refresh-board' => '$refresh',
    ];

    #[Url(as: 'q')]
    public string $q = '';

    #[Url(as: 'candidate')]
    public ?int $candidateId = null;

    #[Url(as: 'principal')]
    public bool $principalOnly = true;

    #[Url(as: 'region')]
    public ?int $regionId = null;

    #[Url(as: 'district')]
    public ?int $districtId = null;

    #[Url(as: 'group')]
    public ?int $groupId = null;

    #[Url(as: 'category')]
    public ?string $category = null;

    #[Url(as: 'guarantor')]
    public ?int $guarantorId = null;

    #[Url(as: 'az')]
    public ?string $az = null;

    #[Url(as: 'pstance')]
    public ?string $principalStance = null;

    #[Url(as: 'per')]
    public int $perPage = 25;

    /** @var array<int> */
    public array $selected = [];

    public bool $selectPage = false;

    /** @var array<int> */
    public array $currentPageIds = [];

    public string $bulkStance = 'indicative';
    public int $bulkConfidence = 50;

    public ?int $drawerDelegateId = null;

    private ?int $cachedPrincipalCandidateId = null;

    public function updatingQ(): void { $this->clearSelection(); $this->resetPage(); }
    public function updatingRegionId(): void { $this->clearSelection(); $this->districtId = null; $this->resetPage(); }
    public function updatingDistrictId(): void { $this->clearSelection(); $this->resetPage(); }
    public function updatingGroupId(): void { $this->clearSelection(); $this->resetPage(); }
    public function updatingCategory(): void { $this->clearSelection(); $this->resetPage(); }
    public function updatingCandidateId(): void { $this->clearSelection(); $this->resetPage(); }
    public function updatingGuarantorId(): void { $this->clearSelection(); $this->resetPage(); }
    public function updatingAz(): void { $this->clearSelection(); $this->resetPage(); }
    public function updatingPrincipalOnly(): void { $this->clearSelection(); $this->resetPage(); }
    public function updatingPrincipalStance(): void { $this->clearSelection(); $this->resetPage(); }
    public function updatingPerPage(): void { $this->clearSelection(); $this->resetPage(); }

    public function mount(): void
    {
        $this->applyPrincipalLock();
        if ($this->perPage < 0) $this->perPage = 25;
    }

    public function updatedPrincipalOnly(): void
    {
        $this->applyPrincipalLock();
        $this->resetPage();
    }

    private function principalCandidateId(): ?int
    {
        if ($this->cachedPrincipalCandidateId !== null) {
            return $this->cachedPrincipalCandidateId;
        }

        $this->cachedPrincipalCandidateId = Candidate::query()
            ->where('is_principal', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->value('id');

        return $this->cachedPrincipalCandidateId;
    }

    private function applyPrincipalLock(): void
    {
        if ($this->principalOnly) {
            $pid = $this->principalCandidateId();
            if ($pid) {
                $this->candidateId = (int) $pid;
                return;
            }
        }

        if (!$this->candidateId) {
            $this->candidateId = Candidate::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->value('id');
        }
    }

    public function openDrawer(int $delegateId): void
    {
        $this->drawerDelegateId = $delegateId;
    }

    public function closeDrawer(): void
    {
        $this->drawerDelegateId = null;
    }

    public function clearSelection(): void
    {
        $this->selected = [];
        $this->selectPage = false;
    }

    public function toggleSelectPage(): void
    {
        $this->selectPage = !$this->selectPage;
        $this->selected = $this->selectPage ? $this->currentPageIds : [];
    }

    public function updatedSelected(): void
    {
        $this->selectPage = count($this->selected) > 0
            && count(array_diff($this->currentPageIds, $this->selected)) === 0;
    }

    /**
     * Stance change MUST NOT overwrite confidence.
     * If row doesn't exist: create with default confidence 50.
     */
    public function setStance(int $delegateId, string $stance): void
    {
        if (!$this->candidateId) return;

        $stance = strtolower(trim($stance));
        if (!in_array($stance, ['for', 'indicative', 'against'], true)) return;

        $row = DelegateCandidateStatus::query()->firstOrNew([
            'delegate_id' => $delegateId,
            'candidate_id' => $this->candidateId,
        ]);

        if (!$row->exists) {
            $row->confidence = 50;
        }

        $row->stance = $stance;
        $row->save();

        $this->dispatch('notify', message: 'Status updated.');
        $this->dispatch('refresh-board');
    }

    public function updateConfidence(int $delegateId, mixed $value): void
    {
        if (!$this->candidateId) return;

        $confidence = max(0, min(100, (int) $value));

        $row = DelegateCandidateStatus::query()->firstOrCreate(
            ['delegate_id' => $delegateId, 'candidate_id' => $this->candidateId],
            ['stance' => 'indicative', 'confidence' => 50]
        );

        $row->confidence = $confidence;
        $row->save();

        $this->dispatch('notify', message: 'Confidence updated.');
        $this->dispatch('refresh-board');
    }

    public function applyBulk(): void
    {
        if (!$this->candidateId) return;

        if (empty($this->selected)) {
            $this->dispatch('notify', message: 'No delegates selected.');
            return;
        }

        $stance = strtolower(trim($this->bulkStance));
        if (!in_array($stance, ['for', 'indicative', 'against'], true)) return;

        $confidence = max(0, min(100, (int) $this->bulkConfidence));

        foreach (array_unique($this->selected) as $delegateId) {
            DelegateCandidateStatus::updateOrCreate(
                ['delegate_id' => (int) $delegateId, 'candidate_id' => $this->candidateId],
                ['stance' => $stance, 'confidence' => $confidence]
            );
        }

        $this->dispatch('notify', message: 'Bulk update applied.');
        $this->dispatch('refresh-board');
        $this->clearSelection();
    }

    private function applyPrincipalStanceFilterTo(Builder $query, ?string $principalStance): void
    {
        $pid = $this->principalCandidateId();
        if (!$pid) return;

        $stance = strtolower((string) ($principalStance ?? ''));
        if ($stance === '') return;

        $statusTable = (new DelegateCandidateStatus())->getTable();

        if ($stance === 'none') {
            $query->whereNotExists(function ($sq) use ($statusTable, $pid) {
                $sq->selectRaw('1')
                    ->from($statusTable)
                    ->whereColumn($statusTable . '.delegate_id', 'delegates.id')
                    ->where($statusTable . '.candidate_id', $pid);
            });
            return;
        }

        if (!in_array($stance, ['for', 'indicative', 'against'], true)) return;

        $query->whereExists(function ($sq) use ($statusTable, $pid, $stance) {
            $sq->selectRaw('1')
                ->from($statusTable)
                ->whereColumn($statusTable . '.delegate_id', 'delegates.id')
                ->where($statusTable . '.candidate_id', $pid)
                ->where($statusTable . '.stance', $stance);
        });
    }

    private function delegateBaseQuery(
        string $search,
        ?string $category,
        ?int $regionId,
        ?int $districtId,
        ?int $groupId,
        ?int $guarantorId,
        ?string $az,
        ?string $principalStance
    ): Builder {
        $query = Delegate::query()
            ->where('is_active', true)
            ->when($search !== '', fn (Builder $q) => $q->where('full_name', 'ilike', "%{$search}%"))
            ->when($category, fn (Builder $q) => $q->where('category', $category))
            ->when($districtId, fn (Builder $q) => $q->where('district_id', $districtId))
            ->when($regionId, fn (Builder $q) => $q->whereHas('district', fn (Builder $d) => $d->where('region_id', $regionId)))
            ->when($groupId, fn (Builder $q) => $q->whereHas('groups', fn (Builder $g) => $g->where('groups.id', $groupId)))
            ->when($guarantorId !== null, function (Builder $q) use ($guarantorId) {
                if ($guarantorId === 0) {
                    $q->whereNull('guarantor_id');
                    return;
                }
                $q->where('guarantor_id', $guarantorId);
            });

        $this->applyPrincipalStanceFilterTo($query, $principalStance);

        $az = strtoupper((string) ($az ?? ''));
        if ($search === '' && $az !== '') {
            $query->where('full_name', 'ilike', $az . '%');
        }

        return $query;
    }

    private function delegateQuery(): Builder
    {
        return $this->delegateBaseQuery(
            $this->q,
            $this->category,
            $this->regionId,
            $this->districtId,
            $this->groupId,
            $this->guarantorId,
            $this->az,
            $this->principalStance
        )
            ->with(['district.region', 'groups', 'guarantor'])
            ->orderByRaw('lower(full_name) asc');
    }

    /**
     * @param  Collection<int,int>  $delegateIds
     * @return array<int,DelegateCandidateStatus>
     */
    private function statusesForPage(Collection $delegateIds): array
    {
        if (!$this->candidateId || $delegateIds->isEmpty()) return [];

        $rows = DelegateCandidateStatus::query()
            ->where('candidate_id', $this->candidateId)
            ->whereIn('delegate_id', $delegateIds->all())
            ->get(['delegate_id', 'stance', 'confidence', 'pending_stance', 'pending_confidence', 'updated_at']);

        $map = [];
        foreach ($rows as $r) {
            $map[(int) $r->delegate_id] = $r;
        }

        return $map;
    }

    /**
     * Counts stances for a candidate across the delegates returned by $delegateIdsQuery.
     *
     * @return array{for:int,indicative:int,against:int,none:int,all:int}
     */
    private function stanceCountsForCandidateOnDelegates(int $candidateId, Builder $delegateIdsQuery): array
    {
        $statusTable = (new DelegateCandidateStatus())->getTable();

        $sub = $delegateIdsQuery
            ->reorder()
            ->select('delegates.id as id')
            ->toBase();

        $counts = DB::query()
            ->fromSub($sub, 'fd')
            ->leftJoin($statusTable . ' as s', function ($join) use ($candidateId) {
                $join->on('s.delegate_id', '=', 'fd.id')
                    ->where('s.candidate_id', '=', $candidateId);
            })
            ->selectRaw("coalesce(s.stance, 'none') as stance, count(*) as c")
            ->groupBy('stance')
            ->pluck('c', 'stance')
            ->all();

        $for = (int) ($counts['for'] ?? 0);
        $indicative = (int) ($counts['indicative'] ?? 0);
        $against = (int) ($counts['against'] ?? 0);
        $none = (int) ($counts['none'] ?? 0);

        return [
            'for' => $for,
            'indicative' => $indicative,
            'against' => $against,
            'none' => $none,
            'all' => $for + $indicative + $against + $none,
        ];
    }

    public function render()
    {
        $this->applyPrincipalLock();

        $candidates = Candidate::query()
            ->orderByDesc('is_active')
            ->orderByDesc('is_principal')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'is_active', 'is_principal']);

        $regions = Region::orderBy('name')->get(['id', 'name']);
        $groups  = Group::orderBy('name')->get(['id', 'name']);

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

        $guarantors = Guarantor::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name']);

        $per = $this->perPage === 0 ? 100000 : max(1, $this->perPage);

        $delegates = $this->delegateQuery()->paginate($per);

        $this->currentPageIds = $delegates->getCollection()->pluck('id')->map(fn ($v) => (int) $v)->all();

        $delegateIds = $delegates->getCollection()->pluck('id');
        $statusMap = $this->statusesForPage($delegateIds);

        // ----------------------------
        // Summary + facet counts
        // ----------------------------

        $totalDelegates = (int) Delegate::query()->where('is_active', true)->count('id');

        $filteredDelegatesQuery = $this->delegateBaseQuery(
            $this->q,
            $this->category,
            $this->regionId,
            $this->districtId,
            $this->groupId,
            $this->guarantorId,
            $this->az,
            $this->principalStance
        );
        $filteredDelegatesCount = (int) (clone $filteredDelegatesQuery)->count('delegates.id');

        $stanceSummary = ['for' => 0, 'indicative' => 0, 'against' => 0, 'none' => 0, 'all' => 0];
        if ($this->candidateId) {
            $stanceSummary = $this->stanceCountsForCandidateOnDelegates((int) $this->candidateId, $filteredDelegatesQuery);
        }

        // Principal stance facet counts (ignore current principalStance)
        $principalStanceCounts = ['for' => 0, 'indicative' => 0, 'against' => 0, 'none' => 0, 'all' => 0];
        $pid = $this->principalCandidateId();
        if ($pid) {
            $principalFilteredNoFacet = $this->delegateBaseQuery(
                $this->q,
                $this->category,
                $this->regionId,
                $this->districtId,
                $this->groupId,
                $this->guarantorId,
                $this->az,
                null
            );
            $principalStanceCounts = $this->stanceCountsForCandidateOnDelegates((int) $pid, $principalFilteredNoFacet);
        }

        // Region facet (ignore region + district)
        $regionFacetQuery = $this->delegateBaseQuery(
            $this->q,
            $this->category,
            null,
            null,
            $this->groupId,
            $this->guarantorId,
            $this->az,
            $this->principalStance
        );
        $regionFacetTotal = (int) (clone $regionFacetQuery)->count('delegates.id');
        $regionCounts = (clone $regionFacetQuery)
            ->join('districts', 'districts.id', '=', 'delegates.district_id')
            ->selectRaw('districts.region_id as id, count(*) as c')
            ->groupBy('districts.region_id')
            ->pluck('c', 'id')
            ->all();

        // District facet (ignore district; keep region)
        $districtFacetQuery = $this->delegateBaseQuery(
            $this->q,
            $this->category,
            $this->regionId,
            null,
            $this->groupId,
            $this->guarantorId,
            $this->az,
            $this->principalStance
        );
        $districtFacetTotal = (int) (clone $districtFacetQuery)->count('delegates.id');
        $districtCounts = (clone $districtFacetQuery)
            ->selectRaw('district_id as id, count(*) as c')
            ->groupBy('district_id')
            ->pluck('c', 'id')
            ->all();

        // Guarantor facet (ignore guarantor)
        $guarantorFacetQuery = $this->delegateBaseQuery(
            $this->q,
            $this->category,
            $this->regionId,
            $this->districtId,
            $this->groupId,
            null,
            $this->az,
            $this->principalStance
        );
        $guarantorFacetTotal = (int) (clone $guarantorFacetQuery)->count('delegates.id');
        $guarantorCounts = (clone $guarantorFacetQuery)
            ->selectRaw('guarantor_id as id, count(*) as c')
            ->groupBy('guarantor_id')
            ->pluck('c', 'id')
            ->all();
        $noGuarantorCount = (int) (clone $guarantorFacetQuery)->whereNull('guarantor_id')->count('delegates.id');

        // A–Z facet (only when search empty; ignore current az)
        $azCounts = [];
        $azFacetTotal = 0;
        if ($this->q === '') {
            $azFacetQuery = $this->delegateBaseQuery(
                '',
                $this->category,
                $this->regionId,
                $this->districtId,
                $this->groupId,
                $this->guarantorId,
                null,
                $this->principalStance
            );

            $azFacetTotal = (int) (clone $azFacetQuery)->count('delegates.id');
            $azCounts = (clone $azFacetQuery)
                ->selectRaw("upper(left(full_name, 1)) as letter, count(*) as c")
                ->groupBy('letter')
                ->pluck('c', 'letter')
                ->all();
        }

        return view('livewire.board.delegate-board', [
            'candidates' => $candidates,
            'regions' => $regions,
            'districts' => $districts,
            'groups' => $groups,
            'categories' => $categories,
            'guarantors' => $guarantors,
            'delegates' => $delegates,
            'statusMap' => $statusMap,

            // summary + facet counts
            'totalDelegates' => $totalDelegates,
            'filteredDelegatesCount' => $filteredDelegatesCount,
            'stanceSummary' => $stanceSummary,

            'principalStanceCounts' => $principalStanceCounts,

            'regionFacetTotal' => $regionFacetTotal,
            'regionCounts' => $regionCounts,

            'districtFacetTotal' => $districtFacetTotal,
            'districtCounts' => $districtCounts,

            'guarantorFacetTotal' => $guarantorFacetTotal,
            'guarantorCounts' => $guarantorCounts,
            'noGuarantorCount' => $noGuarantorCount,

            'azFacetTotal' => $azFacetTotal,
            'azCounts' => $azCounts,
        ])->layout('layouts.app');
    }
}