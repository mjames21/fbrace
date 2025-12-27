<div class="space-y-6">
  @php
    $phone = data_get($delegate, 'phone') ?? data_get($delegate, 'phone_number') ?? data_get($delegate, 'mobile') ?? null;
    $notes = data_get($delegate, 'notes') ?? data_get($delegate, 'internal_notes') ?? null;

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
  <div class="space-y-2">
    <div class="flex items-start justify-between gap-3">
      <div>
        <div class="text-xl font-semibold">{{ $delegate->full_name }}</div>
        <div class="text-sm text-slate-600">
          {{ $delegate->category ?: '—' }}
          <span class="text-slate-300 mx-2">•</span>
          {{ $delegate->district?->region?->name ?? '—' }} / {{ $delegate->district?->name ?? '—' }}
        </div>
      </div>

      <div class="text-right">
        <span class="inline-flex items-center gap-1 text-[11px] px-2 py-0.5 rounded-full border {{ $pill }}">
          <span>{{ $emoji }}</span>
          <span class="font-semibold">{{ $conf }}</span>
        </span>
        <div class="text-[11px] text-slate-500 mt-1">
          {{ $candidate?->name ?? 'Selected candidate' }}
        </div>
      </div>
    </div>

    <div class="flex flex-wrap gap-2 text-xs">
      @foreach(($delegate->groups ?? collect()) as $g)
        <span class="px-2 py-0.5 rounded-full border bg-slate-50 text-slate-700 border-slate-200">
          {{ $g->name }}
        </span>
      @endforeach

      @if(!$delegate->groups || $delegate->groups->isEmpty())
        <span class="text-slate-500">No groups.</span>
      @endif
    </div>
  </div>

  {{-- Contact + Notes --}}
  <div class="grid md:grid-cols-2 gap-4">
    <div class="bg-white border rounded-lg p-4">
      <div class="text-sm font-semibold text-slate-800">Contact</div>
      <div class="mt-2 text-sm text-slate-700 space-y-1">
        <div><span class="text-slate-500">Phone:</span> {{ $phone ?: '—' }}</div>
      </div>
    </div>

    <div class="bg-white border rounded-lg p-4">
      <div class="text-sm font-semibold text-slate-800">Notes</div>
      <div class="mt-2 text-sm text-slate-700 whitespace-pre-line">
        {{ $notes ?: '—' }}
      </div>
    </div>
  </div>

  {{-- Update stance --}}
  <div class="bg-white border rounded-lg p-4 space-y-3">
    <div class="flex items-center justify-between">
      <div class="text-sm font-semibold text-slate-800">Update Support</div>
      <div class="text-xs text-slate-500">Confidence 0–100</div>
    </div>

    <div class="flex flex-wrap items-center gap-2">
      <button wire:click="setStance('for', 70)"
              class="px-3 py-2 text-sm font-semibold rounded bg-emerald-50 border border-emerald-200 text-emerald-800 hover:bg-emerald-100">
        🟢 For us
      </button>

      <button wire:click="setStance('indicative', 50)"
              class="px-3 py-2 text-sm font-semibold rounded bg-amber-50 border border-amber-200 text-amber-900 hover:bg-amber-100">
        🟡 Indicative
      </button>

      <button wire:click="setStance('against', 70)"
              class="px-3 py-2 text-sm font-semibold rounded bg-red-50 border border-red-200 text-red-800 hover:bg-red-100">
        🔴 Against
      </button>

      <div class="ml-auto flex items-center gap-2">
        <span class="text-xs text-slate-500">Confidence</span>
        <input type="number" min="0" max="100"
               value="{{ $conf }}"
               wire:change="updateConfidence($event.target.value)"
               class="w-24 border rounded-md px-3 py-2 text-sm text-right focus:outline-none focus:ring-2 focus:ring-slate-900/10" />
      </div>
    </div>
  </div>

  {{-- Spillover --}}
  <div class="bg-white border rounded-lg p-4">
    <div class="flex items-center justify-between">
      <div>
        <div class="text-sm font-semibold text-slate-800">Alliance Spillover</div>
        <div class="text-xs text-slate-500">Incoming alliances into {{ $candidate?->name ?? 'this candidate' }} (for this delegate)</div>
      </div>
      <div class="text-sm font-semibold">
        Total: <span class="font-mono">{{ number_format((float)($spill['total'] ?? 0), 3) }}</span>
      </div>
    </div>

    @if(empty($spill['rows'] ?? []))
      <div class="mt-3 text-sm text-slate-600">No spillover signals.</div>
    @else
      <div class="mt-3 overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="bg-slate-50 text-slate-600">
            <tr>
              <th class="px-3 py-2 text-left">Source</th>
              <th class="px-3 py-2 text-right">Weight</th>
              <th class="px-3 py-2 text-center">Stance</th>
              <th class="px-3 py-2 text-right">Conf</th>
              <th class="px-3 py-2 text-right">Contribution</th>
            </tr>
          </thead>
          <tbody>
            @foreach($spill['rows'] as $r)
              <tr class="border-t">
                <td class="px-3 py-2 font-medium">{{ $r['source'] }}</td>
                <td class="px-3 py-2 text-right font-mono text-xs">{{ $r['weight'] }}%</td>
                <td class="px-3 py-2 text-center">
                  @if($r['stance'] === 'for') 🟢 @elseif($r['stance'] === 'against') 🔴 @else 🟡 @endif
                </td>
                <td class="px-3 py-2 text-right font-mono text-xs">{{ $r['confidence'] }}</td>
                <td class="px-3 py-2 text-right font-mono text-xs">{{ number_format((float)$r['contribution'], 3) }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>
</div>
