<?php

declare(strict_types=1);

namespace App\Livewire\Manage;

use App\Models\Category;
use App\Models\Delegate;
use App\Models\District;
use App\Models\Group;
use App\Models\Guarantor;
use Illuminate\Support\Str;
use Livewire\Component;

final class DelegatesCreate extends Component
{
    public string $fullName = '';
    public ?int $districtId = null;

    // Category stored by NAME (as requested)
    public string $categoryName = '';

    public ?string $phonePrimary = null;
    public ?string $phoneSecondary = null;

    public ?int $guarantorId = null;
    public ?int $groupId = null; // single group

    public bool $isActive = true;
    public bool $isHighValue = false;

    public ?string $notes = null;

    public function save(): void
    {
        $data = $this->validate([
            'fullName' => ['required', 'string', 'max:255'],
            'districtId' => ['required', 'integer', 'exists:districts,id'],
            'categoryName' => ['nullable', 'string', 'max:255'],

            'phonePrimary' => ['nullable', 'string', 'max:32'],
            'phoneSecondary' => ['nullable', 'string', 'max:32'],

            'guarantorId' => ['nullable', 'integer', 'exists:guarantors,id'],
            'groupId' => ['nullable', 'integer', 'exists:groups,id'],

            'isActive' => ['boolean'],
            'isHighValue' => ['boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        $payload = [
            'full_name' => Str::squish($data['fullName']),
            'district_id' => (int) $data['districtId'],
            'category' => $data['categoryName'] !== '' ? $data['categoryName'] : null, // ✅ name -> delegates.category

            'phone_primary' => $data['phonePrimary'] ? trim($data['phonePrimary']) : null,
            'phone_secondary' => $data['phoneSecondary'] ? trim($data['phoneSecondary']) : null,

            'guarantor_id' => $data['guarantorId'] ? (int) $data['guarantorId'] : null,

            'is_active' => (bool) $data['isActive'],
            'is_high_value' => (bool) $data['isHighValue'],
        ];

        $delegate = new Delegate();

        if ($delegate->isFillable('notes')) {
            $payload['notes'] = $data['notes'] ?? null;
        } elseif ($delegate->isFillable('internal_notes')) {
            $payload['internal_notes'] = $data['notes'] ?? null;
        }

        $created = Delegate::query()->create($payload);

        if (!empty($data['groupId'])) {
            $created->groups()->sync([(int) $data['groupId']]);
        }

        // ✅ redirect back to list after save (your "b")
        session()->flash('status', 'Delegate created.');
        $this->dispatch('notify', message: 'Delegate created.');

        $this->redirectRoute('manage.delegates', navigate: true);
    }

    public function render()
    {
        $districts = District::query()->orderBy('name')->get(['id','name']);

        // Category list is from Category model, but value saved is the name (string)
        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['name']);

        $groups = Group::query()->orderBy('name')->get(['id','name']);

        $guarantors = Guarantor::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id','name']);

        return view('livewire.manage.delegates-create', [
            'districts' => $districts,
            'categories' => $categories,
            'groups' => $groups,
            'guarantors' => $guarantors,
        ])->layout('layouts.app');
    }
}
