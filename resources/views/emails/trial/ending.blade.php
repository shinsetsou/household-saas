@php
    $billingUrl = route('organizations.settings.billing.show', $org->slug);
    $daysLabel = $daysRemaining === 1 ? '1 day' : "{$daysRemaining} days";
@endphp

<x-emails::layout
    :preview="'Your BarangayBooks free trial for ' . $org->name . ' ends in ' . $daysLabel . ' — pick a plan to keep your data active.'"
    :title="'Your trial ends ' . $urgency"
    accent="amber"
>
    <x-emails::heading kicker="Trial reminder" accent="amber">
        Your trial ends {{ $urgency }}
    </x-emails::heading>

    <x-emails::paragraph>
        Hi {{ $org->name }} admin &mdash; your BarangayBooks free trial ends
        <strong style="color:#0f172a;">{{ $org->trial_ends_at->format('F j, Y') }}</strong>
        ({{ $daysLabel }} from now). To keep collecting contributions and managing your HOA's books, choose a plan before then.
    </x-emails::paragraph>

    {{-- What happens next callout --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:20px 0;border:1px solid #fde68a;border-radius:12px;background-color:#fffbeb;">
        <tr>
            <td style="padding:16px 18px;">
                <p style="margin:0 0 6px;font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:11px;font-weight:700;letter-spacing:0.12em;text-transform:uppercase;color:#b45309;">
                    What happens if you don't subscribe
                </p>
                <p style="margin:0;font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:14px;line-height:1.55;color:#78350f;">
                    Your account switches to <strong>read-only</strong>. You can still view your records, but new contributions, expenses, and member invites are disabled until you pick a plan.
                </p>
            </td>
        </tr>
    </table>

    <x-emails::button :href="$billingUrl" color="amber">
        Choose a plan
    </x-emails::button>

    <p style="margin:4px 0 0;font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:13px;color:#475569;">
        Plans start at <strong style="color:#0f172a;">&#8369;199/month</strong> &middot; cancel anytime &middot; all your existing records stay safe.
    </p>

    <p style="margin:24px 0 0;font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:13px;color:#64748b;">
        &mdash; The BarangayBooks team
    </p>
</x-emails::layout>
