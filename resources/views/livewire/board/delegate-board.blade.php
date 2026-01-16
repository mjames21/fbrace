{{-- resources/views/livewire/board/delegate-board.blade.php --}}
<div class="p-6 space-y-6">
  @section('title', 'Delegate Board — '.config('app.name'))

  <div class="flex items-start justify-between gap-4">
    <div>
      <h1 class="text-2xl font-semibold">Delegate Board</h1>
      <p class="text-sm text-slate-600">
        Track and update delegate support (🟢/🟡/🔴) for the selected candidate.
        Confidence never changes unless you change it.
      </p>
    </div>

    <div class="flex items-center gap-2">
      <a href="{{ route('board.compare-candidates') }}"
         class="inline-flex items-center px-4 py-2 text-sm font-semibold rounded-md bg-gray-100 hover:bg-gray-200">
        Compare Candidates
      </a>

      <a href="{{ route('horse-race') }}"
         class="inline-flex items-center px-4 py-2 text-sm font-semibold rounded-md bg-gray-100 hover:bg-gray-200">
        Horse Race
      </a>

      <a href="{{ route('reports.status-history') }}"
         class="inline-flex items-center px-4 py-2 text-sm font-semibold rounded-md bg-slate-900 text-white hover:bg-slate-800">
        Status History
      </a>
    </div>
  </div>

  {{-- Filters --}}
  <div class="bg-white border rounded-lg shadow-sm p-4 space-y-3">
    <div class="grid md:grid-cols-7 gap-2">
      <input wire:model.live.debounce.300ms="q"
             placeholder="Search delegate..."
             class="border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10" />

      {{-- Principal vs Other --}}
      <div class="border rounded-md px-3 py-2 text-sm flex items-center justify-between gap-3">
        <label class="inline-flex items-center gap-2 cursor-pointer select-none">
          <input type="checkbox" class="rounded border-gray-300"
                 wire:model.live="principalOnly">
          <span class="font-semibold text-slate-700">Principal</span>
        </label>

        <span class="text-xs text-slate-500">
          {{ $principalOnly ? 'Locked' : 'Choose' }}
        </span>
      </div>

      {{-- Candidate (disabled when principalOnly) --}}
      <select wire:model.live="candidateId"
              @disabled($principalOnly)
              class="border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10 disabled:bg-slate-50 disabled:text-slate-500">
        @foreach($candidates as $c)
          @php
            $label = $c->name;
            if (!empty($c->is_principal)) $label .= ' (principal)';
            elseif (empty($c->is_active)) $label .= ' (inactive)';
          @endphp
          <option value="{{ $c->id }}">{{ $label }}</option>
        @endforeach
      </select>

      <select wire:model.live="category"
              class="border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10">
        <option value="">All categories</option>
        @foreach($categories as $c)
          <option value="{{ $c }}">{{ $c }}</option>
        @endforeach
      </select>

      <select wire:model.live="regionId"
              class="border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10">
        <option value="">All regions</option>
        @foreach($regions as $r)
          <option value="{{ $r->id }}">{{ $r->name }}</option>
        @endforeach
      </select>

      <select wire:model.live="districtId"
              class="border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10">
        <option value="">All districts</option>
        @foreach($districts as $d)
          <option value="{{ $d->id }}">{{ $d->name }}</option>
        @endforeach
      </select>

      <select wire:model.live="groupId"
              class="border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10">
        <option value="">All groups</option>
        @foreach($groups as $g)
          <option value="{{ $g->id }}">{{ $g->name }}</option>
        @endforeach
      </select>

      {{-- Optional guarantor filter --}}
      @isset($guarantors)
        <select wire:model.live="guarantorId"
                class="border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10">
          <option value="">All guarantors</option>
          @foreach($guarantors as $g)
            <option value="{{ $g->id }}">{{ $g->name }}</option>
          @endforeach
        </select>
      @endisset
    </div>

    <div class="flex flex-wrap items-center gap-2 text-xs text-slate-600">
      <span class="inline-flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-emerald-500"></span> for us</span>
      <span class="inline-flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-amber-500"></span> indicative</span>
      <span class="inline-flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-red-500"></span> against</span>

      <span class="text-slate-300 mx-2">•</span>

      <label class="text-xs text-slate-600">Rows</label>
      <select wire:model.live="perPage" class="border rounded-md px-2 py-1 text-xs">
        <option value="25">25</option>
        <option value="50">50</option>
        <option value="100">100</option>
        <option value="500">500</option>
        <option value="0">All</option>
      </select>

      <div class="ml-auto flex items-center gap-2">
        <button wire:click="toggleSelectPage"
                class="inline-flex items-center px-3 py-1.5 text-xs font-semibold rounded bg-gray-100 hover:bg-gray-200">
          {{ $selectPage ? 'Unselect Page' : 'Select Page' }}
        </button>

        @if(count($selected))
          <span class="text-xs text-slate-600">
            Selected: <span class="font-semibold">{{ count($selected) }}</span>
          </span>
        @endif
      </div>
    </div>
  </div>

  {{-- A–Z --}}
  <div class="bg-white border rounded-lg shadow-sm p-3">
    @php
      $letters = array_merge(['ALL'], range('A','Z'));
      $current = $az ?: 'ALL';
    @endphp

    <div class="flex flex-wrap gap-1">
      @foreach($letters as $L)
        @php $active = strtoupper($current) === $L; @endphp

        <button
          wire:click="$set('az', '{{ $L === 'ALL' ? '' : $L }}')"
          class="px-2 py-1 text-xs rounded border {{ $active ? 'bg-slate-900 text-white border-slate-900' : 'bg-white hover:bg-slate-50 border-slate-200' }}">
          {{ $L }}
        </button>
      @endforeach
    </div>

    <div class="text-[11px] text-slate-500 mt-2">
      A–Z filter applies only when search is empty.
    </div>
  </div>

  {{-- Bulk Bar --}}
  @if(count($selected))
    <div class="bg-white border rounded-lg shadow-sm p-4 flex flex-wrap items-center gap-3">
      <div class="text-sm font-semibold text-slate-700">Bulk Update</div>

      <select wire:model.live="bulkStance" class="border rounded-md px-3 py-2 text-sm">
        <option value="for">🟢 For us</option>
        <option value="indicative">🟡 Indicative</option>
        <option value="against">🔴 Against</option>
      </select>

      <div class="flex items-center gap-2">
        <span class="text-xs text-slate-500">Confidence</span>
        <input type="number" min="0" max="100" wire:model.live="bulkConfidence"
               class="w-24 border rounded-md px-3 py-2 text-sm text-right">
      </div>

      <button wire:click="applyBulk"
              class="inline-flex items-center px-4 py-2 text-sm font-semibold rounded-md bg-slate-900 text-white hover:bg-slate-800">
        Apply to Selected
      </button>

      <button wire:click="clearSelection"
              class="inline-flex items-center px-4 py-2 text-sm font-semibold rounded-md bg-gray-100 hover:bg-gray-200">
        Clear
      </button>
    </div>
  @endif

  {{-- Table --}}
  <div class="bg-white border rounded-lg shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-slate-600">
          <tr>
            <th class="px-3 py-2 text-left w-10">
              <input type="checkbox" class="rounded border-gray-300"
                     wire:click="toggleSelectPage"
                     @checked($selectPage) />
            </th>
            <th class="px-3 py-2 text-left">Delegate</th>
            <th class="px-3 py-2 text-left">Phones</th>
            <th class="px-3 py-2 text-left">Guarantor</th>
            <th class="px-3 py-2 text-left">Location</th>
            <th class="px-3 py-2 text-left">Groups</th>
            <th class="px-3 py-2 text-center">Status</th>
            <th class="px-3 py-2 text-left">Last updated</th>
            <th class="px-3 py-2 text-right">Actions</th>
          </tr>
        </thead>

        <tbody>
          @forelse($delegates as $d)
            @php
              $s = $statusMap[$d->id] ?? null;

              $stance = $s?->stance ?? 'indicative';
              $conf = (int) ($s?->confidence ?? 50);
              $pending = (bool) ($s?->pending_stance);

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

              $phone1 = data_get($d, 'phone_primary') ?? null;
              $phone2 = data_get($d, 'phone_secondary') ?? null;

              $lastUpdated = $s?->updated_at ? $s->updated_at->diffForHumans() : '—';
            @endphp

            <tr class="border-t">
              <td class="px-3 py-2">
                <input type="checkbox" class="rounded border-gray-300"
                       wire:model.live="selected"
                       value="{{ $d->id }}">
              </td>

              <td class="px-3 py-2">
                <div class="font-medium">{{ $d->full_name }}</div>
                <div class="text-xs text-slate-500">{{ $d->category ?: '—' }}</div>
              </td>

              <td class="px-3 py-2 text-xs text-slate-700">
                <div>{{ $phone1 ?: '—' }}</div>
                <div class="text-slate-500">{{ $phone2 ?: '—' }}</div>
              </td>

              <td class="px-3 py-2 text-xs text-slate-700">
                {{ $d->guarantor?->name ?? 'No guarantor' }}
              </td>

              <td class="px-3 py-2 text-xs text-slate-600">
                <div>{{ $d->district?->region?->name ?? '—' }}</div>
                <div>{{ $d->district?->name ?? '—' }}</div>
              </td>

              <td class="px-3 py-2 text-xs text-slate-600">
                {{ $d->groups?->pluck('name')->implode(', ') ?: '—' }}
              </td>

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

              <td class="px-3 py-2 text-xs text-slate-600">
                {{ $lastUpdated }}
              </td>

              <td class="px-3 py-2 text-right">
                <div class="inline-flex items-center gap-1">
                  {{-- IMPORTANT: pass CURRENT confidence so clicking stance does NOT force 70/50 --}}
                  <button wire:click="setStance({{ $d->id }}, 'for', {{ $conf }})"
                          class="px-2 py-1 text-xs font-semibold rounded bg-emerald-50 border border-emerald-200 text-emerald-800 hover:bg-emerald-100">
                    🟢
                  </button>

                  <button wire:click="setStance({{ $d->id }}, 'indicative', {{ $conf }})"
                          class="px-2 py-1 text-xs font-semibold rounded bg-amber-50 border border-amber-200 text-amber-900 hover:bg-amber-100">
                    🟡
                  </button>

                  <button wire:click="setStance({{ $d->id }}, 'against', {{ $conf }})"
                          class="px-2 py-1 text-xs font-semibold rounded bg-red-50 border border-red-200 text-red-800 hover:bg-red-100">
                    🔴
                  </button>

                  <div class="ml-2 inline-flex items-center gap-2">
                    <span class="text-[11px] text-slate-500">Conf</span>
                    <input type="number" min="0" max="100"
                           value="{{ $conf }}"
                           wire:change="updateConfidence({{ $d->id }}, $event.target.value)"
                           class="w-20 border rounded-md px-2 py-1 text-xs text-right focus:outline-none focus:ring-2 focus:ring-slate-900/10" />
                  </div>

                  <button wire:click="openDrawer({{ $d->id }})"
                          class="ml-2 px-3 py-1 text-xs font-semibold rounded bg-gray-100 hover:bg-gray-200">
                    Details
                  </button>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="9" class="px-3 py-8 text-center text-slate-500">No delegates found.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="px-4 py-3 border-t">
      {{-- When perPage=0 (All), your component should return a paginator-like object or skip links.
           If you handle All with ->paginate(PHP_INT_MAX), links will still render but can be huge.
           Best: if All, use ->get() and hide links in the component. --}}
      @if(method_exists($delegates, 'links'))
        {{ $delegates->links() }}
      @endif
    </div>
  </div>

  {{-- Drawer --}}
  @if($drawerDelegateId)
    <div class="fixed inset-0 z-50">
      <div class="absolute inset-0 bg-black/30" wire:click="closeDrawer"></div>

      <div class="absolute right-0 top-0 h-full w-full max-w-xl bg-white shadow-xl border-l">
        <div class="h-16 px-4 border-b flex items-center justify-between">
          <div class="font-semibold">Delegate Details</div>
          <button class="px-3 py-2 text-sm bg-gray-100 rounded hover:bg-gray-200" wire:click="closeDrawer">Close</button>
        </div>

        <div class="p-4 overflow-y-auto h-[calc(100%-64px)]">
          @livewire('board.delegate-drawer', ['delegateId' => $drawerDelegateId, 'candidateId' => $candidateId], key('drawer-'.$drawerDelegateId.'-'.$candidateId))
        </div>
      </div>
    </div>
  @endif
</div>
