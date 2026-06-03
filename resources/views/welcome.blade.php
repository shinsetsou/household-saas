<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BarangayBooks &mdash; HOA dues collection without the group chat chaos</title>
    <meta name="description" content="BarangayBooks helps Philippine Homeowners Associations collect monthly dues, verify payments with photos, and export audit-ready reports. Free during beta.">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />

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

    <script>
        (function () {
            var stored = localStorage.getItem('theme');
            if (stored === 'light') {
                document.documentElement.classList.remove('dark');
            } else if (stored === 'dark') {
                document.documentElement.classList.add('dark');
            } else if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        body { font-family: 'Inter', ui-sans-serif, system-ui, sans-serif; }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 9999px; }
        .dark ::-webkit-scrollbar-thumb { background: #374151; }
        .hero-grid {
            background-image:
                linear-gradient(to right, rgb(99 102 241 / 0.06) 1px, transparent 1px),
                linear-gradient(to bottom, rgb(99 102 241 / 0.06) 1px, transparent 1px);
            background-size: 32px 32px;
            mask-image: radial-gradient(ellipse 80% 60% at 50% 0%, #000 40%, transparent 100%);
        }
        .dark .hero-grid {
            background-image:
                linear-gradient(to right, rgb(99 102 241 / 0.12) 1px, transparent 1px),
                linear-gradient(to bottom, rgb(99 102 241 / 0.12) 1px, transparent 1px);
        }
    </style>
</head>
<body class="antialiased bg-white text-slate-900 dark:bg-slate-950 dark:text-slate-100">

    {{-- Top announcement bar --}}
    <div class="bg-brand-600 text-white text-center text-xs sm:text-sm py-2 px-4">
        <span class="font-semibold">Free during beta</span>
        <span class="opacity-80 hidden sm:inline">&nbsp;&middot;&nbsp;Founding HOAs lock in launch pricing</span>
        <a href="#pricing" class="underline underline-offset-2 ml-2 font-medium hover:opacity-90">See details &rarr;</a>
    </div>

    {{-- Sticky Nav --}}
    <header x-data="{ open: false }" class="sticky top-0 z-40 backdrop-blur bg-white/80 dark:bg-slate-950/80 border-b border-slate-200/60 dark:border-slate-800/60">
        <nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <a href="/" class="flex items-center gap-2 group">
                <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 text-white font-bold shadow-sm shadow-brand-900/20">B</span>
                <span class="font-bold text-lg tracking-tight">Barangay<span class="text-brand-600 dark:text-brand-500">Books</span></span>
            </a>

            <div class="hidden md:flex items-center gap-8 text-sm font-medium text-slate-600 dark:text-slate-300">
                <a href="#features" class="hover:text-slate-900 dark:hover:text-white transition">Features</a>
                <a href="#how" class="hover:text-slate-900 dark:hover:text-white transition">How it works</a>
                <a href="#pricing" class="hover:text-slate-900 dark:hover:text-white transition">Pricing</a>
                <a href="#faq" class="hover:text-slate-900 dark:hover:text-white transition">FAQ</a>
            </div>

            <div class="hidden md:flex items-center gap-3">
                <a href="{{ route('login') }}" class="text-sm font-medium text-slate-700 dark:text-slate-200 hover:text-slate-900 dark:hover:text-white px-3 py-2 rounded-lg transition">Log in</a>
                <a href="{{ route('register') }}" class="text-sm font-semibold bg-brand-600 hover:bg-brand-700 text-white px-4 py-2 rounded-lg shadow-sm shadow-brand-900/10 transition">Start free trial</a>
            </div>

            <button @click="open = !open" class="md:hidden inline-flex items-center justify-center p-2 rounded-lg text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800" aria-label="Toggle menu">
                <svg x-show="!open" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                <svg x-show="open" x-cloak class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </nav>

        {{-- Mobile menu --}}
        <div x-show="open" x-cloak x-transition class="md:hidden border-t border-slate-200 dark:border-slate-800 px-4 py-4 space-y-2 bg-white dark:bg-slate-950">
            <a href="#features" @click="open=false" class="block py-2 text-slate-700 dark:text-slate-200 font-medium">Features</a>
            <a href="#how" @click="open=false" class="block py-2 text-slate-700 dark:text-slate-200 font-medium">How it works</a>
            <a href="#pricing" @click="open=false" class="block py-2 text-slate-700 dark:text-slate-200 font-medium">Pricing</a>
            <a href="#faq" @click="open=false" class="block py-2 text-slate-700 dark:text-slate-200 font-medium">FAQ</a>
            <div class="pt-2 flex gap-2">
                <a href="{{ route('login') }}" class="flex-1 text-center text-sm font-medium border border-slate-300 dark:border-slate-700 px-4 py-2 rounded-lg">Log in</a>
                <a href="{{ route('register') }}" class="flex-1 text-center text-sm font-semibold bg-brand-600 text-white px-4 py-2 rounded-lg">Start free trial</a>
            </div>
        </div>
    </header>

    <main>

        {{-- HERO --}}
        <section class="relative overflow-hidden">
            <div class="absolute inset-0 hero-grid pointer-events-none"></div>
            <div class="absolute top-24 -right-32 w-[480px] h-[480px] bg-brand-500/20 dark:bg-brand-500/10 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute -top-10 -left-32 w-[400px] h-[400px] bg-emerald-400/10 rounded-full blur-3xl pointer-events-none"></div>

            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-16 sm:pt-24 pb-20 sm:pb-28">
                <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">
                    <div>
                        <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-brand-50 text-brand-700 dark:bg-brand-900/30 dark:text-brand-200 ring-1 ring-brand-200/60 dark:ring-brand-700/40">
                            <span class="w-1.5 h-1.5 rounded-full bg-brand-500 animate-pulse"></span>
                            Built for Philippine HOAs
                        </span>
                        <h1 class="mt-5 text-4xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight leading-[1.05]">
                            Collect monthly dues without the
                            <span class="bg-gradient-to-r from-brand-600 to-brand-400 bg-clip-text text-transparent">group chat chaos</span>.
                        </h1>
                        <p class="mt-6 text-lg text-slate-600 dark:text-slate-300 leading-relaxed max-w-xl">
                            BarangayBooks is the modern ledger for homeowners associations. Verified photo payments, instant delinquency reports, and an audit trail your board can trust &mdash; replacing the messy GC threads and dog-eared notebooks.
                        </p>

                        <div class="mt-8 flex flex-col sm:flex-row gap-3">
                            <a href="{{ route('register') }}" class="inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-semibold shadow-lg shadow-brand-900/20 transition">
                                Start free trial
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                            </a>
                            <a href="#how" class="inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl border border-slate-300 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-900 font-semibold transition">
                                See how it works
                            </a>
                        </div>

                        <div class="mt-6 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-slate-500 dark:text-slate-400">
                            <span class="inline-flex items-center gap-1.5"><svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>No credit card</span>
                            <span class="inline-flex items-center gap-1.5"><svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>Free during beta</span>
                            <span class="inline-flex items-center gap-1.5"><svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>Export your data anytime</span>
                        </div>
                    </div>

                    {{-- Mock dashboard preview --}}
                    <div class="relative">
                        <div class="absolute -inset-4 bg-gradient-to-br from-brand-500/20 to-emerald-400/20 rounded-3xl blur-2xl"></div>
                        <div class="relative rounded-2xl bg-white dark:bg-slate-900 ring-1 ring-slate-200 dark:ring-slate-800 shadow-2xl shadow-slate-900/10 overflow-hidden">
                            <div class="flex items-center gap-1.5 px-4 py-3 border-b border-slate-200 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-900/60">
                                <span class="w-2.5 h-2.5 rounded-full bg-red-400"></span>
                                <span class="w-2.5 h-2.5 rounded-full bg-yellow-400"></span>
                                <span class="w-2.5 h-2.5 rounded-full bg-green-400"></span>
                                <span class="ml-3 text-xs text-slate-500 dark:text-slate-400 font-mono">barangaybooks.app/sunshine-hoa/contributions</span>
                            </div>
                            <div class="p-5">
                                <div class="flex items-center justify-between mb-4">
                                    <div>
                                        <p class="text-xs uppercase tracking-wider text-slate-500 dark:text-slate-400 font-semibold">Collected this month</p>
                                        <p class="text-2xl font-bold text-slate-900 dark:text-white mt-1">&#8369;48,200.00</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-xs uppercase tracking-wider text-slate-500 dark:text-slate-400 font-semibold">Verified</p>
                                        <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">241 / 250</p>
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    @php
                                        $mock = [
                                            ['B7 L12', 'Cruz Family', 200.00, 'verified'],
                                            ['B3 L04', 'Reyes Family', 200.00, 'verified'],
                                            ['B9 L21', 'Santos Family', 200.00, 'pending'],
                                            ['B1 L08', 'Dela Cruz', 100.00, 'lacking'],
                                            ['B5 L17', 'Mendoza Family', 200.00, 'verified'],
                                        ];
                                    @endphp
                                    @foreach($mock as [$bl, $name, $amt, $status])
                                        <div class="flex items-center justify-between py-2.5 px-3 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800/50 transition">
                                            <div class="flex items-center gap-3 min-w-0">
                                                <span class="font-mono text-xs px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">{{ $bl }}</span>
                                                <span class="text-sm font-medium text-slate-700 dark:text-slate-200 truncate">{{ $name }}</span>
                                            </div>
                                            <div class="flex items-center gap-3">
                                                <span class="text-sm font-semibold tabular-nums">&#8369;{{ number_format($amt, 2) }}</span>
                                                @if($status === 'verified')
                                                    <span class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-1 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
                                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 010 1.4l-8 8a1 1 0 01-1.4 0l-4-4a1 1 0 011.4-1.4L8 12.6l7.3-7.3a1 1 0 011.4 0z" clip-rule="evenodd"/></svg>
                                                        Verified
                                                    </span>
                                                @elseif($status === 'pending')
                                                    <span class="inline-flex items-center text-xs font-semibold px-2 py-1 rounded-full bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-300">Pending</span>
                                                @else
                                                    <span class="inline-flex items-center text-xs font-semibold px-2 py-1 rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300">Lacking</span>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- PROBLEM / SOLUTION --}}
        <section class="bg-slate-50 dark:bg-slate-900/40 border-y border-slate-200 dark:border-slate-800">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 sm:py-24">
                <div class="max-w-2xl">
                    <p class="text-sm font-semibold text-brand-600 dark:text-brand-400 uppercase tracking-wider">The problem</p>
                    <h2 class="mt-2 text-3xl sm:text-4xl font-bold tracking-tight">Running an HOA shouldn't feel like a part-time job.</h2>
                </div>

                <div class="mt-12 grid md:grid-cols-3 gap-6">
                    <div class="rounded-2xl bg-white dark:bg-slate-900 ring-1 ring-slate-200 dark:ring-slate-800 p-6">
                        <div class="w-10 h-10 rounded-lg bg-red-50 dark:bg-red-900/30 flex items-center justify-center text-red-600 dark:text-red-400 mb-4">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                        </div>
                        <h3 class="font-semibold text-lg">Receipts buried in GC chats</h3>
                        <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">Screenshots scroll past. Treasurers spend weekends sifting through Viber and Messenger threads to reconcile a single month.</p>
                    </div>

                    <div class="rounded-2xl bg-white dark:bg-slate-900 ring-1 ring-slate-200 dark:ring-slate-800 p-6">
                        <div class="w-10 h-10 rounded-lg bg-amber-50 dark:bg-amber-900/30 flex items-center justify-center text-amber-600 dark:text-amber-400 mb-4">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        </div>
                        <h3 class="font-semibold text-lg">Manual ledgers, missing months</h3>
                        <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">Notebooks get lost. Excel files diverge across officers. By election time, no one is sure who owes what.</p>
                    </div>

                    <div class="rounded-2xl bg-white dark:bg-slate-900 ring-1 ring-slate-200 dark:ring-slate-800 p-6">
                        <div class="w-10 h-10 rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-600 dark:text-slate-300 mb-4">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <h3 class="font-semibold text-lg">No proof of who paid what</h3>
                        <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">Disputes drag on for months. Without a verifiable trail, every collection turnover risks accusations and bad blood.</p>
                    </div>
                </div>

                <div class="mt-14 flex items-start gap-4 max-w-3xl">
                    <div class="shrink-0 w-10 h-10 rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 text-white flex items-center justify-center font-bold">B</div>
                    <p class="text-lg text-slate-700 dark:text-slate-200">
                        <span class="font-semibold text-slate-900 dark:text-white">BarangayBooks fixes this.</span> One source of truth for your association &mdash; receipts attached, status verified, reports one click away.
                    </p>
                </div>
            </div>
        </section>

        {{-- FEATURES --}}
        <section id="features" class="py-20 sm:py-28">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="max-w-2xl mx-auto text-center">
                    <p class="text-sm font-semibold text-brand-600 dark:text-brand-400 uppercase tracking-wider">Features</p>
                    <h2 class="mt-2 text-3xl sm:text-4xl font-bold tracking-tight">Everything your board needs. Nothing it doesn't.</h2>
                    <p class="mt-4 text-slate-600 dark:text-slate-300">Designed with treasurers and presidents who'd rather be doing literally anything else on a Sunday afternoon.</p>
                </div>

                <div class="mt-16 grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    @php
                        $features = [
                            ['title' => 'Block + Lot resident lookup', 'desc' => 'Members check their own payment history with their block and lot. Rate-limited against scraping. No accounts required.', 'icon' => 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z'],
                            ['title' => 'Photo-verified payments', 'desc' => 'Members snap their GCash or deposit slip. Officers verify or reject with a single tap. Proof attached to every record.', 'icon' => 'M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z M15 13a3 3 0 11-6 0 3 3 0 016 0z'],
                            ['title' => 'PDF, Excel, CSV reports', 'desc' => 'Contributions, expenses, delinquency &mdash; exported in the format your barangay, auditor, or AGM actually accepts.', 'icon' => 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                            ['title' => 'Delinquency tracker', 'desc' => 'Auto-flag households below your set monthly due. Drill into months missing, amounts owed, and last payment date.', 'icon' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'],
                            ['title' => 'Tamper-evident audit log', 'desc' => 'Every verification, deletion, and import is recorded with the user and timestamp. Defensible records when boards turn over.', 'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'],
                            ['title' => 'Multi-organization', 'desc' => 'Manage one HOA or twenty from a single login. Property managers and barangay officers handle multiple associations cleanly.', 'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
                        ];
                    @endphp

                    @foreach($features as $f)
                        <div class="group relative rounded-2xl bg-white dark:bg-slate-900 ring-1 ring-slate-200 dark:ring-slate-800 p-6 hover:ring-brand-300 dark:hover:ring-brand-700 hover:shadow-lg hover:shadow-brand-900/5 transition">
                            <div class="w-11 h-11 rounded-xl bg-brand-50 dark:bg-brand-900/30 flex items-center justify-center text-brand-600 dark:text-brand-400 mb-5">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $f['icon'] }}"/></svg>
                            </div>
                            <h3 class="font-semibold text-lg">{{ $f['title'] }}</h3>
                            <p class="mt-2 text-sm text-slate-600 dark:text-slate-400 leading-relaxed">{!! $f['desc'] !!}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- HOW IT WORKS --}}
        <section id="how" class="bg-slate-50 dark:bg-slate-900/40 border-y border-slate-200 dark:border-slate-800 py-20 sm:py-28">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="max-w-2xl mx-auto text-center">
                    <p class="text-sm font-semibold text-brand-600 dark:text-brand-400 uppercase tracking-wider">How it works</p>
                    <h2 class="mt-2 text-3xl sm:text-4xl font-bold tracking-tight">From signup to first peso collected in one afternoon.</h2>
                </div>

                <div class="mt-16 grid md:grid-cols-3 gap-8 relative">
                    {{-- Connector line --}}
                    <div class="hidden md:block absolute top-7 left-[16.66%] right-[16.66%] h-px bg-gradient-to-r from-brand-200 via-brand-400 to-brand-200 dark:from-brand-800 dark:via-brand-600 dark:to-brand-800"></div>

                    <div class="relative text-center">
                        <div class="relative inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-white dark:bg-slate-900 ring-2 ring-brand-500 text-brand-600 dark:text-brand-400 font-bold text-xl shadow-lg shadow-brand-900/10">1</div>
                        <h3 class="mt-5 font-semibold text-lg">Create your organization</h3>
                        <p class="mt-2 text-sm text-slate-600 dark:text-slate-400 max-w-xs mx-auto">Sign up, name your HOA, set the monthly due amount. Five fields total.</p>
                    </div>

                    <div class="relative text-center">
                        <div class="relative inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-white dark:bg-slate-900 ring-2 ring-brand-500 text-brand-600 dark:text-brand-400 font-bold text-xl shadow-lg shadow-brand-900/10">2</div>
                        <h3 class="mt-5 font-semibold text-lg">Import your households</h3>
                        <p class="mt-2 text-sm text-slate-600 dark:text-slate-400 max-w-xs mx-auto">Upload a CSV with name, block, and lot. Two hundred households take under a minute.</p>
                    </div>

                    <div class="relative text-center">
                        <div class="relative inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-white dark:bg-slate-900 ring-2 ring-brand-500 text-brand-600 dark:text-brand-400 font-bold text-xl shadow-lg shadow-brand-900/10">3</div>
                        <h3 class="mt-5 font-semibold text-lg">Start collecting & verifying</h3>
                        <p class="mt-2 text-sm text-slate-600 dark:text-slate-400 max-w-xs mx-auto">Members log payments with photo proof. Officers verify in two taps. Reports update live.</p>
                    </div>
                </div>
            </div>
        </section>

        {{-- TRUST STRIP --}}
        <section class="py-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="rounded-3xl bg-gradient-to-br from-emerald-50 via-white to-emerald-50 dark:from-emerald-950/30 dark:via-slate-900 dark:to-emerald-950/30 ring-1 ring-emerald-200 dark:ring-emerald-900/50 p-8 sm:p-10">
                    <div class="text-center mb-8">
                        <p class="text-sm font-semibold text-emerald-700 dark:text-emerald-400 uppercase tracking-wider">Built for trust</p>
                        <h2 class="mt-2 text-2xl sm:text-3xl font-bold tracking-tight">Your members' money. Treated like it.</h2>
                    </div>

                    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div class="flex flex-col items-center text-center">
                            <div class="w-12 h-12 rounded-xl bg-emerald-100 dark:bg-emerald-900/40 flex items-center justify-center text-emerald-700 dark:text-emerald-300 mb-3">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            </div>
                            <h3 class="font-semibold">Encrypted storage</h3>
                            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Receipts and records encrypted at rest and in transit.</p>
                        </div>
                        <div class="flex flex-col items-center text-center">
                            <div class="w-12 h-12 rounded-xl bg-emerald-100 dark:bg-emerald-900/40 flex items-center justify-center text-emerald-700 dark:text-emerald-300 mb-3">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            </div>
                            <h3 class="font-semibold">Role-based access</h3>
                            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Treasurer, president, auditor &mdash; each sees only what they need.</p>
                        </div>
                        <div class="flex flex-col items-center text-center">
                            <div class="w-12 h-12 rounded-xl bg-emerald-100 dark:bg-emerald-900/40 flex items-center justify-center text-emerald-700 dark:text-emerald-300 mb-3">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                            </div>
                            <h3 class="font-semibold">Tamper-evident logs</h3>
                            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Every action recorded with user and timestamp. Defensible at turnover.</p>
                        </div>
                        <div class="flex flex-col items-center text-center">
                            <div class="w-12 h-12 rounded-xl bg-emerald-100 dark:bg-emerald-900/40 flex items-center justify-center text-emerald-700 dark:text-emerald-300 mb-3">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <h3 class="font-semibold">PH-hosted option</h3>
                            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Local data residency available for HOAs with sovereignty requirements.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- PRICING --}}
        <section id="pricing" class="py-20 sm:py-28">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="max-w-2xl mx-auto text-center">
                    <p class="text-sm font-semibold text-brand-600 dark:text-brand-400 uppercase tracking-wider">Pricing</p>
                    <h2 class="mt-2 text-3xl sm:text-4xl font-bold tracking-tight">Free while we build with you.</h2>
                    <p class="mt-4 text-slate-600 dark:text-slate-300">Founding HOAs lock in launch pricing forever. No card on file, no surprise invoices.</p>
                </div>

                <div class="mt-12 max-w-md mx-auto">
                    <div class="relative rounded-3xl bg-gradient-to-br from-white to-brand-50/50 dark:from-slate-900 dark:to-brand-950/30 ring-2 ring-brand-500 p-8 shadow-2xl shadow-brand-900/10">
                        <span class="absolute -top-3 left-1/2 -translate-x-1/2 inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-brand-600 text-white shadow-lg">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-300 animate-pulse"></span>
                            Beta program
                        </span>

                        <h3 class="text-xl font-bold">Founding HOA</h3>
                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">For associations who join us during beta.</p>

                        <div class="mt-6 flex items-baseline gap-2">
                            <span class="text-5xl font-extrabold tracking-tight">&#8369;0</span>
                            <span class="text-slate-500 dark:text-slate-400">/ month</span>
                        </div>

                        <ul class="mt-6 space-y-3 text-sm">
                            <li class="flex items-start gap-3"><svg class="w-5 h-5 text-emerald-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg><span>Unlimited households &amp; contributions</span></li>
                            <li class="flex items-start gap-3"><svg class="w-5 h-5 text-emerald-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg><span>All reports (PDF, Excel, CSV)</span></li>
                            <li class="flex items-start gap-3"><svg class="w-5 h-5 text-emerald-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg><span>Photo-verified payments</span></li>
                            <li class="flex items-start gap-3"><svg class="w-5 h-5 text-emerald-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg><span>Multi-organization access</span></li>
                            <li class="flex items-start gap-3"><svg class="w-5 h-5 text-emerald-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg><span>Email support &amp; founder access</span></li>
                            <li class="flex items-start gap-3"><svg class="w-5 h-5 text-emerald-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg><span>Launch pricing locked in forever</span></li>
                        </ul>

                        <a href="{{ route('register') }}" class="mt-8 block w-full text-center px-6 py-3.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-semibold shadow-lg shadow-brand-900/20 transition">
                            Claim your free spot
                        </a>
                        <p class="mt-3 text-center text-xs text-slate-500 dark:text-slate-400">No credit card required.</p>
                    </div>
                </div>
            </div>
        </section>

        {{-- FAQ --}}
        <section id="faq" class="bg-slate-50 dark:bg-slate-900/40 border-y border-slate-200 dark:border-slate-800 py-20 sm:py-28">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center">
                    <p class="text-sm font-semibold text-brand-600 dark:text-brand-400 uppercase tracking-wider">FAQ</p>
                    <h2 class="mt-2 text-3xl sm:text-4xl font-bold tracking-tight">Questions HOA boards ask first.</h2>
                </div>

                <div class="mt-12 space-y-3" x-data="{ open: 0 }">
                    @php
                        $faqs = [
                            ['Who owns the data?', 'Your HOA does. Always. We are the custodian, not the owner. Every record &mdash; households, contributions, photos, reports &mdash; belongs to your association.'],
                            ['Can we export everything?', 'Yes. Contributions, expenses, delinquency, and household lists export to PDF, Excel, and CSV with one click. Even if you leave us, you walk away with your full ledger.'],
                            ['Is it secure enough for handling member dues?', 'Records and uploaded receipts are encrypted at rest and in transit. Role-based access limits what each officer can see and do. A tamper-evident audit log records every state change with the user and timestamp.'],
                            ['What if a new board comes in and wants something else?', 'Export everything in your preferred format and hand it off. No lock-in. No data hostage. We would rather you leave happy than stay frustrated.'],
                            ['How fast can we onboard?', 'Most HOAs are live the same afternoon. Sign up, upload a CSV of your households, and you are collecting verified payments within the hour.'],
                            ['Do you support GCash, Maya, or local bank integrations?', 'Not yet &mdash; that is on the roadmap. Today, members log payments with a photo of their GCash receipt or deposit slip, and officers verify in two taps. The proof is attached to every record.'],
                        ];
                    @endphp

                    @foreach($faqs as $i => [$q, $a])
                        <div class="rounded-2xl bg-white dark:bg-slate-900 ring-1 ring-slate-200 dark:ring-slate-800 overflow-hidden">
                            <button
                                @click="open = (open === {{ $i }} ? null : {{ $i }})"
                                :aria-expanded="open === {{ $i }}"
                                aria-controls="faq-panel-{{ $i }}"
                                class="w-full flex items-center justify-between gap-4 text-left px-6 py-5 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition"
                            >
                                <span class="font-semibold text-slate-900 dark:text-white">{{ $q }}</span>
                                <svg :class="open === {{ $i }} ? 'rotate-180' : ''" class="w-5 h-5 text-slate-400 shrink-0 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="open === {{ $i }}" x-collapse id="faq-panel-{{ $i }}" class="px-6 pb-5 text-sm text-slate-600 dark:text-slate-300 leading-relaxed">{{ $a }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- FINAL CTA --}}
        <section class="py-20 sm:py-28">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-brand-600 via-brand-700 to-brand-900 px-8 py-16 sm:px-16 sm:py-20 text-center text-white shadow-2xl shadow-brand-900/30">
                    <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(circle at 20% 20%, white 1px, transparent 1px), radial-gradient(circle at 80% 80%, white 1px, transparent 1px); background-size: 40px 40px;"></div>

                    <div class="relative">
                        <h2 class="text-3xl sm:text-5xl font-extrabold tracking-tight">Start your HOA's books in 5 minutes.</h2>
                        <p class="mt-5 text-lg text-brand-100 max-w-2xl mx-auto">Join the founding cohort of Philippine HOAs running on BarangayBooks. Free during beta. No card required.</p>

                        <div class="mt-10 flex flex-col sm:flex-row gap-3 justify-center">
                            <a href="{{ route('register') }}" class="inline-flex items-center justify-center gap-2 px-7 py-4 rounded-xl bg-white text-brand-700 hover:bg-brand-50 font-semibold shadow-lg transition">
                                Start free trial
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                            </a>
                            <a href="mailto:hello@barangaybooks.app?subject=Talk%20to%20founder" class="inline-flex items-center justify-center gap-2 px-7 py-4 rounded-xl bg-brand-800/40 hover:bg-brand-800/60 text-white border border-white/20 font-semibold transition">
                                Talk to founder
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

    </main>

    {{-- FOOTER --}}
    <footer class="bg-white dark:bg-slate-950 border-t border-slate-200 dark:border-slate-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-8">
                <div>
                    <a href="/" class="flex items-center gap-2">
                        <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 text-white font-bold">B</span>
                        <span class="font-bold text-lg tracking-tight">Barangay<span class="text-brand-600 dark:text-brand-500">Books</span></span>
                    </a>
                    <p class="mt-3 text-sm text-slate-600 dark:text-slate-400 max-w-xs">The modern ledger for Philippine homeowners associations.</p>
                </div>

                <div>
                    <h4 class="text-xs uppercase tracking-wider font-semibold text-slate-500 dark:text-slate-400">Product</h4>
                    <ul class="mt-3 space-y-2 text-sm">
                        <li><a href="#features" class="text-slate-700 dark:text-slate-300 hover:text-brand-600 dark:hover:text-brand-400">Features</a></li>
                        <li><a href="#how" class="text-slate-700 dark:text-slate-300 hover:text-brand-600 dark:hover:text-brand-400">How it works</a></li>
                        <li><a href="#pricing" class="text-slate-700 dark:text-slate-300 hover:text-brand-600 dark:hover:text-brand-400">Pricing</a></li>
                        <li><a href="#faq" class="text-slate-700 dark:text-slate-300 hover:text-brand-600 dark:hover:text-brand-400">FAQ</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="text-xs uppercase tracking-wider font-semibold text-slate-500 dark:text-slate-400">Company</h4>
                    <ul class="mt-3 space-y-2 text-sm">
                        <li><a href="mailto:hello@barangaybooks.app" class="text-slate-700 dark:text-slate-300 hover:text-brand-600 dark:hover:text-brand-400">Contact</a></li>
                        <li><a href="{{ route('privacy') }}" class="text-slate-700 dark:text-slate-300 hover:text-brand-600 dark:hover:text-brand-400">Privacy</a></li>
                        <li><a href="{{ route('terms') }}" class="text-slate-700 dark:text-slate-300 hover:text-brand-600 dark:hover:text-brand-400">Terms</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="text-xs uppercase tracking-wider font-semibold text-slate-500 dark:text-slate-400">Get started</h4>
                    <ul class="mt-3 space-y-2 text-sm">
                        <li><a href="{{ route('login') }}" class="text-slate-700 dark:text-slate-300 hover:text-brand-600 dark:hover:text-brand-400">Log in</a></li>
                        <li><a href="{{ route('register') }}" class="text-slate-700 dark:text-slate-300 hover:text-brand-600 dark:hover:text-brand-400">Create account</a></li>
                    </ul>
                </div>
            </div>

            <div class="mt-12 pt-8 border-t border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-4 text-sm text-slate-500 dark:text-slate-400">
                <p>&copy; {{ date('Y') }} BarangayBooks. All rights reserved.</p>
                <p>Made for Philippine homeowners associations.</p>
            </div>
        </div>
    </footer>

    <style>[x-cloak] { display: none !important; }</style>
</body>
</html>
