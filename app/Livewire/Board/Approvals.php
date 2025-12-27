<?php

namespace App\Livewire\Board;

use App\Models\Candidate;
use App\Models\DelegateCandidateStatus;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class Approvals extends Component
{
    use WithPagination;

    public ?int $candidateId = null;

    public function mount(): void
    {
        $this->candidateId = Candidate::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->value('id');
    }

    public function approve(int $statusId): void
    {
        $row = DelegateCandidateStatus::find($statusId);
        if (!$row) return;

        if (!$row->pending_stance) return;

        $row->stance = $row->pending_stance;
        $row->confidence = $row->pending_confidence ?? $row->confidence;

        $row->pending_stance = null;
        $row->pending_confidence = null;

        $row->save();

        $this->dispatch('notify', message: 'Approved.');
    }

    public function reject(int $statusId): void
    {
        $row = DelegateCandidateStatus::find($statusId);
        if (!$row) return;

        $row->pending_stance = null;
        $row->pending_confidence = null;

        $row->save();

        $this->dispatch('notify', message: 'Rejected.');
    }

    public function render()
    {
        $candidates = Candidate::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name']);

        $pending = DelegateCandidateStatus::query()
            ->with(['delegate:id,full_name,district_id', 'delegate.district:id,name,region_id', 'delegate.district.region:id,name', 'candidate:id,name'])
            ->when($this->candidateId, fn (Builder $q) => $q->where('candidate_id', $this->candidateId))
            ->whereNotNull('pending_stance')
            ->latest()
            ->paginate(25);

        return view('livewire.board.approvals', [
            'candidates' => $candidates,
            'pending' => $pending,
        ])->layout('layouts.app');
    }
}
