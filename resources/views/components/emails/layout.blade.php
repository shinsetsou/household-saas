@php
    $brandColors = [
        'indigo'  => ['top' => '#4f46e5', 'soft' => '#eef2ff', 'icon' => '#4338ca', 'button' => '#4f46e5'],
        'emerald' => ['top' => '#10b981', 'soft' => '#ecfdf5', 'icon' => '#047857', 'button' => '#059669'],
        'amber'   => ['top' => '#f59e0b', 'soft' => '#fffbeb', 'icon' => '#b45309', 'button' => '#d97706'],
        'rose'    => ['top' => '#f43f5e', 'soft' => '#fff1f2', 'icon' => '#be123c', 'button' => '#e11d48'],
        'slate'   => ['top' => '#475569', 'soft' => '#f8fafc', 'icon' => '#1e293b', 'button' => '#0f172a'],
    ];
    $palette = $brandColors[$accent] ?? $brandColors['indigo'];
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light only">
    <meta name="supported-color-schemes" content="light">
    <title>{{ $title ?? config('app.name') }}</title>
    @if ($preview)
        <span style="display:none;font-size:1px;color:#ffffff;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;mso-hide:all;">{{ $preview }}</span>
    @endif
</head>
<body style="margin:0;padding:0;background-color:#f8fafc;font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#0f172a;line-height:1.6;-webkit-font-smoothing:antialiased;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f8fafc;">
        <tr>
            <td align="center" style="padding:40px 16px 32px;">
                @include('emails.partials.header')

                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:560px;background-color:#ffffff;border-radius:16px;box-shadow:0 1px 2px rgba(15,23,42,0.04),0 8px 24px -8px rgba(15,23,42,0.10);overflow:hidden;border:1px solid #e2e8f0;">
                    <tr>
                        <td style="height:4px;line-height:4px;font-size:0;background:linear-gradient(90deg,{{ $palette['top'] }} 0%,#7c3aed 100%);">&nbsp;</td>
                    </tr>
                    <tr>
                        <td style="padding:36px 36px 32px;">
                            {{ $slot }}
                        </td>
                    </tr>
                </table>

                @include('emails.partials.footer')
            </td>
        </tr>
    </table>
</body>
</html>
