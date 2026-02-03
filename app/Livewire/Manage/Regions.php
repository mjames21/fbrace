<?php

namespace App\Livewire\Manage;

use App\Models\Region;
use Livewire\Component;

class Regions extends Component
{
    public ?int $editingId = null;

    public string $name = '';
    public ?string $code = null;

    public function createNew(): void
    {
        $this->reset(['editingId', 'name', 'code']);
        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function edit(int $id): void
    {
        $r = Region::query()->findOrFail($id);

        $this->editingId = (int) $r->id;
        $this->name = (string) $r->name;
        $this->code = $r->code;
        $this->slug= $r->code;
        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function save(): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:30'],
        ]);

        Region::updateOrCreate(
            ['id' => $this->editingId],
            [
                'name' => $data['name'],
                'code' => $data['code'] ? strtoupper(trim($data['code'])) : null,
                'slug'=> $data['code'] ? strtoupper(trim($data['code'])) : null,
            ]
        );

        session()->flash('status', $this->editingId ? 'Region updated.' : 'Region created.');
        $this->createNew();
    }

    public function render()
    {
        $regions = Region::query()
            ->orderBy('name')
            ->get(['id', 'name', 'code','slug']);

        return view('livewire.manage.regions', [
            'regions' => $regions,
        ])->layout('layouts.app');
    }
}
