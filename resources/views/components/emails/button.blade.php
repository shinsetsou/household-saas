@php
    $colors = [
        'indigo'  => ['bg' => '#4f46e5', 'text' => '#ffffff', 'shadow' => 'rgba(79,70,229,0.35)'],
        'emerald' => ['bg' => '#059669', 'text' => '#ffffff', 'shadow' => 'rgba(5,150,105,0.35)'],
        'amber'   => ['bg' => '#d97706', 'text' => '#ffffff', 'shadow' => 'rgba(217,119,6,0.35)'],
        'rose'    => ['bg' => '#e11d48', 'text' => '#ffffff', 'shadow' => 'rgba(225,29,72,0.35)'],
        'slate'   => ['bg' => '#0f172a', 'text' => '#ffffff', 'shadow' => 'rgba(15,23,42,0.25)'],
    ];
    $palette = $colors[$color] ?? $colors['indigo'];
    $display = $full ? 'block' : 'inline-block';
    $width = $full ? 'width:100%;' : '';
@endphp

<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:24px 0;">
    <tr>
        <td align="left">
            <a href="{{ $href }}" style="display:{{ $display }};{{ $width }}padding:13px 24px;background-color:{{ $palette['bg'] }};color:{{ $palette['text'] }};text-decoration:none;border-radius:10px;font-weight:600;font-size:14px;letter-spacing:0.01em;font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;box-shadow:0 4px 12px {{ $palette['shadow'] }};mso-padding-alt:0;">
                {{ $slot }}
            </a>
        </td>
    </tr>
</table>
