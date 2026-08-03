@props([
    'preview' => null,
    'title' => null,
    'accent' => 'indigo',
])

@php
    $accents = [
        'indigo'  => ['top' => '#6366f1', 'soft' => '#eef2ff', 'icon' => '#4338ca'],
        'emerald' => ['top' => '#10b981', 'soft' => '#ecfdf5', 'icon' => '#047857'],
        'amber'   => ['top' => '#f59e0b', 'soft' => '#fffbeb', 'icon' => '#b45309'],
        'rose'    => ['top' => '#f43f5e', 'soft' => '#fff1f2', 'icon' => '#be123c'],
        'slate'   => ['top' => '#475569', 'soft' => '#f8fafc', 'icon' => '#1e293b'],
    ];
    $palette = $accents[$accent] ?? $accents['indigo'];
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
<body style="margin:0;padding:0;background-color:#f1f5f9;background-image:linear-gradient(180deg,#eef2ff 0%,#f1f5f9 320px);font-family:'Figtree',-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#0f172a;line-height:1.55;-webkit-font-smoothing:antialiased;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:transparent;">
        <tr>
            <td align="center" style="padding:40px 16px 32px;">
                {{-- Brand mark above card --}}
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 auto 20px;">
                    <tr>
                        <td align="center">
                            <a href="{{ url('/') }}" style="text-decoration:none;display:inline-flex;align-items:center;gap:10px;">
                                <span style="display:inline-flex;align-items:center;justify-content:center;width:40px;height:40px;border-radius:12px;background:linear-gradient(135deg,#6366f1 0%,#7c3aed 100%);color:#ffffff;font-weight:700;font-size:17px;letter-spacing:-0.02em;box-shadow:0 4px 14px rgba(99,102,241,0.35);">B</span>
                                <span style="font-weight:700;font-size:18px;letter-spacing:-0.01em;color:#0f172a;">Barangay<span style="color:#6366f1;">Books</span></span>
                            </a>
                        </td>
                    </tr>
                </table>

                {{-- Main card --}}
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:560px;background-color:#ffffff;border-radius:16px;box-shadow:0 1px 2px rgba(15,23,42,0.04),0 8px 24px -8px rgba(15,23,42,0.10);overflow:hidden;border:1px solid #e2e8f0;">
                    {{-- Accent strip --}}
                    <tr>
                        <td style="height:4px;line-height:4px;font-size:0;background:linear-gradient(90deg,{{ $palette['top'] }} 0%,#7c3aed 100%);">&nbsp;</td>
                    </tr>
                    {{-- Body --}}
                    <tr>
                        <td style="padding:36px 36px 32px;">
                            {{ $slot }}
                        </td>
                    </tr>
                </table>

                {{-- Footer --}}
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:560px;margin:0 auto;">
                    <tr>
                        <td align="center" style="padding:24px 16px 0;">
                            <p style="margin:0;font-size:12px;color:#64748b;line-height:1.6;">
                                &copy; {{ date('Y') }} BarangayBooks &middot; Built for Filipino community treasurers
                            </p>
                            <p style="margin:6px 0 0;font-size:11px;color:#94a3b8;line-height:1.6;">
                                You received this because you have an account on BarangayBooks.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
