@php
    $money = fn (int $cents) => '&#8369;'.number_format($cents / 100, 2);
    $support = $payload['support_tickets'] ?? [];
    $openTickets = is_array($support) ? ($support['open'] ?? 0) : 0;
@endphp

<div style="font-family: ui-sans-serif, system-ui, sans-serif; max-width: 640px; margin: 0 auto; padding: 24px;">
    <h1 style="font-size: 24px; margin: 0 0 8px 0;">BarangayBooks Founder Brief</h1>
    <p style="color: #6b7280; margin: 0 0 24px 0;">{{ $date }} &middot; dispatch <code>{{ $dispatchId }}</code></p>

    <div style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 12px; padding: 16px 20px; margin-bottom: 16px;">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
            <div><strong>MRR:</strong> {{ $money((int) ($payload['mrr_usd_cents'] ?? 0)) }}</div>
            <div><strong>Revenue (24h):</strong> {{ $money((int) ($payload['revenue_usd_cents'] ?? 0)) }}</div>
            <div><strong>New users:</strong> {{ $payload['new_users'] ?? 0 }}</div>
            <div><strong>New trials:</strong> {{ $payload['new_trials'] ?? 0 }}</div>
            <div><strong>New paying:</strong> {{ $payload['new_paying'] ?? 0 }}</div>
            <div><strong>Churn:</strong> {{ $payload['churn_count'] ?? 0 }}</div>
            <div><strong>Failed payments:</strong> {{ $payload['failed_payments'] ?? 0 }}</div>
            <div><strong>Open tickets:</strong> {{ $openTickets }}</div>
        </div>
    </div>

    @if (! empty($payload['top_3_risks']))
        <h2 style="font-size: 18px; margin-top: 24px;">Top 3 risks</h2>
        <ul>
            @foreach ((array) $payload['top_3_risks'] as $risk)
                <li>{{ is_array($risk) ? ($risk['title'] ?? json_encode($risk)) : $risk }}</li>
            @endforeach
        </ul>
    @endif

    @if (! empty($payload['top_3_wins']))
        <h2 style="font-size: 18px; margin-top: 24px;">Top 3 wins</h2>
        <ul>
            @foreach ((array) $payload['top_3_wins'] as $win)
                <li>{{ is_array($win) ? ($win['title'] ?? json_encode($win)) : $win }}</li>
            @endforeach
        </ul>
    @endif

    @if (! empty($payload['suggested_priorities']))
        <h2 style="font-size: 18px; margin-top: 24px;">Suggested priorities</h2>
        <ul>
            @foreach ((array) $payload['suggested_priorities'] as $priority)
                <li>{{ is_array($priority) ? ($priority['title'] ?? json_encode($priority)) : $priority }}</li>
            @endforeach
        </ul>
    @endif
</div>