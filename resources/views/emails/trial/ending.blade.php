@component('mail::message')
# Your trial ends {{ $urgency }}

Hi {{ $org->name }} admin,

Your BarangayBooks free trial ends **{{ $org->trial_ends_at->format('F j, Y') }}**. To keep collecting contributions and managing your HOA's books, pick a plan before then.

@component('mail::button', ['url' => route('organizations.settings.billing.show', $org->slug), 'color' => 'primary'])
Choose a plan
@endcomponent

Plans start at &#8369;199/month and you can cancel anytime.

If you don't pick a plan, your account will switch to read-only mode and you'll need to subscribe before adding new contributions or expenses.

Thanks,
The BarangayBooks team
@endcomponent