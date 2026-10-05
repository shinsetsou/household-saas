<x-emails::layout
    :preview="'Confirm your email to finish setting up your BarangayBooks account.'"
    :title="'Verify your email'"
    accent="indigo"
>
    <x-emails::heading kicker="Welcome to BarangayBooks" accent="indigo">
        Confirm your email address
    </x-emails::heading>

    <x-emails::paragraph>
        Hi {{ $user->name ?? 'there' }} &mdash; thanks for signing up for BarangayBooks!
        To finish setting up your account and start managing your community's books, please confirm that
        <strong style="color:#0f172a;">{{ $user->email }}</strong> is the right email.
    </x-emails::paragraph>

    <x-emails::button :href="$verificationUrl" color="indigo">
        Verify my email
    </x-emails::button>

    <p style="margin:18px 0 4px;font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:13px;color:#475569;">
        If the button above doesn't work, paste this link into your browser:
    </p>
    <p style="margin:0;word-break:break-all;font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:12px;color:#6366f1;">
        <a href="{{ $verificationUrl }}" style="color:#6366f1;text-decoration:underline;">{{ $verificationUrl }}</a>
    </p>

    {{-- Security callout --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:24px 0 0;border:1px solid #e2e8f0;border-radius:12px;background-color:#f8fafc;">
        <tr>
            <td style="padding:14px 16px;">
                <p style="margin:0 0 4px;font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:11px;font-weight:700;letter-spacing:0.12em;text-transform:uppercase;color:#475569;">
                    Didn't sign up?
                </p>
                <p style="margin:0;font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:13px;line-height:1.55;color:#475569;">
                    You can safely ignore this email &mdash; nothing will happen until you click the link above.
                    This verification link will expire in 60 minutes.
                </p>
            </td>
        </tr>
    </table>

    <p style="margin:24px 0 0;font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:13px;color:#64748b;">
        &mdash; The BarangayBooks team
    </p>
</x-emails::layout>
