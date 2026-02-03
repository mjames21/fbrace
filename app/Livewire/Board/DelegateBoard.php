<?php

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

    // Locks board to principal candidate
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

    // A–Z filter (applies only if q empty)
    #[Url(as: 'az')]
    public ?string $az = null;

    // Filter delegates by PRINCIPAL stance (for/indicative/against/none)
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
        return Candidate::query()
            ->where('is_principal', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->value('id');
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

    private function delegateQuery(): Builder
    {
        $query = Delegate::query()
            ->with(['district.region', 'groups', 'guarantor'])
            //->where('is_active', true) // ✅ hide archived/deleted
            ->when($this->q !== '', fn (Builder $q) => $q->where('full_name', 'ilike', "%{$this->q}%"))
            ->when($this->category, fn (Builder $q) => $q->where('category', $this->category))
            ->when($this->districtId, fn (Builder $q) => $q->where('district_id', $this->districtId))
            ->when($this->regionId, fn (Builder $q) => $q->whereHas('district', fn (Builder $d) => $d->where('region_id', $this->regionId)))
            ->when($this->groupId, fn (Builder $q) => $q->whereHas('groups', fn (Builder $g) => $g->where('groups.id', $this->groupId)))
            ->when($this->guarantorId, fn (Builder $q) => $q->where('guarantor_id', $this->guarantorId));

        $this->applyPrincipalStanceFilter($query);

        $az = strtoupper((string) ($this->az ?? ''));
        if ($this->q === '' && $az !== '') {
            $query->where('full_name', 'ilike', $az . '%');
        }

        return $query->orderByRaw('lower(full_name) asc');
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

        return view('livewire.board.delegate-board', [
            'candidates' => $candidates,
            'regions' => $regions,
            'districts' => $districts,
            'groups' => $groups,
            'categories' => $categories,
            'guarantors' => $guarantors,
            'delegates' => $delegates,
            'statusMap' => $statusMap,
        ])->layout('layouts.app');
    }
}
