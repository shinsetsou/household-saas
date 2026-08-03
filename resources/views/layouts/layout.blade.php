<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'BarangayBooks') &mdash; BarangayBooks</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50:  '#eef2ff',
                            100: '#e0e7ff',
                            200: '#c7d2fe',
                            500: '#6366f1',
                            600: '#4f46e5',
                            700: '#4338ca',
                            800: '#3730a3',
                            900: '#312e81',
                        }
                    }
                }
            }
        }
    </script>

    <!-- Theme init: run before paint to prevent flash -->
    <script>
        (function () {
            var stored = localStorage.getItem('theme');
            if (stored === 'light') {
                document.documentElement.classList.remove('dark');
            } else {
                // Default dark
                document.documentElement.classList.add('dark');
            }
        })();
    </script>

    <style>
        body { font-family: 'Inter', ui-sans-serif, system-ui, sans-serif; }
        [x-cloak] { display: none !important; }
        /* Scrollbar */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 9999px; }
        .dark ::-webkit-scrollbar-thumb { background: #374151; }
    </style>
</head>

<body class="h-full bg-gray-50 text-gray-900 antialiased">

    <!-- ===== TOP NAVBAR ===== -->
    <nav class="fixed top-0 inset-x-0 z-40 h-14 bg-white/80 dark:bg-slate-950/80 backdrop-blur-md border-b border-slate-200/70 dark:border-slate-800/70">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 h-full flex items-center gap-3">

            <!-- Brand -->
            <a href="{{ $navContext->organization ? route('organizations.index', $navContext->organization->slug) : url('/') }}"
               class="flex items-center gap-2 flex-shrink-0 group">
                <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-slate-900 dark:bg-white text-white dark:text-slate-900 text-[13px] font-semibold tracking-tight">
                    B
                </span>
                <span class="hidden sm:inline text-[15px] font-semibold tracking-tight text-slate-900 dark:text-white">
                    BarangayBooks
                </span>
            </a>

            @if($navContext->organization)
                <span class="hidden md:inline-block h-5 w-px bg-slate-200 dark:bg-slate-800 mx-1"></span>
                <a href="{{ route('home', $navContext->organization->slug) }}"
                   class="hidden md:inline-flex items-center gap-1.5 text-sm text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition-colors">
                    <span class="inline-flex items-center justify-center w-5 h-5 rounded bg-slate-100 dark:bg-slate-800 text-[10px] font-bold uppercase text-slate-600 dark:text-slate-300">
                        {{ \Illuminate\Support\Str::limit($navContext->organization->name, 2, '') }}
                    </span>
                    <span class="truncate max-w-[160px]">{{ $navContext->organization->name }}</span>
                </a>
            @endif

            <!-- Nav links — only when an organization context exists -->
            @if($navContext->organization)
            <div class="hidden sm:flex items-center gap-0.5 flex-1 ml-2 p-0.5 rounded-lg bg-slate-100/60 dark:bg-slate-900/60 ring-1 ring-slate-200/60 dark:ring-slate-800/60 w-fit">
                @auth
                    <a href="{{ route('organizations.index', $navContext->organization->slug) }}"
                       class="nav-pill {{ request()->routeIs('organizations.index') ? 'nav-pill-active' : '' }}">
                        Dashboard
                    </a>

                    <a href="{{ route('home', $navContext->organization->slug) }}"
                       class="nav-pill {{ request()->routeIs('home') || request()->routeIs('households.*') ? 'nav-pill-active' : '' }}">
                        Households
                    </a>

                    <a href="{{ route('contributions.index', $navContext->organization->slug) }}"
                       class="nav-pill {{ request()->routeIs('contributions.index') ? 'nav-pill-active' : '' }}">
                        Contributions
                    </a>

                    <a href="{{ route('expenses.index', $navContext->organization->slug) }}"
                       class="nav-pill {{ request()->routeIs('expenses.*') ? 'nav-pill-active' : '' }}">
                        Expenses
                    </a>

                    @if($navContext->canVerify)
                        <a href="{{ route('contributions.verify', $navContext->organization->slug) }}"
                           class="nav-pill inline-flex items-center gap-1.5 {{ request()->routeIs('contributions.verify*') ? 'nav-pill-active' : '' }}">
                            Verify
                            @if(($pendingVerificationCount ?? 0) > 0)
                                <span class="inline-flex items-center justify-center min-w-[1.1rem] h-[1.1rem] px-1 rounded-md bg-rose-600 text-white text-[10px] font-semibold tabular-nums"
                                      data-count="{{ $pendingVerificationCount }}">
                                    {{ $pendingVerificationCount }}
                                </span>
                            @endif
                        </a>
                    @endif

                    {{-- Reports dropdown --}}
                    <div x-data="{ open: false }" @click.outside="open = false" @keydown.escape="open = false" class="relative">
                        <button type="button"
                                @click="open = !open"
                                :aria-expanded="open"
                                class="nav-pill inline-flex items-center gap-1 {{ request()->routeIs('reports.*') ? 'nav-pill-active' : '' }}">
                            Reports
                            <svg class="w-3 h-3 text-slate-400 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="open" x-cloak x-transition.opacity
                             class="absolute left-0 mt-2 w-56 rounded-xl bg-white dark:bg-slate-900 shadow-lg ring-1 ring-slate-200 dark:ring-slate-800 py-1 z-50">
                            <a href="{{ route('reports.contributions.index', $navContext->organization->slug) }}"
                               class="flex items-center gap-2 px-3 py-1.5 mx-1 rounded-md text-[13px] text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                Contributions
                            </a>
                            <a href="{{ route('reports.expenses.index', $navContext->organization->slug) }}"
                               class="flex items-center gap-2 px-3 py-1.5 mx-1 rounded-md text-[13px] text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800">
                                <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                Expenses
                            </a>
                            <a href="{{ route('reports.delinquency.index', $navContext->organization->slug) }}"
                               class="flex items-center gap-2 px-3 py-1.5 mx-1 rounded-md text-[13px] text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                Delinquency
                            </a>
                        </div>
                    </div>
                @endauth
            </div>
            @endif

            <!-- Right actions -->
            <div class="flex items-center gap-1 ml-auto">

                @auth
                <!-- Dark mode toggle -->
                <button id="theme-toggle" type="button"
                    class="p-1.5 rounded-md text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white transition-colors focus:outline-none focus:ring-2 focus:ring-brand-500"
                    aria-label="Toggle dark mode">
                    <svg id="icon-sun" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 hidden dark:block" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" clip-rule="evenodd"/>
                    </svg>
                    <svg id="icon-moon" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 block dark:hidden" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"/>
                    </svg>
                </button>

                {{-- User dropdown --}}
                <div class="relative" x-data="{ open: false }" @click.outside="open = false" @keydown.escape="open = false">
                    <button type="button"
                            @click="open = !open"
                            :aria-expanded="open"
                            aria-haspopup="true"
                            class="flex items-center gap-1.5 p-0.5 pr-1.5 rounded-md hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors focus:outline-none focus:ring-2 focus:ring-brand-500">
                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-md bg-slate-900 dark:bg-white text-white dark:text-slate-900 text-[11px] font-semibold uppercase">
                            {{ $navContext->userInitials }}
                        </span>
                        <svg class="h-3.5 w-3.5 text-slate-400 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>

                    <div x-show="open" x-cloak x-transition.opacity
                         class="absolute right-0 mt-2 w-60 rounded-xl bg-white dark:bg-slate-900 shadow-lg ring-1 ring-slate-200 dark:ring-slate-800 py-1 z-50"
                         role="menu" aria-orientation="vertical">
                        <div class="px-3 py-2.5 border-b border-slate-100 dark:border-slate-800">
                            <p class="text-[13px] font-medium text-slate-900 dark:text-white truncate">{{ auth()->user()->name }}</p>
                            <p class="text-xs text-slate-500 truncate">{{ auth()->user()->email }}</p>
                        </div>

                        <div class="py-1">
                            <a href="{{ route('profile.edit', $navContext->organization ? ['organization' => $navContext->organization->slug] : []) }}"
                               class="flex items-center gap-2 px-3 py-1.5 mx-1 rounded-md text-[13px] text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800" role="menuitem">
                                Profile
                            </a>

                            @if($navContext->isAdmin)
                                <a href="{{ route('organizations.settings.show', $navContext->organization->slug) }}"
                                   class="flex items-center gap-2 px-3 py-1.5 mx-1 rounded-md text-[13px] text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800" role="menuitem">
                                    Settings
                                </a>
                            @endif
                        </div>

                        <div class="border-t border-slate-100 dark:border-slate-800 py-1">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit"
                                        class="w-full flex items-center gap-2 px-3 py-1.5 mx-1 rounded-md text-[13px] text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/20" role="menuitem">
                                    Sign out
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Mobile menu button -->
                @if($navContext->organization)
                <button id="mobile-menu-btn" type="button"
                    class="sm:hidden p-1.5 rounded-md text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                    aria-label="Open menu">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                @endif
                @endauth
            </div>
        </div>

        <!-- Mobile dropdown menu -->
        @if($navContext->organization)
        <div id="mobile-menu" class="hidden sm:hidden border-t border-slate-200 dark:border-slate-800 bg-white/95 dark:bg-slate-950/95 backdrop-blur-md">
            <div class="px-3 py-2 space-y-0.5">
                <a href="{{ route('organizations.index', $navContext->organization->slug) }}" class="mobile-nav-link {{ request()->routeIs('organizations.index') ? 'mobile-nav-link-active' : '' }}">Dashboard</a>
                <a href="{{ route('home', $navContext->organization->slug) }}" class="mobile-nav-link {{ request()->routeIs('home') || request()->routeIs('households.*') ? 'mobile-nav-link-active' : '' }}">Households</a>
                <a href="{{ route('contributions.index', $navContext->organization->slug) }}" class="mobile-nav-link {{ request()->routeIs('contributions.index') ? 'mobile-nav-link-active' : '' }}">Contributions</a>
                <a href="{{ route('expenses.index', $navContext->organization->slug) }}" class="mobile-nav-link {{ request()->routeIs('expenses.*') ? 'mobile-nav-link-active' : '' }}">Expenses</a>

                @if($navContext->canVerify)
                    <a href="{{ route('contributions.verify', $navContext->organization->slug) }}" class="mobile-nav-link flex items-center justify-between {{ request()->routeIs('contributions.verify*') ? 'mobile-nav-link-active' : '' }}">
                        <span>Verify payments</span>
                        @if(($pendingVerificationCount ?? 0) > 0)
                            <span class="inline-flex items-center justify-center min-w-[1.1rem] h-[1.1rem] px-1 rounded-md bg-rose-600 text-white text-[10px] font-semibold"
                                  data-count="{{ $pendingVerificationCount }}">{{ $pendingVerificationCount }}</span>
                        @endif
                    </a>
                @endif

                <p class="px-2 pt-3 pb-1 text-[10px] uppercase tracking-wider text-slate-400 font-semibold">Reports</p>
                <a href="{{ route('reports.contributions.index', $navContext->organization->slug) }}" class="mobile-nav-link">Contributions</a>
                <a href="{{ route('reports.expenses.index', $navContext->organization->slug) }}" class="mobile-nav-link">Expenses</a>
                <a href="{{ route('reports.delinquency.index', $navContext->organization->slug) }}" class="mobile-nav-link">Delinquency</a>

                <hr class="my-2 border-slate-200 dark:border-slate-800">

                @auth
                <a href="{{ route('profile.edit', $navContext->organization ? ['organization' => $navContext->organization->slug] : []) }}" class="mobile-nav-link">Profile</a>

                @if($navContext->isAdmin)
                    <a href="{{ route('organizations.settings.show', $navContext->organization->slug) }}" class="mobile-nav-link">Settings</a>
                @endif

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="mobile-nav-link w-full text-left text-rose-600">Sign out</button>
                </form>
                @endauth
            </div>
        </div>
        @endif
    </nav>

    <!-- Nav link styles — pill active state inside the segmented control. -->
    <style>
        .nav-pill {
            @apply inline-flex items-center px-2.5 py-1 rounded-md text-[13px] font-medium text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition-colors;
        }
        .nav-pill-active {
            @apply text-slate-900 dark:text-white bg-white dark:bg-slate-800 shadow-sm ring-1 ring-slate-200/60 dark:ring-slate-700/60;
        }
        .mobile-nav-link {
            @apply block px-2.5 py-2 rounded-md text-sm font-medium text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors;
        }
        .mobile-nav-link-active {
            @apply text-slate-900 dark:text-white bg-slate-100 dark:bg-slate-800;
        }
    </style>

    <!-- ===== PAGE WRAPPER ===== -->
    <div class="pt-14 min-h-screen flex flex-col">

        @auth
            @php
                $layoutPendingInvitations = \App\Models\OrganizationInvitation::where('email', auth()->user()->email)
                    ->pending()
                    ->with('organization')
                    ->latest()
                    ->get();
            @endphp
            @if($layoutPendingInvitations->isNotEmpty())
                <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8 mt-4">
                    <div class="rounded-xl border border-indigo-200 bg-indigo-50 px-5 py-4">
                        <div class="flex items-start gap-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mt-0.5 text-indigo-600 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                                <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
                            </svg>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-indigo-900">
                                    @if($layoutPendingInvitations->count() === 1)
                                        You have a pending invitation
                                    @else
                                        You have {{ $layoutPendingInvitations->count() }} pending invitations
                                    @endif
                                </p>
                                <ul class="mt-2 space-y-2">
                                    @foreach($layoutPendingInvitations as $invitation)
                                        <li class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 bg-white rounded-lg border border-indigo-100 px-4 py-2.5">
                                            <div class="text-sm text-gray-700">
                                                <span class="font-medium text-gray-900">{{ $invitation->organization->name }}</span>
                                                &mdash; join as
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-semibold bg-gray-100 text-gray-700">{{ $invitation->role()->label() }}</span>
                                                <span class="text-xs text-gray-500 ml-1">expires {{ $invitation->expires_at->format('M j') }}</span>
                                            </div>
                                            <a href="{{ URL::signedRoute('invitations.show', ['invitation' => $invitation]) }}"
                                               class="self-start sm:self-auto px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold">
                                                Review
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        @endauth

        <!-- Flash messages -->
        @if(session('success') || session('error') || session('info'))
        <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8 mt-4">
            @if(session('success'))
            <div class="flex items-center gap-3 p-4 rounded-xl bg-green-50 border border-green-200 text-green-800 text-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0 text-green-500" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
            @endif
            @if(session('error'))
            <div class="flex items-center gap-3 p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0 text-red-500" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                <span>{{ session('error') }}</span>
            </div>
            @endif
            @if(session('info'))
            <div class="flex items-center gap-3 p-4 rounded-xl bg-blue-50 border border-blue-200 text-blue-800 text-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0 text-blue-500" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 100 2 1 1 0 000-2zm-1 4a1 1 0 011-1h1a1 1 0 110 2v3a1 1 0 11-2 0v-4z" clip-rule="evenodd"/>
                </svg>
                <span>{{ session('info') }}</span>
            </div>
            @endif
        </div>
        @endif

        <!-- Main content -->
        <main class="flex-1 mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8 py-8">
            @yield('content')
        </main>

        <!-- ===== FOOTER ===== -->
        <footer class="border-t border-gray-200 bg-white">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between">
                <p class="text-xs text-gray-400">
                    &copy; {{ date('Y') }} BarangayBooks &mdash; Household books for Philippine HOAs
                </p>
                <p class="text-xs text-gray-400">v1.0</p>
            </div>
        </footer>
    </div>

    <!-- ===== SCRIPTS ===== -->
    @vite(['resources/js/app.js'])
    <script>
        // Dark mode toggle
        document.addEventListener('DOMContentLoaded', function () {
            var btn = document.getElementById('theme-toggle');
            if (!btn) return;
            btn.addEventListener('click', function () {
                var html = document.documentElement;
                if (html.classList.contains('dark')) {
                    html.classList.remove('dark');
                    localStorage.setItem('theme', 'light');
                } else {
                    html.classList.add('dark');
                    localStorage.setItem('theme', 'dark');
                }
                // Mirror the inline Tailwind dark:block / dark:hidden classes.
                document.querySelectorAll('[data-theme-icon]').forEach(function (el) {
                    var target = el.getAttribute('data-theme-icon');
                    if (target === 'sun') {
                        el.classList.toggle('hidden', !html.classList.contains('dark'));
                        el.classList.toggle('block', html.classList.contains('dark'));
                    } else if (target === 'moon') {
                        el.classList.toggle('hidden', html.classList.contains('dark'));
                        el.classList.toggle('block', !html.classList.contains('dark'));
                    }
                });
            });

            // Mobile menu
            var mobileBtn = document.getElementById('mobile-menu-btn');
            var mobileMenu = document.getElementById('mobile-menu');
            if (mobileBtn && mobileMenu) {
                mobileBtn.addEventListener('click', function () {
                    mobileMenu.classList.toggle('hidden');
                });
            }
        });
    </script>
</body>
</html>
