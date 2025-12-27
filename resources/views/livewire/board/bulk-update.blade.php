<div class="p-6 space-y-6">
  @section('title', 'Bulk Update — '.config('app.name'))

  <div>
    <h1 class="text-2xl font-semibold">Bulk Update</h1>
    <p class="text-sm text-slate-600">Apply 🟢/🟡/🔴 + confidence to all delegates matching the current filters.</p>
  </div>

  <div class="bg-white border rounded-lg shadow-sm p-4 space-y-4">
    <div class="grid md:grid-cols-3 gap-2">
      <input wire:model.live.debounce.300ms="q" placeholder="Search delegate..."
             class="border rounded-md px-3 py-2 text-sm" />

      <select wire:model.live="candidateId" class="border rounded-md px-3 py-2 text-sm">
        @foreach($candidates as $c)
          <option value="{{ $c->id }}">{{ $c->name }}{{ $c->is_active ? '' : ' (inactive)' }}</option>
        @endforeach
      </select>

      <select wire:model.live="category" class="border rounded-md px-3 py-2 text-sm">
        <option value="">All categories</option>
      </select>
    </div>

    <div class="grid md:grid-cols-3 gap-2">
      <select wire:model.live="stance" class="border rounded-md px-3 py-2 text-sm">
        <option value="for">🟢 For us</option>
        <option value="indicative">🟡 Indicative</option>
        <option value="against">🔴 Against</option>
      </select>

      <input type="number" min="0" max="100" wire:model.live="confidence"
             class="border rounded-md px-3 py-2 text-sm" />

      <button wire:click="apply"
              class="inline-flex items-center justify-center px-4 py-2 text-sm font-semibold rounded-md bg-slate-900 text-white hover:bg-slate-800">
        Apply Bulk Update
      </button>
    </div>

    @if($affected)
      <div class="text-sm text-emerald-700">Updated: {{ $affected }}</div>
    @endif
  </div>
</div>
