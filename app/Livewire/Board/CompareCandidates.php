<?php

namespace App\Livewire\Board;

use App\Models\Candidate;
use App\Models\Delegate;
use App\Models\DelegateCandidateStatus;
use App\Models\District;
use App\Models\Group;
use App\Models\Region;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class CompareCandidates extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $q = '';

    #[Url(as: 'region')]
    public ?int $filterRegionId = null;

    #[Url(as: 'district')]
    public ?int $filterDistrictId = null;

    #[Url(as: 'group')]
    public ?int $filterGroupId = null;

    #[Url(as: 'category')]
    public ?string $filterCategory = null;

    public function updatingQ(): void { $this->resetPage(); }
    public function updatingFilterRegionId(): void { $this->filterDistrictId = null; $this->resetPage(); }
    public function updatingFilterDistrictId(): void { $this->resetPage(); }
    public function updatingFilterGroupId(): void { $this->resetPage(); }
    public function updatingFilterCategory(): void { $this->resetPage(); }

    public function render()
    {
        $candidates = Candidate::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name']);

        $regions = Region::orderBy('name')->get(['id', 'name']);
        $groups = Group::orderBy('name')->get(['id', 'name']);

        $districtsForFilter = District::query()
            ->when($this->filterRegionId, fn (Builder $q) => $q->where('region_id', $this->filterRegionId))
            ->orderBy('name')
            ->get(['id', 'name', 'region_id']);

        $categories = Delegate::query()
            ->select('category')
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->all();

        $delegates = Delegate::query()
            ->with(['district.region', 'groups'])
            ->when($this->q !== '', fn (Builder $q) => $q->where('full_name', 'like', "%{$this->q}%"))
            ->when($this->filterCategory, fn (Builder $q) => $q->where('category', $this->filterCategory))
            ->when($this->filterDistrictId, fn (Builder $q) => $q->where('district_id', $this->filterDistrictId))
            ->when($this->filterRegionId, fn (Builder $q) => $q->whereHas('district', fn (Builder $d) => $d->where('region_id', $this->filterRegionId)))
            ->when($this->filterGroupId, fn (Builder $q) => $q->whereHas('groups', fn (Builder $g) => $g->where('groups.id', $this->filterGroupId)))
            ->orderBy('full_name')
            ->paginate(25);

        $delegateIds = $delegates->getCollection()->pluck('id')->all();
        $candidateIds = $candidates->pluck('id')->all();

        $statuses = DelegateCandidateStatus::query()
            ->whereIn('delegate_id', $delegateIds)
            ->whereIn('candidate_id', $candidateIds)
            ->get(['delegate_id', 'candidate_id', 'stance', 'confidence', 'pending_stance', 'pending_confidence']);

        $matrix = [];
        foreach ($statuses as $s) {
            $matrix[(int)$s->delegate_id][(int)$s->candidate_id] = $s;
        }

        return view('livewire.board.compare-candidates', [
            'candidates' => $candidates,
            'regions' => $regions,
            'districtsForFilter' => $districtsForFilter,
            'groups' => $groups,
            'categories' => $categories,
            'delegates' => $delegates,
            'matrix' => $matrix,
        ])->layout('layouts.app');
    }
}
