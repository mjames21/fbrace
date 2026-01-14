{{-- resources/views/livewire/board/delegate-drawer.blade.php --}}

<div class="space-y-6">
  @php
    $stance = $status?->stance ?? 'indicative';
    $conf = (int)($status?->confidence ?? 50);

    $pill = match($stance) {
      'for' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
      'against' => 'bg-red-50 text-red-700 border-red-200',
      default => 'bg-amber-50 text-amber-800 border-amber-200',
    };

    $emoji = match($stance) {
      'for' => '🟢',
      'against' => '🔴',
      default => '🟡',
    };
  @endphp

  {{-- Header --}}
  <div class="space-y-1">
    <div class="text-xs uppercase tracking-wide text-slate-500">Delegate</div>
    <div class="text-xl font-semibold">{{ $delegate->full_name ?? $delegate->name }}</div>

    <div class="text-sm text-slate-600">
      <span class="font-medium">{{ $delegate->district?->region?->name ?? '—' }}</span>
      <span class="text-slate-300 mx-2">•</span>
      <span>{{ $delegate->district?->name ?? '—' }}</span>
    </div>

    <div class="text-xs text-slate-500">
      Category: <span class="text-slate-700 font-medium">{{ $delegate->category ?? '—' }}</span>
    </div>

    <div class="text-xs text-slate-500">
      Groups: <span class="text-slate-700">{{ $delegate->groups?->pluck('name')->implode(', ') ?: '—' }}</span>
    </div>
  </div>

  {{-- ONE SAVE BUTTON --}}
  <div class="flex items-center justify-end">
    <button wire:click="save"
            class="inline-flex items-center px-4 py-2 text-sm font-semibold rounded bg-slate-900 text-white hover:bg-slate-800">
      Save
    </button>
  </div>

  {{-- Delegate profile --}}
  <div class="bg-white border rounded-lg shadow-sm p-4 space-y-4">
    <div class="text-sm font-semibold text-slate-700">Delegate Profile</div>

    <div class="grid grid-cols-2 gap-3">
      <div>
        <label class="block text-xs font-medium text-slate-600">Phone 1</label>
        <input wire:model.defer="phonePrimary"
               class="mt-1 w-full border rounded-md px-3 py-2 text-sm"
               placeholder="e.g. +232..." />
      </div>

      <div>
        <label class="block text-xs font-medium text-slate-600">Phone 2</label>
        <input wire:model.defer="phoneSecondary"
               class="mt-1 w-full border rounded-md px-3 py-2 text-sm"
               placeholder="optional" />
      </div>
    </div>

    <div>
      <label class="block text-xs font-medium text-slate-600">Guarantor</label>
      <select wire:model.defer="guarantorId" class="mt-1 w-full border rounded-md px-3 py-2 text-sm">
        <option value="">No guarantor</option>
        @foreach($guarantors as $g)
          <option value="{{ $g->id }}">{{ $g->name }}</option>
        @endforeach
      </select>
    </div>

    <div>
      <label class="block text-xs font-medium text-slate-600">Category</label>
      <div class="grid grid-cols-2 gap-2">
        <select wire:model.defer="category" class="mt-1 w-full border rounded-md px-3 py-2 text-sm">
          <option value="">—</option>
          @foreach($categories as $c)
            <option value="{{ $c }}">{{ $c }}</option>
          @endforeach
        </select>

        <input wire:model.defer="category"
               class="mt-1 w-full border rounded-md px-3 py-2 text-sm"
               placeholder="Or type a new category" />
      </div>
    </div>

    <div>
      <label class="block text-xs font-medium text-slate-600">Group (single)</label>
      <select wire:model.defer="groupId" class="mt-1 w-full border rounded-md px-3 py-2 text-sm">
        <option value="">No group</option>
        @foreach($groups as $g)
          <option value="{{ $g->id }}">{{ $g->name }}</option>
        @endforeach
      </select>
    </div>

    <div>
      <label class="block text-xs font-medium text-slate-600">Notes (optional)</label>
      <textarea wire:model.defer="notes" rows="3"
                class="mt-1 w-full border rounded-md px-3 py-2 text-sm"
                placeholder="Quick notes about access, relationships, issues..."></textarea>
    </div>

    <p class="text-[11px] text-slate-500">
      This profile is shared across candidates. Status below is per selected candidate.
    </p>
  </div>

  {{-- Log Interaction --}}
  <div class="bg-white border rounded-lg shadow-sm p-4 space-y-3">
    <div class="text-sm font-semibold text-slate-700">Log Interaction</div>

    <div class="grid grid-cols-2 gap-2">
      <div>
        <label class="block text-xs font-medium text-slate-600">Type</label>
        <select wire:model.defer="interactionType" class="mt-1 w-full border rounded-md px-3 py-2 text-sm">
          @foreach($types as $k => $label)
            <option value="{{ $k }}">{{ $label }}</option>
          @endforeach
        </select>
      </div>

      <div>
        <label class="block text-xs font-medium text-slate-600">Outcome (optional)</label>
        <select wire:model.defer="interactionOutcome" class="mt-1 w-full border rounded-md px-3 py-2 text-sm">
          <option value="">—</option>
          @foreach($outcomes as $k => $label)
            <option value="{{ $k }}">{{ $label }}</option>
          @endforeach
        </select>
      </div>
    </div>

    <div>
      <label class="block text-xs font-medium text-slate-600">Notes (optional)</label>
      <textarea wire:model.defer="interactionNotes" rows="3"
                class="mt-1 w-full border rounded-md px-3 py-2 text-sm"
                placeholder="Details of the interaction..."></textarea>
    </div>

    <div>
      <label class="block text-xs font-medium text-slate-600">Next step date/time (optional)</label>
      <input type="datetime-local" wire:model.defer="nextStepAt"
             class="mt-1 w-full border rounded-md px-3 py-2 text-sm" />
    </div>

    <p class="text-[11px] text-slate-500">
      Fill any interaction field and hit <b>Save</b>. If all are empty, nothing is logged.
    </p>
  </div>

  {{-- Recent interactions --}}
  <div class="bg-white border rounded-lg shadow-sm p-4 space-y-3">
    <div class="flex items-center justify-between">
      <div class="text-sm font-semibold text-slate-700">Recent Interactions</div>
      <div class="text-xs text-slate-500">Latest 10</div>
    </div>

    @if($recentInteractions->isEmpty())
      <div class="text-sm text-slate-600">No interactions logged yet.</div>
    @else
      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="bg-slate-50 text-slate-600">
            <tr>
              <th class="px-2 py-2 text-left">When</th>
              <th class="px-2 py-2 text-left">Type</th>
              <th class="px-2 py-2 text-left">Outcome</th>
              <th class="px-2 py-2 text-left">Notes</th>
              <th class="px-2 py-2 text-left">User</th>
              <th class="px-2 py-2 text-left">Next step</th>
            </tr>
          </thead>
          <tbody>
            @foreach($recentInteractions as $i)
              <tr class="border-t align-top">
                <td class="px-2 py-2 text-xs text-slate-500 whitespace-nowrap">
                  {{ $i->created_at?->diffForHumans() }}
                  <div class="text-[11px]">{{ $i->created_at?->toDateTimeString() }}</div>
                </td>
                <td class="px-2 py-2 font-mono text-xs">{{ $i->type }}</td>
                <td class="px-2 py-2 text-xs">{{ $i->outcome ? ($outcomes[$i->outcome] ?? $i->outcome) : '—' }}</td>
                <td class="px-2 py-2 text-xs whitespace-pre-wrap">{{ $i->notes ?? '—' }}</td>
                <td class="px-2 py-2 text-xs">{{ $i->user?->name ?? 'Unknown' }}</td>
                <td class="px-2 py-2 text-xs whitespace-nowrap">
                  {{ $i->next_step_at ? $i->next_step_at->toDateTimeString() : '—' }}
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>

  {{-- Candidate Status --}}
  <div class="bg-white border rounded-lg shadow-sm p-4 space-y-4">
    <div class="flex items-center justify-between">
      <div>
        <div class="text-sm font-semibold text-slate-700">Status for Candidate</div>
        <div class="text-xs text-slate-500">{{ $candidate?->name ?? '—' }}</div>
      </div>

      <span class="inline-flex items-center gap-1 text-[11px] px-2 py-0.5 rounded-full border {{ $pill }}">
        <span>{{ $emoji }}</span>
        <span class="font-semibold">{{ strtoupper($stance) }}</span>
        <span class="font-mono opacity-80">{{ $conf }}%</span>
      </span>
    </div>

    <div class="flex items-center gap-2">
      <button wire:click="setStance('for')"
              class="px-3 py-1.5 text-xs font-semibold rounded bg-emerald-50 border border-emerald-200 text-emerald-800 hover:bg-emerald-100">
        🟢 For us
      </button>

      <button wire:click="setStance('indicative')"
              class="px-3 py-1.5 text-xs font-semibold rounded bg-amber-50 border border-amber-200 text-amber-900 hover:bg-amber-100">
        🟡 Indicative
      </button>

      <button wire:click="setStance('against')"
              class="px-3 py-1.5 text-xs font-semibold rounded bg-red-50 border border-red-200 text-red-800 hover:bg-red-100">
        🔴 Against
      </button>

      <div class="ml-auto flex items-center gap-2">
        <span class="text-xs text-slate-500">Confidence</span>
        <input type="number" min="0" max="100"
               value="{{ $conf }}"
               wire:change="updateConfidence($event.target.value)"
               class="w-24 border rounded-md px-3 py-2 text-sm text-right" />
      </div>
    </div>

    <p class="text-[11px] text-slate-500">
      Stance does not overwrite confidence. Confidence is edited separately.
    </p>
  </div>

  {{-- Alliance spillover --}}
  <div class="bg-white border rounded-lg shadow-sm p-4 space-y-3">
    <div class="flex items-center justify-between">
      <div class="text-sm font-semibold text-slate-700">Alliance Spillover</div>
      <div class="text-xs text-slate-500">
        Total: <span class="font-semibold text-slate-700">{{ number_format((float)($spill['total'] ?? 0), 3) }}</span>
      </div>
    </div>

    @if(empty($spill['rows']))
      <div class="text-sm text-slate-600">No active alliances affecting this candidate.</div>
    @else
      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="bg-slate-50 text-slate-600">
            <tr>
              <th class="px-2 py-2 text-left">Source</th>
              <th class="px-2 py-2 text-left">Weight</th>
              <th class="px-2 py-2 text-left">Stance</th>
              <th class="px-2 py-2 text-left">Conf</th>
              <th class="px-2 py-2 text-right">Contribution</th>
            </tr>
          </thead>
          <tbody>
            @foreach($spill['rows'] as $r)
              <tr class="border-t">
                <td class="px-2 py-2">{{ $r['source'] }}</td>
                <td class="px-2 py-2 text-xs">{{ $r['weight'] }}%</td>
                <td class="px-2 py-2 text-xs">{{ strtoupper($r['stance']) }}</td>
                <td class="px-2 py-2 text-xs">{{ $r['confidence'] }}%</td>
                <td class="px-2 py-2 text-right font-mono text-xs">{{ number_format((float)$r['contribution'], 3) }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>
</div>
