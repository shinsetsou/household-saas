@props([
    'variant' => 'primary',  // primary | secondary | danger | success | ghost
    'icon' => null,
    'size' => 'md',          // sm | md | lg
    'type' => 'button',
    'href' => null,
])

@php
    $variants = [
        'primary'   => 'bg-gradient-to-r from-brand-600 to-violet-600 text-white shadow-sm hover:from-brand-700 hover:to-violet-700 focus:ring-brand-500',
        'secondary' => 'bg-white text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50 hover:ring-slate-300 focus:ring-brand-500',
        'danger'    => 'bg-gradient-to-r from-rose-500 to-red-600 text-white shadow-sm hover:from-rose-600 hover:to-red-700 focus:ring-rose-500',
        'success'   => 'bg-gradient-to-r from-emerald-500 to-green-600 text-white shadow-sm hover:from-emerald-600 hover:to-green-700 focus:ring-emerald-500',
        'ghost'     => 'bg-transparent text-slate-600 hover:bg-slate-100 hover:text-slate-900 focus:ring-slate-400',
    ];
    $sizes = [
        'sm' => 'px-3 py-1.5 text-xs gap-1.5',
        'md' => 'px-4 py-2.5 text-sm gap-2',
        'lg' => 'px-5 py-3 text-sm gap-2',
    ];
    $variantClasses = $variants[$variant] ?? $variants['primary'];
    $sizeClasses = $sizes[$size] ?? $sizes['md'];
@endphp

@php $tag = $href ? 'a' : 'button'; @endphp

<{{ $tag }}
    @if($href) href="{{ $href }}" @else type="{{ $type }}" @endif
    {{ $attributes->merge(['class' => "inline-flex items-center justify-center font-semibold rounded-xl transition focus:outline-none focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-slate-900 $variantClasses $sizeClasses"]) }}>
    @if($icon)
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icon }}"/></svg>
    @endif
    {{ $slot }}
</{{ $tag }}>