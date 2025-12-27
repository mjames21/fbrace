<div class="p-6 space-y-6">
  @section('title', 'Approvals — '.config('app.name'))

  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-2xl font-semibold">Approvals</h1>
      <p class="text-sm text-slate-600">Review pending delegate status changes.</p>
    </div>

    <select wire:model.live="candidateId" class="border rounded-md px-3 py-2 text-sm">
      @foreach($candidates as $c)
        <option value="{{ $c->id }}">{{ $c->name }}</option>
      @endforeach
    </select>
  </div>

  <div class="bg-white border rounded-lg shadow-sm overflow-hidden">
    <table class="min-w-full text-sm">
      <thead class="bg-slate-50 text-slate-600">
        <tr>
          <th class="px-3 py-2 text-left">Delegate</th>
          <th class="px-3 py-2 text-left">Location</th>
          <th class="px-3 py-2 text-left">Candidate</th>
          <th class="px-3 py-2 text-left">Pending</th>
          <th class="px-3 py-2 text-right">Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse($pending as $p)
          <tr class="border-t">
            <td class="px-3 py-2">
              <div class="font-medium">{{ $p->delegate?->full_name }}</div>
            </td>
            <td class="px-3 py-2 text-xs text-slate-600">
              <div>{{ $p->delegate?->district?->region?->name ?? '—' }}</div>
              <div>{{ $p->delegate?->district?->name ?? '—' }}</div>
            </td>
            <td class="px-3 py-2">{{ $p->candidate?->name }}</td>
            <td class="px-3 py-2">
              <span class="font-mono text-xs">{{ $p->pending_stance }}</span>
              <span class="text-xs text-slate-500">({{ $p->pending_confidence ?? '—' }})</span>
            </td>
            <td class="px-3 py-2 text-right">
              <button wire:click="approve({{ $p->id }})"
                      class="px-3 py-1.5 text-xs font-semibold rounded bg-slate-900 text-white hover:bg-slate-800">
                Approve
              </button>
              <button wire:click="reject({{ $p->id }})"
                      class="px-3 py-1.5 text-xs font-semibold rounded bg-gray-100 hover:bg-gray-200">
                Reject
              </button>
            </td>
          </tr>
        @empty
          <tr><td colspan="5" class="px-3 py-8 text-center text-slate-500">No pending approvals.</td></tr>
        @endforelse
      </tbody>
    </table>

    <div class="px-4 py-3 border-t">
      {{ $pending->links() }}
    </div>
  </div>
</div>
