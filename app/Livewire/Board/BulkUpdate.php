<?php

namespace App\Livewire\Board;

use App\Models\Candidate;
use App\Models\Delegate;
use App\Models\DelegateCandidateStatus;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use Livewire\Component;

class BulkUpdate extends Component
{
    #[Url(as: 'candidate')]
    public ?int $candidateId = null;

    #[Url(as: 'q')]
    public string $q = '';

    #[Url(as: 'region')]
    public ?int $regionId = null;

    #[Url(as: 'district')]
    public ?int $districtId = null;

    #[Url(as: 'group')]
    public ?int $groupId = null;

    #[Url(as: 'category')]
    public ?string $category = null;

    public string $stance = 'indicative';
    public int $confidence = 50;

    public int $affected = 0;

    public function mount(): void
    {
        if (!$this->candidateId) {
            $this->candidateId = Candidate::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->value('id');
        }
    }

    private function delegateIds(): array
    {
        return Delegate::query()
            ->when($this->q !== '', fn (Builder $q) => $q->where('full_name', 'like', "%{$this->q}%"))
            ->when($this->category, fn (Builder $q) => $q->where('category', $this->category))
            ->when($this->districtId, fn (Builder $q) => $q->where('district_id', $this->districtId))
            ->when($this->regionId, fn (Builder $q) => $q->whereHas('district', fn (Builder $d) => $d->where('region_id', $this->regionId)))
            ->when($this->groupId, fn (Builder $q) => $q->whereHas('groups', fn (Builder $g) => $g->where('groups.id', $this->groupId)))
            ->pluck('id')
            ->all();
    }

    public function apply(): void
    {
        if (!$this->candidateId) return;

        $stance = strtolower(trim($this->stance));
        if (!in_array($stance, ['for', 'indicative', 'against'], true)) return;

        $confidence = max(0, min(100, (int)$this->confidence));

        $ids = $this->delegateIds();
        $this->affected = 0;

        foreach (array_chunk($ids, 500) as $chunk) {
            foreach ($chunk as $delegateId) {
                DelegateCandidateStatus::updateOrCreate(
                    ['delegate_id' => $delegateId, 'candidate_id' => $this->candidateId],
                    ['stance' => $stance, 'confidence' => $confidence]
                );
                $this->affected++;
            }
        }

        $this->dispatch('notify', message: "Bulk update complete ({$this->affected}).");
    }

    public function render()
    {
        $candidates = Candidate::query()
            ->orderByDesc('is_active')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'is_active']);

        return view('livewire.board.bulk-update', [
            'candidates' => $candidates,
        ])->layout('layouts.app');
    }
}
