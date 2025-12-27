<div class="p-6 space-y-6">
  @section('title', 'Compare Candidates — '.config('app.name'))

  <div class="flex items-center justify-between gap-4">
    <div>
      <h1 class="text-2xl font-semibold">Compare Candidates</h1>
      <p class="text-sm text-slate-600">Same delegates, all candidates’ stances in one table.</p>
    </div>

    <a href="{{ route('dashboard') }}"
       class="inline-flex items-center px-4 py-2 text-sm font-semibold rounded-md bg-gray-100 hover:bg-gray-200">
      Back
    </a>
  </div>

  <div class="bg-white border rounded-lg shadow-sm p-4 space-y-3">
    <div class="grid md:grid-cols-5 gap-2">
      <input wire:model.live.debounce.300ms="q" placeholder="Search delegate..."
             class="border rounded-md px-3 py-2 text-sm" />

      <select wire:model.live="filterCategory" class="border rounded-md px-3 py-2 text-sm">
        <option value="">All categories</option>
        @foreach($categories as $c)
          <option value="{{ $c }}">{{ $c }}</option>
        @endforeach
      </select>

      <select wire:model.live="filterRegionId" class="border rounded-md px-3 py-2 text-sm">
        <option value="">All regions</option>
        @foreach($regions as $r)
          <option value="{{ $r->id }}">{{ $r->name }}</option>
        @endforeach
      </select>

      <select wire:model.live="filterDistrictId" class="border rounded-md px-3 py-2 text-sm">
        <option value="">All districts</option>
        @foreach($districtsForFilter as $d)
          <option value="{{ $d->id }}">{{ $d->name }}</option>
        @endforeach
      </select>

      <select wire:model.live="filterGroupId" class="border rounded-md px-3 py-2 text-sm">
        <option value="">All groups</option>
        @foreach($groups as $g)
          <option value="{{ $g->id }}">{{ $g->name }}</option>
        @endforeach
      </select>
    </div>
  </div>

  <div class="bg-white border rounded-lg shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-slate-600">
          <tr>
            <th class="px-3 py-2 text-left sticky left-0 bg-slate-50 z-10 w-[280px]">Delegate</th>
            <th class="px-3 py-2 text-left sticky left-[280px] bg-slate-50 z-10 hidden lg:table-cell w-[240px]">Location</th>
            @foreach($candidates as $c)
              <th class="px-3 py-2 text-center whitespace-nowrap">{{ $c->name }}</th>
            @endforeach
          </tr>
        </thead>

        <tbody>
          @forelse($delegates as $d)
            <tr class="border-t">
              <td class="px-3 py-2 sticky left-0 bg-white z-10 w-[280px]">
                <div class="font-medium">{{ $d->full_name }}</div>
                <div class="text-xs text-slate-500">{{ $d->category ?: '—' }}</div>
                @if($d->groups?->count())
                  <div class="mt-1 text-[11px] text-slate-500">{{ $d->groups->pluck('name')->implode(', ') }}</div>
                @endif
              </td>

              <td class="px-3 py-2 sticky left-[280px] bg-white z-10 hidden lg:table-cell w-[240px]">
                <div>{{ $d->district?->name ?? '—' }}</div>
                <div class="text-xs text-slate-500">{{ $d->district?->region?->name ?? '—' }}</div>
              </td>

              @foreach($candidates as $c)
                @php
                  $s = $matrix[$d->id][$c->id] ?? null;
                  $stance = $s?->stance ?? 'indicative';
                  $pending = (bool)($s?->pending_stance);
                  $conf = $s?->confidence ?? 50;

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

                <td class="px-3 py-2 text-center">
                  <span class="inline-flex items-center gap-1 text-[11px] px-2 py-0.5 rounded-full border {{ $pill }}"
                        title="Confidence: {{ $conf }}{{ $pending ? ' | Pending exists' : '' }}">
                    <span>{{ $emoji }}</span>
                    <span class="font-semibold">{{ $conf }}</span>
                    @if($pending)
                      <span class="ml-1 inline-flex items-center justify-center w-4 h-4 rounded-full bg-slate-900 text-white text-[10px]">P</span>
                    @endif
                  </span>
                </td>
              @endforeach
            </tr>
          @empty
            <tr><td colspan="{{ 2 + count($candidates) }}" class="px-3 py-8 text-center text-slate-500">No delegates found.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="px-4 py-3 border-t">
      {{ $delegates->links() }}
    </div>
  </div>
</div>
