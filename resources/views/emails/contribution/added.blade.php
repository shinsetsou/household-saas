<x-emails::layout
    :preview="'A new ₱' . number_format($contributionData['amount'], 2) . ' contribution was recorded for household #' . $contributionData['household_id']"
    :title="'New contribution added'"
    accent="emerald"
>
    <x-emails::heading kicker="Contribution recorded" accent="emerald">
        A new contribution has been added
    </x-emails::heading>

    <x-emails::paragraph>
        A new contribution was just recorded for household
        <strong style="color:#0f172a;">#{{ $contributionData['household_id'] }}</strong>.
        It is now <span style="display:inline-block;padding:2px 8px;border-radius:9999px;background-color:#fef3c7;color:#92400e;font-size:12px;font-weight:600;letter-spacing:0.01em;font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">pending verification</span>
        &mdash; a treasurer or admin can review the proof of payment below.
    </x-emails::paragraph>

    <x-emails::data-list :rows="[
        ['Amount' => '&#8369;' . number_format($contributionData['amount'], 2)],
        ['Reference number' => '<span style=\"font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;color:#4338ca;\"\u003e' . e($contributionData['reference_number']) . '</span>'],
        ['Date' => now()->format('F j, Y')],
        ['Household' => '#' . $contributionData['household_id']],
    ]" />

    @if (! empty($contributionData['photo']))
        <p style="margin:24px 0 8px;font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:11px;font-weight:700;letter-spacing:0.12em;text-transform:uppercase;color:#64748b;">
            Proof of payment
        </p>
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 8px;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;background-color:#f8fafc;">
            <tr>
                <td style="padding:8px;">
                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('s3')->url($contributionData['photo']) }}" alt="Proof of payment" style="display:block;width:100%;height:auto;border-radius:8px;">
                </td>
            </tr>
        </table>
        <p style="margin:0;font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:12px;color:#94a3b8;">
            Attached separately so it remains in your inbox even if images are blocked.
        </p>
    @endif

    <x-emails::paragraph muted>
        You're receiving this because you're a member of this BarangayBooks organization.
    </x-emails::paragraph>

    <p style="margin:24px 0 0;font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:13px;color:#64748b;">
        &mdash; The BarangayBooks team
    </p>
</x-emails::layout>
