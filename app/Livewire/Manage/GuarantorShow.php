<?php

namespace App\Livewire\Manage;

use App\Models\Delegate;
use App\Models\Guarantor;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class GuarantorShow extends Component
{
    use WithPagination;

    public int $guarantorId;

    #[Url(as: 'q')]
    public string $q = '';

    #[Url(as: 'per')]
    public int $perPage = 25;

    public function mount(int $guarantorId): void
    {
        $this->guarantorId = $guarantorId;
    }

    public function updatingQ(): void
    {
        $this->resetPage();
    }

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    public function assignDelegate(int $delegateId): void
    {
        Delegate::query()
            ->whereKey($delegateId)
            ->update(['guarantor_id' => $this->guarantorId]);

        $this->dispatch('notify', message: 'Delegate assigned.');
        $this->resetPage();
    }

    public function unassignDelegate(int $delegateId): void
    {
        Delegate::query()
            ->whereKey($delegateId)
            ->where('guarantor_id', $this->guarantorId)
            ->update(['guarantor_id' => null]);

        $this->dispatch('notify', message: 'Delegate removed.');
        $this->resetPage();
    }

    public function render()
    {
        $guarantor = Guarantor::query()
            ->with(['district.region'])
            ->findOrFail($this->guarantorId);

        // Assigned delegates
        $assigned = Delegate::query()
            ->with(['district.region', 'groups'])
            ->where('guarantor_id', $this->guarantorId)
            ->when($this->q !== '', function (Builder $x) {
                // Postgres: ilike. MySQL: like (change if needed)
                return $x->where('full_name', 'ilike', "%{$this->q}%");
            })
            ->orderBy('full_name')
            ->paginate($this->perPage, ['*'], pageName: 'assignedPage');

        // Unassigned delegates (for quick attach)
        $unassigned = Delegate::query()
            ->with(['district.region'])
            ->whereNull('guarantor_id')
            ->when($this->q !== '', function (Builder $x) {
                return $x->where('full_name', 'ilike', "%{$this->q}%");
            })
            ->orderBy('full_name')
            ->limit(25)
            ->get(['id', 'full_name', 'district_id', 'phone_primary', 'phone_secondary']);

        return view('livewire.manage.guarantor-show', [
            'guarantor' => $guarantor,
            'assigned' => $assigned,
            'unassigned' => $unassigned,
        ])->layout('layouts.app');
    }
}
