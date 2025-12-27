<div class="p-6 space-y-6">
  @section('title', 'Status History — '.config('app.name'))

  <div class="flex items-center justify-between gap-4">
    <div>
      <h1 class="text-2xl font-semibold">Status History</h1>
      <p class="text-sm text-slate-600">Audit trail of interactions and status updates.</p>
    </div>

    <div class="flex items-center gap-2">
      <a href="{{ route('reports.status-history.export.csv', request()->query()) }}"
         class="inline-flex items-center px-4 py-2 text-sm font-semibold rounded-md bg-gray-100 hover:bg-gray-200">
        Export CSV
      </a>
      <a href="{{ route('reports.status-history.export.pdf', request()->query()) }}"
         class="inline-flex items-center px-4 py-2 text-sm font-semibold rounded-md bg-slate-900 text-white hover:bg-slate-800">
        Export PDF
      </a>
    </div>
  </div>

  <div class="bg-white border rounded-lg shadow-sm p-4 space-y-3">
    <div class="grid md:grid-cols-6 gap-2">
      <input wire:model.live.debounce.300ms="q" placeholder="Search delegate..."
             class="border rounded-md px-3 py-2 text-sm" />

      <select wire:model.live="candidateId" class="border rounded-md px-3 py-2 text-sm">
        <option value="">All candidates</option>
        @foreach($candidates as $c)
          <option value="{{ $c->id }}">{{ $c->name }}</option>
        @endforeach
      </select>

      <select wire:model.live="type" class="border rounded-md px-3 py-2 text-sm">
        <option value="">All types</option>
        @foreach($types as $t)
          <option value="{{ $t }}">{{ $t }}</option>
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

    <div class="grid md:grid-cols-4 gap-2">
      <div>
        <label class="block text-xs font-medium text-slate-600">From</label>
        <input type="date" wire:model.live="dateFrom" class="mt-1 w-full border rounded-md px-3 py-2 text-sm" />
      </div>
      <div>
        <label class="block text-xs font-medium text-slate-600">To</label>
        <input type="date" wire:model.live="dateTo" class="mt-1 w-full border rounded-md px-3 py-2 text-sm" />
      </div>
    </div>
  </div>

  <div class="bg-white border rounded-lg shadow-sm overflow-hidden">
    <table class="min-w-full text-sm">
      <thead class="bg-slate-50 text-slate-600">
        <tr>
          <th class="px-3 py-2 text-left">Time</th>
          <th class="px-3 py-2 text-left">Delegate</th>
          <th class="px-3 py-2 text-left">Location</th>
          <th class="px-3 py-2 text-left">Candidate</th>
          <th class="px-3 py-2 text-left">Type</th>
          <th class="px-3 py-2 text-left">Notes</th>
          <th class="px-3 py-2 text-left">User</th>
        </tr>
      </thead>
      <tbody>
        @forelse($events as $e)
          <tr class="border-t">
            <td class="px-3 py-2 text-xs text-slate-500">
              {{ $e->created_at?->diffForHumans() }}
              <div class="text-[11px]">{{ $e->created_at?->toDateTimeString() }}</div>
            </td>

            <td class="px-3 py-2">
              <div class="font-medium">{{ $e->delegate?->full_name }}</div>
              <div class="text-xs text-slate-500">{{ $e->delegate?->category ?: '—' }}</div>
              <div class="text-[11px] text-slate-500">{{ $e->delegate?->groups?->pluck('name')->implode(', ') ?: '—' }}</div>
            </td>

            <td class="px-3 py-2 text-xs text-slate-600">
              <div>{{ $e->delegate?->district?->region?->name ?? '—' }}</div>
              <div>{{ $e->delegate?->district?->name ?? '—' }}</div>
            </td>

            <td class="px-3 py-2">{{ $e->candidate?->name ?? '—' }}</td>
            <td class="px-3 py-2 font-mono text-xs">{{ $e->type ?? '—' }}</td>
            <td class="px-3 py-2 text-xs text-slate-700 whitespace-pre-wrap">{{ $e->notes }}</td>
            <td class="px-3 py-2">{{ $e->user?->name ?? 'Unknown' }}</td>
          </tr>
        @empty
          <tr><td colspan="7" class="px-3 py-8 text-center text-slate-500">No history found.</td></tr>
        @endforelse
      </tbody>
    </table>

    <div class="px-4 py-3 border-t">
      {{ $events->links() }}
    </div>
  </div>
</div>
