<?php

declare(strict_types=1);

namespace App\AI\Tools\Founder;

use App\AI\Contracts\ToolResult;
use App\AI\ToolResultPayload;
use App\AI\Tools\BaseTool;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\SupportTicket;
use App\Models\User;
use Carbon\Carbon;

class GetDailyMetricsTool extends BaseTool
{
    public function name(): string
    {
        return 'get_daily_metrics';
    }

    public function description(): string
    {
        return 'Aggregate platform metrics for a given day (defaults to today). Returns new users, new trials, new paying customers, churn count, failed payments, and open support tickets.';
    }

    public function schema(): array
    {
        return $this->objectSchema([
            'date' => [
                'type' => 'string',
                'description' => 'ISO date (YYYY-MM-DD). Defaults to today.',
            ],
        ]);
    }

    protected function handle(array $arguments): ToolResult
    {
        $date = isset($arguments['date'])
            ? Carbon::parse($arguments['date'])->startOfDay()
            : Carbon::today();

        $endOfDay = $date->copy()->endOfDay();

        $newUsers = User::query()
            ->whereBetween('created_at', [$date, $endOfDay])
            ->count();

        $newTrials = Organization::query()
            ->where('subscription_status', Organization::SUBSCRIPTION_TRIALING)
            ->whereBetween('created_at', [$date, $endOfDay])
            ->count();

        $newPaying = Payment::query()
            ->where('status', Payment::STATUS_PAID)
            ->whereBetween('paid_at', [$date, $endOfDay])
            ->count();

        $revenueCents = (int) Payment::query()
            ->where('status', Payment::STATUS_PAID)
            ->whereBetween('paid_at', [$date, $endOfDay])
            ->sum('amount_cents');

        $failedPayments = Payment::query()
            ->where('status', Payment::STATUS_FAILED)
            ->whereBetween('created_at', [$date, $endOfDay])
            ->count();

        $churnCount = Organization::query()
            ->where('subscription_status', Organization::SUBSCRIPTION_CANCELED)
            ->whereBetween('updated_at', [$date, $endOfDay])
            ->count();

        $openTickets = SupportTicket::query()
            ->where('status', 'open')
            ->count();

        $todayTickets = SupportTicket::query()
            ->whereBetween('created_at', [$date, $endOfDay])
            ->count();

        return ToolResultPayload::success([
            'date' => $date->toDateString(),
            'new_users' => $newUsers,
            'new_trials' => $newTrials,
            'new_paying' => $newPaying,
            'revenue_usd_cents' => $revenueCents,
            'churn_count' => $churnCount,
            'failed_payments' => $failedPayments,
            'support_tickets' => [
                'open' => $openTickets,
                'today' => $todayTickets,
            ],
        ]);
    }
}
