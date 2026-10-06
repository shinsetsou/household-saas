<x-emails::layout
    :preview="'Reset your BarangayBooks password. The link expires in 60 minutes.'"
    :title="'Reset your password'"
    accent="indigo"
>
    <x-emails::heading kicker="Password reset" accent="indigo">
        Reset your password
    </x-emails::heading>

    <x-emails::paragraph>
        Hi {{ $user->name ?? 'there' }} &mdash; we received a request to reset the password for your BarangayBooks account
        (<strong style="color:#0f172a;">{{ $user->email }}</strong>).
    </x-emails::paragraph>

    <x-emails::paragraph>
        Click the button below to choose a new password. The link expires in 60 minutes and can only be used once.
    </x-emails::paragraph>

    <x-emails::button :href="$resetUrl" color="indigo">
        Choose a new password
    </x-emails::button>

    <p style="margin:18px 0 4px;font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:13px;color:#475569;">
        If the button above doesn't work, paste this link into your browser:
    </p>
    <p style="margin:0;word-break:break-all;font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:12px;color:#6366f1;">
        <a href="{{ $resetUrl }}" style="color:#6366f1;text-decoration:underline;">{{ $resetUrl }}</a>
    </p>

    {{-- Security callout --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:24px 0 0;border:1px solid #fde68a;border-radius:12px;background-color:#fffbeb;">
        <tr>
            <td style="padding:14px 16px;">
                <p style="margin:0 0 4px;font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:11px;font-weight:700;letter-spacing:0.12em;text-transform:uppercase;color:#b45309;">
                    Didn't request this?
                </p>
                <p style="margin:0;font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:13px;line-height:1.55;color:#78350f;">
                    No changes have been made to your account. You can safely ignore this email.
                    If you're concerned, sign in and change your password from the Profile page.
                </p>
            </td>
        </tr>
    </table>

    <p style="margin:24px 0 0;font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:13px;color:#64748b;">
        &mdash; The BarangayBooks team
    </p>
</x-emails::layout>
