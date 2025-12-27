<div class="p-6 space-y-8">
  @section('title', 'Groups — '.config('app.name'))

  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-2xl font-semibold">Groups</h1>
      <p class="text-sm text-slate-600">Delegate groupings (e.g. District Executives, MPs, Councilors, Constituencies).</p>
    </div>

    <button wire:click="createNew"
            class="inline-flex items-center px-4 py-2 text-sm font-semibold rounded-md bg-slate-900 text-white hover:bg-slate-800">
      New Group
    </button>
  </div>

  <div class="grid lg:grid-cols-3 gap-6">
    {{-- List --}}
    <div class="lg:col-span-2 bg-white border rounded-lg shadow-sm overflow-hidden">
      <div class="px-4 py-3 border-b flex items-center justify-between">
        <h2 class="text-sm font-semibold text-slate-700">Group List</h2>
        <span class="text-xs text-slate-500">{{ $groups->count() }} records</span>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="bg-slate-50 text-slate-600">
            <tr>
              <th class="px-3 py-2 text-left">Name</th>
              <th class="px-3 py-2 text-left">Code</th>
              <th class="px-3 py-2 text-right">Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($groups as $g)
              <tr class="border-t">
                <td class="px-3 py-2 font-medium">{{ $g->name }}</td>
                <td class="px-3 py-2 font-mono text-xs">{{ $g->code ?: '—' }}</td>
                <td class="px-3 py-2 text-right">
                  <button wire:click="edit({{ $g->id }})"
                          class="text-xs font-semibold text-slate-700 hover:underline">
                    Edit
                  </button>
                </td>
              </tr>
            @empty
              <tr><td colspan="3" class="px-3 py-8 text-center text-slate-500">No groups yet.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    {{-- Form --}}
    <div class="bg-white border rounded-lg shadow-sm p-4 space-y-4">
      <div class="flex items-center justify-between">
        <h2 class="text-sm font-semibold text-slate-700">{{ $editingId ? 'Edit Group' : 'New Group' }}</h2>
        @if (session('status'))
          <span class="text-[11px] text-emerald-600">{{ session('status') }}</span>
        @endif
      </div>

      <div class="space-y-3 text-sm">
        <div>
          <label class="block text-xs font-medium text-slate-600">Name</label>
          <input type="text" wire:model.live="name"
                 class="mt-1 w-full border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10">
          @error('name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
          <label class="block text-xs font-medium text-slate-600">Code (optional)</label>
          <input type="text" wire:model.live="code"
                 class="mt-1 w-full border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10">
          @error('code') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="pt-2">
          <button wire:click="save"
                  class="inline-flex items-center px-4 py-2 text-sm font-semibold rounded-md bg-slate-900 text-white hover:bg-slate-800">
            Save Group
          </button>
        </div>
      </div>
    </div>
  </div>
</div>
