<div class="p-6 space-y-6">
  @section('title', 'Horse Race — '.config('app.name'))

  <div class="flex items-center justify-between gap-4">
    <div>
      <h1 class="text-2xl font-semibold">Horse Race</h1>
      <p class="text-sm text-slate-600">
        Rank = Direct + Spillover (optional). Direct = 🟢(1.0) + 🟡(adjustable).
      </p>
      <p class="text-xs text-slate-500 mt-1">
        Scope delegates: <span class="font-semibold">{{ $scopeTotalDelegates }}</span>
        @if($targetDelegates)
          <span class="text-slate-300 mx-2">•</span>
          Target: <span class="font-semibold">{{ $targetDelegates }}</span>
        @endif
      </p>
    </div>

    <a href="{{ route('manage.alliances') }}"
       class="inline-flex items-center px-4 py-2 text-sm font-semibold rounded-md bg-gray-100 hover:bg-gray-200">
      Manage Alliances
    </a>
  </div>

  {{-- Filters --}}
  <div class="bg-white border rounded-lg shadow-sm p-4 space-y-3">
    <div class="grid md:grid-cols-4 gap-2">
      <select wire:model.live="category" class="border rounded-md px-3 py-2 text-sm">
        <option value="">All categories</option>
        @foreach($categories as $c)
          <option value="{{ $c }}">{{ $c }}</option>
        @endforeach
      </select>

      <select wire:model.live="regionId" class="border rounded-md px-3 py-2 text-sm">
        <option value="">All regions</option>
        @foreach($regions as $r)
          <option value="{{ $r->id }}">{{ $r->name }}</option>
        @endforeach
      </select>

      <select wire:model.live="districtId" class="border rounded-md px-3 py-2 text-sm">
        <option value="">All districts</option>
        @foreach($districts as $d)
          <option value="{{ $d->id }}">{{ $d->name }}</option>
        @endforeach
      </select>

      <select wire:model.live="groupId" class="border rounded-md px-3 py-2 text-sm">
        <option value="">All groups</option>
        @foreach($groups as $g)
          <option value="{{ $g->id }}">{{ $g->name }}</option>
        @endforeach
      </select>
    </div>

    {{-- What-if controls --}}
    <div class="grid md:grid-cols-3 gap-4 items-center pt-2">
      <label class="inline-flex items-center gap-2 text-sm">
        <input type="checkbox" class="rounded border-gray-300" wire:model.live="includeSpillover">
        <span class="font-medium">Include alliance spillover</span>
      </label>

      <div class="md:col-span-2">
        <div class="flex items-center justify-between">
          <div class="text-sm font-medium text-slate-700">🟡 Indicative weight</div>
          <div class="text-xs font-mono text-slate-600">{{ number_format($indicativeWeight, 2) }}</div>
        </div>
        <input type="range" min="0.30" max="0.70" step="0.05" wire:model.live="indicativeWeight" class="w-full mt-2">
        <p class="text-xs text-slate-500 mt-1">Tune how much “indicative” counts toward the race.</p>
      </div>
    </div>

    {{-- Target --}}
    <div class="grid md:grid-cols-3 gap-4 items-center pt-2">
      <label class="inline-flex items-center gap-2 text-sm">
        <input type="checkbox" class="rounded border-gray-300" wire:model.live="autoTarget">
        <span class="font-medium">Auto target (majority of scope)</span>
      </label>

      <div class="md:col-span-2">
        <div class="flex items-center gap-2">
          <div class="text-sm font-medium text-slate-700">Target delegates to win</div>
          <input type="number" min="0" wire:model.live="targetDelegates"
                 class="w-32 border rounded-md px-3 py-2 text-sm text-right"
                 @disabled($autoTarget)>
          <span class="text-xs text-slate-500">Used for “Remaining” + “% to target”.</span>
        </div>
      </div>
    </div>
  </div>

  {{-- Table --}}
  <div class="bg-white border rounded-lg shadow-sm overflow-hidden">
    <table class="min-w-full text-sm">
      <thead class="bg-slate-50 text-slate-600">
        <tr>
          <th class="px-3 py-2 text-left">Candidate</th>
          <th class="px-3 py-2 text-center">🟢 For</th>
          <th class="px-3 py-2 text-center">🟡 Indicative</th>
          <th class="px-3 py-2 text-center">🔴 Against</th>
          <th class="px-3 py-2 text-right">Direct</th>
          <th class="px-3 py-2 text-right">Spillover</th>
          <th class="px-3 py-2 text-right">Total</th>
          <th class="px-3 py-2 text-right">Remaining</th>
          <th class="px-3 py-2 text-right">% to target</th>
        </tr>
      </thead>

      <tbody>
        @foreach($rows as $r)
          @php
            $pct = (float)($r['percent_to_target'] ?? 0);
            $pctLabel = $targetDelegates > 0 ? (int) round($pct * 100) : 0;
          @endphp

          <tr class="border-t">
            <td class="px-3 py-2 font-medium">{{ $r['candidate'] }}</td>

            <td class="px-3 py-2 text-center">{{ $r['for'] }}</td>
            <td class="px-3 py-2 text-center">{{ $r['indicative'] }}</td>
            <td class="px-3 py-2 text-center">{{ $r['against'] }}</td>

            <td class="px-3 py-2 text-right font-mono text-xs">{{ number_format($r['direct'], 2) }}</td>
            <td class="px-3 py-2 text-right font-mono text-xs">{{ number_format($r['spillover'], 2) }}</td>
            <td class="px-3 py-2 text-right font-semibold">{{ number_format($r['total'], 2) }}</td>

            <td class="px-3 py-2 text-right font-mono text-xs">
              {{ $targetDelegates > 0 ? number_format($r['remaining'], 2) : '—' }}
            </td>

            <td class="px-3 py-2 text-right">
              @if($targetDelegates > 0)
                <div class="flex items-center justify-end gap-2">
                  <div class="w-28 h-2 bg-slate-100 rounded overflow-hidden">
                    <div class="h-2 bg-slate-900" style="width: {{ $pctLabel }}%"></div>
                  </div>
                  <span class="text-xs font-semibold">{{ $pctLabel }}%</span>
                </div>
              @else
                —
              @endif
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>
