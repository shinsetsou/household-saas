<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/android-chrome-192x192.png" type="image/png" sizes="192x192">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">
    <meta name="theme-color" content="#4f46e5">
    <title>@yield('title', 'BarangayBooks') &mdash; BarangayBooks</title>
    @seo
    @stack('head')

    {{-- Chat widget helper: must be defined before Alpine hydrates x-data --}}
    <script>@include('partials.chat-widget-helper')</script>

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

    <!-- Nav link + mobile dropdown styles. Must use type="text/tailwindcss"
         so the CDN processes @apply; plain <style> is silently dropped. -->
    <style type="text/tailwindcss">
        .nav-link {
            @apply relative inline-flex items-center px-2 lg:px-2.5 pt-2.5 pb-2 text-[13px] sm:text-sm font-semibold text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white transition-colors whitespace-nowrap;
        }
        .nav-link::after {
            content: '';
            @apply absolute left-2 right-2 bottom-0 h-0.5 rounded-full bg-brand-600 dark:bg-brand-500 origin-center transition-transform duration-200;
            transform: scaleX(0);
        }
        .nav-link:hover::after {
            transform: scaleX(0.6);
        }
        .nav-link-active {
            @apply text-brand-700 dark:text-brand-200;
        }
        .nav-link-active::after {
            transform: scaleX(1);
        }
        .mobile-nav-link {
            @apply block px-3 py-2.5 rounded-xl text-sm font-medium text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors;
        }
        .mobile-nav-link-active {
            @apply text-brand-700 bg-brand-50 dark:text-brand-200 dark:bg-slate-800;
        }
    </style>

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
    <nav class="fixed top-0 inset-x-0 z-40 h-16 bg-white/80 dark:bg-slate-950/80 backdrop-blur-md border-b border-slate-200/70 dark:border-slate-800/70 shadow-sm">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 h-full flex items-center gap-2 sm:gap-4">

            <!-- Brand -->
            <a href="{{ $navContext->organization ? route('organizations.index', $navContext->organization->slug) : url('/') }}"
               class="flex items-center gap-2.5 flex-shrink-0 group">
                <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-gradient-to-br from-brand-500 to-violet-600 text-white font-bold shadow-sm shadow-brand-900/20 ring-1 ring-brand-700/10 group-hover:shadow-brand-glow transition">
                    <span class="text-sm">B</span>
                </span>
                <span class="hidden lg:inline text-base font-extrabold tracking-tight text-slate-900 dark:text-white whitespace-nowrap">
                    Barangay<span class="text-brand-600 dark:text-brand-500">Books</span>
                </span>
            </a>

            <!-- Nav links — only when an organization context exists -->
            @if($navContext->organization)
            <div class="hidden sm:flex items-center gap-1 lg:gap-2 flex-1 ml-3 min-w-0">
                @auth
                    <a href="{{ route('organizations.index', $navContext->organization->slug) }}"
                       class="nav-link {{ request()->routeIs('organizations.index') ? 'nav-link-active' : '' }}">
                        Dashboard
                    </a>

                    <a href="{{ route('home', $navContext->organization->slug) }}"
                       class="nav-link {{ request()->routeIs('home') || request()->routeIs('households.*') ? 'nav-link-active' : '' }}">
                        Households
                    </a>

                    <a href="{{ route('contributions.index', $navContext->organization->slug) }}"
                       class="nav-link {{ request()->routeIs('contributions.index') ? 'nav-link-active' : '' }}">
                        Contributions
                    </a>

                    <a href="{{ route('expenses.index', $navContext->organization->slug) }}"
                       class="nav-link {{ request()->routeIs('expenses.*') ? 'nav-link-active' : '' }}">
                        Expenses
                    </a>

                    @if($navContext->canVerify)
                        <a href="{{ route('contributions.verify', $navContext->organization->slug) }}"
                           class="nav-link inline-flex items-center gap-1.5 {{ request()->routeIs('contributions.verify*') ? 'nav-link-active' : '' }}">
                            Verify
                            @if(($pendingVerificationCount ?? 0) > 0)
                                <span class="relative inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1.5 rounded-full bg-rose-600 text-white text-[11px] font-bold"
                                      data-count="{{ $pendingVerificationCount }}">
                                    {{ $pendingVerificationCount }}
                                    <span class="absolute inset-0 rounded-full bg-rose-600 opacity-75 animate-ping"></span>
                                </span>
                            @endif
                        </a>
                    @endif

                    {{-- Reports dropdown --}}
                    <div x-data="{ open: false }" @click.outside="open = false" @keydown.escape="open = false" class="relative">
                        <button type="button"
                                @click="open = !open"
                                :aria-expanded="open"
                                class="nav-link inline-flex items-center gap-1 {{ request()->routeIs('reports.*') ? 'nav-link-active' : '' }}">
                            Reports
                            <svg class="w-3.5 h-3.5 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="open" x-cloak x-transition
                             class="absolute left-0 mt-2 w-56 rounded-xl bg-white dark:bg-slate-900 shadow-lg ring-1 ring-slate-200 dark:ring-slate-700 py-1.5 z-50">
                            <a href="{{ route('reports.contributions.index', $navContext->organization->slug) }}"
                               class="flex items-center gap-2 px-4 py-2 text-sm text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800">
                                <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                                Contributions report
                            </a>
                            <a href="{{ route('reports.expenses.index', $navContext->organization->slug) }}"
                               class="flex items-center gap-2 px-4 py-2 text-sm text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800">
                                <svg class="w-4 h-4 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l-4-4 4-4m6 8l4-4-4-4"/></svg>
                                Expenses report
                            </a>
                            <a href="{{ route('reports.delinquency.index', $navContext->organization->slug) }}"
                               class="flex items-center gap-2 px-4 py-2 text-sm text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800">
                                <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                Delinquency report
                            </a>
                        </div>
                    </div>
                @endauth
            </div>
            @endif

            <!-- Right actions -->
            <div class="flex items-center gap-1 sm:gap-2 ml-auto flex-shrink-0">

                @auth
                <!-- Dark mode toggle (desktop only — moves into the mobile menu on small screens) -->
                <button id="theme-toggle" type="button"
                    class="hidden sm:inline-flex p-2 rounded-lg text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors focus:outline-none focus:ring-2 focus:ring-brand-500"
                    aria-label="Toggle dark mode">
                    <svg id="icon-sun" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 hidden dark:block" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" clip-rule="evenodd"/>
                    </svg>
                    <svg id="icon-moon" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 block dark:hidden" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"/>
                    </svg>
                </button>

                {{-- User dropdown — desktop: avatar + chevron + Alpine dropdown.
                     Mobile: avatar only, no chevron, no dropdown — profile / settings /
                     sign-out are reachable from the hamburger menu below. --}}
                <div class="relative hidden sm:block" x-data="{ open: false }" @click.outside="open = false" @keydown.escape="open = false">
                    <button type="button"
                            @click="open = !open"
                            :aria-expanded="open"
                            aria-haspopup="true"
                            class="flex items-center gap-1.5 p-1 pr-2 rounded-full hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors focus:outline-none focus:ring-2 focus:ring-brand-500">
                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-gradient-to-br from-brand-500 to-violet-600 text-white text-xs font-bold uppercase ring-1 ring-brand-700/10">
                            {{ $navContext->userInitials }}
                        </span>
                        <svg class="h-4 w-4 text-slate-500 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>

                    <div x-show="open" x-cloak x-transition
                         class="absolute right-0 mt-2 w-60 rounded-xl bg-white dark:bg-slate-900 shadow-lg ring-1 ring-slate-200 dark:ring-slate-700 py-1.5 z-50"
                         role="menu" aria-orientation="vertical">
                        <div class="px-4 py-3 border-b border-slate-100 dark:border-slate-800">
                            <p class="text-sm font-semibold text-slate-900 dark:text-white truncate">{{ auth()->user()->name }}</p>
                            <p class="text-xs text-slate-500 truncate">{{ auth()->user()->email }}</p>
                        </div>

                        <a href="{{ route('profile.edit', $navContext->organization ? ['organization' => $navContext->organization->slug] : []) }}"
                           class="flex items-center gap-2 px-4 py-2 text-sm text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800" role="menuitem">
                            <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            Profile
                        </a>

                        @if($navContext->isAdmin)
                            <a href="{{ route('organizations.settings.show', $navContext->organization->slug) }}"
                               class="flex items-center gap-2 px-4 py-2 text-sm text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800" role="menuitem">
                                <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                Settings
                            </a>
                        @endif

                        <div class="border-t border-slate-100 dark:border-slate-800 mt-1 pt-1">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit"
                                        class="w-full flex items-center gap-2 px-4 py-2 text-sm text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/20" role="menuitem">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                    Sign out
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- Mobile-only avatar chip (decorative — the actual account
                     menu lives inside the hamburger dropdown below). --}}
                <div class="sm:hidden flex items-center" aria-hidden="true">
                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-gradient-to-br from-brand-500 to-violet-600 text-white text-xs font-bold uppercase ring-1 ring-brand-700/10">
                        {{ $navContext->userInitials }}
                    </span>
                </div>

                <!-- Mobile menu button (far right of the bar) -->
                @if($navContext->organization)
                <button id="mobile-menu-btn" type="button"
                    class="sm:hidden p-2 rounded-lg text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors focus:outline-none focus:ring-2 focus:ring-brand-500"
                    aria-label="Open menu"
                    aria-expanded="false"
                    aria-controls="mobile-menu">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                @endif
                @endauth
            </div>
        </div>

        <!-- Mobile dropdown menu -->
        @if($navContext->organization)
        <div id="mobile-menu" class="hidden sm:hidden border-t border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900">
            <div class="px-3 py-2 space-y-1">
                @auth
                <a href="{{ route('organizations.index', $navContext->organization->slug) }}" class="mobile-nav-link {{ request()->routeIs('organizations.index') ? 'mobile-nav-link-active' : '' }}">Dashboard</a>
                <a href="{{ route('home', $navContext->organization->slug) }}" class="mobile-nav-link {{ request()->routeIs('home') || request()->routeIs('households.*') ? 'mobile-nav-link-active' : '' }}">Households</a>
                <a href="{{ route('contributions.index', $navContext->organization->slug) }}" class="mobile-nav-link {{ request()->routeIs('contributions.index') ? 'mobile-nav-link-active' : '' }}">Contributions</a>
                <a href="{{ route('expenses.index', $navContext->organization->slug) }}" class="mobile-nav-link {{ request()->routeIs('expenses.*') ? 'mobile-nav-link-active' : '' }}">Expenses</a>

                @if($navContext->canVerify)
                    <a href="{{ route('contributions.verify', $navContext->organization->slug) }}" class="mobile-nav-link flex items-center justify-between {{ request()->routeIs('contributions.verify*') ? 'mobile-nav-link-active' : '' }}">
                        <span>Verify payments</span>
                        @if(($pendingVerificationCount ?? 0) > 0)
                            <span class="verify-badge inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1.5 rounded-full bg-rose-600 text-white text-xs font-semibold"
                                  data-count="{{ $pendingVerificationCount }}">{{ $pendingVerificationCount }}</span>
                        @endif
                    </a>
                @endif

                <p class="px-3 pt-3 pb-1 text-[10px] uppercase tracking-wider text-slate-400 dark:text-slate-500 font-bold">Reports</p>
                <a href="{{ route('reports.contributions.index', $navContext->organization->slug) }}" class="mobile-nav-link">Contributions</a>
                <a href="{{ route('reports.expenses.index', $navContext->organization->slug) }}" class="mobile-nav-link">Expenses</a>
                <a href="{{ route('reports.delinquency.index', $navContext->organization->slug) }}" class="mobile-nav-link">Delinquency</a>

                <hr class="my-2 border-slate-200 dark:border-slate-800">

                {{-- Account block (only when authed) — replaces the desktop
                     Alpine dropdown. --}}
                <div class="px-3 pt-2 pb-1 flex items-center gap-3">
                    <span class="inline-flex items-center justify-center w-9 h-9 rounded-full bg-gradient-to-br from-brand-500 to-violet-600 text-white text-xs font-bold uppercase ring-1 ring-brand-700/10">
                        {{ $navContext->userInitials }}
                    </span>
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-slate-900 dark:text-white truncate">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-slate-500 truncate">{{ auth()->user()->email }}</p>
                    </div>
                </div>

                <a href="{{ route('profile.edit', $navContext->organization ? ['organization' => $navContext->organization->slug] : []) }}" class="mobile-nav-link">Profile</a>

                @if($navContext->isAdmin)
                    <a href="{{ route('organizations.settings.show', $navContext->organization->slug) }}" class="mobile-nav-link">Settings</a>
                @endif

                <button type="button" id="mobile-theme-toggle"
                        class="mobile-nav-link w-full text-left flex items-center justify-between">
                    <span class="flex items-center gap-2">
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"/>
                        </svg>
                        <span>Theme</span>
                    </span>
                    <span class="text-xs text-slate-500" data-theme-label>{{ \App\Support\NavContext::currentThemeLabel() }}</span>
                </button>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="mobile-nav-link w-full text-left text-rose-600">Sign out</button>
                </form>
                @endauth
            </div>
        </div>
        @endif
    </nav>

    <!-- ===== PAGE WRAPPER ===== -->
    <div class="pt-16 min-h-screen flex flex-col">

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
        // Dark mode + mobile menu toggle
        document.addEventListener('DOMContentLoaded', function () {
            var html = document.documentElement;

            function syncThemeLabel() {
                var isDark = html.classList.contains('dark');
                document.querySelectorAll('[data-theme-label]').forEach(function (el) {
                    el.textContent = isDark ? 'Dark' : 'Light';
                });
            }

            function toggleTheme() {
                if (html.classList.contains('dark')) {
                    html.classList.remove('dark');
                    localStorage.setItem('theme', 'light');
                } else {
                    html.classList.add('dark');
                    localStorage.setItem('theme', 'dark');
                }
                syncThemeLabel();
            }

            var btn = document.getElementById('theme-toggle');
            if (btn) btn.addEventListener('click', toggleTheme);

            var mobileThemeBtn = document.getElementById('mobile-theme-toggle');
            if (mobileThemeBtn) mobileThemeBtn.addEventListener('click', toggleTheme);

            syncThemeLabel();

            // Mobile menu
            var mobileBtn = document.getElementById('mobile-menu-btn');
            var mobileMenu = document.getElementById('mobile-menu');
            if (mobileBtn && mobileMenu) {
                mobileBtn.addEventListener('click', function () {
                    var willOpen = mobileMenu.classList.contains('hidden');
                    mobileMenu.classList.toggle('hidden');
                    mobileBtn.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
                });
            }
        });
    </script>

    @auth
        @if($navContext->canVerify && $navContext->organization)
            <x-chat-widget
                state="support"
                endpoint="/api/ai/support/message"
                :organization-slug="$navContext->organization->slug"
                greeting="Hi! Ask anything about using BarangayBooks — imports, billing, reports."
                placeholder="Ask about CSV import, billing, reports..."
            />
        @endif
    @endauth

    @stack('scripts')
</body>
</html>