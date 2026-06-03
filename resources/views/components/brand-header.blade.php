@props(['linkHome' => true])

@php
    $tag = $linkHome ? 'a' : 'span';
@endphp

<{{ $tag }}
    @if($linkHome) href="/" @endif
    {{ $attributes->class(['inline-flex items-center gap-2']) }}
>
    <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 text-white font-bold shadow-sm shadow-brand-900/20">B</span>
    <span class="font-bold text-lg tracking-tight text-slate-900 dark:text-white">Barangay<span class="text-brand-600 dark:text-brand-500">Books</span></span>
</{{ $tag }}>
