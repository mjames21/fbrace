{{-- resources/views/livewire/manage/delegates.blade.php --}}
<div class="p-6 space-y-8">
  @section('title', 'Delegates — '.config('app.name'))

  {{-- Header --}}
  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-2xl font-semibold">Delegates</h1>
      <p class="text-sm text-slate-600">
        Manage the canonical delegate registry (region/district/groups/category/phones/guarantor).
      </p>
    </div>

    <div class="flex items-center gap-2">
      {{-- Optional: if you added a dedicated create route --}}
      @if(\Illuminate\Support\Facades\Route::has('manage.delegates.create'))
        <a href="{{ route('manage.delegates.create') }}"
           class="inline-flex items-center px-4 py-2 text-sm font-semibold rounded-md bg-gray-100 hover:bg-gray-200">
          Add Delegate
        </a>
      @endif

      <button wire:click="createNew"
              class="inline-flex items-center px-4 py-2 text-sm font-semibold rounded-md bg-slate-900 text-white hover:bg-slate-800">
        New Delegate
      </button>
    </div>
  </div>

  {{-- Filters --}}
  <div class="bg-white border rounded-lg shadow-sm p-4 space-y-3">
    <div class="grid md:grid-cols-5 gap-2">
      <input wire:model.live.debounce.300ms="q"
             placeholder="Search delegate..."
             class="border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10" />

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
    </div>
  </div>

  <div class="grid lg:grid-cols-3 gap-6">
    {{-- List --}}
    <div class="lg:col-span-2 bg-white border rounded-lg shadow-sm overflow-hidden">
      <div class="px-4 py-3 border-b flex items-center justify-between">
        <h2 class="text-sm font-semibold text-slate-700">Delegate List</h2>
        <span class="text-xs text-slate-500">{{ $delegates->total() }} records</span>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="bg-slate-50 text-slate-600">
            <tr>
              <th class="px-3 py-2 text-left">Delegate</th>
              <th class="px-3 py-2 text-left">Category</th>
              <th class="px-3 py-2 text-left">Phones</th>
              <th class="px-3 py-2 text-left">Guarantor</th>
              <th class="px-3 py-2 text-left">Location</th>
              <th class="px-3 py-2 text-left">Group</th>
              <th class="px-3 py-2 text-right">Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($delegates as $d)
              <tr class="border-t">
                <td class="px-3 py-2">
                  <div class="font-medium">{{ $d->full_name }}</div>
                </td>

                <td class="px-3 py-2 text-xs text-slate-600">
                  {{ $d->category ?: '—' }}
                </td>

                <td class="px-3 py-2 text-xs text-slate-700">
                  <div>{{ $d->phone_primary ?: '—' }}</div>
                  <div class="text-slate-500">{{ $d->phone_secondary ?: '—' }}</div>
                </td>

                <td class="px-3 py-2 text-xs text-slate-700">
                  {{ $d->guarantor?->name ?? 'No guarantor' }}
                </td>

                <td class="px-3 py-2 text-xs text-slate-600">
                  <div>{{ $d->district?->region?->name ?? '—' }}</div>
                  <div>{{ $d->district?->name ?? '—' }}</div>
                </td>

                <td class="px-3 py-2 text-xs text-slate-600">
                  {{-- single-group UI; still works if DB has many-to-many --}}
                  {{ $d->groups?->pluck('name')->implode(', ') ?: '—' }}
                </td>

                <td class="px-3 py-2 text-right">
                  <button wire:click="edit({{ $d->id }})"
                          class="text-xs font-semibold text-slate-700 hover:underline">
                    Edit
                  </button>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="px-3 py-8 text-center text-slate-500">No delegates found.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="px-4 py-3 border-t">
        {{ $delegates->links() }}
      </div>
    </div>

    {{-- Form --}}
    <div class="bg-white border rounded-lg shadow-sm p-4 space-y-4">
      <div class="flex items-center justify-between">
        <h2 class="text-sm font-semibold text-slate-700">
          {{ $editingId ? 'Edit Delegate' : 'New Delegate' }}
        </h2>
        @if (session('status'))
          <span class="text-[11px] text-emerald-600">{{ session('status') }}</span>
        @endif
      </div>

      <div class="space-y-3 text-sm">
        <div>
          <label class="block text-xs font-medium text-slate-600">Full name</label>
          <input type="text" wire:model.defer="full_name"
                 class="mt-1 w-full border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10">
          @error('full_name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
          <label class="block text-xs font-medium text-slate-600">Category (optional)</label>
          {{-- datalist so you can pick existing categories or type new --}}
          <input type="text" wire:model.defer="category_form" list="category_list"
                 class="mt-1 w-full border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10"
                 placeholder="e.g. District Executives, MPs, Councillors...">
          <datalist id="category_list">
            @foreach($categories as $c)
              <option value="{{ $c }}"></option>
            @endforeach
          </datalist>
          @error('category_form') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-medium text-slate-600">Phone 1</label>
            <input type="text" wire:model.defer="phone_primary"
                   class="mt-1 w-full border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10"
                   placeholder="optional">
            @error('phone_primary') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
          </div>

          <div>
            <label class="block text-xs font-medium text-slate-600">Phone 2</label>
            <input type="text" wire:model.defer="phone_secondary"
                   class="mt-1 w-full border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10"
                   placeholder="optional">
            @error('phone_secondary') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
          </div>
        </div>

        <div>
          <label class="block text-xs font-medium text-slate-600">Guarantor</label>
          <select wire:model.defer="guarantor_id"
                  class="mt-1 w-full border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10">
            <option value="">No guarantor</option>
            @foreach(($guarantors ?? []) as $g)
              <option value="{{ $g->id }}">{{ $g->name }}</option>
            @endforeach
          </select>
          @error('guarantor_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
          <label class="block text-xs font-medium text-slate-600">District</label>
          <select wire:model.defer="district_id"
                  class="mt-1 w-full border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10">
            <option value="">—</option>
            @foreach($districts as $d)
              <option value="{{ $d->id }}">{{ $d->name }}</option>
            @endforeach
          </select>
          @error('district_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
          <label class="block text-xs font-medium text-slate-600">Group</label>
          {{-- single-select UI (even if DB allows many) --}}
          <select wire:model.defer="group_id"
                  class="mt-1 w-full border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10">
            <option value="">No group</option>
            @foreach($groups as $g)
              <option value="{{ $g->id }}">{{ $g->name }}</option>
            @endforeach
          </select>
          @error('group_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="pt-2 flex items-center gap-2">
          <button wire:click="save"
                  class="inline-flex items-center px-4 py-2 text-sm font-semibold rounded-md bg-slate-900 text-white hover:bg-slate-800">
            Save Delegate
          </button>

          @if($editingId)
            <button wire:click="createNew"
                    class="inline-flex items-center px-4 py-2 text-sm font-semibold rounded-md bg-gray-100 hover:bg-gray-200">
              New
            </button>
          @endif
        </div>
      </div>

      <p class="text-[11px] text-slate-500">
        Note: this form uses <span class="font-mono">wire:model.defer</span> so Save always submits stable values.
      </p>
    </div>
  </div>
</div>
