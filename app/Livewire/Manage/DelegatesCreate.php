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
    public ?int $categoryId = null;

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
            'categoryId' => ['nullable', 'integer', 'exists:categories,id'],
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
            'category_id' => $data['categoryId'] ? (int) $data['categoryId'] : null,
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

        $delegate = Delegate::query()->create($payload);

        if (!empty($data['groupId'])) {
            $delegate->groups()->sync([(int) $data['groupId']]);
        }

        $this->dispatch('notify', message: 'Delegate created.');
        $this->reset(['fullName','districtId','categoryId','phonePrimary','phoneSecondary','guarantorId','groupId','isHighValue','notes']);
        $this->isActive = true;
    }

    public function render()
    {
        $districts = District::query()->orderBy('name')->get(['id','name']);
        $categories = Category::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(['id','name']);
        $groups = Group::query()->orderBy('name')->get(['id','name']);
        $guarantors = Guarantor::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(['id','name']);

        return view('livewire.manage.delegates-create', [
            'districts' => $districts,
            'categories' => $categories,
            'groups' => $groups,
            'guarantors' => $guarantors,
        ])->layout('layouts.app');
    }
}
