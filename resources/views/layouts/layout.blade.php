<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'HOA Manager') &mdash; HOA Manager</title>

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
    <nav class="fixed top-0 inset-x-0 z-40 h-14 bg-white border-b border-gray-200 shadow-sm">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 h-full flex items-center justify-between gap-4">

            <!-- Brand -->
            <a href="javascript:void(0)" class="flex items-center gap-2.5 flex-shrink-0 group">
                <!-- Simple house icon -->
                <span class="flex items-center justify-center w-8 h-8 rounded-lg bg-indigo-600 text-white shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4.5 h-4.5" viewBox="0 0 20 20" fill="currentColor" width="18" height="18">
                        <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/>
                    </svg>
                </span>
                <span class="text-base font-semibold text-gray-900 tracking-tight">HOA Manager</span>
            </a>

            @php
                // Resolve organization from view variable or route parameter
                $navOrg = $organization ?? request()->route('organization') ?? null;
            @endphp

            <!-- Nav links (center / left-of-center) — only shown when organization context exists -->
            @if($navOrg)
            <div class="hidden sm:flex items-center gap-3 flex-1 ml-4">
                @auth
                <a href="{{ route('organizations.index', $navOrg->slug) }}"
                   class="nav-link {{ request()->routeIs('organizations.index') ? 'nav-link-active' : '' }}">
                    Dashboard
                </a>

                <a href="{{ route('contributions.index', $navOrg->slug) }}"
                   class="nav-link {{ request()->routeIs('contributions.index') ? 'nav-link-active' : '' }}">
                    Contributions
                </a>

                <a href="{{ route('expenses.index', $navOrg->slug) }}"
                   class="nav-link {{ request()->routeIs('expenses.index') ? 'nav-link-active' : '' }}">
                    Expenses
                </a>

                <a href="{{ route('contributions.verify', $navOrg->slug) }}"
                   class="nav-link inline-flex items-center gap-1.5 {{ request()->routeIs('contributions.verify') ? 'nav-link-active' : '' }}">
                    Verify
                    @if(($pendingVerificationCount ?? 0) > 0)
                        <span class="verify-badge inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1.5 rounded-full bg-red-600 text-white text-xs font-semibold"
                              data-count="{{ $pendingVerificationCount }}">
                            {{ $pendingVerificationCount }}
                        </span>
                    @endif
                </a>
                @endauth
            </div>
            @endif

            <!-- Right actions -->
            <div class="flex items-center gap-2 ml-auto">
                <!-- Organization badge -->
                @if($navOrg)
                <span class="hidden md:inline-flex items-center gap-1.5 text-xs font-medium text-indigo-700 bg-indigo-50 px-2.5 py-1 rounded-full border border-indigo-200">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 110 2h-3a1 1 0 01-1-1v-2a1 1 0 00-1-1H9a1 1 0 00-1 1v2a1 1 0 01-1 1H4a1 1 0 110-2V4zm3 1h2v2H7V5zm2 4H7v2h2V9zm2-4h2v2h-2V5zm2 4h-2v2h2V9z" clip-rule="evenodd"/>
                    </svg>
                    {{ $navOrg->name }}
                </span>
                @endif

                <!-- Dark mode toggle -->
                <button id="theme-toggle" type="button"
                    class="p-2 rounded-lg text-gray-500 hover:bg-gray-100 transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    aria-label="Toggle dark mode">
                    <!-- Sun icon (shown in dark mode) -->
                    <svg id="icon-sun" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 hidden" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" clip-rule="evenodd"/>
                    </svg>
                    <!-- Moon icon (shown in light mode) -->
                    <svg id="icon-moon" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 block" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"/>
                    </svg>
                </button>

                @auth
                @php
                    $userInitials = collect(explode(' ', trim(auth()->user()->name)))
                        ->map(fn ($w) => mb_substr($w, 0, 1))
                        ->take(2)
                        ->join('');
                    $isAdmin = $navOrg
                        ? auth()->user()->organizations()
                            ->where('organizations.id', $navOrg->id)
                            ->wherePivot('role', 'admin')
                            ->exists()
                        : false;
                @endphp

                <div class="relative" x-data="{ open: false }" @click.outside="open = false" @keydown.escape="open = false">
                    <button type="button"
                            @click="open = !open"
                            :aria-expanded="open"
                            aria-haspopup="true"
                            class="flex items-center gap-1.5 p-1 pr-2 rounded-full hover:bg-gray-100 transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <span class="flex items-center justify-center w-8 h-8 rounded-full bg-indigo-600 text-white text-xs font-bold uppercase">
                            {{ $userInitials ?: '?' }}
                        </span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-500" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                    </button>

                    <div x-show="open" x-cloak x-transition
                         class="absolute right-0 mt-2 w-56 rounded-xl bg-white shadow-lg ring-1 ring-gray-200 py-1 z-50"
                         role="menu" aria-orientation="vertical">
                        <div class="px-4 py-3 border-b border-gray-100">
                            <p class="text-sm font-semibold text-gray-900 truncate">{{ auth()->user()->name }}</p>
                            <p class="text-xs text-gray-500 truncate">{{ auth()->user()->email }}</p>
                        </div>

                        <a href="{{ route('profile.edit', $navOrg ? ['organization' => $navOrg->slug] : []) }}"
                           class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" role="menuitem">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                            </svg>
                            Profile
                        </a>

                        @if($navOrg && $isAdmin)
                            <a href="{{ route('organizations.settings.show', $navOrg->slug) }}"
                               class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" role="menuitem">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"/>
                                </svg>
                                Settings
                            </a>
                        @endif

                        <div class="border-t border-gray-100 mt-1 pt-1">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit"
                                        class="w-full flex items-center gap-2 px-4 py-2 text-sm text-red-600 hover:bg-red-50" role="menuitem">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M3 4.25A2.25 2.25 0 015.25 2h5.5A2.25 2.25 0 0113 4.25v2a.75.75 0 01-1.5 0v-2a.75.75 0 00-.75-.75h-5.5a.75.75 0 00-.75.75v11.5c0 .414.336.75.75.75h5.5a.75.75 0 00.75-.75v-2a.75.75 0 011.5 0v2A2.25 2.25 0 0110.75 18h-5.5A2.25 2.25 0 013 15.75V4.25z"/>
                                        <path d="M6 10a.75.75 0 01.75-.75h6.69L11.97 7.78a.75.75 0 011.06-1.06l2.75 2.75a.75.75 0 010 1.06l-2.75 2.75a.75.75 0 01-1.06-1.06l1.47-1.47H6.75A.75.75 0 016 10z"/>
                                    </svg>
                                    Sign out
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                @endauth

                <!-- Mobile menu button -->
                @if($navOrg)
                <button id="mobile-menu-btn" type="button"
                    class="sm:hidden p-2 rounded-lg text-gray-500 hover:bg-gray-100 transition-colors"
                    aria-label="Open menu">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                @endif
            </div>
        </div>

        <!-- Mobile dropdown menu -->
        @if($navOrg)
        <div id="mobile-menu" class="hidden sm:hidden border-t border-gray-200 bg-white">
            <div class="px-3 py-2 space-y-1">
                <a href="{{ route('organizations.index', $navOrg->slug) }}" class="mobile-nav-link">Dashboard</a>
                <a href="{{ route('home', $navOrg->slug) }}" class="mobile-nav-link">Households</a>
                <a href="{{ route('contributions.index', $navOrg->slug) }}" class="mobile-nav-link">Contributions</a>
                <a href="{{ route('expenses.index', $navOrg->slug) }}" class="mobile-nav-link">Expenses</a>
                <a href="{{ route('contributions.verify', $navOrg->slug) }}" class="mobile-nav-link flex items-center justify-between">
                    <span>Verify Contributions</span>
                    @if(($pendingVerificationCount ?? 0) > 0)
                        <span class="verify-badge inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1.5 rounded-full bg-red-600 text-white text-xs font-semibold"
                              data-count="{{ $pendingVerificationCount }}">
                            {{ $pendingVerificationCount }}
                        </span>
                    @endif
                </a>

                <hr class="my-2 border-gray-200">

                @auth
                <a href="{{ route('profile.edit', $navOrg ? ['organization' => $navOrg->slug] : []) }}" class="mobile-nav-link">Profile</a>

                @php
                    $mobileIsAdmin = $navOrg
                        ? auth()->user()->organizations()
                            ->where('organizations.id', $navOrg->id)
                            ->wherePivot('role', 'admin')
                            ->exists()
                        : false;
                @endphp

                @if($navOrg && $mobileIsAdmin)
                    <a href="{{ route('organizations.settings.show', $navOrg->slug) }}" class="mobile-nav-link">Settings</a>
                @endif

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="mobile-nav-link w-full text-left text-red-600">Sign out</button>
                </form>
                @endauth
            </div>
        </div>
        @endif
    </nav>

    <!-- Shared inline styles for nav links (since we're on CDN tailwind, JIT is fully available) -->
    <style>
        .nav-link {
            @apply px-3 py-1.5 rounded-md text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-100 transition-colors;
        }
        .nav-link-active {
            @apply text-indigo-600 bg-indigo-50 hover:bg-indigo-50 hover:text-indigo-600
        }
        .mobile-nav-link {
            @apply block px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-100 transition-colors;
        }
    </style>

    <!-- ===== PAGE WRAPPER ===== -->
    <div class="pt-14 min-h-screen flex flex-col">

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
                    &copy; {{ date('Y') }} HOA Manager &mdash; Household Contribution System
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
