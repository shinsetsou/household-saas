<x-emails::layout
    :preview="$invitation->inviter->name . ' invited you to join ' . $invitation->organization->name . ' on BarangayBooks as ' . $role->label()"
    :title="'You\'re invited to ' . $invitation->organization->name"
    accent="indigo"
>
    <x-emails::heading kicker="Organization invitation" accent="indigo">
        You've been invited to {{ $invitation->organization->name }}
    </x-emails::heading>

    <x-emails::paragraph>
        <strong style="color:#0f172a;">{{ $invitation->inviter->name }}</strong> has invited you to join
        <strong style="color:#0f172a;">{{ $invitation->organization->name }}</strong> on BarangayBooks as a
        <span style="display:inline-block;padding:2px 10px;border-radius:9999px;background-color:#eef2ff;color:#4338ca;font-size:12px;font-weight:600;letter-spacing:0.01em;font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">{{ $role->label() }}</span>.
    </x-emails::paragraph>

    <x-emails::paragraph muted>
        {{ $role->description() }}
    </x-emails::paragraph>

    <x-emails::button :href="$acceptUrl" color="indigo">
        Review &amp; accept invitation
    </x-emails::button>

    {{-- Verification code card --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:8px 0 4px;border:1px solid #c7d2fe;border-radius:12px;background:linear-gradient(180deg,#eef2ff 0%,#ffffff 100%);">
        <tr>
            <td style="padding:18px 20px;text-align:center;">
                <p style="margin:0 0 6px;font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:11px;font-weight:700;letter-spacing:0.12em;text-transform:uppercase;color:#4338ca;">
                    Your 6-digit confirmation code
                </p>
                <p style="margin:0;font-family:Menlo,Consolas,monospace;font-size:30px;font-weight:700;letter-spacing:0.45em;color:#0f172a;">
                    {{ $verificationCode }}
                </p>
                <p style="margin:10px 0 0;font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:12px;line-height:1.5;color:#475569;">
                    Enter this on the invitation page (along with the email it was sent to) to confirm. The code alone isn't enough &mdash; you also need the link above.
                </p>
            </td>
        </tr>
    </table>

    <p style="margin:18px 0 0;font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:12px;color:#94a3b8;">
        This invitation expires on <strong style="color:#475569;">{{ $invitation->expires_at->format('F j, Y') }}</strong>.
    </p>

    <p style="margin:18px 0 4px;font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:13px;color:#475569;">
        If the button above doesn't work, paste this link into your browser:
    </p>
    <p style="margin:0;word-break:break-all;font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:12px;color:#6366f1;">
        <a href="{{ $acceptUrl }}" style="color:#6366f1;text-decoration:underline;">{{ $acceptUrl }}</a>
    </p>

    <p style="margin:18px 0 0;font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:12px;color:#94a3b8;line-height:1.6;">
        If you don't have a BarangayBooks account yet, you'll be prompted to create one before accepting.
    </p>

    <p style="margin:24px 0 0;font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:13px;color:#64748b;">
        &mdash; The BarangayBooks team
    </p>
</x-emails::layout>
