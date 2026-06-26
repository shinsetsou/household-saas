@props([
    'icon' => null,
    'title',
    'subtitle' => null,
    'tone' => 'slate',  // slate | emerald | rose | amber
])

@php
    $tones = [
        'slate'   => 'from-slate-100 to-slate-200 text-slate-500',
        'emerald' => 'from-emerald-100 to-green-200 text-emerald-600',
        'rose'    => 'from-rose-100 to-red-200 text-rose-600',
        'amber'   => 'from-amber-100 to-orange-200 text-amber-600',
    ];
    $gradient = $tones[$tone] ?? $tones['slate'];
@endphp

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center py-20 px-6 bg-white dark:bg-gray-800 border border-dashed border-slate-300 dark:border-slate-700 rounded-2xl']) }}>
    <span class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br {{ $gradient }} mb-4 shadow-sm">
        @if($icon)
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="{{ $icon }}"/></svg>
        @endif
    </span>
    <p class="text-base font-bold text-slate-900 dark:text-white">{{ $title }}</p>
    @if($subtitle)
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400 max-w-sm text-center">{{ $subtitle }}</p>
    @endif
    @isset($action)
        <div class="mt-5">{{ $action }}</div>
    @endisset
</div>