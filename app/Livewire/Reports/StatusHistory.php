<?php

namespace App\Livewire\Reports;

use App\Models\Candidate;
use App\Models\District;
use App\Models\Group;
use App\Models\Interaction;
use App\Models\Region;
use App\Support\StatusHistoryFilters;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class StatusHistory extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $q = '';

    #[Url(as: 'candidate')]
    public ?int $candidateId = null;

    #[Url(as: 'type')]
    public ?string $type = null;

    #[Url(as: 'region')]
    public ?int $regionId = null;

    #[Url(as: 'district')]
    public ?int $districtId = null;

    #[Url(as: 'group')]
    public ?int $groupId = null;

    #[Url(as: 'from')]
    public ?string $dateFrom = null;

    #[Url(as: 'to')]
    public ?string $dateTo = null;

    public function updatingQ(): void { $this->resetPage(); }
    public function updatingCandidateId(): void { $this->resetPage(); }
    public function updatingType(): void { $this->resetPage(); }
    public function updatingRegionId(): void { $this->districtId = null; $this->resetPage(); }
    public function updatingDistrictId(): void { $this->resetPage(); }
    public function updatingGroupId(): void { $this->resetPage(); }
    public function updatingDateFrom(): void { $this->resetPage(); }
    public function updatingDateTo(): void { $this->resetPage(); }

    public function render()
    {
        $candidates = Candidate::query()
            ->orderByDesc('is_active')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'is_active']);

        $regions = Region::orderBy('name')->get(['id', 'name']);
        $groups = Group::orderBy('name')->get(['id', 'name']);

        $districts = District::query()
            ->when($this->regionId, fn (Builder $q) => $q->where('region_id', $this->regionId))
            ->orderBy('name')
            ->get(['id', 'name', 'region_id']);

        $base = Interaction::query()
            ->with([
                'user:id,name',
                'delegate:id,full_name,category,district_id',
                'delegate.district:id,name,region_id',
                'delegate.district.region:id,name',
                'delegate.groups:id,name',
                'candidate:id,name',
            ]);

        StatusHistoryFilters::apply($base, [
            'q' => $this->q,
            'candidateId' => $this->candidateId,
            'type' => $this->type,
            'regionId' => $this->regionId,
            'districtId' => $this->districtId,
            'groupId' => $this->groupId,
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo,
        ]);

        $events = $base->latest()->paginate(25);

        $types = ['note', 'status_change', 'bulk', 'approval'];

        return view('livewire.reports.status-history', [
            'candidates' => $candidates,
            'regions' => $regions,
            'districts' => $districts,
            'groups' => $groups,
            'types' => $types,
            'events' => $events,
        ])->layout('layouts.app');
    }
}
