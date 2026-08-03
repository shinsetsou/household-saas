@props([
    'title',
    'subtitle' => null,
    'eyebrow' => null,
    'icon' => null,           // svg path string
    'tone' => 'brand',         // brand | slate | emerald | rose | amber | violet
])

@php
    $tones = [
        'brand'   => 'from-brand-500 to-violet-600',
        'slate'   => 'from-slate-500 to-slate-700',
        'emerald' => 'from-emerald-500 to-green-600',
        'rose'    => 'from-rose-500 to-red-600',
        'amber'   => 'from-amber-500 to-orange-600',
        'violet'  => 'from-violet-500 to-purple-600',
    ];
    $gradient = $tones[$tone] ?? $tones['brand'];
@endphp

<header class="relative overflow-hidden rounded-2xl bg-gradient-to-br {{ $gradient }} text-white shadow-brand-glow">
    <div class="absolute inset-0 opacity-30 pointer-events-none" style="background: radial-gradient(40rem 20rem at 90% -10%, rgba(255,255,255,.35), transparent 60%), radial-gradient(30rem 18rem at -10% 110%, rgba(255,255,255,.18), transparent 60%);"></div>
    <div class="relative flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4 px-5 py-5 sm:px-8 sm:py-8">
        <div class="flex items-start gap-3 sm:gap-4 min-w-0">
            @if($icon)
                <span class="flex-shrink-0 inline-flex items-center justify-center w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-white/15 ring-1 ring-white/25 backdrop-blur-sm">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icon }}"/></svg>
                </span>
            @endif
            <div class="min-w-0 flex-1">
                @if($eyebrow)
                    <p class="text-xs uppercase tracking-wider text-white/70 font-semibold">{{ $eyebrow }}</p>
                @endif
                <h1 class="mt-1 text-xl sm:text-3xl font-extrabold tracking-tight truncate">{{ $title }}</h1>
                @if($subtitle)
                    <p class="mt-1 text-sm text-white/80 truncate">{{ $subtitle }}</p>
                @endif
            </div>
        </div>

        @if(isset($actions))
            <div class="flex flex-wrap items-center gap-2 -mx-1 sm:mx-0 sm:flex-shrink-0">
                {{ $actions }}
            </div>
        @endif
    </div>
</header>