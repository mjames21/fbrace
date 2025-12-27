<?php

namespace App\Livewire\Manage;

use App\Models\Candidate;
use Illuminate\Support\Str;
use Livewire\Component;

class Candidates extends Component
{
    public ?int $editingId = null;

    public string $name = '';
    public bool $isActive = true;
    public int $sortOrder = 1000;

    public function createNew(): void
    {
        $this->resetForm();
    }

    public function edit(int $id): void
    {
        $c = Candidate::query()->findOrFail($id);

        $this->editingId = (int) $c->id;
        $this->name = (string) $c->name;
        $this->isActive = (bool) $c->is_active;
        $this->sortOrder = (int) ($c->sort_order ?? 1000);
    }

    public function toggleActive(int $id): void
    {
        $c = Candidate::query()->findOrFail($id);

        $c->update([
            'is_active' => !$c->is_active,
        ]);

        session()->flash('status', 'Updated.');
        $this->dispatch('$refresh');
    }

    public function save(): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'isActive' => ['boolean'],
            'sortOrder' => ['integer', 'min:0', 'max:999999'],
        ]);

        $slug = $this->uniqueSlug($data['name'], $this->editingId);

        Candidate::updateOrCreate(
            ['id' => $this->editingId],
            [
                'name' => $data['name'],
                'slug' => $slug,
                'is_active' => (bool) $data['isActive'],
                'sort_order' => (int) $data['sortOrder'],
            ]
        );

        session()->flash('status', 'Saved.');
        $this->resetForm();
        $this->dispatch('$refresh');
    }

    public function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->isActive = true;
        $this->sortOrder = 1000;
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'candidate';
        $slug = $base;
        $i = 2;

        while (
            Candidate::query()
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
        $candidates = Candidate::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'is_active', 'sort_order']);

        return view('livewire.manage.candidates', [
            'candidates' => $candidates,
        ])->layout('layouts.app');
    }
}
