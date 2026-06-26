@props(['as' => 'button'])

@php
$tag = $as;
@endphp

<{{ $tag }} {{ $attributes->merge(['class' => 'inline-flex items-center px-4 py-2 bg-gradient-to-r from-brand-600 to-violet-600 border border-transparent rounded-xl font-semibold text-xs text-white uppercase tracking-widest shadow-sm hover:from-brand-700 hover:to-violet-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</{{ $tag }}>