<?php

namespace App\Services;

use App\Models\Organization;
use Illuminate\Support\Carbon;

class DelinquencyService
{
    public function forOrganization(Organization $organization, int $year, int $month): array
    {
        $period = Carbon::create($year, $month, 1)->startOfMonth();
        $monthlyDue = (float) $organization->monthly_due_amount;
        $rows = [];

        foreach ($organization->households()->with('contributions')->orderBy('block')->orderBy('lot')->get() as $household) {
            $monthContributions = $household->contributions->filter(function ($contribution) use ($period) {
                $creditDate = $contribution->month_for ?? $contribution->created_at;

                return $creditDate && $creditDate->isSameMonth($period);
            });

            $verifiedPaid = $monthContributions
                ->where('verification_status', 'verified')
                ->where('status', 'paid')
                ->sum('amount');

            $hasLacking = $monthContributions->contains(fn ($contribution) => $contribution->status === 'lacking');

            if ($verifiedPaid >= $monthlyDue && ! $hasLacking) {
                continue;
            }

            $rows[] = [
                'household' => $household,
                'months_missing' => [$period->format('Y-m')],
                'amount_due' => max($monthlyDue - (float) $verifiedPaid, 0.0),
                'latest_payment_date' => $household->contributions->max('created_at'),
            ];
        }

        return $rows;
    }
}
