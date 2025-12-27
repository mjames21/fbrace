<?php

namespace App\Livewire\Manage;

use App\Models\Group;
use Livewire\Component;

class Groups extends Component
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
        $g = Group::query()->findOrFail($id);

        $this->editingId = (int) $g->id;
        $this->name = (string) $g->name;
        $this->code = $g->code;

        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function save(): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:30'],
        ]);

        Group::updateOrCreate(
            ['id' => $this->editingId],
            [
                'name' => $data['name'],
                'code' => $data['code'] ? strtoupper(trim($data['code'])) : null,
            ]
        );

        session()->flash('status', $this->editingId ? 'Group updated.' : 'Group created.');
        $this->createNew();
    }

    public function render()
    {
        $groups = Group::query()
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return view('livewire.manage.groups', [
            'groups' => $groups,
        ])->layout('layouts.app');
    }
}
