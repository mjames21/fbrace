<?php

namespace App\Livewire\Manage;

use App\Models\Delegate;
use App\Models\District;
use App\Models\Group;
use App\Models\Region;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Delegates extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $q = '';

    #[Url(as: 'category')]
    public string $category = '';

    #[Url(as: 'region')]
    public ?int $regionId = null;

    #[Url(as: 'district')]
    public ?int $districtId = null;

    #[Url(as: 'group')]
    public ?int $groupId = null;

    // Form
    public ?int $editingId = null;
    public string $full_name = '';
    public ?string $category_form = null;
    public ?int $district_id = null;
    public array $group_ids = [];

    // Delete confirm
    public ?int $confirmDeleteId = null;

    public function updatingQ(): void { $this->resetPage(); }
    public function updatingCategory(): void { $this->resetPage(); }
    public function updatingRegionId(): void { $this->districtId = null; $this->resetPage(); }
    public function updatingDistrictId(): void { $this->resetPage(); }
    public function updatingGroupId(): void { $this->resetPage(); }

    public function createNew(): void
    {
        $this->resetForm();
    }

    public function edit(int $id): void
    {
        $d = Delegate::query()->with('groups')->findOrFail($id);

        $this->editingId = $d->id;
        $this->full_name = (string) $d->full_name;
        $this->category_form = $d->category;
        $this->district_id = $d->district_id;
        $this->group_ids = $d->groups->pluck('id')->map(fn ($v) => (int) $v)->all();
    }

    public function save(): void
    {
        $data = $this->validate([
            'full_name' => ['required', 'string', 'min:3', 'max:255'],
            'category_form' => ['nullable', 'string', 'max:255'],
            'district_id' => ['nullable', 'integer', 'exists:districts,id'],
            'group_ids' => ['array'],
            'group_ids.*' => ['integer', 'exists:groups,id'],
        ]);

        $payload = [
            'full_name' => $data['full_name'],
            'category' => $data['category_form'] ?: null,
            'district_id' => $data['district_id'] ?: null,
            'is_active' => true,
        ];

        if ($this->editingId) {
            Delegate::query()->whereKey($this->editingId)->update($payload);
            $delegateId = $this->editingId;
        } else {
            $delegateId = (int) Delegate::query()->create($payload)->id;
            $this->editingId = $delegateId;
        }

        // Groups
        $delegate = Delegate::query()->findOrFail($delegateId);
        $delegate->groups()->sync($data['group_ids'] ?? []);

        session()->flash('status', 'Saved.');
        $this->dispatch('notify', message: 'Delegate saved.');
    }

    public function confirmDelete(int $id): void
    {
        $this->confirmDeleteId = $id;
    }

    public function cancelDelete(): void
    {
        $this->confirmDeleteId = null;
    }

    /**
     * SAFE delete = archive (is_active=false).
     * Keeps history/statuses intact.
     */
    public function deleteDelegate(): void
    {
        if (!$this->confirmDeleteId) return;

        $id = $this->confirmDeleteId;

        $d = Delegate::query()->findOrFail($id);

        // Optional: detach groups to keep pivots clean
        $d->groups()->detach();

        // Archive
        $d->is_active = false;
        $d->save();

        // If currently editing, reset form
        if ($this->editingId === $id) {
            $this->resetForm();
        }

        $this->confirmDeleteId = null;

        session()->flash('status', 'Delegate deleted (archived).');
        $this->dispatch('notify', message: 'Delegate deleted (archived).');

        $this->resetPage();
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->full_name = '';
        $this->category_form = null;
        $this->district_id = null;
        $this->group_ids = [];
        $this->confirmDeleteId = null;
    }

    private function baseQuery(): Builder
    {
        return Delegate::query()
            ->with(['district.region', 'groups'])
            ->when($this->q !== '', function (Builder $q) {
                $q->where('full_name', 'ilike', '%' . $this->q . '%');
            })
            ->when($this->category !== '', fn (Builder $q) => $q->where('category', $this->category))
            ->when($this->regionId, fn (Builder $q) => $q->whereHas('district', fn (Builder $d) => $d->where('region_id', $this->regionId)))
            ->when($this->districtId, fn (Builder $q) => $q->where('district_id', $this->districtId))
            ->when($this->groupId, fn (Builder $q) => $q->whereHas('groups', fn (Builder $g) => $g->where('groups.id', $this->groupId)))
            ->orderBy('full_name');
    }

    public function render()
    {
        $regions = Region::query()->orderBy('name')->get(['id', 'name']);

        $districts = District::query()
            ->when($this->regionId, fn (Builder $q) => $q->where('region_id', $this->regionId))
            ->orderBy('name')
            ->get(['id', 'name', 'region_id']);

        $groups = Group::query()->orderBy('name')->get(['id', 'name']);

        $categories = Delegate::query()
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->all();

        $delegates = $this->baseQuery()->paginate(25);

        return view('livewire.manage.delegates', [
            'delegates' => $delegates,
            'regions' => $regions,
            'districts' => $districts,
            'groups' => $groups,
            'categories' => $categories,
        ])->layout('layouts.app');
    }
}
