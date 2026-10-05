@php
    $logoUrl = config('app.url') . '/android-chrome-512x512.png';
    $homeUrl = config('app.url');
@endphp

<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 auto 24px;" width="100%">
    <tr>
        <td align="center" style="padding:0;">
            <a href="{{ $homeUrl }}" style="text-decoration:none;display:inline-block;" target="_blank">
                <img src="{{ $logoUrl }}" alt="BarangayBooks" width="48" height="48" style="display:block;width:48px;height:48px;border:0;border-radius:12px;box-shadow:0 4px 14px rgba(79,70,229,0.25);" />
            </a>
        </td>
    </tr>
    <tr>
        <td align="center" style="padding:10px 0 0;">
            <a href="{{ $homeUrl }}" style="text-decoration:none;font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:18px;font-weight:800;letter-spacing:-0.01em;color:#0f172a;" target="_blank">
                Barangay<span style="color:#4f46e5;">Books</span>
            </a>
        </td>
    </tr>
</table>
