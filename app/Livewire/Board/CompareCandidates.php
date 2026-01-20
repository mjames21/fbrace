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

    public function updatingQ(): void { $this->resetPage(); }
    public function updatingRegionId(): void { $this->districtId = null; $this->resetPage(); }
    public function updatingDistrictId(): void { $this->resetPage(); }
    public function updatingGroupId(): void { $this->resetPage(); }
    public function updatingCategory(): void { $this->resetPage(); }
    public function updatingAz(): void { $this->resetPage(); }
    public function updatingPerPage(): void { $this->resetPage(); }

    public function mount(): void
    {
        $this->sort = in_array($this->sort, ['name', 'region', 'district', 'category', 'group'], true) ? $this->sort : 'name';
        $this->dir = $this->dir === 'desc' ? 'desc' : 'asc';
    }

    public function sortBy(string $field): void
    {
        if (!in_array($field, ['name', 'region', 'district', 'category', 'group'], true)) {
            return;
        }

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

    private function delegatesQuery(): Builder
    {
        $q = Delegate::query()
            ->with(['district.region', 'groups'])
             ->where('is_active', true)
            ->when($this->q !== '', fn (Builder $b) => $b->where('full_name', 'ilike', "%{$this->q}%"))
            ->when($this->category, fn (Builder $b) => $b->where('category', $this->category))
            ->when($this->districtId, fn (Builder $b) => $b->where('district_id', $this->districtId))
            ->when($this->regionId, fn (Builder $b) => $b->whereHas('district', fn (Builder $d) => $d->where('region_id', $this->regionId)))
            ->when($this->groupId, fn (Builder $b) => $b->whereHas('groups', fn (Builder $g) => $g->where('groups.id', $this->groupId)));

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
     * @param  Collection<int,int>  $delegateIds
     * @param  Collection<int,int>  $candidateIds
     * @return array<int,array<int,DelegateCandidateStatus>>
     */
    private function statusesMatrix(Collection $delegateIds, Collection $candidateIds): array
    {
        if ($delegateIds->isEmpty() || $candidateIds->isEmpty()) return [];

        $rows = DelegateCandidateStatus::query()
            ->whereIn('delegate_id', $delegateIds->all())
            ->whereIn('candidate_id', $candidateIds->all())
            ->get(['delegate_id', 'candidate_id', 'stance', 'confidence', 'updated_at']);

        $matrix = [];
        foreach ($rows as $r) {
            $matrix[(int)$r->delegate_id][(int)$r->candidate_id] = $r;
        }
        return $matrix;
    }

    public function render()
    {
        $candidates = Candidate::query()
            ->orderByDesc('is_active')
            ->orderByDesc('is_principal')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'is_active', 'is_principal']);

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

        $per = $this->perPage === 0 ? 100000 : $this->perPage;
        $delegates = $this->delegatesQuery()->paginate($per);

        $delegateIds = $delegates->getCollection()->pluck('id');
        $candidateIds = $candidates->pluck('id');

        $matrix = $this->statusesMatrix($delegateIds, $candidateIds);

        return view('livewire.board.compare-candidates', [
            'candidates' => $candidates,
            'regions' => $regions,
            'districts' => $districts,
            'groups' => $groups,
            'categories' => $categories,
            'delegates' => $delegates,
            'matrix' => $matrix,
            'sort' => $this->sort,
            'dir' => $this->dir,
        ])->layout('layouts.app');
    }
}
