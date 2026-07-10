@php
    $billingUrl = route('organizations.settings.billing.show', $org->slug);
@endphp

<x-emails::layout :preview="'Your BarangayBooks free trial ends ' . $org->trial_ends_at->format('F j, Y')" :title="'Your trial ends ' . $urgency">
    <h1 style="margin:0 0 16px;font-size:20px;font-weight:700;color:#0f172a;">Your trial ends {{ $urgency }}</h1>

    <p style="margin:0 0 16px;">Hi {{ $org->name }} admin,</p>

    <p style="margin:0 0 16px;">
        Your BarangayBooks free trial ends <strong>{{ $org->trial_ends_at->format('F j, Y') }}</strong>.
        To keep collecting contributions and managing your HOA's books, pick a plan before then.
    </p>

    <p style="margin:24px 0;">
        <a href="{{ $billingUrl }}" style="display:inline-block;padding:10px 18px;background:#6366f1;color:#ffffff;text-decoration:none;border-radius:12px;font-weight:600;font-size:14px;">
            Choose a plan
        </a>
    </p>

    <p style="margin:16px 0;color:#475569;">
        Plans start at <strong>&#8369;199/month</strong> and you can cancel anytime.
    </p>

    <p style="margin:16px 0;color:#475569;">
        If you don't pick a plan, your account will switch to read-only mode and you'll need to subscribe before adding new contributions or expenses.
    </p>

    <p style="margin:24px 0 0;color:#64748b;font-size:13px;">
        Thanks,<br>The BarangayBooks team
    </p>
</x-emails::layout>
