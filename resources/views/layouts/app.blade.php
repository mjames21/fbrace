<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <title>@yield('title', config('app.name', 'FBrace'))</title>

  <link rel="preconnect" href="https://fonts.bunny.net">
  <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700" rel="stylesheet" />

  @vite(['resources/css/app.css', 'resources/js/app.js'])
  @livewireStyles
</head>

<body class="bg-gray-100 text-gray-900 antialiased">
  <div class="min-h-screen flex">

    {{-- Sidebar --}}
    <aside class="w-72 bg-white border-r flex flex-col">
      {{-- Brand --}}
      <div class="h-16 px-4 flex items-center justify-between border-b">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
          <div class="w-8 h-8 rounded-full bg-slate-900 text-white flex items-center justify-center text-xs font-bold">
            FB
          </div>
          <div>
            <div class="font-semibold text-lg tracking-tight">FBrace</div>
            <div class="text-[11px] text-slate-500">Delegate Intelligence Console</div>
          </div>
        </a>
      </div>

      <nav class="flex-1 py-4 text-sm overflow-y-auto">

        <div class="px-4 mt-2 mb-1 text-[11px] uppercase tracking-wide text-slate-500">
          Operations
        </div>
        <a href="{{ route('board.delegate-board') }}"
           class="flex items-center gap-2 px-4 py-2 hover:bg-slate-50
                  {{ request()->routeIs('board.delegate-board') ? 'bg-slate-100 font-semibold border-l-4 border-slate-900' : 'border-l-4 border-transparent' }}">
          <svg class="w-4 h-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
            <path d="M4 4h16v16H4z" />
            <path d="M8 8h8" />
            <path d="M8 12h8" />
            <path d="M8 16h6" />
          </svg>
          <span>Delegate Board</span>
        </a>

        <a href="{{ route('horse-race') }}"
           class="flex items-center gap-2 px-4 py-2 hover:bg-slate-50
                  {{ request()->routeIs('horse-race') ? 'bg-slate-100 font-semibold border-l-4 border-slate-900' : 'border-l-4 border-transparent' }}">
          <svg class="w-4 h-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
            <path d="M4 19l5-6 4 3 7-9" />
            <path d="M4 5h4v4H4z" />
            <path d="M10 11h4v4h-4z" />
            <path d="M16 5h4v4h-4z" />
          </svg>
          <span>Horse Race</span>
        </a>

        <a href="{{ route('board.compare-candidates') }}"
           class="flex items-center gap-2 px-4 py-2 hover:bg-slate-50
                  {{ request()->routeIs('board.compare-candidates') ? 'bg-slate-100 font-semibold border-l-4 border-slate-900' : 'border-l-4 border-transparent' }}">
          <svg class="w-4 h-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
            <path d="M4 4h7v7H4z" />
            <path d="M13 4h7v7h-7z" />
            <path d="M4 13h7v7H4z" />
            <path d="M13 13h7v7h-7z" />
          </svg>
          <span>Compare Candidates</span>
        </a>

        <a href="{{ route('board.approvals') }}"
           class="flex items-center gap-2 px-4 py-2 hover:bg-slate-50
                  {{ request()->routeIs('board.approvals') ? 'bg-slate-100 font-semibold border-l-4 border-slate-900' : 'border-l-4 border-transparent' }}">
          <svg class="w-4 h-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
            <path d="M9 12l2 2 4-4" />
            <path d="M7 3h10v4H7z" />
            <path d="M6 7h12v14H6z" />
          </svg>
          <span>Approvals</span>
        </a>

        <a href="{{ route('board.bulk-update') }}"
           class="flex items-center gap-2 px-4 py-2 hover:bg-slate-50
                  {{ request()->routeIs('board.bulk-update') ? 'bg-slate-100 font-semibold border-l-4 border-slate-900' : 'border-l-4 border-transparent' }}">
          <svg class="w-4 h-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
            <path d="M4 6h16" />
            <path d="M4 12h16" />
            <path d="M4 18h10" />
          </svg>
          <span>Bulk Update</span>
        </a>

        <div class="mt-6 mb-1 px-4 text-[11px] uppercase tracking-wide text-slate-500">
          Manage
        </div>

        <a href="{{ route('manage.regions') }}"
           class="flex items-center gap-2 px-4 py-2 hover:bg-slate-50
                  {{ request()->routeIs('manage.regions') ? 'bg-slate-100 font-semibold border-l-4 border-slate-900' : 'border-l-4 border-transparent' }}">
          <svg class="w-4 h-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 2l7 7-7 7-7-7z" />
            <path d="M12 9v13" />
          </svg>
          <span>Regions</span>
        </a>

        <a href="{{ route('manage.districts') }}"
           class="flex items-center gap-2 px-4 py-2 hover:bg-slate-50
                  {{ request()->routeIs('manage.districts') ? 'bg-slate-100 font-semibold border-l-4 border-slate-900' : 'border-l-4 border-transparent' }}">
          <svg class="w-4 h-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
            <path d="M4 4h7v7H4z" />
            <path d="M13 4h7v7h-7z" />
            <path d="M4 13h7v7H4z" />
            <path d="M13 13h7v7h-7z" />
          </svg>
          <span>Districts</span>
        </a>
      
          <a href="{{ route('manage.categories') }}"
           class="flex items-center gap-2 px-4 py-2 hover:bg-slate-50
                  {{ request()->routeIs('manage.categories*') ? 'bg-slate-100 font-semibold border-l-4 border-slate-900' : 'border-l-4 border-transparent' }}">
          <svg class="w-4 h-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor"
               stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M4 7h16" />
            <path d="M4 12h16" />
            <path d="M4 17h16" />
            <path d="M7 7v10" />
            <path d="M12 7v10" />
            <path d="M17 7v10" />
          </svg>
          <span>Categories</span>
        </a>

        <a href="{{ route('manage.groups') }}"
           class="flex items-center gap-2 px-4 py-2 hover:bg-slate-50
                  {{ request()->routeIs('manage.groups') ? 'bg-slate-100 font-semibold border-l-4 border-slate-900' : 'border-l-4 border-transparent' }}">
          <svg class="w-4 h-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
            <path d="M7 8h10" />
            <path d="M7 12h10" />
            <path d="M7 16h10" />
            <path d="M5 4h14v16H5z" />
          </svg>
          <span>Groups</span>
        </a>
        <a href="{{ route('manage.guarantors') }}"
           class="flex items-center gap-2 px-4 py-2 hover:bg-slate-50
                  {{ request()->routeIs('manage.guarantors*') ? 'bg-slate-100 font-semibold border-l-4 border-slate-900' : 'border-l-4 border-transparent' }}">
          <svg class="w-4 h-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor"
               stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
            <circle cx="9" cy="7" r="4" />
            <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
            <path d="M16 3.13a4 4 0 0 1 0 7.75" />
          </svg>
          <span>Guarantors</span>
        </a>


        <a href="{{ route('manage.delegates') }}"
           class="flex items-center gap-2 px-4 py-2 hover:bg-slate-50
                  {{ request()->routeIs('manage.delegates') ? 'bg-slate-100 font-semibold border-l-4 border-slate-900' : 'border-l-4 border-transparent' }}">
          <svg class="w-4 h-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
            <path d="M8 7a3 3 0 1 1 6 0v1H8z" />
            <path d="M5 20v-2a4 4 0 0 1 4-4h6a4 4 0 0 1 4 4v2" />
          </svg>
          <span>Delegates</span>
        </a>
        <a href="{{ route('manage.delegates.create') }}"
           class="flex items-center gap-2 px-4 py-2 hover:bg-slate-50
                  {{ request()->routeIs('manage.delegates.create') ? 'bg-slate-100 font-semibold border-l-4 border-slate-900' : 'border-l-4 border-transparent' }}">
          <svg class="w-4 h-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor"
               stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M12 5v14" />
            <path d="M5 12h14" />
          </svg>
          <span>Add Delegate</span>
        </a>
        <a href="{{ route('manage.candidates') }}"
           class="flex items-center gap-2 px-4 py-2 hover:bg-slate-50
                  {{ request()->routeIs('manage.candidates') ? 'bg-slate-100 font-semibold border-l-4 border-slate-900' : 'border-l-4 border-transparent' }}">
          <svg class="w-4 h-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 2l4 8-4 4-4-4z" />
            <path d="M6 22l6-6 6 6" />
          </svg>
          <span>Candidates</span>
        </a>

        <a href="{{ route('manage.alliances') }}"
           class="flex items-center gap-2 px-4 py-2 hover:bg-slate-50
                  {{ request()->routeIs('manage.alliances') ? 'bg-slate-100 font-semibold border-l-4 border-slate-900' : 'border-l-4 border-transparent' }}">
          <svg class="w-4 h-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
            <path d="M10 13a5 5 0 0 0 7.07 0l1.41-1.41a5 5 0 0 0 0-7.07" />
            <path d="M14 11a5 5 0 0 0-7.07 0L5.52 12.41a5 5 0 0 0 0 7.07" />
          </svg>
          <span>Alliances</span>
        </a>

        <div class="mt-6 mb-1 px-4 text-[11px] uppercase tracking-wide text-slate-500">
          Reports
        </div>

        <a href="{{ route('reports.status-history') }}"
           class="flex items-center gap-2 px-4 py-2 hover:bg-slate-50
                  {{ request()->routeIs('reports.status-history') ? 'bg-slate-100 font-semibold border-l-4 border-slate-900' : 'border-l-4 border-transparent' }}">
          <svg class="w-4 h-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
            <path d="M4 4h16v16H4z" />
            <path d="M7 8h10" />
            <path d="M7 12h10" />
            <path d="M7 16h6" />
          </svg>
          <span>Status History</span>
        </a>

      </nav>
    </aside>

    {{-- Main --}}
    <div class="flex-1 flex flex-col bg-white">
      @livewire('navigation-menu')

      <main class="flex-1 bg-gray-50 px-6 py-4">
        {{ $slot }}
      </main>

      <footer class="bg-white border-t px-6 py-4 text-sm text-gray-500">
        <div class="flex flex-col md:flex-row justify-between items-center">
          <span class="mb-2 md:mb-0">
            © {{ date('Y') }} <span class="text-gray-600">FBrace</span>. All Rights Reserved.
          </span>
          <ul class="flex space-x-4">
            <li><a href="/privacy-policy" class="hover:underline text-gray-500">Privacy Policy</a></li>
          </ul>
        </div>
      </footer>
    </div>
  </div>

  @livewireScripts
  @stack('modals')
</body>
</html>
