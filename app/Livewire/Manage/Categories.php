<?php

declare(strict_types=1);

namespace App\Livewire\Manage;

use App\Models\Category;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

final class Categories extends Component
{
    /** @var array<int,Category> */
    public array $rows = [];

    public ?int $editingId = null;

    public string $name = '';
    public bool $isActive = true;
    public int $sortOrder = 1000;
    public ?string $notes = null;

    public function mount(): void
    {
        $this->refreshRows();
    }

    public function refreshRows(): void
    {
        $this->rows = Category::query()
            ->orderByDesc('is_active')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->all();
    }

    public function createNew(): void
    {
        $this->resetForm();
        $this->editingId = null;
    }

    public function edit(int $id): void
    {
        $c = Category::query()->findOrFail($id);

        $this->editingId = (int) $c->id;
        $this->name = (string) $c->name;
        $this->isActive = (bool) $c->is_active;
        $this->sortOrder = (int) $c->sort_order;
        $this->notes = $c->notes ? (string) $c->notes : null;
    }

    public function save(): void
    {
        $id = $this->editingId;

        $data = $this->validate([
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('categories', 'name')->ignore($id),
            ],
            'isActive' => ['boolean'],
            'sortOrder' => ['integer', 'min:0', 'max:999999'],
            'notes' => ['nullable', 'string'],
        ]);

        $slugBase = Str::slug($data['name']);
        $slug = $slugBase;

        $cat = $id ? Category::query()->findOrFail($id) : new Category();

        if (!$cat->exists || empty($cat->slug)) {
            $i = 1;
            while (Category::query()->where('slug', $slug)->exists()) {
                $i++;
                $slug = "{$slugBase}-{$i}";
            }
            $cat->slug = $slug;
        }

        $cat->name = $data['name'];
        $cat->is_active = (bool) $data['isActive'];
        $cat->sort_order = (int) $data['sortOrder'];
        $cat->notes = $data['notes'] ?? null;
        $cat->save();

        $this->dispatch('notify', message: 'Category saved.');
        $this->resetForm();
        $this->editingId = null;
        $this->refreshRows();
    }

    public function toggleActive(int $id): void
    {
        $c = Category::query()->findOrFail($id);
        $c->is_active = !$c->is_active;
        $c->save();

        $this->refreshRows();
    }

    private function resetForm(): void
    {
        $this->reset(['name', 'isActive', 'sortOrder', 'notes']);
        $this->isActive = true;
        $this->sortOrder = 1000;
    }

    public function render()
    {
        return view('livewire.manage.categories', [
            'rows' => $this->rows,
        ])->layout('layouts.app');
    }
}
