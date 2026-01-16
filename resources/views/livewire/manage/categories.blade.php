<div class="p-6 space-y-8">
  @section('title', 'Categories — '.config('app.name'))

  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-2xl font-semibold">Categories</h1>
      <p class="text-sm text-slate-600">Create and manage delegate categories used across the platform.</p>
    </div>

    <button wire:click="createNew"
            class="inline-flex items-center px-4 py-2 text-sm font-semibold rounded-md bg-slate-900 text-white hover:bg-slate-800">
      New Category
    </button>
  </div>

  <div class="grid lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 bg-white border rounded-lg shadow-sm overflow-hidden">
      <div class="px-4 py-3 border-b flex items-center justify-between">
        <h2 class="text-sm font-semibold text-slate-700">Category List</h2>
        <span class="text-xs text-slate-500">{{ count($rows) }} records</span>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="bg-slate-50 text-slate-600">
            <tr>
              <th class="px-3 py-2 text-left">Name</th>
              <th class="px-3 py-2 text-left">Status</th>
              <th class="px-3 py-2 text-left">Order</th>
              <th class="px-3 py-2 text-right">Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($rows as $r)
              <tr class="border-t">
                <td class="px-3 py-2 font-medium">{{ $r->name }}</td>
                <td class="px-3 py-2 text-xs">
                  @if($r->is_active)
                    <span class="px-2 py-0.5 rounded-full border border-emerald-200 bg-emerald-50 text-emerald-800">Active</span>
                  @else
                    <span class="px-2 py-0.5 rounded-full border border-slate-200 bg-slate-50 text-slate-700">Inactive</span>
                  @endif
                </td>
                <td class="px-3 py-2 font-mono text-xs">{{ $r->sort_order }}</td>
                <td class="px-3 py-2 text-right">
                  <button wire:click="edit({{ $r->id }})" class="text-xs font-semibold text-slate-700 hover:underline">Edit</button>
                  <span class="text-slate-300 mx-2">|</span>
                  <button wire:click="toggleActive({{ $r->id }})" class="text-xs font-semibold text-slate-700 hover:underline">
                    {{ $r->is_active ? 'Disable' : 'Enable' }}
                  </button>
                </td>
              </tr>
            @empty
              <tr><td colspan="4" class="px-3 py-8 text-center text-slate-500">No categories yet.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    <div class="bg-white border rounded-lg shadow-sm p-4 space-y-4">
      <div class="flex items-center justify-between">
        <h2 class="text-sm font-semibold text-slate-700">{{ $editingId ? 'Edit Category' : 'New Category' }}</h2>
      </div>

      <div class="space-y-3 text-sm">
        <div>
          <label class="block text-xs font-medium text-slate-600">Name</label>
          <input type="text" wire:model.defer="name"
                 class="mt-1 w-full border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10">
          @error('name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-medium text-slate-600">Active</label>
            <select wire:model.defer="isActive" class="mt-1 w-full border rounded-md px-3 py-2 text-sm">
              <option value="1">Yes</option>
              <option value="0">No</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-medium text-slate-600">Sort Order</label>
            <input type="number" min="0" wire:model.defer="sortOrder"
                   class="mt-1 w-full border rounded-md px-3 py-2 text-sm">
          </div>
        </div>

        <div>
          <label class="block text-xs font-medium text-slate-600">Notes</label>
          <textarea rows="3" wire:model.defer="notes"
                    class="mt-1 w-full border rounded-md px-3 py-2 text-sm"></textarea>
        </div>

        <div class="pt-2">
          <button wire:click="save"
                  class="inline-flex items-center px-4 py-2 text-sm font-semibold rounded-md bg-slate-900 text-white hover:bg-slate-800">
            Save Category
          </button>
        </div>
      </div>
    </div>
  </div>
</div>
