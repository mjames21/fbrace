<?php

namespace App\Livewire\Manage;

use App\Models\Candidate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;

class Candidates extends Component
{
    public ?int $editingId = null;

    public string $name = '';
    public ?string $slug = null;

    public bool $isActive = true;
    public bool $isPrincipal = false;

    public int $sortOrder = 1000;
    public ?string $notes = null;

    public function createNew(): void
    {
        $this->resetForm();
        $this->editingId = null;
    }

    public function edit(int $id): void
    {
        $c = Candidate::query()->findOrFail($id);

        $this->editingId = $c->id;
        $this->name = (string) $c->name;
        $this->slug = (string) ($c->slug ?? Str::slug($c->name));
        $this->isActive = (bool) ($c->is_active ?? true);
        $this->isPrincipal = (bool) ($c->is_principal ?? false);
        $this->sortOrder = (int) ($c->sort_order ?? 1000);
        $this->notes = $c->notes ?? null;
    }

    public function save(): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'isActive' => ['boolean'],
            'isPrincipal' => ['boolean'],
            'sortOrder' => ['integer', 'min:0', 'max:1000000'],
            'notes' => ['nullable', 'string'],
        ]);

        $slug = $data['slug'] ? Str::slug($data['slug']) : Str::slug($data['name']);

        DB::transaction(function () use ($data, $slug) {
            // enforce ONLY ONE principal
            if ($data['isPrincipal']) {
                Candidate::query()->update(['is_principal' => false]);
            }

            $payload = [
                'name' => $data['name'],
                'slug' => $slug,
                'is_active' => (bool) $data['isActive'],
                'is_principal' => (bool) $data['isPrincipal'],
                'sort_order' => (int) $data['sortOrder'],
                'notes' => $data['notes'] ?? null,
            ];

            if ($this->editingId) {
                Candidate::query()->whereKey($this->editingId)->update($payload);
            } else {
                Candidate::query()->create($payload);
            }
        });

        session()->flash('status', 'Candidate saved.');
        $this->dispatch('notify', message: 'Candidate saved.');
        $this->resetForm();
    }

    public function setPrincipal(int $candidateId): void
    {
        DB::transaction(function () use ($candidateId) {
            Candidate::query()->update(['is_principal' => false]);
            Candidate::query()->whereKey($candidateId)->update(['is_principal' => true]);
        });

        $this->dispatch('notify', message: 'Principal candidate updated.');
    }

    public function toggleActive(int $candidateId): void
    {
        $c = Candidate::query()->findOrFail($candidateId);
        $c->is_active = !$c->is_active;
        $c->save();

        $this->dispatch('notify', message: 'Candidate updated.');
    }

    public function delete(int $candidateId): void
    {
        Candidate::query()->whereKey($candidateId)->delete();
        $this->dispatch('notify', message: 'Candidate deleted.');
        $this->createNew();
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->slug = null;
        $this->isActive = true;
        $this->isPrincipal = false;
        $this->sortOrder = 1000;
        $this->notes = null;
    }

    public function render()
    {
        $candidates = Candidate::query()
            ->orderByDesc('is_principal')
            ->orderByDesc('is_active')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('livewire.manage.candidates', [
            'candidates' => $candidates,
        ])->layout('layouts.app');
    }
}
