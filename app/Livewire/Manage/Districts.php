<?php

namespace App\Livewire\Manage;

use App\Models\District;
use App\Models\Region;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class Districts extends Component
{
    use WithPagination;

    public string $q = '';
    public ?int $filterRegionId = null;

    public ?int $editingId = null;

    public string $name = '';
    public ?string $code = null;
    public ?int $region_id = null;

    public function updatingQ(): void { $this->resetPage(); }
    public function updatingFilterRegionId(): void { $this->resetPage(); }

    public function createNew(): void
    {
        $this->reset(['editingId', 'name', 'code', 'region_id']);
        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function edit(int $id): void
    {
        $d = District::query()->findOrFail($id);

        $this->editingId = (int) $d->id;
        $this->name = (string) $d->name;
        $this->code = $d->code;
        $this->region_id = $d->region_id;

        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function save(): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:30'],
            'region_id' => ['nullable', 'integer', 'exists:regions,id'],
        ]);

        District::updateOrCreate(
            ['id' => $this->editingId],
            [
                'name' => $data['name'],
                'code' => $data['code'] ? strtoupper(trim($data['code'])) : null,
                'region_id' => $data['region_id'],
            ]
        );

        session()->flash('status', $this->editingId ? 'District updated.' : 'District created.');
        $this->createNew();
    }

    public function render()
    {
        $regions = Region::query()->orderBy('name')->get(['id', 'name']);

        $districts = District::query()
            ->with('region:id,name')
            ->when($this->q !== '', fn (Builder $q) => $q->where('name', 'like', "%{$this->q}%"))
            ->when($this->filterRegionId, fn (Builder $q) => $q->where('region_id', $this->filterRegionId))
            ->orderBy('name')
            ->paginate(25);

        return view('livewire.manage.districts', [
            'regions' => $regions,
            'districts' => $districts,
        ])->layout('layouts.app');
    }
}
