@php
    $billingUrl = route('organizations.settings.billing.show', $org->slug);
    $trialEndLabel = optional($org->trial_ends_at)->format('F j, Y') ?? 'today';
@endphp

<x-emails::layout :preview="'Your BarangayBooks free trial ended on ' . $trialEndLabel" :title="'Your trial has ended'">
    <h1 style="margin:0 0 16px;font-size:20px;font-weight:700;color:#0f172a;">Your trial has ended</h1>

    <p style="margin:0 0 16px;">Hi {{ $org->name }} admin,</p>

    <p style="margin:0 0 16px;">
        Your BarangayBooks free trial ended on <strong>{{ $trialEndLabel }}</strong>.
        Until you pick a plan, your HOA's account is read-only &mdash; you can view your data, but new
        contributions, expenses, and member invites are disabled.
    </p>

    <p style="margin:24px 0;">
        <a href="{{ $billingUrl }}" style="display:inline-block;padding:10px 18px;background:#6366f1;color:#ffffff;text-decoration:none;border-radius:12px;font-weight:600;font-size:14px;">
            Choose a plan
        </a>
    </p>

    <p style="margin:16px 0;color:#475569;">
        All your existing records are safe and will be waiting when you subscribe.
    </p>

    <p style="margin:24px 0 0;color:#64748b;font-size:13px;">
        Thanks,<br>The BarangayBooks team
    </p>
</x-emails::layout>
