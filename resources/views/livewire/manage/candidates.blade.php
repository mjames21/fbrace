<div class="p-6 space-y-8">
  @section('title', 'Candidates — '.config('app.name'))

  {{-- Header --}}
  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-2xl font-semibold">Candidates</h1>
      <p class="text-sm text-slate-600">
        Manage candidates and set the <span class="font-semibold">Principal</span> candidate (default on Delegate Board).
      </p>
    </div>

    <button wire:click="createNew"
            class="inline-flex items-center px-4 py-2 text-sm font-semibold rounded-md bg-slate-900 text-white hover:bg-slate-800">
      New Candidate
    </button>
  </div>

  <div class="grid lg:grid-cols-3 gap-6">
    {{-- Left: list --}}
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
              <th class="px-3 py-2 text-left">Status</th>
              <th class="px-3 py-2 text-left">Principal</th>
              <th class="px-3 py-2 text-right">Actions</th>
            </tr>
          </thead>

          <tbody>
            @forelse($candidates as $c)
              <tr class="border-t">
                <td class="px-3 py-2">
                  <div class="font-medium">{{ $c->name }}</div>
                  <div class="text-[11px] text-slate-500 font-mono">{{ $c->slug }}</div>
                </td>

                <td class="px-3 py-2">
                  @if($c->is_active)
                    <span class="text-[11px] px-2 py-0.5 rounded-full border border-emerald-200 bg-emerald-50 text-emerald-700">Active</span>
                  @else
                    <span class="text-[11px] px-2 py-0.5 rounded-full border border-slate-200 bg-slate-50 text-slate-600">Inactive</span>
                  @endif
                </td>

                <td class="px-3 py-2">
                  @if($c->is_principal)
                    <span class="text-[11px] px-2 py-0.5 rounded-full border border-indigo-200 bg-indigo-50 text-indigo-700">
                      Principal
                    </span>
                  @else
                    <button wire:click="setPrincipal({{ $c->id }})"
                            class="text-xs font-semibold px-2 py-1 rounded bg-gray-100 hover:bg-gray-200">
                      Set Principal
                    </button>
                  @endif
                </td>

                <td class="px-3 py-2 text-right">
                  <div class="inline-flex items-center gap-2">
                    <button wire:click="toggleActive({{ $c->id }})"
                            class="text-xs font-semibold px-2 py-1 rounded bg-gray-100 hover:bg-gray-200">
                      {{ $c->is_active ? 'Deactivate' : 'Activate' }}
                    </button>

                    <button wire:click="edit({{ $c->id }})"
                            class="text-xs font-semibold text-slate-700 hover:underline">
                      Edit
                    </button>

                    <button wire:click="delete({{ $c->id }})"
                            class="text-xs font-semibold text-red-600 hover:underline"
                            onclick="return confirm('Delete this candidate?')">
                      Delete
                    </button>
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="4" class="px-3 py-8 text-center text-slate-500">
                  No candidates yet.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    {{-- Right: form --}}
    <div class="bg-white border rounded-lg shadow-sm p-4 space-y-4">
      <div class="flex items-center justify-between">
        <h2 class="text-sm font-semibold text-slate-700">
          {{ $editingId ? 'Edit Candidate' : 'New Candidate' }}
        </h2>
        @if(session('status'))
          <span class="text-[11px] text-emerald-600">{{ session('status') }}</span>
        @endif
      </div>

      <div class="space-y-3 text-sm">
        <div>
          <label class="block text-xs font-medium text-slate-600">Name</label>
          <input type="text" wire:model.defer="name"
                 class="mt-1 w-full border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10" />
          @error('name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
          <label class="block text-xs font-medium text-slate-600">Slug (optional)</label>
          <input type="text" wire:model.defer="slug"
                 class="mt-1 w-full border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10"
                 placeholder="auto if empty" />
          @error('slug') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-medium text-slate-600">Sort order</label>
            <input type="number" wire:model.defer="sortOrder"
                   class="mt-1 w-full border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10" />
            @error('sortOrder') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
          </div>

          <div class="flex items-end">
            <label class="inline-flex items-center gap-2 text-sm">
              <input type="checkbox" wire:model.defer="isActive" class="rounded border-gray-300">
              <span class="text-slate-700">Active</span>
            </label>
          </div>
        </div>

        <div>
          <label class="inline-flex items-center gap-2 text-sm">
            <input type="checkbox" wire:model.defer="isPrincipal" class="rounded border-gray-300">
            <span class="text-slate-700">Set as Principal (only one allowed)</span>
          </label>
        </div>

        <div>
          <label class="block text-xs font-medium text-slate-600">Notes (optional)</label>
          <textarea wire:model.defer="notes" rows="3"
                    class="mt-1 w-full border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10"></textarea>
          @error('notes') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="pt-2 flex items-center gap-2">
          <button wire:click="save"
                  class="inline-flex items-center px-4 py-2 text-sm font-semibold rounded-md bg-slate-900 text-white hover:bg-slate-800">
            Save Candidate
          </button>

          <button wire:click="createNew"
                  class="inline-flex items-center px-4 py-2 text-sm font-semibold rounded-md bg-gray-100 hover:bg-gray-200">
            Clear
          </button>
        </div>

        <p class="text-[11px] text-slate-500">
          If you check Principal, the system will automatically unset Principal on all other candidates.
        </p>
      </div>
    </div>
  </div>
</div>
