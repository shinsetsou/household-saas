@php
    $billingUrl = route('organizations.settings.billing.show', $org->slug);
    $trialEndLabel = optional($org->trial_ends_at)->format('F j, Y') ?? 'today';
@endphp

<x-emails::layout
    :preview="'Your BarangayBooks free trial ended on ' . $trialEndLabel . ' — subscribe to restore write access.'"
    :title="'Your trial has ended'"
    accent="rose"
>
    <x-emails::heading kicker="Trial ended" accent="rose">
        Your trial has ended
    </x-emails::heading>

    <x-emails::paragraph>
        Hi {{ $org->name }} admin &mdash; your BarangayBooks free trial ended on
        <strong style="color:#0f172a;">{{ $trialEndLabel }}</strong>.
        Until you pick a plan, your HOA's account is <strong style="color:#0f172a;">read-only</strong> &mdash; you can still view your data, but new contributions, expenses, and member invites are disabled.
    </x-emails::paragraph>

    <x-emails::data-list :rows="[
        ['Organization' => e($org->name)],
        ['Trial ended' => $trialEndLabel],
        ['Current status' => '<span style=\"display:inline-block;padding:2px 8px;border-radius:9999px;background-color:#ffe4e6;color:#be123c;font-size:12px;font-weight:600;\">Read-only</span>'],
    ]" />

    <x-emails::button :href="$billingUrl" color="rose">
        Choose a plan
    </x-emails::button>

    <p style="margin:4px 0 0;font-size:13px;color:#475569;line-height:1.6;">
        All your existing records are safe and will be waiting when you subscribe. No data has been deleted.
    </p>

    <p style="margin:24px 0 0;font-size:13px;color:#64748b;">
        &mdash; The BarangayBooks team
    </p>
</x-emails::layout>
