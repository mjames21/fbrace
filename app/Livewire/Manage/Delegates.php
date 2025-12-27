<?php

namespace App\Livewire\Manage;

use App\Models\Delegate;
use App\Models\District;
use App\Models\Group;
use App\Models\Region;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class Delegates extends Component
{
    use WithPagination;

    public string $q = '';
    public ?int $regionId = null;
    public ?int $districtId = null;
    public ?int $groupId = null;
    public ?string $category = null;

    public ?int $editingId = null;

    public string $full_name = '';
    public ?string $category_form = null;
    public ?int $district_id = null;
    /** @var array<int> */
    public array $group_ids = [];

    public function updatingQ(): void { $this->resetPage(); }
    public function updatingRegionId(): void { $this->districtId = null; $this->resetPage(); }
    public function updatingDistrictId(): void { $this->resetPage(); }
    public function updatingGroupId(): void { $this->resetPage(); }
    public function updatingCategory(): void { $this->resetPage(); }

    public function createNew(): void
    {
        $this->reset(['editingId', 'full_name', 'category_form', 'district_id', 'group_ids']);
        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function edit(int $id): void
    {
        $d = Delegate::query()->with('groups:id')->findOrFail($id);

        $this->editingId = $d->id;
        $this->full_name = (string) $d->full_name;
        $this->category_form = $d->category;
        $this->district_id = $d->district_id;
        $this->group_ids = $d->groups->pluck('id')->map(fn ($v) => (int)$v)->all();

        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function save(): void
    {
        $data = $this->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'category_form' => ['nullable', 'string', 'max:255'],
            'district_id' => ['nullable', 'integer', 'exists:districts,id'],
            'group_ids' => ['array'],
            'group_ids.*' => ['integer', 'exists:groups,id'],
        ]);

        $delegate = Delegate::updateOrCreate(
            ['id' => $this->editingId],
            [
                'full_name' => $data['full_name'],
                'category' => $data['category_form'],
                'district_id' => $data['district_id'],
            ]
        );

        $delegate->groups()->sync($data['group_ids'] ?? []);

        session()->flash('status', $this->editingId ? 'Delegate updated.' : 'Delegate created.');
        $this->createNew();
    }

    public function render()
    {
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

        $delegates = Delegate::query()
            ->with(['district.region', 'groups'])
            ->when($this->q !== '', fn (Builder $q) => $q->where('full_name', 'like', "%{$this->q}%"))
            ->when($this->category, fn (Builder $q) => $q->where('category', $this->category))
            ->when($this->districtId, fn (Builder $q) => $q->where('district_id', $this->districtId))
            ->when($this->regionId, fn (Builder $q) => $q->whereHas('district', fn (Builder $d) => $d->where('region_id', $this->regionId)))
            ->when($this->groupId, fn (Builder $q) => $q->whereHas('groups', fn (Builder $g) => $g->where('groups.id', $this->groupId)))
            ->orderBy('full_name')
            ->paginate(25);

        return view('livewire.manage.delegates', [
            'regions' => $regions,
            'districts' => $districts,
            'groups' => $groups,
            'categories' => $categories,
            'delegates' => $delegates,
        ])->layout('layouts.app');
    }
}
