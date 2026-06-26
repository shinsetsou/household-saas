@props(['step' => 1, 'title' => 'Onboarding', 'totalSteps' => 3])

@php
    $steps = [
        1 => ['label' => 'Create organization', 'icon' => 'home'],
        2 => ['label' => 'Add households',     'icon' => 'users'],
        3 => ['label' => 'You\'re all set',    'icon' => 'check'],
    ];
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} — BarangayBooks</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'] },
                    colors: {
                        brand: {
                            50:'#eef2ff',100:'#e0e7ff',200:'#c7d2fe',500:'#6366f1',
                            600:'#4f46e5',700:'#4338ca',800:'#3730a3',900:'#312e81',
                        }
                    },
                    boxShadow: { 'brand-glow': '0 30px 60px -20px rgba(79,70,229,.55)' }
                }
            }
        }
    </script>
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
        .brand-mesh {
            background:
                radial-gradient(40rem 30rem at 12% 18%, rgba(236, 72, 153, 0.55), transparent 60%),
                radial-gradient(35rem 28rem at 88% 22%, rgba(99, 102, 241, 0.75), transparent 60%),
                radial-gradient(30rem 28rem at 50% 110%, rgba(14, 165, 233, 0.45), transparent 60%),
                linear-gradient(135deg, #312e81 0%, #4f46e5 45%, #7c3aed 100%);
        }
        .float-blob { position: absolute; border-radius: 9999px; filter: blur(60px); opacity: .55; }
        .float-blob-1 { width: 18rem; height: 18rem; top: -4rem; left: -6rem; background: rgba(236, 72, 153, .65); }
        .float-blob-2 { width: 22rem; height: 22rem; bottom: -6rem; right: -8rem; background: rgba(56, 189, 248, .55); }

        /* Stepper */
        .step-pulse { animation: pulse 2s ease-in-out infinite; }
        @keyframes pulse { 0%,100% { box-shadow: 0 0 0 0 rgba(255,255,255,.4); } 50% { box-shadow: 0 0 0 12px rgba(255,255,255,0); } }

        /* Card enter animation */
        @keyframes fade-up { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
        .fade-up { animation: fade-up .35s ease-out both; }
    </style>
</head>

<body class="antialiased bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 min-h-screen">

<div class="min-h-screen flex flex-col lg:flex-row">

    {{-- Left brand panel --}}
    <aside class="relative overflow-hidden brand-mesh text-white lg:w-[36%] xl:w-[32%] flex">
        <div class="float-blob float-blob-1 pointer-events-none"></div>
        <div class="float-blob float-blob-2 pointer-events-none"></div>

        <div class="relative z-10 flex flex-col w-full p-8 sm:p-12 lg:p-14">
            <a href="/" class="inline-flex items-center gap-2.5">
                <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-white/15 backdrop-blur-sm ring-1 ring-white/30 font-bold text-lg">B</span>
                <span class="font-bold text-lg tracking-tight">Barangay<span class="text-pink-200">Books</span></span>
            </a>

            {{-- Stepper --}}
            <ol class="mt-12 space-y-5">
                @foreach ($steps as $i => $s)
                    @php $state = $i < $step ? 'done' : ($i === $step ? 'active' : 'pending'); @endphp
                    <li class="flex items-center gap-4">
                        <span @class([
                            'flex items-center justify-center w-9 h-9 rounded-full text-sm font-bold ring-1 transition',
                            'bg-white text-brand-700 ring-white shadow-sm' => $state === 'active',
                            'bg-white/15 text-white ring-white/30' => $state === 'done',
                            'bg-white/5 text-white/60 ring-white/15' => $state === 'pending',
                        ])>
                            @if($state === 'done')
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                            @else
                                {{ $i }}
                            @endif
                        </span>
                        <div>
                            <p @class(['text-sm font-semibold', 'text-white' => $state !== 'pending', 'text-white/60' => $state === 'pending'])>
                                {{ $s['label'] }}
                            </p>
                            <p class="text-xs text-white/60">
                                @switch($i)
                                    @case(1) Name your HOA and set the monthly due. @break
                                    @case(2) Upload a CSV or skip and add later. @break
                                    @case(3) Start collecting contributions. @break
                                @endswitch
                            </p>
                        </div>
                    </li>
                @endforeach
            </ol>

            <p class="mt-auto pt-10 text-xs text-white/60">
                Need help? Email <a href="mailto:hello@barangaybooks.app" class="underline hover:text-white">hello@barangaybooks.app</a>
            </p>
        </div>
    </aside>

    {{-- Right form panel --}}
    <main class="flex-1 flex items-center justify-center px-5 sm:px-8 py-10 lg:py-0">
        <div class="w-full max-w-xl">
            <div class="mb-6 lg:hidden">
                <x-brand-header />
            </div>

            <div class="rounded-2xl bg-white dark:bg-slate-900 ring-1 ring-slate-200 dark:ring-slate-800 shadow-sm px-6 py-8 sm:px-10 sm:py-10 fade-up">
                {{ $slot }}
            </div>
        </div>
    </main>
</div>

</body>
</html>