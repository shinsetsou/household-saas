@php
    $homeUrl = 'https://barangaybooks.com';
    $contactEmail = 'hello@barangaybooks.app';
@endphp

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 auto;max-width:560px;">
    <tr>
        <td align="center" style="padding:24px 16px 0;">
            <p style="margin:0;font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:12px;color:#64748b;line-height:1.6;">
                &copy; {{ date('Y') }} BarangayBooks &middot; Built for Philippine homeowners associations
            </p>
            <p style="margin:6px 0 0;font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:11px;color:#94a3b8;line-height:1.6;">
                Need help? Email
                <a href="mailto:{{ $contactEmail }}" style="color:#4f46e5;text-decoration:underline;">{{ $contactEmail }}</a>
                &middot;
                <a href="{{ $homeUrl }}" style="color:#4f46e5;text-decoration:underline;" target="_blank">https://barangaybooks.com</a>
            </p>
        </td>
    </tr>
</table>
