@props([
    'label',
    'value',
    'hint' => null,
    'tone' => 'brand',
    'icon' => null,
    'href' => null,
])

@php
    $tones = [
        'brand'   => ['bg' => 'bg-brand-100',     'text' => 'text-brand-700',    'value' => 'text-slate-900',   'accent' => 'from-brand-500 to-violet-500'],
        'emerald' => ['bg' => 'bg-emerald-100',   'text' => 'text-emerald-700',  'value' => 'text-emerald-700', 'accent' => 'from-emerald-500 to-green-500'],
        'rose'    => ['bg' => 'bg-rose-100',      'text' => 'text-rose-700',     'value' => 'text-rose-700',    'accent' => 'from-rose-500 to-red-500'],
        'amber'   => ['bg' => 'bg-amber-100',     'text' => 'text-amber-700',    'value' => 'text-amber-700',   'accent' => 'from-amber-500 to-orange-500'],
        'slate'   => ['bg' => 'bg-slate-100',     'text' => 'text-slate-600',    'value' => 'text-slate-900',   'accent' => 'from-slate-500 to-slate-700'],
        'violet'  => ['bg' => 'bg-violet-100',    'text' => 'text-violet-700',   'value' => 'text-violet-700',  'accent' => 'from-violet-500 to-purple-500'],
    ];
    $t = $tones[$tone] ?? $tones['brand'];
@endphp

@if($href)
    <a href="{{ $href }}" class="group block">
@endif
<div {{ $attributes->merge(['class' => 'relative overflow-hidden rounded-2xl bg-white ring-1 ring-slate-200 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all p-5']) }}>
    <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r {{ $t['accent'] }} opacity-80"></div>
    <div class="flex items-center justify-between mb-3">
        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">{{ $label }}</span>
        @if($icon)
            <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl {{ $t['bg'] }} {{ $t['text'] }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icon }}"/></svg>
            </span>
        @endif
    </div>
    <p class="text-3xl font-extrabold {{ $t['value'] }}">{{ $value }}</p>
    @if($hint)
        <p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>
    @endif
    @isset($footer)
        <div class="mt-3 pt-3 border-t border-slate-100">{{ $footer }}</div>
    @endisset
</div>
@if($href)
    </a>
@endif