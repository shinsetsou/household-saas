@component('mail::message')
# Your trial has ended

Hi {{ $org->name }} admin,

Your BarangayBooks free trial ended on {{ optional($org->trial_ends_at)->format('F j, Y') ?? 'today' }}. Until you pick a plan, your HOA's account is read-only — you can view your data, but new contributions, expenses, and member invites are disabled.

@component('mail::button', ['url' => route('organizations.settings.billing.show', $org->slug), 'color' => 'primary'])
Choose a plan
@endcomponent

All your existing records are safe and will be waiting when you subscribe.

Thanks,
The BarangayBooks team
@endcomponent