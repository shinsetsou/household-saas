@props(['step' => 1, 'title' => 'Onboarding'])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} &mdash; BarangayBooks</title>

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
                    }
                }
            }
        }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>[x-cloak] { display: none !important; } body { font-family: 'Inter', ui-sans-serif, system-ui, sans-serif; }</style>
</head>
<body class="antialiased bg-slate-50 text-slate-900 min-h-screen">

<header class="px-4 sm:px-6 lg:px-8 py-6">
    <x-brand-header />
</header>

<main class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 pb-16">
    <div class="mb-8">
        <p class="text-xs uppercase tracking-wider text-slate-500 font-semibold">Step {{ $step }} of 3</p>
        <div class="mt-2 flex gap-2">
            @for ($i = 1; $i <= 3; $i++)
                <div class="flex-1 h-1.5 rounded-full {{ $i <= $step ? 'bg-brand-600' : 'bg-slate-200' }}"></div>
            @endfor
        </div>
    </div>

    <div class="rounded-2xl bg-white ring-1 ring-slate-200 shadow-sm px-6 py-8 sm:px-10 sm:py-10">
        {{ $slot }}
    </div>
</main>

</body>
</html>
