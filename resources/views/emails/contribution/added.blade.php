<x-emails::layout :preview="'A new contribution has been added for household #' . $contributionData['household_id']" :title="'New Contribution Added'">
    <h1 style="margin:0 0 16px;font-size:20px;font-weight:700;color:#0f172a;">New contribution added</h1>

    <p style="margin:0 0 16px;">
        A new contribution has been recorded for household
        <strong>#{{ $contributionData['household_id'] }}</strong>.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:16px 0;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;">
        <tr>
            <td style="padding:12px 16px;background-color:#f8fafc;font-size:13px;color:#64748b;width:40%;">Amount</td>
            <td style="padding:12px 16px;font-size:14px;font-weight:600;color:#0f172a;">&#8369;{{ number_format($contributionData['amount'], 2) }}</td>
        </tr>
        <tr>
            <td style="padding:12px 16px;background-color:#f8fafc;font-size:13px;color:#64748b;">Reference number</td>
            <td style="padding:12px 16px;font-size:14px;color:#0f172a;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;">{{ $contributionData['reference_number'] }}</td>
        </tr>
        <tr>
            <td style="padding:12px 16px;background-color:#f8fafc;font-size:13px;color:#64748b;">Date</td>
            <td style="padding:12px 16px;font-size:14px;color:#0f172a;">{{ now()->format('F j, Y') }}</td>
        </tr>
    </table>

    @if (! empty($contributionData['photo']))
        <p style="margin:24px 0 8px;font-size:13px;font-weight:600;color:#0f172a;">Proof of payment</p>
        <img src="{{ \Illuminate\Support\Facades\Storage::disk('s3')->url($contributionData['photo']) }}" alt="Proof of payment" style="display:block;max-width:100%;height:auto;border-radius:12px;border:1px solid #e2e8f0;">
    @endif

    <p style="margin:24px 0 0;color:#64748b;font-size:13px;">
        Thanks,<br>The BarangayBooks team
    </p>
</x-emails::layout>
