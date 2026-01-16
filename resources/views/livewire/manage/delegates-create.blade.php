<div class="p-6 space-y-6">
  @section('title', 'Add Delegate — '.config('app.name'))

  <div class="flex items-start justify-between gap-4">
    <div>
      <h1 class="text-2xl font-semibold">Add Delegate</h1>
      <p class="text-sm text-slate-600">Create a delegate record and assign district + category.</p>
    </div>

    <a href="{{ route('manage.delegates') }}"
       class="inline-flex items-center px-4 py-2 text-sm font-semibold rounded-md bg-gray-100 hover:bg-gray-200">
      Back to Delegates
    </a>
  </div>

  <div class="bg-white border rounded-lg shadow-sm p-5 space-y-5 max-w-3xl">
    <div class="grid md:grid-cols-2 gap-4">
      <div class="md:col-span-2">
        <label class="block text-xs font-medium text-slate-600">Full Name</label>
        <input wire:model.defer="fullName"
               class="mt-1 w-full border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10"
               placeholder="e.g. Hon. John Doe" />
        @error('fullName') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
      </div>

      <div>
        <label class="block text-xs font-medium text-slate-600">District</label>
        <select wire:model.defer="districtId"
                class="mt-1 w-full border rounded-md px-3 py-2 text-sm">
          <option value="">Select district</option>
          @foreach($districts as $d)
            <option value="{{ $d->id }}">{{ $d->name }}</option>
          @endforeach
        </select>
        @error('districtId') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
      </div>

      <div>
        <label class="block text-xs font-medium text-slate-600">Category</label>
        <select wire:model.defer="categoryId"
                class="mt-1 w-full border rounded-md px-3 py-2 text-sm">
          <option value="">No category</option>
          @foreach($categories as $c)
            <option value="{{ $c->id }}">{{ $c->name }}</option>
          @endforeach
        </select>
        @error('categoryId') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        <div class="text-[11px] text-slate-500 mt-1">
          Manage categories in <a class="underline" href="{{ route('manage.categories') }}">Categories</a>.
        </div>
      </div>

      <div>
        <label class="block text-xs font-medium text-slate-600">Phone 1</label>
        <input wire:model.defer="phonePrimary"
               class="mt-1 w-full border rounded-md px-3 py-2 text-sm"
               placeholder="optional" />
        @error('phonePrimary') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
      </div>

      <div>
        <label class="block text-xs font-medium text-slate-600">Phone 2</label>
        <input wire:model.defer="phoneSecondary"
               class="mt-1 w-full border rounded-md px-3 py-2 text-sm"
               placeholder="optional" />
        @error('phoneSecondary') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
      </div>

      <div>
        <label class="block text-xs font-medium text-slate-600">Guarantor</label>
        <select wire:model.defer="guarantorId"
                class="mt-1 w-full border rounded-md px-3 py-2 text-sm">
          <option value="">No guarantor</option>
          @foreach($guarantors as $g)
            <option value="{{ $g->id }}">{{ $g->name }}</option>
          @endforeach
        </select>
        @error('guarantorId') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
      </div>

      <div>
        <label class="block text-xs font-medium text-slate-600">Group (single)</label>
        <select wire:model.defer="groupId"
                class="mt-1 w-full border rounded-md px-3 py-2 text-sm">
          <option value="">No group</option>
          @foreach($groups as $g)
            <option value="{{ $g->id }}">{{ $g->name }}</option>
          @endforeach
        </select>
        @error('groupId') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
      </div>

      <div class="md:col-span-2">
        <label class="block text-xs font-medium text-slate-600">Notes</label>
        <textarea wire:model.defer="notes" rows="3"
                  class="mt-1 w-full border rounded-md px-3 py-2 text-sm"
                  placeholder="optional"></textarea>
        @error('notes') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
      </div>

      <div class="flex items-center gap-4 md:col-span-2">
        <label class="inline-flex items-center gap-2 text-sm">
          <input type="checkbox" class="rounded border-gray-300" wire:model.defer="isActive">
          <span>Active</span>
        </label>

        <label class="inline-flex items-center gap-2 text-sm">
          <input type="checkbox" class="rounded border-gray-300" wire:model.defer="isHighValue">
          <span>High value</span>
        </label>
      </div>
    </div>

    <div class="pt-2">
      <button wire:click="save"
              class="inline-flex items-center px-5 py-2.5 text-sm font-semibold rounded-md bg-slate-900 text-white hover:bg-slate-800">
        Save Delegate
      </button>
    </div>
  </div>
</div>
