@php
    $money = fn (int $cents) => 'PHP '.number_format($cents / 100, 2);
    $support = $payload['support_tickets'] ?? [];
    $openTickets = is_array($support) ? ($support['open'] ?? 0) : 0;
@endphp
BarangayBooks Founder Brief
{{ $date }} - dispatch {{ $dispatchId }}

MRR: {{ $money((int) ($payload['mrr_usd_cents'] ?? 0)) }}
Revenue (24h): {{ $money((int) ($payload['revenue_usd_cents'] ?? 0)) }}
New users: {{ $payload['new_users'] ?? 0 }}
New trials: {{ $payload['new_trials'] ?? 0 }}
New paying: {{ $payload['new_paying'] ?? 0 }}
Churn: {{ $payload['churn_count'] ?? 0 }}
Failed payments: {{ $payload['failed_payments'] ?? 0 }}
Open tickets: {{ $openTickets }}

@if (! empty($payload['top_3_risks']))
TOP 3 RISKS
@foreach ((array) $payload['top_3_risks'] as $risk)
- {{ is_array($risk) ? ($risk['title'] ?? json_encode($risk)) : $risk }}
@endforeach
@endif

@if (! empty($payload['top_3_wins']))
TOP 3 WINS
@foreach ((array) $payload['top_3_wins'] as $win)
- {{ is_array($win) ? ($win['title'] ?? json_encode($win)) : $win }}
@endforeach
@endif

@if (! empty($payload['suggested_priorities']))
SUGGESTED PRIORITIES
@foreach ((array) $payload['suggested_priorities'] as $priority)
- {{ is_array($priority) ? ($priority['title'] ?? json_encode($priority)) : $priority }}
@endforeach
@endif