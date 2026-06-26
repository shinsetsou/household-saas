<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#4f46e5">

    <title>{{ $title ?? config('app.name', 'BarangayBooks') }}</title>

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />

    {{-- Vite assets (kept for parity with the rest of the app) --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Tailwind CDN with brand tokens --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'] },
                    colors: {
                        brand: {
                            50:  '#eef2ff',
                            100: '#e0e7ff',
                            200: '#c7d2fe',
                            300: '#a5b4fc',
                            400: '#818cf8',
                            500: '#6366f1',
                            600: '#4f46e5',
                            700: '#4338ca',
                            800: '#3730a3',
                            900: '#312e81',
                            950: '#1e1b4b',
                        },
                        accent: {
                            500: '#ec4899',
                            600: '#db2777',
                        },
                    },
                    boxShadow: {
                        'brand-glow': '0 30px 60px -20px rgba(79, 70, 229, 0.55)',
                    },
                }
            }
        }
    </script>

    {{-- Alpine + dark mode bootstrap --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
        (function () {
            try {
                if (localStorage.getItem('theme') === 'dark'
                    || (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                    document.documentElement.classList.add('dark');
                }
            } catch (e) {}
        })();
    </script>
    <style>
        [x-cloak] { display: none !important; }
        body { font-family: 'Inter', ui-sans-serif, system-ui, sans-serif; }

        /* Animated gradient mesh on the brand panel */
        .brand-mesh {
            background:
                radial-gradient(40rem 30rem at 12% 18%, rgba(236, 72, 153, 0.55), transparent 60%),
                radial-gradient(35rem 28rem at 88% 22%, rgba(99, 102, 241, 0.75), transparent 60%),
                radial-gradient(30rem 28rem at 50% 110%, rgba(14, 165, 233, 0.45), transparent 60%),
                linear-gradient(135deg, #312e81 0%, #4f46e5 45%, #7c3aed 100%);
            background-size: 200% 200%;
            animation: brand-mesh 18s ease-in-out infinite;
        }
        @keyframes brand-mesh {
            0%, 100% { background-position: 0% 0%; }
            50%      { background-position: 100% 100%; }
        }

        /* Soft floating glow blobs */
        .float-blob { position: absolute; border-radius: 9999px; filter: blur(60px); opacity: .55; }
        .float-blob-1 { width: 18rem; height: 18rem; top: -4rem; left: -6rem; background: rgba(236, 72, 153, .65); animation: float-1 14s ease-in-out infinite; }
        .float-blob-2 { width: 22rem; height: 22rem; bottom: -6rem; right: -8rem; background: rgba(56, 189, 248, .55); animation: float-2 18s ease-in-out infinite; }
        @keyframes float-1 { 0%,100%{ transform: translate(0,0); } 50%{ transform: translate(2rem,-1.5rem); } }
        @keyframes float-2 { 0%,100%{ transform: translate(0,0); } 50%{ transform: translate(-2rem,2rem); } }

        /* Subtle grid texture overlay */
        .grid-overlay {
            background-image:
                linear-gradient(rgba(255,255,255,.08) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,.08) 1px, transparent 1px);
            background-size: 32px 32px;
            mask-image: radial-gradient(ellipse at center, black 30%, transparent 75%);
        }

        /* Password strength meter segments */
        .pw-bar { @apply h-1.5 flex-1 rounded-full bg-slate-200 dark:bg-slate-700; transition: background-color .2s ease; }
        .pw-bar.on-1 { background: #ef4444; }
        .pw-bar.on-2 { background: #f59e0b; }
        .pw-bar.on-3 { background: #eab308; }
        .pw-bar.on-4 { background: #22c55e; }

        /* Smooth card lift on focus */
        .auth-input:focus-within { box-shadow: 0 0 0 4px rgba(99, 102, 241, .15); }
    </style>
</head>

<body class="font-sans antialiased bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 min-h-screen">

    <div class="min-h-screen flex flex-col lg:flex-row">

        {{-- BRAND PANEL (left) --}}
        <aside class="relative overflow-hidden brand-mesh text-white lg:w-[44%] xl:w-[40%] lg:min-h-screen flex">
            {{-- Texture overlays --}}
            <div class="absolute inset-0 grid-overlay pointer-events-none"></div>
            <div class="float-blob float-blob-1 pointer-events-none"></div>
            <div class="float-blob float-blob-2 pointer-events-none"></div>

            <div class="relative z-10 flex flex-col justify-between w-full p-8 sm:p-12 lg:p-14">
                {{-- Logo --}}
                <a href="/" class="inline-flex items-center gap-2.5 group" aria-label="BarangayBooks home">
                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-white/15 backdrop-blur-sm ring-1 ring-white/30 group-hover:bg-white/25 transition">
                        <span class="font-bold text-lg">B</span>
                    </span>
                    <span class="font-bold text-lg tracking-tight">Barangay<span class="text-pink-200">Books</span></span>
                </a>

                {{-- Center: marketing pitch --}}
                <div class="hidden lg:block max-w-md">
                    <p class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold uppercase tracking-wider bg-white/10 ring-1 ring-white/20 backdrop-blur-sm">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-300 animate-pulse"></span>
                        Built for Philippine HOAs
                    </p>
                    <h1 class="mt-5 text-4xl xl:text-5xl font-extrabold tracking-tight leading-[1.1]">
                        {{ $panelTitle ?? 'Dues, verified in two taps.' }}
                    </h1>
                    <p class="mt-4 text-base text-white/80 leading-relaxed">
                        {{ $panelSubtitle ?? 'Photo-proofed contributions, instant delinquency reports, and an audit trail your board can trust — replacing the messy GCash threads and dog-eared notebooks.' }}
                    </p>

                    @isset($panelFeatures)
                        <ul class="mt-8 space-y-3">
                            {{ $panelFeatures }}
                        </ul>
                    @endisset
                </div>

                {{-- Footer: social proof + legal --}}
                <div class="mt-10 lg:mt-0 flex flex-col gap-4">
                    <blockquote class="hidden lg:block max-w-md rounded-2xl bg-white/10 backdrop-blur-sm ring-1 ring-white/20 p-5">
                        <p class="text-sm leading-relaxed text-white/90">
                            "We collected ₱48,000 in two weeks after switching from GCash threads. The treasurer verifies payments in minutes — no more chasing receipts."
                        </p>
                        <footer class="mt-3 flex items-center gap-3">
                            <span class="inline-flex items-center justify-center w-9 h-9 rounded-full bg-gradient-to-br from-pink-400 to-violet-500 text-white font-bold text-sm">M</span>
                            <div class="text-xs">
                                <p class="font-semibold">Maria S.</p>
                                <p class="text-white/70">Treasurer, Sampaguita Village HOA</p>
                            </div>
                        </footer>
                    </blockquote>
                    <p class="text-xs text-white/60">
                        © {{ date('Y') }} BarangayBooks. Made for Filipino communities.
                    </p>
                </div>
            </div>
        </aside>

        {{-- FORM PANEL (right) --}}
        <main class="flex-1 flex items-center justify-center px-5 sm:px-8 py-10 lg:py-0">
            <div class="w-full max-w-md">
                {{-- Top-right utility row (mobile-only logo sits above) --}}
                <div class="lg:hidden mb-8">
                    <x-brand-header />
                </div>

                {{ $slot }}
            </div>
        </main>
    </div>

</body>
</html>