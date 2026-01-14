<div class="p-6 space-y-8">
  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-2xl font-semibold">Guarantors</h1>
      <p class="text-sm text-slate-600">People who can influence and manage delegates under them.</p>
    </div>

    <button wire:click="createNew"
            class="inline-flex items-center px-4 py-2 text-sm font-semibold rounded-md bg-slate-900 text-white hover:bg-slate-800">
      New Guarantor
    </button>
  </div>

  <div class="grid lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 bg-white border rounded-lg shadow-sm">
      <div class="px-4 py-3 border-b flex items-center justify-between">
        <h2 class="text-sm font-semibold text-slate-700">Guarantor List</h2>
        <span class="text-xs text-slate-500">{{ count($guarantors) }} records</span>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="bg-slate-50 text-slate-600">
          <tr>
            <th class="px-3 py-2 text-left">Name</th>
            <th class="px-3 py-2 text-left">Phone</th>
            <th class="px-3 py-2 text-left">Active</th>
            <th class="px-3 py-2 text-right">Actions</th>
          </tr>
          </thead>
          <tbody>
          @forelse($guarantors as $g)
            <tr class="border-t">
              <td class="px-3 py-2">
                <div class="font-medium">{{ $g->name }}</div>
                <a class="text-xs text-slate-500 hover:underline" href="{{ route('manage.guarantors.show', $g->id) }}">
                  Open assignments →
                </a>
              </td>
              <td class="px-3 py-2 text-xs">
                <div>{{ $g->phone_primary ?: '—' }}</div>
                <div class="text-slate-500">{{ $g->phone_secondary ?: '' }}</div>
              </td>
              <td class="px-3 py-2">
                @if($g->is_active)
                  <span class="text-xs px-2 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">Active</span>
                @else
                  <span class="text-xs px-2 py-1 rounded-full bg-gray-50 text-gray-600 border border-gray-200">Inactive</span>
                @endif
              </td>
              <td class="px-3 py-2 text-right text-xs">
                <button wire:click="edit({{ $g->id }})" class="font-semibold text-slate-700 hover:underline">Edit</button>
                <span class="text-slate-300 px-1">|</span>
                <button wire:click="toggleActive({{ $g->id }})" class="font-semibold text-slate-700 hover:underline">Toggle</button>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="4" class="px-3 py-4 text-center text-slate-500">No guarantors yet.</td>
            </tr>
          @endforelse
          </tbody>
        </table>
      </div>
    </div>

    <div class="space-y-6">
      <div class="bg-white border rounded-lg shadow-sm p-4 space-y-4">
        <div class="flex items-center justify-between">
          <h2 class="text-sm font-semibold text-slate-700">{{ $editingId ? 'Edit Guarantor' : 'New Guarantor' }}</h2>
          @if (session('status'))
            <span class="text-[11px] text-emerald-600">{{ session('status') }}</span>
          @endif
        </div>

        <div class="space-y-3 text-sm">
          <div>
            <label class="block text-xs font-medium text-slate-600">Name</label>
            <input wire:model.defer="name" class="mt-1 w-full border rounded-md px-3 py-2" />
            @error('name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-medium text-slate-600">Phone 1</label>
              <input wire:model.defer="phonePrimary" class="mt-1 w-full border rounded-md px-3 py-2" />
            </div>
            <div>
              <label class="block text-xs font-medium text-slate-600">Phone 2</label>
              <input wire:model.defer="phoneSecondary" class="mt-1 w-full border rounded-md px-3 py-2" />
            </div>
          </div>

          <div>
            <label class="block text-xs font-medium text-slate-600">District (optional)</label>
            <select wire:model.defer="districtId" class="mt-1 w-full border rounded-md px-3 py-2">
              <option value="">—</option>
              @foreach($districts as $d)
                <option value="{{ $d->id }}">{{ $d->name }}</option>
              @endforeach
            </select>
          </div>

          <div class="grid grid-cols-2 gap-3 items-center">
            <div>
              <label class="block text-xs font-medium text-slate-600">Sort order</label>
              <input type="number" wire:model.defer="sortOrder" class="mt-1 w-full border rounded-md px-3 py-2" />
            </div>
            <label class="inline-flex items-center gap-2 mt-6 text-sm">
              <input type="checkbox" wire:model.defer="isActive" class="rounded border-gray-300" />
              Active
            </label>
          </div>

          <div>
            <label class="block text-xs font-medium text-slate-600">Notes</label>
            <textarea wire:model.defer="notes" rows="3" class="mt-1 w-full border rounded-md px-3 py-2"></textarea>
          </div>

          <button wire:click="save"
                  class="inline-flex items-center px-4 py-2 text-sm font-semibold rounded-md bg-slate-900 text-white hover:bg-slate-800">
            Save Guarantor
          </button>
        </div>
      </div>
    </div>
  </div>
</div>
