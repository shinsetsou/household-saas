@component('mail::message')
# You've been invited to {{ $invitation->organization->name }}

{{ $invitation->inviter->name }} has invited you to join **{{ $invitation->organization->name }}** on BarangayBooks as a **{{ $role->label() }}**.

{{ $role->description() }}

@component('mail::button', ['url' => $acceptUrl, 'color' => 'primary'])
Accept invitation
@endcomponent

This invitation expires on {{ $invitation->expires_at->format('F j, Y') }}.

If you don't have a BarangayBooks account yet, you'll be prompted to create one before accepting.

Thanks,
The BarangayBooks team

@endcomponent