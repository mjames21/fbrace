<div class="p-6 space-y-6">
  @section('title', 'Compare — '.config('app.name'))

  <div class="flex items-start justify-between gap-4">
    <div>
      <h1 class="text-2xl font-semibold">Compare</h1>
      <p class="text-sm text-slate-600">Principal vs one other candidate.</p>
    </div>

    <a href="{{ route('board.delegate-board') }}"
       class="inline-flex items-center px-4 py-2 text-sm font-semibold rounded-md bg-gray-100 hover:bg-gray-200">
      Back to Board
    </a>
  </div>

  {{-- Filters --}}
  <div class="bg-white border rounded-lg shadow-sm p-4 space-y-3">
    <div class="grid md:grid-cols-7 gap-2">
      <input wire:model.live.debounce.300ms="q"
             placeholder="Search delegate..."
             class="border rounded-md px-3 py-2 text-sm" />

      <select wire:model.live="principalStance" class="border rounded-md px-3 py-2 text-sm">
        <option value="">Principal: All</option>
        <option value="for">Principal: For (🟢)</option>
        <option value="indicative">Principal: Indicative (🟡)</option>
        <option value="against">Principal: Against (🔴)</option>
        <option value="none">Principal: Not assessed (⭕)</option>
      </select>

      <select wire:model.live="compareCandidateId" class="border rounded-md px-3 py-2 text-sm">
        <option value="">No other candidate</option>
        @foreach($candidates as $c)
          @if(empty($c->is_principal))
            <option value="{{ $c->id }}">{{ $c->name }}</option>
          @endif
        @endforeach
      </select>

      <select wire:model.live="regionId" class="border rounded-md px-3 py-2 text-sm">
        <option value="">All categories</option>
        @foreach($regions as $r)
          <option value="{{ $r->id }}">{{ $r->name }}</option>
        @endforeach
      </select>

      <select wire:model.live="districtId" class="border rounded-md px-3 py-2 text-sm">
        <option value="">All subcategories</option>
        @foreach($districts as $d)
          <option value="{{ $d->id }}">{{ $d->name }}</option>
        @endforeach
      </select>
    </div>

    {{-- A–Z + Rows + Reset --}}
    <div class="flex flex-wrap items-center justify-between gap-3 pt-2">
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

      <div class="flex items-center gap-2">
        <select wire:model.live="perPage" class="border rounded-md px-3 py-2 text-sm">
          <option value="25">25</option>
          <option value="50">50</option>
          <option value="100">100</option>
          <option value="500">500</option>
          <option value="0">All</option>
        </select>

        <button wire:click="resetSorting"
                class="px-3 py-2 text-xs font-semibold rounded bg-gray-100 hover:bg-gray-200">
          Reset sort
        </button>
      </div>
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

            <th class="px-3 py-2 text-center whitespace-nowrap font-semibold">Principal</th>

            @if($compareCandidate)
              <th class="px-3 py-2 text-center whitespace-nowrap font-semibold">{{ $compareCandidate->name }}</th>
            @endif
          </tr>
        </thead>

        <tbody>
          @forelse($delegates as $d)
            @php
              $ps = $principalMap[$d->id] ?? null;
              $pStance = $ps?->stance ?? null;
              $pConf = (int)($ps?->confidence ?? 0);

              $pPill = match($pStance) {
                'for' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                'against' => 'bg-red-50 text-red-800 border-red-200',
                'indicative' => 'bg-amber-50 text-amber-900 border-amber-200',
                default => 'bg-slate-50 text-slate-700 border-slate-200',
              };

              $pEmoji = match($pStance) {
                'for' => '🟢',
                'against' => '🔴',
                'indicative' => '🟡',
                default => '⭕',
              };

              $cs = $compareCandidate ? ($compareMap[$d->id] ?? null) : null;
              $cStance = $cs?->stance ?? null;
              $cConf = (int)($cs?->confidence ?? 0);

              $cPill = match($cStance) {
                'for' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                'against' => 'bg-red-50 text-red-800 border-red-200',
                'indicative' => 'bg-amber-50 text-amber-900 border-amber-200',
                default => 'bg-slate-50 text-slate-700 border-slate-200',
              };

              $cEmoji = match($cStance) {
                'for' => '🟢',
                'against' => '🔴',
                'indicative' => '🟡',
                default => '⭕',
              };
            @endphp

            <tr class="border-t">
              <td class="px-3 py-2"><div class="font-medium">{{ $d->full_name }}</div></td>
              <td class="px-3 py-2 text-xs text-slate-700">{{ $d->district?->region?->name ?? '—' }}</td>
              <td class="px-3 py-2 text-xs text-slate-700">{{ $d->district?->name ?? '—' }}</td>
              <td class="px-3 py-2 text-xs text-slate-700">{{ $d->category ?: '—' }}</td>
              <td class="px-3 py-2 text-xs text-slate-700">{{ $d->groups?->pluck('name')->first() ?: '—' }}</td>

              <td class="px-3 py-2 text-center">
                <span class="inline-flex items-center gap-1 text-[11px] px-2 py-0.5 rounded-full border {{ $pPill }}">
                  <span>{{ $pEmoji }}</span>
                  <span class="font-semibold">{{ $ps ? $pConf : '—' }}</span>
                </span>
              </td>

              @if($compareCandidate)
                <td class="px-3 py-2 text-center">
                  <span class="inline-flex items-center gap-1 text-[11px] px-2 py-0.5 rounded-full border {{ $cPill }}">
                    <span>{{ $cEmoji }}</span>
                    <span class="font-semibold">{{ $cs ? $cConf : '—' }}</span>
                  </span>
                </td>
              @endif
            </tr>
          @empty
            <tr>
              <td colspan="{{ $compareCandidate ? 7 : 6 }}" class="px-3 py-8 text-center text-slate-500">
                No delegates found.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="px-4 py-3 border-t">
      @if($perPage !== 0)
        {{ $delegates->links() }}
      @endif
    </div>
  </div>
</div>
