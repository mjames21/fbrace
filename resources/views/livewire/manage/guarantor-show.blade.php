{{-- resources/views/livewire/manage/guarantor-show.blade.php --}}

@section('title', ($guarantor->name ?? 'Guarantor').' — '.config('app.name'))

<div class="p-6 space-y-6">
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold">{{ $guarantor->name }}</h1>
            <p class="text-sm text-slate-600">
                Manage the delegates assigned to this guarantor.
            </p>

            <div class="mt-3 flex flex-wrap items-center gap-2 text-xs">
                <span class="inline-flex items-center px-2 py-1 rounded-md border bg-white">
                    <span class="text-slate-500 mr-1">Primary:</span>
                    <span class="font-medium text-slate-800">{{ $guarantor->phone_primary ?: '—' }}</span>
                </span>

                <span class="inline-flex items-center px-2 py-1 rounded-md border bg-white">
                    <span class="text-slate-500 mr-1">Secondary:</span>
                    <span class="font-medium text-slate-800">{{ $guarantor->phone_secondary ?: '—' }}</span>
                </span>

                <span class="inline-flex items-center px-2 py-1 rounded-md border bg-white">
                    <span class="text-slate-500 mr-1">District:</span>
                    <span class="font-medium text-slate-800">
                        {{ $guarantor->district?->name ?: '—' }}
                    </span>
                </span>

                <span class="inline-flex items-center px-2 py-1 rounded-md border bg-white">
                    <span class="text-slate-500 mr-1">Region:</span>
                    <span class="font-medium text-slate-800">
                        {{ $guarantor->district?->region?->name ?: '—' }}
                    </span>
                </span>

                <span class="inline-flex items-center px-2 py-1 rounded-md border bg-white">
                    <span class="text-slate-500 mr-1">Active:</span>
                    <span class="font-medium {{ $guarantor->is_active ? 'text-emerald-700' : 'text-slate-600' }}">
                        {{ $guarantor->is_active ? 'Yes' : 'No' }}
                    </span>
                </span>
            </div>

            @if($guarantor->notes)
                <div class="mt-3 text-sm text-slate-700 bg-slate-50 border rounded-lg p-3">
                    {{ $guarantor->notes }}
                </div>
            @endif
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('manage.guarantors') }}"
               class="inline-flex items-center px-4 py-2 text-sm font-semibold rounded-md bg-gray-100 hover:bg-gray-200">
                Back
            </a>

            <a href="{{ route('board.delegate-board', ['guarantor' => $guarantor->id]) }}"
               class="inline-flex items-center px-4 py-2 text-sm font-semibold rounded-md bg-slate-900 text-white hover:bg-slate-800">
                View in Board
            </a>
        </div>
    </div>

    {{-- Controls --}}
    <div class="bg-white border rounded-lg shadow-sm p-4">
        <div class="flex flex-wrap items-center gap-2">
            <input
                wire:model.live.debounce.300ms="q"
                placeholder="Search delegates..."
                class="w-full md:w-96 border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10"
            />

            <div class="flex items-center gap-2">
                <span class="text-xs text-slate-500">Rows</span>
                <select wire:model.live="perPage" class="border rounded-md px-2 py-1 text-xs">
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>

            <div class="ml-auto text-xs text-slate-500">
                Assigned: <span class="font-semibold text-slate-800">{{ $assigned->total() }}</span>
            </div>
        </div>
    </div>

    <div class="grid lg:grid-cols-2 gap-6">
        {{-- Assigned --}}
        <div class="bg-white border rounded-lg shadow-sm overflow-hidden">
            <div class="px-4 py-3 border-b flex items-center justify-between">
                <div>
                    <div class="font-semibold">Assigned Delegates</div>
                    <div class="text-xs text-slate-500">These delegates are currently under this guarantor.</div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th class="px-3 py-2 text-left">Delegate</th>
                            <th class="px-3 py-2 text-left">Phones</th>
                            <th class="px-3 py-2 text-left">Location</th>
                            <th class="px-3 py-2 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($assigned as $d)
                            <tr class="border-t">
                                <td class="px-3 py-2">
                                    <div class="font-medium text-slate-900">{{ $d->full_name }}</div>
                                    <div class="text-xs text-slate-500">{{ $d->category ?: '—' }}</div>
                                    <div class="text-xs text-slate-500">
                                        {{ $d->groups?->pluck('name')->implode(', ') ?: '' }}
                                    </div>
                                </td>

                                <td class="px-3 py-2 text-xs text-slate-700">
                                    <div>{{ $d->phone_primary ?: '—' }}</div>
                                    <div class="text-slate-400">{{ $d->phone_secondary ?: '—' }}</div>
                                </td>

                                <td class="px-3 py-2 text-xs text-slate-600">
                                    <div>{{ $d->district?->region?->name ?: '—' }}</div>
                                    <div>{{ $d->district?->name ?: '—' }}</div>
                                </td>

                                <td class="px-3 py-2 text-right">
                                    <button
                                        wire:click="unassignDelegate({{ $d->id }})"
                                        class="inline-flex items-center px-3 py-1.5 text-xs font-semibold rounded-md bg-gray-100 hover:bg-gray-200"
                                    >
                                        Remove
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-3 py-8 text-center text-slate-500">No delegates assigned.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-4 py-3 border-t">
                {{ $assigned->links() }}
            </div>
        </div>

        {{-- Unassigned quick attach --}}
        <div class="bg-white border rounded-lg shadow-sm overflow-hidden">
            <div class="px-4 py-3 border-b">
                <div class="font-semibold">Assign Delegates</div>
                <div class="text-xs text-slate-500">
                    Shows up to 25 unassigned delegates (filtered by your search box).
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th class="px-3 py-2 text-left">Delegate</th>
                            <th class="px-3 py-2 text-left">Phones</th>
                            <th class="px-3 py-2 text-left">Location</th>
                            <th class="px-3 py-2 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($unassigned as $d)
                            <tr class="border-t">
                                <td class="px-3 py-2">
                                    <div class="font-medium text-slate-900">{{ $d->full_name }}</div>
                                </td>

                                <td class="px-3 py-2 text-xs text-slate-700">
                                    <div>{{ $d->phone_primary ?: '—' }}</div>
                                    <div class="text-slate-400">{{ $d->phone_secondary ?: '—' }}</div>
                                </td>

                                <td class="px-3 py-2 text-xs text-slate-600">
                                    {{-- district loaded, region via relationship --}}
                                    <div>{{ $d->district?->region?->name ?: '—' }}</div>
                                    <div>{{ $d->district?->name ?: '—' }}</div>
                                </td>

                                <td class="px-3 py-2 text-right">
                                    <button
                                        wire:click="assignDelegate({{ $d->id }})"
                                        class="inline-flex items-center px-3 py-1.5 text-xs font-semibold rounded-md bg-slate-900 text-white hover:bg-slate-800"
                                    >
                                        Assign
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-3 py-8 text-center text-slate-500">No unassigned delegates found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-4 py-3 border-t text-xs text-slate-500">
                Tip: use the search box to find a specific delegate, then click Assign.
            </div>
        </div>
    </div>
</div>
