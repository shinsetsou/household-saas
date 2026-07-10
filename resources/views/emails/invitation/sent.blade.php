<x-emails::layout :preview="$invitation->inviter->name . ' invited you to join ' . $invitation->organization->name . ' on BarangayBooks'" :title="'You\'re invited to ' . $invitation->organization->name">
    <h1 style="margin:0 0 16px;font-size:20px;font-weight:700;color:#0f172a;">
        You've been invited to {{ $invitation->organization->name }}
    </h1>

    <p style="margin:0 0 12px;">
        {{ $invitation->inviter->name }} has invited you to join
        <strong>{{ $invitation->organization->name }}</strong> on BarangayBooks as a
        <strong>{{ $role->label() }}</strong>.
    </p>

    <p style="margin:0 0 20px;color:#475569;">
        {{ $role->description() }}
    </p>

    <p style="margin:24px 0;">
        <a href="{{ $acceptUrl }}" style="display:inline-block;padding:10px 18px;background:#6366f1;color:#ffffff;text-decoration:none;border-radius:12px;font-weight:600;font-size:14px;">
            Accept invitation
        </a>
    </p>

    <p style="margin:16px 0 4px;font-size:13px;color:#64748b;">
        This invitation expires on {{ $invitation->expires_at->format('F j, Y') }}.
    </p>

    <p style="margin:8px 0 4px;font-size:13px;color:#475569;">
        If the button above doesn't work, paste this link into your browser:
    </p>
    <p style="margin:0;word-break:break-all;font-size:13px;color:#6366f1;">
        <a href="{{ $acceptUrl }}" style="color:#6366f1;">{{ $acceptUrl }}</a>
    </p>

    <p style="margin:16px 0 0;font-size:13px;color:#64748b;">
        If you don't have a BarangayBooks account yet, you'll be prompted to create one before accepting.
    </p>

    <p style="margin:24px 0 0;color:#64748b;font-size:13px;">
        Thanks,<br>The BarangayBooks team
    </p>
</x-emails::layout>
