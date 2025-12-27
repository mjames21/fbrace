<div class="p-6 space-y-8">
  @section('title', 'Candidates — '.config('app.name'))

  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-2xl font-semibold">Candidates</h1>
      <p class="text-sm text-slate-600">Add and manage candidates. Active candidates appear in Board + Horse Race.</p>
    </div>

    <button wire:click="createNew"
            class="inline-flex items-center px-4 py-2 text-sm font-semibold rounded-md bg-slate-900 text-white hover:bg-slate-800">
      New Candidate
    </button>
  </div>

  <div class="grid lg:grid-cols-3 gap-6">
    {{-- List --}}
    <div class="lg:col-span-2 bg-white border rounded-lg shadow-sm overflow-hidden">
      <div class="px-4 py-3 border-b flex items-center justify-between">
        <h2 class="text-sm font-semibold text-slate-700">Candidate List</h2>
        <span class="text-xs text-slate-500">{{ $candidates->count() }} records</span>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="bg-slate-50 text-slate-600">
            <tr>
              <th class="px-3 py-2 text-left">Name</th>
              <th class="px-3 py-2 text-left">Sort</th>
              <th class="px-3 py-2 text-left">Active</th>
              <th class="px-3 py-2 text-right">Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($candidates as $c)
              <tr class="border-t">
                <td class="px-3 py-2">
                  <div class="font-medium">{{ $c->name }}</div>
                </td>
                <td class="px-3 py-2 font-mono text-xs">{{ (int)($c->sort_order ?? 0) }}</td>
                <td class="px-3 py-2">
                  @if($c->is_active)
                    <span class="text-[11px] px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">Active</span>
                  @else
                    <span class="text-[11px] px-2 py-0.5 rounded-full bg-gray-50 text-gray-700 border border-gray-200">Inactive</span>
                  @endif
                </td>
                <td class="px-3 py-2 text-right">
                  <button wire:click="edit({{ $c->id }})"
                          class="text-xs font-semibold text-slate-700 hover:underline">
                    Edit
                  </button>
                  <span class="text-slate-300 mx-2">|</span>
                  <button wire:click="toggleActive({{ $c->id }})"
                          class="text-xs font-semibold text-slate-700 hover:underline">
                    Toggle
                  </button>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="4" class="px-3 py-6 text-center text-slate-500">No candidates yet.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    {{-- Form --}}
    <div class="bg-white border rounded-lg shadow-sm p-4 space-y-4">
      <div class="flex items-center justify-between">
        <h2 class="text-sm font-semibold text-slate-700">{{ $editingId ? 'Edit Candidate' : 'New Candidate' }}</h2>
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

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-medium text-slate-600">Sort order</label>
            <input type="number" min="0" wire:model.live="sort_order"
                   class="mt-1 w-full border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10">
            @error('sort_order') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
          </div>

          <div class="flex items-end">
            <label class="inline-flex items-center gap-2">
              <input type="checkbox" wire:model.live="is_active" class="rounded border-gray-300">
              <span class="text-sm text-slate-700">Active</span>
            </label>
            @error('is_active') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
          </div>
        </div>

        <div class="pt-2">
          <button wire:click="save"
                  class="inline-flex items-center px-4 py-2 text-sm font-semibold rounded-md bg-slate-900 text-white hover:bg-slate-800">
            Save Candidate
          </button>
        </div>
      </div>
    </div>
  </div>
</div>
