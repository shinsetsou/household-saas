@props([
    'preview' => null,
    'title' => null,
])

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light only">
    <title>{{ $title ?? config('app.name') }}</title>
    @if ($preview)
        <span style="display:none;font-size:1px;color:transparent;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;">{{ $preview }}</span>
    @endif
</head>
<body style="margin:0;padding:0;background-color:#f8fafc;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#1f2937;line-height:1.5;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f8fafc;">
        <tr>
            <td align="center" style="padding:32px 16px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:560px;background-color:#ffffff;border-radius:16px;box-shadow:0 1px 2px rgba(15,23,42,0.06);overflow:hidden;">
                    <tr>
                        <td style="padding:24px 32px;border-bottom:1px solid #e2e8f0;">
                            <a href="{{ url('/') }}" style="text-decoration:none;display:inline-flex;align-items:center;gap:10px;">
                                <span style="display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:12px;background:linear-gradient(135deg,#6366f1,#7c3aed);color:#ffffff;font-weight:700;font-size:16px;">B</span>
                                <span style="font-weight:700;font-size:18px;letter-spacing:-0.01em;color:#0f172a;">Barangay<span style="color:#6366f1;">Books</span></span>
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;">
                            {{ $slot }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:20px 32px;background-color:#f8fafc;border-top:1px solid #e2e8f0;">
                            <p style="margin:0;font-size:12px;color:#64748b;line-height:1.5;">
                                &copy; {{ date('Y') }} BarangayBooks. All rights reserved.
                            </p>
                            <p style="margin:6px 0 0;font-size:12px;color:#94a3b8;">
                                You received this email because you have an account on BarangayBooks.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
