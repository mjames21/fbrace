<?php

namespace App\Livewire\Board;

use App\Models\Candidate;
use App\Models\Delegate;
use App\Models\DelegateCandidateStatus;
use App\Models\District;
use App\Models\Group;
use App\Models\Region;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class CompareCandidates extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $q = '';

    #[Url(as: 'region')]
    public ?int $regionId = null;

    #[Url(as: 'district')]
    public ?int $districtId = null;

    #[Url(as: 'group')]
    public ?int $groupId = null;

    #[Url(as: 'category')]
    public ?string $category = null;

    #[Url(as: 'az')]
    public ?string $az = null;

    #[Url(as: 'per')]
    public int $perPage = 25;

    #[Url(as: 'sort')]
    public string $sort = 'name'; // name|region|district|category|group

    #[Url(as: 'dir')]
    public string $dir = 'asc'; // asc|desc

    // Principal stance filter
    #[Url(as: 'pstance')]
    public ?string $principalStance = null;

    // Compare against ONE candidate
    #[Url(as: 'compare')]
    public ?int $compareCandidateId = null;

    public function updatingQ(): void { $this->resetPage(); }
    public function updatingRegionId(): void { $this->districtId = null; $this->resetPage(); }
    public function updatingDistrictId(): void { $this->resetPage(); }
    public function updatingGroupId(): void { $this->resetPage(); }
    public function updatingCategory(): void { $this->resetPage(); }
    public function updatingAz(): void { $this->resetPage(); }
    public function updatingPerPage(): void { $this->resetPage(); }
    public function updatingPrincipalStance(): void { $this->resetPage(); }
    public function updatingCompareCandidateId(): void { $this->resetPage(); }

    public function mount(): void
    {
        $this->sort = in_array($this->sort, ['name', 'region', 'district', 'category', 'group'], true) ? $this->sort : 'name';
        $this->dir = $this->dir === 'desc' ? 'desc' : 'asc';

        if (!$this->compareCandidateId) {
            $this->compareCandidateId = Candidate::query()
                ->where('is_active', true)
                ->where('is_principal', false)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->value('id');
        }
    }

    private function principalCandidateId(): ?int
    {
        return Candidate::query()
            ->where('is_principal', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->value('id');
    }

    public function sortBy(string $field): void
    {
        if (!in_array($field, ['name', 'region', 'district', 'category', 'group'], true)) return;

        if ($this->sort === $field) {
            $this->dir = $this->dir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sort = $field;
            $this->dir = 'asc';
        }

        $this->resetPage();
    }

    public function resetSorting(): void
    {
        $this->sort = 'name';
        $this->dir = 'asc';
        $this->az = null;
        $this->resetPage();
    }

    private function applyPrincipalStanceFilter(Builder $query): void
    {
        $pid = $this->principalCandidateId();
        if (!$pid) return;

        $stance = strtolower((string) ($this->principalStance ?? ''));
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

    private function delegatesQuery(): Builder
    {
        $q = Delegate::query()
            ->with(['district.region', 'groups'])
            ->where('is_active', true) // ✅ hide archived/deleted
            ->when($this->q !== '', fn (Builder $b) => $b->where('full_name', 'ilike', "%{$this->q}%"))
            ->when($this->category, fn (Builder $b) => $b->where('category', $this->category))
            ->when($this->districtId, fn (Builder $b) => $b->where('district_id', $this->districtId))
            ->when($this->regionId, fn (Builder $b) => $b->whereHas('district', fn (Builder $d) => $d->where('region_id', $this->regionId)))
            ->when($this->groupId, fn (Builder $b) => $b->whereHas('groups', fn (Builder $g) => $g->where('groups.id', $this->groupId)));

        $this->applyPrincipalStanceFilter($q);

        $az = strtoupper((string)($this->az ?? ''));
        if ($this->q === '' && $az !== '') {
            $q->where('full_name', 'ilike', $az . '%');
        }

        $dir = $this->dir === 'desc' ? 'desc' : 'asc';

        return match ($this->sort) {
            'region' => $q
                ->leftJoin('districts as d', 'd.id', '=', 'delegates.district_id')
                ->leftJoin('regions as r', 'r.id', '=', 'd.region_id')
                ->select('delegates.*')
                ->orderBy('r.name', $dir)
                ->orderBy('d.name', 'asc')
                ->orderByRaw('lower(delegates.full_name) asc'),

            'district' => $q
                ->leftJoin('districts as d', 'd.id', '=', 'delegates.district_id')
                ->select('delegates.*')
                ->orderBy('d.name', $dir)
                ->orderByRaw('lower(delegates.full_name) asc'),

            'category' => $q
                ->orderBy('category', $dir)
                ->orderByRaw('lower(full_name) asc'),

            'group' => $q
                ->orderByRaw(
                    "(select min(g.name)
                      from delegate_group dg
                      join groups g on g.id = dg.group_id
                      where dg.delegate_id = delegates.id) {$dir} nulls last"
                )
                ->orderByRaw('lower(full_name) asc'),

            default => $q->orderByRaw("lower(full_name) {$dir}"),
        };
    }

    /**
     * @param  Collection<int,int> $delegateIds
     * @param  int|null $candidateId
     * @return array<int,DelegateCandidateStatus>
     */
    private function statusMapFor(Collection $delegateIds, ?int $candidateId): array
    {
        if (!$candidateId || $delegateIds->isEmpty()) return [];

        $rows = DelegateCandidateStatus::query()
            ->where('candidate_id', $candidateId)
            ->whereIn('delegate_id', $delegateIds->all())
            ->get(['delegate_id', 'stance', 'confidence', 'updated_at']);

        $map = [];
        foreach ($rows as $r) {
            $map[(int)$r->delegate_id] = $r;
        }
        return $map;
    }

    public function render()
    {
        $principalId = $this->principalCandidateId();

        $candidates = Candidate::query()
            ->where('is_active', true)
            ->orderByDesc('is_principal')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'is_principal']);

        $regions = Region::orderBy('name')->get(['id', 'name']);
        $groups = Group::orderBy('name')->get(['id', 'name']);

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

        $per = $this->perPage === 0 ? 100000 : max(1, $this->perPage);

        $delegates = $this->delegatesQuery()->paginate($per);

        $delegateIds = $delegates->getCollection()->pluck('id');

        $principalMap = $this->statusMapFor($delegateIds, $principalId);
        $compareMap = $this->statusMapFor($delegateIds, $this->compareCandidateId);

        $compareCandidate = $this->compareCandidateId
            ? $candidates->firstWhere('id', $this->compareCandidateId)
            : null;

        return view('livewire.board.compare-candidates', [
            'candidates' => $candidates,
            'compareCandidate' => $compareCandidate,
            'regions' => $regions,
            'districts' => $districts,
            'groups' => $groups,
            'categories' => $categories,
            'delegates' => $delegates,
            'principalMap' => $principalMap,
            'compareMap' => $compareMap,
            'sort' => $this->sort,
            'dir' => $this->dir,
        ])->layout('layouts.app');
    }
}
