<div class="p-6 space-y-6">
  @section('title', 'Compare Candidates — '.config('app.name'))

  <div class="flex items-start justify-between gap-4">
    <div>
      <h1 class="text-2xl font-semibold">Compare Candidates</h1>
      <p class="text-sm text-slate-600">Sort delegates by name/region/district/category/group.</p>
    </div>

    <div class="flex items-center gap-2">
      <a href="{{ route('board.delegate-board') }}"
         class="inline-flex items-center px-4 py-2 text-sm font-semibold rounded-md bg-gray-100 hover:bg-gray-200">
        Back to Board
      </a>
    </div>
  </div>

  {{-- Filters --}}
  <div class="bg-white border rounded-lg shadow-sm p-4 space-y-3">
    <div class="grid md:grid-cols-6 gap-2">
      <input wire:model.live.debounce.300ms="q"
             placeholder="Search delegate..."
             class="border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10" />

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

      <select wire:model.live="perPage" class="border rounded-md px-3 py-2 text-sm">
        <option value="25">25</option>
        <option value="50">50</option>
        <option value="100">100</option>
        <option value="500">500</option>
        <option value="0">All</option>
      </select>
    </div>

    {{-- A–Z + Reset --}}
    <div class="flex items-center justify-between gap-3 pt-2">
      <div class="flex flex-wrap gap-1">
        @php
          $letters = array_merge(['ALL'], range('A','Z'));
          $current = $az ?: 'ALL';
        @endphp

        @foreach($letters as $L)
          @php $active = strtoupper($current) === $L; @endphp
          <button wire:click="$set('az','{{ $L === 'ALL' ? '' : $L }}')"
                  class="px-2 py-1 text-xs rounded border {{ $active ? 'bg-slate-900 text-white border-slate-900' : 'bg-white hover:bg-slate-50 border-slate-200' }}">
            {{ $L }}
          </button>
        @endforeach
      </div>

      <button wire:click="resetSorting"
              class="px-3 py-1.5 text-xs font-semibold rounded bg-gray-100 hover:bg-gray-200">
        Reset sort
      </button>
    </div>

    <div class="text-[11px] text-slate-500">A–Z applies only when search is empty.</div>
  </div>

  {{-- Table --}}
  <div class="bg-white border rounded-lg shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-slate-600">
          <tr>
            <th class="px-3 py-2 text-left">
              <button wire:click="sortBy('name')" class="font-semibold hover:underline">
                Delegate
                @if(($sort ?? 'name') === 'name')
                  <span class="ml-1 text-[10px]">{{ ($dir ?? 'asc') === 'asc' ? '▲' : '▼' }}</span>
                @endif
              </button>
            </th>

            <th class="px-3 py-2 text-left">
              <button wire:click="sortBy('region')" class="font-semibold hover:underline">
                Region
                @if(($sort ?? '') === 'region')
                  <span class="ml-1 text-[10px]">{{ ($dir ?? 'asc') === 'asc' ? '▲' : '▼' }}</span>
                @endif
              </button>
            </th>

            <th class="px-3 py-2 text-left">
              <button wire:click="sortBy('district')" class="font-semibold hover:underline">
                District
                @if(($sort ?? '') === 'district')
                  <span class="ml-1 text-[10px]">{{ ($dir ?? 'asc') === 'asc' ? '▲' : '▼' }}</span>
                @endif
              </button>
            </th>

            <th class="px-3 py-2 text-left">
              <button wire:click="sortBy('category')" class="font-semibold hover:underline">
                Category
                @if(($sort ?? '') === 'category')
                  <span class="ml-1 text-[10px]">{{ ($dir ?? 'asc') === 'asc' ? '▲' : '▼' }}</span>
                @endif
              </button>
            </th>

            <th class="px-3 py-2 text-left">
              <button wire:click="sortBy('group')" class="font-semibold hover:underline">
                Group
                @if(($sort ?? '') === 'group')
                  <span class="ml-1 text-[10px]">{{ ($dir ?? 'asc') === 'asc' ? '▲' : '▼' }}</span>
                @endif
              </button>
            </th>

            @foreach($candidates as $c)
              <th class="px-3 py-2 text-center whitespace-nowrap font-semibold">
                {{ $c->name }}
                @if(!empty($c->is_principal))
                  <span class="ml-1 text-[10px] px-2 py-0.5 rounded-full border border-slate-300 bg-white">P</span>
                @endif
              </th>
            @endforeach
          </tr>
        </thead>

        <tbody>
          @forelse($delegates as $d)
            <tr class="border-t">
              <td class="px-3 py-2">
                <div class="font-medium">{{ $d->full_name }}</div>
                <div class="text-xs text-slate-500">{{ $d->category ?: '—' }}</div>
              </td>

              <td class="px-3 py-2 text-xs text-slate-700">
                {{ $d->district?->region?->name ?? '—' }}
              </td>

              <td class="px-3 py-2 text-xs text-slate-700">
                {{ $d->district?->name ?? '—' }}
              </td>

              <td class="px-3 py-2 text-xs text-slate-700">
                {{ $d->category ?: '—' }}
              </td>

              <td class="px-3 py-2 text-xs text-slate-700">
                {{ $d->groups?->pluck('name')->first() ?: '—' }}
              </td>

              @foreach($candidates as $c)
                @php
                  $s = $matrix[$d->id][$c->id] ?? null;
                  $stance = $s?->stance ?? 'indicative';
                  $conf = (int)($s?->confidence ?? 50);

                  $pill = match($stance) {
                    'for' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                    'against' => 'bg-red-50 text-red-800 border-red-200',
                    default => 'bg-amber-50 text-amber-900 border-amber-200',
                  };

                  $emoji = match($stance) {
                    'for' => '🟢',
                    'against' => '🔴',
                    default => '🟡',
                  };
                @endphp

                <td class="px-3 py-2 text-center">
                  <span class="inline-flex items-center gap-1 text-[11px] px-2 py-0.5 rounded-full border {{ $pill }}">
                    <span>{{ $emoji }}</span>
                    <span class="font-semibold">{{ $conf }}</span>
                  </span>
                </td>
              @endforeach
            </tr>
          @empty
            <tr>
              <td colspan="{{ 5 + count($candidates) }}" class="px-3 py-8 text-center text-slate-500">
                No delegates found.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="px-4 py-3 border-t">
      {{ $delegates->links() }}
    </div>
  </div>
</div>
