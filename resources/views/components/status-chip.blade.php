@props([
    'tone' => 'slate',  // emerald | amber | rose | slate | indigo | violet | blue
    'icon' => null,     // svg path
])

@php
    $tones = [
        'emerald' => 'bg-emerald-100 text-emerald-800 ring-emerald-200',
        'amber'   => 'bg-amber-100 text-amber-800 ring-amber-200',
        'rose'    => 'bg-rose-100 text-rose-800 ring-rose-200',
        'red'     => 'bg-red-100 text-red-800 ring-red-200',
        'slate'   => 'bg-slate-100 text-slate-700 ring-slate-200',
        'indigo'  => 'bg-indigo-100 text-indigo-700 ring-indigo-200',
        'violet'  => 'bg-violet-100 text-violet-700 ring-violet-200',
        'blue'    => 'bg-blue-100 text-blue-700 ring-blue-200',
    ];
    $classes = $tones[$tone] ?? $tones['slate'];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold ring-1 $classes"]) }}>
    @if($icon)
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="{{ $icon }}"/></svg>
    @endif
    {{ $slot }}
</span>