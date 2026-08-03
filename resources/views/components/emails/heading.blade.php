@php
    $accents = [
        'indigo'  => '#4338ca',
        'emerald' => '#047857',
        'amber'   => '#b45309',
        'rose'    => '#be123c',
        'slate'   => '#1e293b',
    ];
    $kickerColor = $accents[$accent] ?? $accents['indigo'];
@endphp

@if ($kicker)
    <p style="margin:0 0 8px;font-size:11px;font-weight:700;letter-spacing:0.12em;text-transform:uppercase;color:{{ $kickerColor }};">
        {{ $kicker }}
    </p>
@endif

<h1 style="margin:0 0 16px;font-size:24px;line-height:1.25;font-weight:700;letter-spacing:-0.015em;color:#0f172a;">
    {{ $slot }}
</h1>
