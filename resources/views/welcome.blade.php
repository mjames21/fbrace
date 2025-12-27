{{-- filepath: resources/views/welcome.blade.php --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="csrf-token" content="{{ csrf_token() }}" />

  <title>{{ config('app.name', 'FBrace') }} — Delegate Intelligence Platform</title>
  <meta name="description" content="FBrace organizes your delegate list, support signals, field notes, and alliance dynamics into one clear view." />

  <link rel="preconnect" href="https://fonts.bunny.net">
  <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700,800" rel="stylesheet" />

  {{-- Tailwind CDN (no Vite, no app.js) --}}
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            brand: { DEFAULT: '#146c2e', ink: '#0c3e1a', ring: '#b7e3c3' }
          },
          boxShadow: {
            soft: '0 10px 30px rgba(0,0,0,.08)'
          },
          fontFamily: {
            ui: ['Inter','ui-sans-serif','system-ui','-apple-system','Segoe UI','Roboto','Ubuntu','Arial','sans-serif']
          }
        }
      }
    }
  </script>
</head>

<body class="min-h-screen bg-slate-50 text-slate-900 font-ui antialiased selection:bg-brand/10 selection:text-brand-ink">
  <a href="#main"
     class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:bg-white focus:px-3 focus:py-2 focus:rounded-lg focus:shadow">
    Skip to content
  </a>

  {{-- Top nav --}}
  <header class="sticky top-0 z-50 bg-white/90 backdrop-blur border-b border-slate-200">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 h-16 flex items-center gap-4">
      <a href="{{ url('/') }}" class="flex items-center gap-3">
        <div class="w-9 h-9 rounded-xl bg-slate-900 text-white flex items-center justify-center text-xs font-extrabold">
          FB
        </div>
        <div class="leading-tight">
          <div class="font-extrabold tracking-tight text-base">FBrace</div>
          <div class="text-[11px] text-slate-500 font-semibold">Delegate Intelligence Platform</div>
        </div>
      </a>

      <nav class="ml-auto hidden md:flex items-center gap-6 text-sm font-semibold">
        <a href="#how" class="text-slate-600 hover:text-slate-900">How it works</a>
        <a href="#modules" class="text-slate-600 hover:text-slate-900">Modules</a>
        <a href="#faq" class="text-slate-600 hover:text-slate-900">FAQ</a>
      </nav>

      <div class="ml-auto md:ml-0 flex items-center gap-2">
        @auth
          <a href="{{ route('dashboard') }}"
             class="inline-flex items-center rounded-full bg-slate-900 px-4 py-2 text-sm font-extrabold text-white hover:bg-slate-800">
            Open Console
          </a>
        @else
          @if (Route::has('login'))
            <a href="{{ route('login') }}"
               class="hidden sm:inline-flex items-center rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-extrabold text-slate-800 hover:border-slate-300">
              Sign in
            </a>
          @endif
          @if (Route::has('register'))
            <a href="{{ route('register') }}"
               class="inline-flex items-center rounded-full bg-brand px-4 py-2 text-sm font-extrabold text-white hover:shadow-[0_0_0_4px] hover:shadow-[color:#b7e3c3]">
              Get Started
            </a>
          @endif
        @endauth
      </div>
    </div>
  </header>

  <main id="main">
    {{-- HERO --}}
    <section class="relative overflow-hidden">
      {{-- Background --}}
      <div class="absolute inset-0 -z-10">
        <div class="absolute inset-0 bg-gradient-to-b from-emerald-50 via-white to-slate-50"></div>
        <div class="absolute -top-40 -right-40 w-[34rem] h-[34rem] rounded-full bg-emerald-200/40 blur-3xl"></div>
        <div class="absolute -bottom-52 -left-40 w-[34rem] h-[34rem] rounded-full bg-slate-200/60 blur-3xl"></div>
        <div class="absolute inset-0 opacity-[0.06]"
             style="background-image: radial-gradient(circle at 1px 1px, rgb(15 23 42) 1px, transparent 0); background-size: 28px 28px;"></div>
      </div>

      <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-14 sm:py-16 lg:py-20">
        <div class="grid lg:grid-cols-12 gap-10 items-center">
          {{-- Left --}}
          <div class="lg:col-span-7">
            <div class="inline-flex items-center gap-2 text-xs font-extrabold px-3 py-1.5 rounded-full border border-slate-200 bg-white shadow-soft">
              <span class="inline-flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span> for us
              </span>
              <span class="text-slate-300">•</span>
              <span class="inline-flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-amber-500"></span> indicative
              </span>
              <span class="text-slate-300">•</span>
              <span class="inline-flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-red-500"></span> against
              </span>
            </div>

            <h1 class="mt-5 text-4xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight leading-[1.05]">
              Measure your winning strength
              <span class="text-slate-600">delegate by delegate.</span>
            </h1>

            <p class="mt-5 text-lg sm:text-xl text-slate-600 leading-relaxed max-w-2xl">
              FBrace organizes your delegate list, support signals, field notes, and alliance dynamics into one clear view. <br>
              Update status instantly (🟢/🟡/🔴), track movement over time, and see momentum as it changes.
            </p>

            <div class="mt-7 flex flex-col sm:flex-row gap-3">
              @auth
                <a href="{{ route('dashboard') }}"
                   class="inline-flex justify-center items-center rounded-full bg-slate-900 px-6 py-3 text-sm font-extrabold text-white hover:bg-slate-800">
                  Open Console
                </a>
              @else
                @if (Route::has('register'))
                  <a href="{{ route('register') }}"
                     class="inline-flex justify-center items-center rounded-full bg-brand px-6 py-3 text-sm font-extrabold text-white hover:shadow-[0_0_0_4px] hover:shadow-[color:#b7e3c3]">
                    Create Account
                  </a>
                @endif
                @if (Route::has('login'))
                  <a href="{{ route('login') }}"
                     class="inline-flex justify-center items-center rounded-full bg-white border border-slate-200 px-6 py-3 text-sm font-extrabold text-slate-900 hover:bg-slate-50 hover:border-slate-300">
                    Sign In
                  </a>
                @endif
              @endauth
            </div>

            <div class="mt-7 flex flex-wrap gap-2 text-xs font-semibold text-slate-700">
              <span class="px-3 py-1.5 rounded-full bg-white border border-slate-200 shadow-sm">party primaries</span>
              <span class="px-3 py-1.5 rounded-full bg-white border border-slate-200 shadow-sm">delegate congresses</span>
              <span class="px-3 py-1.5 rounded-full bg-white border border-slate-200 shadow-sm">internal elections</span>
              <span class="px-3 py-1.5 rounded-full bg-white border border-slate-200 shadow-sm">coalition strategy</span>
            </div>
          </div>

          {{-- Right --}}
          <div class="lg:col-span-5">
            <div class="rounded-2xl border border-slate-200 bg-white shadow-soft overflow-hidden">
              <div class="px-6 py-5 border-b border-slate-200">
                <div class="text-sm font-extrabold text-slate-900">Core signals you track</div>
                <div class="text-xs text-slate-500 font-semibold">Simple inputs → clear decision-making</div>
              </div>

              <div class="p-6 space-y-4">
                <div class="rounded-xl border border-slate-200 p-4">
                  <div class="flex items-start gap-3">
                    <div class="mt-0.5 w-10 h-10 rounded-xl bg-emerald-50 border border-emerald-200 flex items-center justify-center">
                      <span class="text-base">🟢</span>
                    </div>
                    <div>
                      <div class="text-sm font-extrabold">Support status</div>
                      <div class="text-sm text-slate-600">For us, indicative, against — update anytime as you win delegates over.</div>
                    </div>
                  </div>
                </div>

                <div class="rounded-xl border border-slate-200 p-4">
                  <div class="flex items-start gap-3">
                    <div class="mt-0.5 w-10 h-10 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-center">
                      <svg class="w-4 h-4 text-slate-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 6h16M4 12h16M4 18h10" />
                      </svg>
                    </div>
                    <div>
                      <div class="text-sm font-extrabold">Ownership + notes</div>
                      <div class="text-sm text-slate-600">Assign a delegate to a team member. Capture conversations and next steps.</div>
                    </div>
                  </div>
                </div>

                <div class="rounded-xl border border-slate-200 p-4">
                  <div class="flex items-start gap-3">
                    <div class="mt-0.5 w-10 h-10 rounded-xl bg-amber-50 border border-amber-200 flex items-center justify-center">
                      <svg class="w-4 h-4 text-amber-800" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 19l5-6 4 3 7-9" />
                      </svg>
                    </div>
                    <div>
                      <div class="text-sm font-extrabold">Alliances + horse-race</div>
                      <div class="text-sm text-slate-600">Map influence weights and see the race ranking shift as reality changes.</div>
                    </div>
                  </div>
                </div>

                <div class="rounded-xl bg-slate-50 border border-slate-200 p-4">
                  <div class="text-xs font-extrabold text-slate-700">Accountability</div>
                  <div class="mt-1 text-sm text-slate-600">
                    High-value delegates can require approval. Every change is logged with user + time in Status History.
                  </div>
                </div>
              </div>
            </div>

            <div class="mt-4 grid grid-cols-3 gap-3">
              <div class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
                <div class="text-[11px] text-slate-500 font-semibold">Fast updates</div>
                <div class="text-sm font-extrabold">Board</div>
              </div>
              <div class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
                <div class="text-[11px] text-slate-500 font-semibold">Coalition</div>
                <div class="text-sm font-extrabold">Alliances</div>
              </div>
              <div class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
                <div class="text-[11px] text-slate-500 font-semibold">Momentum</div>
                <div class="text-sm font-extrabold">Horse Race</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    {{-- What / Why / How --}}
    <section id="how" class="border-t border-slate-200 bg-white">
      <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-14">
        <div class="grid gap-8 lg:grid-cols-3">
          <div>
            <h2 class="text-2xl font-extrabold tracking-tight">What it is</h2>
            <p class="mt-2 text-sm text-slate-600 leading-relaxed">
              FBrace keeps your delegate structure (region, district, group), support signals, and field activity organized in one system.
            </p>
          </div>

          <div>
            <h2 class="text-2xl font-extrabold tracking-tight">Why it matters</h2>
            <p class="mt-2 text-sm text-slate-600 leading-relaxed">
              Primaries are won by small margins. When status is unclear, effort is wasted and momentum is misread.
              FBrace makes the picture obvious.
            </p>
          </div>

          <div>
            <h2 class="text-2xl font-extrabold tracking-tight">How it works</h2>
            <p class="mt-2 text-sm text-slate-600 leading-relaxed">
              Update 🟢/🟡/🔴 after every interaction, record notes, assign ownership, model alliances, and review approvals
              where needed — then read the horse-race score as the situation evolves.
            </p>
          </div>
        </div>

        <div class="mt-10 grid gap-4 md:grid-cols-3">
          <div class="rounded-2xl border border-slate-200 bg-slate-50 p-6">
            <div class="text-sm font-extrabold">Color coding</div>
            <p class="mt-2 text-sm text-slate-600 leading-relaxed">Quick status updates that reflect reality: for us, indicative, against.</p>
          </div>

          <div class="rounded-2xl border border-slate-200 bg-slate-50 p-6">
            <div class="text-sm font-extrabold">Alliance weights</div>
            <p class="mt-2 text-sm text-slate-600 leading-relaxed">Add candidates and map who supports who — weight spillover clearly.</p>
          </div>

          <div class="rounded-2xl border border-slate-200 bg-slate-50 p-6">
            <div class="text-sm font-extrabold">History + approvals</div>
            <p class="mt-2 text-sm text-slate-600 leading-relaxed">Sensitive updates can require approval; every change has an audit trail.</p>
          </div>
        </div>
      </div>
    </section>

    {{-- Modules --}}
    <section id="modules" class="border-t border-slate-200 bg-slate-50">
      <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-14">
        <div class="flex items-end justify-between gap-4 flex-wrap">
          <div>
            <h2 class="text-2xl font-extrabold tracking-tight">Modules</h2>
            <p class="mt-2 text-sm text-slate-600">Built around how delegate operations actually run.</p>
          </div>

          <div class="flex items-center gap-2">
            @auth
              <a href="{{ route('dashboard') }}"
                 class="inline-flex items-center rounded-full bg-slate-900 px-5 py-2.5 text-sm font-extrabold text-white hover:bg-slate-800">
                Open Console
              </a>
            @else
              @if (Route::has('register'))
                <a href="{{ route('register') }}"
                   class="inline-flex items-center rounded-full bg-brand px-5 py-2.5 text-sm font-extrabold text-white hover:shadow-[0_0_0_4px] hover:shadow-[color:#b7e3c3]">
                  Get Started
                </a>
              @endif
            @endauth
          </div>
        </div>

        @php
          $mods = [
            ['t'=>'Delegate Board','d'=>'Search, filter, and update 🟢/🟡/🔴 for each delegate.'],
            ['t'=>'Bulk Updates','d'=>'Select page or all filtered; apply stance safely in chunks.'],
            ['t'=>'Delegate Profile','d'=>'Assignment, notes, and status timeline in a slide-over.'],
            ['t'=>'Candidates','d'=>'Add candidates and manage active contenders.'],
            ['t'=>'Alliances','d'=>'Map candidate-to-candidate influence weights.'],
            ['t'=>'Horse Race','d'=>'Ranking based on direct support and alliance spillover.'],
            ['t'=>'Approvals','d'=>'Queue and approve sensitive updates for high-value delegates.'],
            ['t'=>'Status History','d'=>'Audit trail: who changed what, when, and why.'],
            ['t'=>'Imports','d'=>'Bring in delegates and reference lists (regions/districts/groups).'],
          ];
        @endphp

        <div class="mt-8 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
          @foreach($mods as $m)
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-soft hover:border-slate-300 transition">
              <div class="flex items-start gap-3">
                <div class="w-10 h-10 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-center">
                  <svg class="w-5 h-5 text-slate-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 6h16M4 12h16M4 18h10" />
                  </svg>
                </div>
                <div>
                  <div class="text-sm font-extrabold">{{ $m['t'] }}</div>
                  <p class="mt-1 text-sm text-slate-600 leading-relaxed">{{ $m['d'] }}</p>
                </div>
              </div>
            </div>
          @endforeach
        </div>

        <div class="mt-10 rounded-2xl border border-slate-200 bg-white p-6 flex flex-col md:flex-row items-start md:items-center justify-between gap-4 shadow-soft">
          <div>
            <div class="text-lg font-extrabold">Start tracking the real picture</div>
            <div class="text-sm text-slate-600 mt-1">Delegate status, notes, ownership, alliances — all in one place.</div>
          </div>
          <div class="flex items-center gap-2">
            @auth
              <a href="{{ route('dashboard') }}"
                 class="inline-flex items-center rounded-full bg-slate-900 px-6 py-3 text-sm font-extrabold text-white hover:bg-slate-800">
                Open Console
              </a>
            @else
              @if (Route::has('register'))
                <a href="{{ route('register') }}"
                   class="inline-flex items-center rounded-full bg-brand px-6 py-3 text-sm font-extrabold text-white hover:shadow-[0_0_0_4px] hover:shadow-[color:#b7e3c3]">
                  Get Started
                </a>
              @endif
              @if (Route::has('login'))
                <a href="{{ route('login') }}"
                   class="inline-flex items-center rounded-full bg-white border border-slate-200 px-6 py-3 text-sm font-extrabold text-slate-900 hover:bg-slate-50 hover:border-slate-300">
                  Login
                </a>
              @endif
            @endauth
          </div>
        </div>
      </div>
    </section>

    {{-- FAQ --}}
   <section id="faq" class="border-t border-slate-200 bg-white">
  <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-14">
    <h2 class="text-2xl font-extrabold tracking-tight">FAQ</h2>

    <div class="mt-6 grid gap-3 lg:grid-cols-2 lg:gap-4">
      <details class="bg-slate-50 border border-slate-200 rounded-xl p-4 lg:col-span-2" open>
        <summary class="font-extrabold cursor-pointer">Who is this for?</summary>
        <p class="mt-2 text-slate-600 text-sm leading-relaxed">
          This platform is for a <span class="font-semibold text-slate-800">Flag Bearer aspirant</span> contesting in
          <span class="font-semibold text-slate-800">primary conventions</span> within a political party — and the campaign team
          managing delegate support, field notes, and coalition dynamics.
        </p>
      </details>

      <details class="bg-slate-50 border border-slate-200 rounded-xl p-4">
        <summary class="font-extrabold cursor-pointer">What problem does it solve?</summary>
        <p class="mt-2 text-slate-600 text-sm leading-relaxed">
          It turns scattered WhatsApp updates and handwritten notes into a single, up-to-date view of
          <span class="font-semibold text-slate-800">who supports you, who is undecided, and who is against you</span>.
        </p>
      </details>

      <details class="bg-slate-50 border border-slate-200 rounded-xl p-4">
        <summary class="font-extrabold cursor-pointer">Do I need an account?</summary>
        <p class="mt-2 text-slate-600 text-sm leading-relaxed">
          The landing page is public; the console (your delegate board) remains protected behind login.
        </p>
      </details>

      <details class="bg-slate-50 border border-slate-200 rounded-xl p-4">
        <summary class="font-extrabold cursor-pointer">What do the 🟢/🟡/🔴 statuses mean?</summary>
        <p class="mt-2 text-slate-600 text-sm leading-relaxed">
          <span class="font-semibold text-slate-800">🟢 For us</span> = committed support,
          <span class="font-semibold text-slate-800">🟡 Indicative</span> = leaning/undecided,
          <span class="font-semibold text-slate-800">🔴 Against</span> = committed elsewhere.
        </p>
      </details>

      <details class="bg-slate-50 border border-slate-200 rounded-xl p-4">
        <summary class="font-extrabold cursor-pointer">How do approvals work?</summary>
        <p class="mt-2 text-slate-600 text-sm leading-relaxed">
          High-value or sensitive status changes can be queued for review. Every change is logged with user + timestamp.
        </p>
      </details>

      <details class="bg-slate-50 border border-slate-200 rounded-xl p-4">
        <summary class="font-extrabold cursor-pointer">Can multiple team members work at the same time?</summary>
        <p class="mt-2 text-slate-600 text-sm leading-relaxed">
          Yes. Assign ownership per delegate and track updates to avoid duplicated outreach and conflicting reports.
        </p>
      </details>

      <details class="bg-slate-50 border border-slate-200 rounded-xl p-4">
        <summary class="font-extrabold cursor-pointer">How do alliances affect the horse-race?</summary>
        <p class="mt-2 text-slate-600 text-sm leading-relaxed">
          Alliances can model influence spillover so rankings reflect both direct support and coalition dynamics.
        </p>
      </details>

      <details class="bg-slate-50 border border-slate-200 rounded-xl p-4">
        <summary class="font-extrabold cursor-pointer">Is my data private?</summary>
        <p class="mt-2 text-slate-600 text-sm leading-relaxed">
          Access is controlled by login. Only authorized users in your team can view or update delegate records.
        </p>
      </details>

      <details class="bg-slate-50 border border-slate-200 rounded-xl p-4 lg:col-span-2">
        <summary class="font-extrabold cursor-pointer">Can I use my phone?</summary>
        <p class="mt-2 text-slate-600 text-sm leading-relaxed">
          Yes — the layout is responsive and optimized for quick field updates.
        </p>
      </details>
    </div>
  </div>
</section>


    {{-- Footer --}}
    <footer class="border-t border-slate-200 bg-white">
      <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-10 flex flex-col md:flex-row gap-4 md:items-center md:justify-between">
        <div class="text-sm text-slate-600">
          © {{ date('Y') }} <span class="font-extrabold text-slate-800">FBrace</span>. All rights reserved.
        </div>
        <div class="flex items-center gap-4 text-sm font-semibold">
          <a href="/privacy-policy" class="text-slate-600 hover:text-slate-900 hover:underline">Privacy Policy</a>
          <a href="#modules" class="text-slate-600 hover:text-slate-900 hover:underline">Modules</a>
          <a href="#how" class="text-slate-600 hover:text-slate-900 hover:underline">How it works</a>
        </div>
      </div>
    </footer>
  </main>
</body>
</html>
