<?php

namespace App\Livewire\Board;

use App\Models\Alliance;
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

    #[Url(as: 'q')]
    public string $q = '';

    #[Url(as: 'candidate')]
    public ?int $candidateId = null;

    #[Url(as: 'region')]
    public ?int $regionId = null;

    #[Url(as: 'district')]
    public ?int $districtId = null;

    #[Url(as: 'group')]
    public ?int $groupId = null;

    #[Url(as: 'category')]
    public ?string $category = null;

    // NEW
    #[Url(as: 'guarantor')]
    public ?int $guarantorId = null;

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

    public function mount(): void
    {
        if (!$this->candidateId) {
            $q = Candidate::query()->where('is_active', true)->orderBy('name');

            // If you DO have sort_order, keep it; otherwise remove it.
            if (\Illuminate\Support\Facades\Schema::hasColumn('candidates', 'sort_order')) {
                $q->orderBy('sort_order');
            }

            $this->candidateId = $q->value('id');
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
     * Assign / remove guarantor for a delegate.
     * Pass empty / 0 to remove.
     */
    public function assignGuarantor(int $delegateId, mixed $guarantorId): void
    {
        $gid = is_numeric($guarantorId) ? (int) $guarantorId : null;
        if ($gid === 0) $gid = null;

        Delegate::query()
            ->whereKey($delegateId)
            ->update(['guarantor_id' => $gid]);

        $this->dispatch('notify', message: 'Guarantor updated.');
    }

    /**
     * IMPORTANT: stance buttons should NOT force confidence to 70/50.
     * If row exists: only update stance (keep confidence).
     * If row doesn't exist: create with neutral default confidence (60).
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
            $row->confidence = 60;
        }

        $row->stance = $stance;
        $row->save();

        $this->dispatch('notify', message: 'Status updated.');
    }

    public function updateConfidence(int $delegateId, mixed $value): void
    {
        if (!$this->candidateId) return;

        $confidence = max(0, min(100, (int) $value));

        $row = DelegateCandidateStatus::firstOrCreate(
            ['delegate_id' => $delegateId, 'candidate_id' => $this->candidateId],
            ['stance' => 'indicative', 'confidence' => 60]
        );

        $row->confidence = $confidence;
        $row->save();

        $this->dispatch('notify', message: 'Confidence updated.');
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
        $this->clearSelection();
    }

    private function delegateQuery(): Builder
    {
        return Delegate::query()
            ->with(['district.region', 'groups', 'guarantor'])
            ->when($this->q !== '', fn (Builder $q) => $q->where('full_name', 'like', "%{$this->q}%"))
            ->when($this->category, fn (Builder $q) => $q->where('category', $this->category))
            ->when($this->districtId, fn (Builder $q) => $q->where('district_id', $this->districtId))
            ->when($this->regionId, fn (Builder $q) => $q->whereHas('district', fn (Builder $d) => $d->where('region_id', $this->regionId)))
            ->when($this->groupId, fn (Builder $q) => $q->whereHas('groups', fn (Builder $g) => $g->where('groups.id', $this->groupId)))
            ->when($this->guarantorId, fn (Builder $q) => $q->where('guarantor_id', $this->guarantorId))
            ->orderBy('full_name');
    }

    /**
     * @param  Collection<int,int>  $delegateIds
     * @return array<int,DelegateCandidateStatus>
     */
    private function statusesForPage(Collection $delegateIds): array
    {
        if (!$this->candidateId || $delegateIds->isEmpty()) return [];

        $cols = ['delegate_id', 'stance', 'confidence', 'updated_at'];
        if (\Illuminate\Support\Facades\Schema::hasColumn('delegate_candidate_status', 'pending_stance')) {
            $cols[] = 'pending_stance';
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn('delegate_candidate_status', 'pending_confidence')) {
            $cols[] = 'pending_confidence';
        }

        $rows = DelegateCandidateStatus::query()
            ->where('candidate_id', $this->candidateId)
            ->whereIn('delegate_id', $delegateIds->all())
            ->get($cols);

        $map = [];
        foreach ($rows as $r) {
            $map[(int) $r->delegate_id] = $r;
        }
        return $map;
    }

    // (your spilloverForPage stays the same)

    /**
     * @param  Collection<int,int>  $delegateIds
     * @return array{totals: array<int,float>, details: array<int,array<int,array{source:string,weight:float,stance:string,confidence:int,contribution:float}>>}
     */
    private function spilloverForPage(Collection $delegateIds): array
    {
        if (!$this->candidateId || $delegateIds->isEmpty()) {
            return ['totals' => [], 'details' => []];
        }

        $incoming = Alliance::query()
            ->where('is_active', true)
            ->where('to_candidate_id', $this->candidateId)
            ->get(['from_candidate_id', 'weight']);

        if ($incoming->isEmpty()) {
            return ['totals' => [], 'details' => []];
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

        $sourceStatuses = DelegateCandidateStatus::query()
            ->whereIn('candidate_id', $sourceIds)
            ->whereIn('delegate_id', $delegateIds->all())
            ->get(['delegate_id', 'candidate_id', 'stance', 'confidence']);

        $totals = [];
        $details = [];

        foreach ($sourceStatuses as $s) {
            $delegateId = (int) $s->delegate_id;
            $sourceId = (int) $s->candidate_id;

            $w = $weights[$sourceId] ?? 0.0;
            if ($w <= 0.0) continue;

            $stanceVal = match ($s->stance) {
                'for' => 1.0,
                'indicative' => 0.5,
                default => 0.0,
            };

            $confInt = (int) ($s->confidence ?? 60);
            $conf = max(0.0, min(100.0, (float) $confInt)) / 100.0;

            $contrib = $w * $stanceVal * $conf;
            if ($contrib <= 0.0) continue;

            $totals[$delegateId] = ($totals[$delegateId] ?? 0.0) + $contrib;

            $details[$delegateId] ??= [];
            $details[$delegateId][] = [
                'source' => $sourceNames[$sourceId] ?? ('Candidate '.$sourceId),
                'weight' => $w,
                'stance' => (string) $s->stance,
                'confidence' => $confInt,
                'contribution' => $contrib,
            ];
        }

        foreach ($details as $delegateId => $rows) {
            usort($rows, fn ($a, $b) => $b['contribution'] <=> $a['contribution']);
            $details[$delegateId] = $rows;
        }

        return ['totals' => $totals, 'details' => $details];
    }

    public function render()
    {
        $candidates = Candidate::query()
            ->orderByDesc('is_active')
            ->when(\Illuminate\Support\Facades\Schema::hasColumn('candidates', 'sort_order'), fn ($q) => $q->orderBy('sort_order'))
            ->orderBy('name')
            ->get(['id', 'name', 'is_active']);

        $regions = Region::orderBy('name')->get(['id', 'name']);
        $groups = Group::orderBy('name')->get(['id', 'name']);

        $districts = District::query()
            ->when($this->regionId, fn (Builder $q) => $q->where('region_id', $this->regionId))
            ->orderBy('name')
            ->get(['id', 'name', 'region_id']);

        // FIX: use "name" (not full_name)
        $guarantors = Guarantor::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name']);

        $categories = Delegate::query()
            ->select('category')
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->all();

        $delegates = $this->delegateQuery()->paginate($this->perPage);

        $this->currentPageIds = $delegates->getCollection()->pluck('id')->map(fn ($v) => (int) $v)->all();
        $delegateIdCollection = $delegates->getCollection()->pluck('id');

        $statusMap = $this->statusesForPage($delegateIdCollection);

        $spill = $this->spilloverForPage($delegateIdCollection);
        $spilloverMap = $spill['totals'];
        $spilloverDetailsMap = $spill['details'];

        return view('livewire.board.delegate-board', [
            'candidates' => $candidates,
            'regions' => $regions,
            'districts' => $districts,
            'groups' => $groups,
            'guarantors' => $guarantors,
            'categories' => $categories,
            'delegates' => $delegates,
            'statusMap' => $statusMap,
            'spilloverMap' => $spilloverMap,
            'spilloverDetailsMap' => $spilloverDetailsMap,
        ])->layout('layouts.app');
    }
}
