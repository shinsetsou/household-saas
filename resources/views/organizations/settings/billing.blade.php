@extends('organizations.settings.layout', ['activeTab' => 'billing'])

@section('tab-content')

@php
    use Illuminate\Support\Str;

    $org = $organization;
    $status = $org->subscription_status;
    $isTrialing = $org->isOnTrial();
    $isCanceling = $org->isCancelingAtPeriodEnd();
    $isCanceled = $status === 'canceled';
    $isPastDue = $status === 'past_due';

    $currentPlan = $org->plan;
    $householdCount = $org->households()->count();
    $householdLimit = $org->householdLimit();
    $householdProgress = $householdLimit ? min(100, (int) round($householdCount / max($householdLimit, 1) * 100)) : 0;

    $statusMeta = [
        'trialing' => ['label' => 'Trial', 'tone' => 'amber', 'pill' => 'bg-amber-100 text-amber-800'],
        'active'   => ['label' => 'Active', 'tone' => 'emerald', 'pill' => 'bg-emerald-100 text-emerald-800'],
        'past_due' => ['label' => 'Past due', 'tone' => 'red', 'pill' => 'bg-red-100 text-red-800'],
        'canceled' => ['label' => 'Canceled', 'tone' => 'slate', 'pill' => 'bg-slate-200 text-slate-700'],
        'unpaid'   => ['label' => 'Unpaid', 'tone' => 'red', 'pill' => 'bg-red-100 text-red-800'],
    ];
    $meta = $statusMeta[$status] ?? $statusMeta['canceled'];

    $trialDays = $isTrialing ? $org->daysUntilTrialEnds() : null;
@endphp

<div class="space-y-6">

    {{-- Current plan hero --}}
    <div class="rounded-2xl overflow-hidden ring-1 ring-slate-200 dark:ring-slate-700 shadow-sm bg-white dark:bg-gray-800">
        <div class="relative bg-gradient-to-br from-brand-700 via-brand-600 to-violet-600 text-white px-6 py-7 sm:px-8 sm:py-8">
            <div class="absolute inset-0 opacity-30 pointer-events-none" style="background: radial-gradient(40rem 20rem at 90% -10%, rgba(236,72,153,.4), transparent 60%), radial-gradient(30rem 18rem at -10% 110%, rgba(56,189,248,.4), transparent 60%);"></div>
            <div class="relative flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <p class="text-xs uppercase tracking-wider text-white/70 font-semibold">Current plan</p>
                    <h2 class="mt-1 text-3xl font-extrabold tracking-tight">
                        {{ $currentPlan?->name ?? 'No plan' }}
                    </h2>
                    <p class="mt-1 text-sm text-white/80">
                        @if($currentPlan)
                            {!! $currentPlan->formattedPrice() !!} / {{ $currentPlan->interval }}
                        @else
                            Choose a plan below to start collecting dues.
                        @endif
                    </p>
                </div>

                <div class="flex flex-col items-start sm:items-end gap-2">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-white/15 ring-1 ring-white/25 backdrop-blur-sm">
                        <span class="w-1.5 h-1.5 rounded-full bg-{{ $meta['tone'] }}-300 animate-pulse"></span>
                        {{ $meta['label'] }}
                    </span>
                    @if($isTrialing && $trialDays !== null)
                        <p class="text-xs text-white/80">{{ $trialDays }} {{ Str::plural('day', $trialDays) }} left in trial</p>
                    @elseif($isCanceling)
                        <p class="text-xs text-white/80">Ends {{ $org->subscription?->canceled_at?->format('M j, Y') }}</p>
                    @elseif($org->subscription?->current_period_end && $status === 'active')
                        <p class="text-xs text-white/80">Renews {{ $org->subscription->current_period_end->format('M j, Y') }}</p>
                    @endif
                </div>
            </div>
        </div>

        {{-- Household usage bar --}}
        <div class="px-6 py-6 sm:px-8">
            <div class="flex items-baseline justify-between mb-2">
                <p class="text-sm font-semibold text-slate-900 dark:text-white">Households</p>
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    <span class="font-bold text-slate-900 dark:text-white">{{ $householdCount }}</span>
                    @if($householdLimit !== null)
                        / {{ $householdLimit }}
                        @if($isTrialing)
                            <span class="ml-1 text-amber-600">(trial cap)</span>
                        @endif
                    @else
                        / &infin;
                    @endif
                </p>
            </div>
            @if($householdLimit !== null)
                <div class="h-2 rounded-full bg-slate-100 dark:bg-slate-700 overflow-hidden">
                    <div class="h-full rounded-full transition-all
                                {{ $householdProgress >= 100 ? 'bg-red-500' : ($householdProgress >= 80 ? 'bg-amber-500' : 'bg-gradient-to-r from-brand-500 to-violet-500') }}"
                         style="width: {{ $householdProgress }}%"></div>
                </div>
            @else
                <div class="h-2 rounded-full bg-slate-100 dark:bg-slate-700 overflow-hidden">
                    <div class="h-full w-1/3 rounded-full bg-gradient-to-r from-brand-500 to-violet-500 opacity-60"></div>
                </div>
            @endif

            {{-- Inline alert banner --}}
            @if($isTrialing && $trialDays !== null && $trialDays <= 7)
                <div class="mt-5 flex items-start gap-3 rounded-xl bg-amber-50 dark:bg-amber-900/20 ring-1 ring-amber-200 dark:ring-amber-800 p-4">
                    <svg class="w-5 h-5 flex-shrink-0 mt-0.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <div class="flex-1">
                        <p class="text-sm font-semibold text-amber-900 dark:text-amber-100">
                            Trial ends in {{ $trialDays }} {{ Str::plural('day', $trialDays) }}
                        </p>
                        <p class="mt-0.5 text-xs text-amber-800 dark:text-amber-200">
                            Pick a plan below to keep your HOA's books running without interruption.
                        </p>
                    </div>
                </div>
            @elseif($isCanceling)
                <div class="mt-5 flex items-start gap-3 rounded-xl bg-slate-50 dark:bg-slate-800/50 ring-1 ring-slate-200 dark:ring-slate-700 p-4">
                    <svg class="w-5 h-5 flex-shrink-0 mt-0.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <div class="flex-1">
                        <p class="text-sm font-semibold text-slate-900 dark:text-white">
                            Subscription ends {{ $org->subscription?->canceled_at?->format('M j, Y') }}
                        </p>
                        <p class="mt-0.5 text-xs text-slate-600 dark:text-slate-300">
                            You'll keep full access until then. Resume anytime before that date.
                        </p>
                    </div>
                    <form method="POST" action="{{ route('organizations.settings.billing.resume', $org->slug) }}">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold rounded-lg bg-gradient-to-r from-brand-600 to-violet-600 text-white hover:from-brand-700 hover:to-violet-700 transition">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            Resume
                        </button>
                    </form>
                </div>
            @elseif($isCanceled || $isPastDue)
                <div class="mt-5 flex items-start gap-3 rounded-xl bg-red-50 dark:bg-red-900/20 ring-1 ring-red-200 dark:ring-red-800 p-4">
                    <svg class="w-5 h-5 flex-shrink-0 mt-0.5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <div class="flex-1">
                        <p class="text-sm font-semibold text-red-900 dark:text-red-100">
                            {{ $isPastDue ? 'Payment is past due' : 'Subscription canceled' }}
                        </p>
                        <p class="mt-0.5 text-xs text-red-700 dark:text-red-200">
                            Pick a plan below to reactivate your HOA's account.
                        </p>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Plan picker --}}
    <div class="rounded-2xl bg-white dark:bg-gray-800 ring-1 ring-slate-200 dark:ring-slate-700 shadow-sm overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-700 flex items-start gap-3">
            <span class="flex-shrink-0 inline-flex items-center justify-center w-10 h-10 rounded-xl bg-gradient-to-br from-brand-500 to-violet-600 text-white shadow-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
            </span>
            <div class="flex-1">
                <h2 class="text-base font-semibold text-slate-900 dark:text-white">Choose a plan</h2>
                <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">Upgrade or downgrade at any time. Changes take effect immediately.</p>
            </div>
        </div>

        <div class="px-6 py-6 grid grid-cols-1 md:grid-cols-3 gap-4">
            @foreach($plans as $plan)
                @php
                    $isCurrent = $org->plan_id === $plan->id;
                    $isDowngrade = $currentPlan && $plan->price_cents < $currentPlan->price_cents;
                    $isUpgrade = $currentPlan && $plan->price_cents > $currentPlan->price_cents;
                    // Show a "subscribe" / "reactivate" button on the current plan
                    // when the org is trialing or past_due — they haven't actually
                    // paid for it yet, so the no-button-on-current rule doesn't apply.
                    $needsPayment = $isCurrent && ($isTrialing || $isPastDue);
                @endphp
                <div @class([
                    'relative rounded-2xl border-2 p-5 flex flex-col transition',
                    'border-brand-500 ring-2 ring-brand-200' => $isCurrent && ! $needsPayment,
                    'border-amber-400 ring-2 ring-amber-200' => $needsPayment,
                    'border-slate-200 dark:border-slate-700 hover:border-slate-300' => ! $isCurrent,
                ])>
                    @if($isCurrent)
                        @if($needsPayment)
                            <span class="absolute -top-2 right-4 inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider {{ $isPastDue ? 'bg-red-100 text-red-800' : 'bg-amber-100 text-amber-800' }}">
                                {{ $isPastDue ? 'Current · Past due' : 'Current · Trial' }}
                            </span>
                        @else
                            <span class="absolute -top-2 right-4 inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-gradient-to-r from-brand-600 to-violet-600 text-white shadow-sm">
                                Current
                            </span>
                        @endif
                    @elseif($isUpgrade)
                        <span class="absolute -top-2 right-4 inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-emerald-100 text-emerald-800">
                            Upgrade
                        </span>
                    @elseif($isDowngrade)
                        <span class="absolute -top-2 right-4 inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-amber-100 text-amber-800">
                            Downgrade
                        </span>
                    @endif

                    <h3 class="font-bold text-slate-900 dark:text-white">{{ $plan->name }}</h3>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400 min-h-[2.5rem]">{{ $plan->description }}</p>

                    <div class="mt-4 flex items-baseline gap-1">
                        <span class="text-3xl font-extrabold text-slate-900 dark:text-white">{!! $plan->formattedPrice() !!}</span>
                        <span class="text-sm text-slate-500">/ {{ $plan->interval }}</span>
                    </div>

                    <ul class="mt-4 space-y-1.5 text-sm text-slate-700 dark:text-slate-200 flex-1">
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            @if($plan->hasUnlimitedHouseholds())
                                Unlimited households
                            @else
                                Up to {{ $plan->max_households }} households
                            @endif
                        </li>
                        <li class="flex items-center gap-2">
                            @if($plan->csv_import)
                                <svg class="w-4 h-4 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            @else
                                <svg class="w-4 h-4 text-slate-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            @endif
                            CSV import
                        </li>
                        <li class="flex items-center gap-2">
                            @if($plan->api_access)
                                <svg class="w-4 h-4 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            @else
                                <svg class="w-4 h-4 text-slate-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            @endif
                            API access
                        </li>
                    </ul>

                    @if(! $isCurrent || $needsPayment)
                        <a href="{{ config('services.paymongo.storefront_url') }}"
                           target="_blank"
                           rel="noopener noreferrer"
                           @class([
                               'w-full inline-flex items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold transition focus:outline-none focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-slate-900',
                               'bg-gradient-to-r from-brand-600 to-violet-600 text-white shadow-sm hover:from-brand-700 hover:to-violet-700 focus:ring-brand-500' => $isUpgrade || ! $currentPlan || $needsPayment,
                               'bg-amber-50 text-amber-800 ring-1 ring-amber-200 hover:bg-amber-100 focus:ring-amber-500' => $isDowngrade,
                           ])>
                            @if($isDowngrade)
                                Downgrade to {{ $plan->name }}
                            @elseif($needsPayment && $isPastDue)
                                Reactivate {{ $plan->name }}
                            @elseif($needsPayment && $isTrialing)
                                Subscribe to {{ $plan->name }}
                            @else
                                Switch to {{ $plan->name }}
                            @endif
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                        </a>
                        @if($isDowngrade)
                            <p class="mt-2 text-[11px] text-slate-500 dark:text-slate-400 text-center">
                                Your current plan ends at period close — no double charge.
                            </p>
                        @elseif($needsPayment)
                            <p class="mt-2 text-[11px] text-slate-500 dark:text-slate-400 text-center">
                                @if($isTrialing)
                                    Start your {{ $plan->name }} subscription now &mdash; your trial converts to a paid plan.
                                @else
                                    Pay {{ $plan->formattedPrice() }} to restore write access to your HOA.
                                @endif
                            </p>
                        @endif
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    {{-- Recent payments --}}
    @if($payments->isNotEmpty())
        <div class="rounded-2xl bg-white dark:bg-gray-800 ring-1 ring-slate-200 dark:ring-slate-700 shadow-sm overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-700">
                <h2 class="text-base font-semibold text-slate-900 dark:text-white">Recent payments</h2>
            </div>
            <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-700 text-sm">
                <thead class="bg-slate-50 dark:bg-slate-800/50">
                    <tr class="text-left text-xs uppercase tracking-wider text-slate-500">
                        <th class="px-6 py-3 font-semibold">Date</th>
                        <th class="px-6 py-3 font-semibold">Description</th>
                        <th class="px-6 py-3 font-semibold">Amount</th>
                        <th class="px-6 py-3 font-semibold">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                    @foreach($payments as $payment)
                        <tr>
                            <td class="px-6 py-3 text-slate-700 dark:text-slate-200">{{ optional($payment->paid_at)->format('M j, Y') ?? $payment->created_at->format('M j, Y') }}</td>
                            <td class="px-6 py-3 text-slate-700 dark:text-slate-200">{{ $payment->description }}</td>
                            <td class="px-6 py-3 text-slate-900 dark:text-white font-semibold">{!! $payment->formattedAmount() !!}</td>
                            <td class="px-6 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold
                                    @switch($payment->status)
                                        @case('paid') bg-emerald-100 text-emerald-700 @break
                                        @case('failed') bg-red-100 text-red-700 @break
                                        @default bg-slate-100 text-slate-700
                                    @endswitch
                                ">{{ ucfirst($payment->status) }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- Danger zone: cancel subscription --}}
    @if($status === 'active' && ! $isCanceling)
        <div class="rounded-2xl bg-white dark:bg-gray-800 ring-1 ring-slate-200 dark:ring-slate-700 shadow-sm overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-700 flex items-start gap-3">
                <span class="flex-shrink-0 inline-flex items-center justify-center w-10 h-10 rounded-xl bg-red-100 text-red-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </span>
                <div>
                    <h2 class="text-base font-semibold text-slate-900 dark:text-white">Cancel subscription</h2>
                    <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">Your HOA keeps access until {{ $org->subscription?->current_period_end?->format('M j, Y') }}. Resume anytime before then.</p>
                </div>
            </div>
            <div class="px-6 py-5">
                <form method="POST" action="{{ route('organizations.settings.billing.cancel', $org->slug) }}" onsubmit="return confirm('Cancel at end of period? You\\'ll keep access until {{ $org->subscription?->current_period_end?->format('M j, Y') }}.');">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300 ring-1 ring-red-200 dark:ring-red-800 px-4 py-2 text-sm font-semibold hover:bg-red-100 dark:hover:bg-red-900/40 transition">
                        Cancel at end of period
                    </button>
                </form>
            </div>
        </div>
    @endif

</div>

@endsection