<?php

namespace App\Livewire\Manage;

use App\Models\District;
use App\Models\Guarantor;
use Illuminate\Support\Str;
use Livewire\Component;

class Guarantors extends Component
{
    public ?int $editingId = null;

    public string $name = '';
    public ?string $phonePrimary = null;
    public ?string $phoneSecondary = null;
    public ?int $districtId = null;

    public bool $isActive = true;
    public int $sortOrder = 1000;
    public ?string $notes = null;

    public function createNew(): void
    {
        $this->resetForm();
    }

    public function edit(int $id): void
    {
        $g = Guarantor::query()->findOrFail($id);

        $this->editingId = (int) $g->id;
        $this->name = (string) $g->name;
        $this->phonePrimary = $g->phone_primary;
        $this->phoneSecondary = $g->phone_secondary;
        $this->districtId = $g->district_id ? (int) $g->district_id : null;
        $this->isActive = (bool) $g->is_active;
        $this->sortOrder = (int) ($g->sort_order ?? 1000);
        $this->notes = $g->notes;
    }

    public function toggleActive(int $id): void
    {
        $g = Guarantor::query()->findOrFail($id);
        $g->update(['is_active' => !$g->is_active]);

        session()->flash('status', 'Updated.');
        $this->dispatch('$refresh');
    }

    public function save(): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'phonePrimary' => ['nullable', 'string', 'max:32'],
            'phoneSecondary' => ['nullable', 'string', 'max:32'],
            'districtId' => ['nullable', 'integer'],
            'isActive' => ['boolean'],
            'sortOrder' => ['integer', 'min:0', 'max:999999'],
            'notes' => ['nullable', 'string'],
        ]);

        $slug = $this->uniqueSlug($data['name'], $this->editingId);

        Guarantor::updateOrCreate(
            ['id' => $this->editingId],
            [
                'name' => $data['name'],
                'slug' => $slug,
                'phone_primary' => $data['phonePrimary'],
                'phone_secondary' => $data['phoneSecondary'],
                'district_id' => $data['districtId'],
                'is_active' => $data['isActive'],
                'sort_order' => $data['sortOrder'],
                'notes' => $data['notes'],
            ]
        );

        session()->flash('status', 'Saved.');
        $this->resetForm();
        $this->dispatch('$refresh');
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->phonePrimary = null;
        $this->phoneSecondary = null;
        $this->districtId = null;
        $this->isActive = true;
        $this->sortOrder = 1000;
        $this->notes = null;
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'guarantor';
        $slug = $base;
        $i = 2;

        while (
            Guarantor::query()
                ->where('slug', $slug)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base . '-' . $i;
            $i++;
        }

        return $slug;
    }

    public function render()
    {
        $guarantors = Guarantor::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'phone_primary', 'phone_secondary', 'district_id', 'is_active', 'sort_order']);

        $districts = District::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('livewire.manage.guarantors', [
            'guarantors' => $guarantors,
            'districts' => $districts,
        ])->layout('layouts.app');
    }
}
