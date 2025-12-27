<div class="p-6 space-y-8">
  @section('title', 'Alliances — '.config('app.name'))

  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-2xl font-semibold">Alliances</h1>
      <p class="text-sm text-slate-600">
        Source → Target mapping. Choose <b>Exclusive</b> (single target) or <b>Split</b> (multiple targets, total cap).
      </p>
    </div>

    <button wire:click="createNew"
            class="inline-flex items-center px-4 py-2 text-sm font-semibold rounded-md bg-slate-900 text-white hover:bg-slate-800">
      New Alliance
    </button>
  </div>

  <div class="grid lg:grid-cols-3 gap-6">
    {{-- List --}}
    <div class="lg:col-span-2 bg-white border rounded-lg shadow-sm overflow-hidden">
      <div class="px-4 py-3 border-b flex items-center justify-between">
        <h2 class="text-sm font-semibold text-slate-700">Alliance List</h2>
        <span class="text-xs text-slate-500">{{ $alliances->count() }} records</span>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="bg-slate-50 text-slate-600">
            <tr>
              <th class="px-3 py-2 text-left">Source</th>
              <th class="px-3 py-2 text-left">Target</th>
              <th class="px-3 py-2 text-left">Weight</th>
              <th class="px-3 py-2 text-left">Active</th>
              <th class="px-3 py-2 text-right">Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($alliances as $a)
              @php $pct = (int) round(((float) $a->weight) * 100); @endphp
              <tr class="border-t">
                <td class="px-3 py-2 font-medium">{{ $a->fromCandidate?->name ?? '—' }}</td>
                <td class="px-3 py-2 font-medium">{{ $a->toCandidate?->name ?? '—' }}</td>
                <td class="px-3 py-2 font-mono text-xs">{{ $pct }}%</td>
                <td class="px-3 py-2">
                  @if($a->is_active)
                    <span class="text-[11px] px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">Active</span>
                  @else
                    <span class="text-[11px] px-2 py-0.5 rounded-full bg-gray-50 text-gray-700 border border-gray-200">Inactive</span>
                  @endif
                </td>
                <td class="px-3 py-2 text-right">
                  <button wire:click="edit({{ $a->id }})"
                          class="text-xs font-semibold text-slate-700 hover:underline">
                    Edit
                  </button>
                  <span class="text-slate-300 mx-2">|</span>
                  <button wire:click="toggleActive({{ $a->id }})"
                          class="text-xs font-semibold text-slate-700 hover:underline">
                    Toggle
                  </button>
                </td>
              </tr>
            @empty
              <tr><td colspan="5" class="px-3 py-8 text-center text-slate-500">No alliances yet.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    {{-- Form --}}
    <div class="bg-white border rounded-lg shadow-sm p-4 space-y-4">
      <div class="flex items-center justify-between">
        <h2 class="text-sm font-semibold text-slate-700">{{ $editingId ? 'Edit Alliance' : 'New Alliance' }}</h2>
        @if (session('status'))
          <span class="text-[11px] text-emerald-600">{{ session('status') }}</span>
        @endif
      </div>

      <div class="space-y-3 text-sm">
        <div>
          <label class="block text-xs font-medium text-slate-600">Source candidate</label>
          <select wire:model.live="from_candidate_id"
                  class="mt-1 w-full border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10">
            <option value="">—</option>
            @foreach($candidates as $c)
              <option value="{{ $c->id }}">{{ $c->name }}{{ $c->is_active ? '' : ' (inactive)' }}</option>
            @endforeach
          </select>
          @error('from_candidate_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        {{-- Policy --}}
        <div class="rounded border bg-slate-50 p-3">
          <div class="text-xs font-semibold text-slate-700">Source policy</div>

          <div class="mt-2 space-y-2">
            <label class="flex items-center gap-2 text-sm">
              <input type="radio" class="rounded border-gray-300" wire:model.live="policy_mode" value="exclusive">
              <span><b>Exclusive</b> (only one active target allowed)</span>
            </label>

            <label class="flex items-center gap-2 text-sm">
              <input type="radio" class="rounded border-gray-300" wire:model.live="policy_mode" value="split">
              <span><b>Split</b> (multiple targets, total weight capped)</span>
            </label>

            <div class="flex items-center gap-2">
              <span class="text-xs text-slate-600">Max total (%)</span>
              <input type="number" min="1" max="100" wire:model.live="policy_max_total_weight_percent"
                     class="w-24 border rounded-md px-2 py-1 text-xs text-right">
            </div>

            @error('policy_mode') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            @error('policy_max_total_weight_percent') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
          </div>
        </div>

        <div>
          <label class="block text-xs font-medium text-slate-600">Target candidate</label>
          <select wire:model.live="to_candidate_id"
                  class="mt-1 w-full border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10">
            <option value="">—</option>
            @foreach($candidates as $c)
              <option value="{{ $c->id }}">{{ $c->name }}{{ $c->is_active ? '' : ' (inactive)' }}</option>
            @endforeach
          </select>
          @error('to_candidate_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-medium text-slate-600">Weight (%)</label>
            <input type="number" min="0" max="100" wire:model.live="weight_percent"
                   class="mt-1 w-full border rounded-md px-3 py-2 text-sm text-right focus:outline-none focus:ring-2 focus:ring-slate-900/10">
            @error('weight_percent') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
          </div>

          <div class="flex items-end">
            <label class="inline-flex items-center gap-2">
              <input type="checkbox" wire:model.live="is_active" class="rounded border-gray-300">
              <span class="text-sm text-slate-700">Active</span>
            </label>
          </div>
        </div>

        <div>
          <label class="block text-xs font-medium text-slate-600">Notes (optional)</label>
          <textarea wire:model.live="notes" rows="3"
                    class="mt-1 w-full border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10"
                    placeholder="Who is coordinating / why the alliance is real..."></textarea>
          @error('notes') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="pt-2">
          <button wire:click="save"
                  class="inline-flex items-center px-4 py-2 text-sm font-semibold rounded-md bg-slate-900 text-white hover:bg-slate-800">
            Save Alliance
          </button>
        </div>

        <p class="text-[11px] text-slate-500">
          Exclusive mode auto-disables other active targets for the same source candidate.
        </p>
      </div>
    </div>
  </div>

  {{-- Policy Summary --}}
  <div class="bg-white border rounded-lg shadow-sm overflow-hidden">
    <div class="px-4 py-3 border-b">
      <h2 class="text-sm font-semibold text-slate-700">Policy Summary</h2>
      <p class="text-xs text-slate-500">Per source candidate: mode, cap, and current active usage.</p>
    </div>

    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-slate-600">
          <tr>
            <th class="px-3 py-2 text-left">Source</th>
            <th class="px-3 py-2 text-left">Mode</th>
            <th class="px-3 py-2 text-right">Max (%)</th>
            <th class="px-3 py-2 text-right">Active targets</th>
            <th class="px-3 py-2 text-right">Active total (%)</th>
          </tr>
        </thead>
        <tbody>
          @foreach($policySummary as $r)
            @php
              $modePill = $r['mode'] === 'exclusive'
                ? 'bg-slate-900 text-white'
                : 'bg-slate-100 text-slate-800 border border-slate-200';
            @endphp
            <tr class="border-t">
              <td class="px-3 py-2 font-medium">{{ $r['source'] }}</td>
              <td class="px-3 py-2">
                <span class="text-[11px] px-2 py-0.5 rounded-full {{ $modePill }}">
                  {{ strtoupper($r['mode']) }}
                </span>
              </td>
              <td class="px-3 py-2 text-right font-mono text-xs">{{ $r['max'] }}</td>
              <td class="px-3 py-2 text-right font-mono text-xs">{{ $r['active_targets'] }}</td>
              <td class="px-3 py-2 text-right font-mono text-xs">{{ $r['active_total'] }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>

  {{-- Preview --}}
  <div class="bg-white border rounded-lg shadow-sm overflow-hidden">
    <div class="px-4 py-3 border-b flex items-center justify-between">
      <div>
        <h2 class="text-sm font-semibold text-slate-700">Alliance Spillover Preview</h2>
        <p class="text-xs text-slate-500">Direct = 🟢(1.0) + 🟡(0.5). Spillover uses active alliances only.</p>
      </div>
      <a href="{{ route('horse-race') }}" class="text-sm text-indigo-600 hover:underline">Open Horse Race</a>
    </div>

    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-slate-600">
          <tr>
            <th class="px-3 py-2 text-left">Candidate</th>
            <th class="px-3 py-2 text-center">🟢</th>
            <th class="px-3 py-2 text-center">🟡</th>
            <th class="px-3 py-2 text-center">🔴</th>
            <th class="px-3 py-2 text-right">Direct</th>
            <th class="px-3 py-2 text-right">Spillover</th>
            <th class="px-3 py-2 text-right">Total</th>
          </tr>
        </thead>
        <tbody>
          @foreach($preview as $r)
            <tr class="border-t">
              <td class="px-3 py-2 font-medium">{{ $r['candidate'] }}</td>
              <td class="px-3 py-2 text-center">{{ $r['for'] }}</td>
              <td class="px-3 py-2 text-center">{{ $r['indicative'] }}</td>
              <td class="px-3 py-2 text-center">{{ $r['against'] }}</td>
              <td class="px-3 py-2 text-right font-mono text-xs">{{ number_format($r['direct'], 1) }}</td>
              <td class="px-3 py-2 text-right font-mono text-xs">{{ number_format($r['spillover'], 1) }}</td>
              <td class="px-3 py-2 text-right font-semibold">{{ number_format($r['total'], 1) }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>
