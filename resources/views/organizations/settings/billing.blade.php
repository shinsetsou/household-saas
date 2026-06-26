@extends('organizations.settings.layout', ['activeTab' => 'billing'])

@section('tab-content')

<div class="space-y-6">
    <div class="rounded-xl shadow-sm bg-white px-6 py-6">
        <h2 class="text-lg font-semibold text-gray-900">Current plan</h2>
        <p class="mt-1 text-sm text-gray-500">Your subscription status and trial window.</p>

        <dl class="mt-6 grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <dt class="text-xs uppercase tracking-wider text-gray-500">Plan</dt>
                <dd class="mt-1 text-lg font-semibold text-gray-900">
                    {{ $organization->plan?->name ?? 'No plan' }}
                </dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wider text-gray-500">Status</dt>
                <dd class="mt-1">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold
                        @switch($organization->subscription_status)
                            @case('active') bg-green-100 text-green-700 @break
                            @case('trialing') bg-yellow-100 text-yellow-700 @break
                            @case('past_due') bg-red-100 text-red-700 @break
                            @default bg-gray-100 text-gray-700
                        @endswitch
                    ">
                        {{ ucfirst($organization->subscription_status) }}
                    </span>
                </dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wider text-gray-500">Households</dt>
                <dd class="mt-1 text-lg font-semibold text-gray-900">
                    {{ $organization->households()->count() }}
                    @if($organization->plan?->max_households)
                        / {{ $organization->plan->max_households }}
                    @else
                        / &infin;
                    @endif
                </dd>
            </div>
        </dl>

        @if($organization->isOnTrial())
            <p class="mt-4 text-sm text-yellow-700 bg-yellow-50 border border-yellow-200 rounded-lg px-3 py-2">
                Trial ends {{ $organization->trial_ends_at->format('M j, Y') }}.
                Add a payment method to keep your HOA's books running.
            </p>
        @endif
    </div>

    <div class="rounded-xl shadow-sm bg-white px-6 py-6">
        <h2 class="text-lg font-semibold text-gray-900">Choose a plan</h2>
        <p class="mt-1 text-sm text-gray-500">Upgrade or downgrade at any time.</p>

        <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
            @foreach($plans as $plan)
                <div class="rounded-xl border-2 {{ $organization->plan_id === $plan->id ? 'border-indigo-600' : 'border-gray-200' }} px-5 py-5 flex flex-col">
                    <div class="flex items-baseline justify-between">
                        <h3 class="font-bold text-gray-900">{{ $plan->name }}</h3>
                        @if($organization->plan_id === $plan->id)
                            <span class="text-xs font-semibold text-indigo-700">Current</span>
                        @endif
                    </div>
                    <p class="mt-1 text-sm text-gray-500">{{ $plan->description }}</p>

                    <div class="mt-4 flex items-baseline gap-1">
                        <span class="text-3xl font-extrabold">{!! $plan->formattedPrice() !!}</span>
                        <span class="text-sm text-gray-500">/ {{ $plan->interval }}</span>
                    </div>

                    <ul class="mt-4 space-y-1.5 text-sm text-gray-700 flex-1">
                        <li>
                            @if($plan->hasUnlimitedHouseholds())
                                &infin; households
                            @else
                                Up to {{ $plan->max_households }} households
                            @endif
                        </li>
                        <li>{{ $plan->csv_import ? 'CSV import' : 'No CSV import' }}</li>
                        <li>{{ $plan->api_access ? 'API access' : 'No API access' }}</li>
                    </ul>

                    @if($organization->plan_id !== $plan->id)
                        <form method="POST" action="{{ route('organizations.settings.billing.checkout', [$organization->slug, $plan->slug]) }}" class="mt-5">
                            @csrf
                            <button type="submit" class="w-full px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold">
                                Switch to {{ $plan->name }}
                            </button>
                        </form>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    @if($payments->isNotEmpty())
        <div class="rounded-xl shadow-sm bg-white px-6 py-6">
            <h2 class="text-lg font-semibold text-gray-900">Recent payments</h2>
            <table class="mt-4 min-w-full divide-y divide-gray-200 text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wider text-gray-500">
                        <th class="py-2">Date</th>
                        <th class="py-2">Description</th>
                        <th class="py-2">Amount</th>
                        <th class="py-2">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($payments as $payment)
                        <tr>
                            <td class="py-2 text-gray-700">{{ optional($payment->paid_at)->format('M j, Y') ?? $payment->created_at->format('M j, Y') }}</td>
                            <td class="py-2 text-gray-700">{{ $payment->description }}</td>
                            <td class="py-2 text-gray-900 font-semibold">{!! $payment->formattedAmount() !!}</td>
                            <td class="py-2">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold
                                    @switch($payment->status)
                                        @case('paid') bg-green-100 text-green-700 @break
                                        @case('failed') bg-red-100 text-red-700 @break
                                        @default bg-gray-100 text-gray-700
                                    @endswitch
                                ">{{ ucfirst($payment->status) }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if($organization->subscription_status === 'active')
        <div class="rounded-xl shadow-sm bg-white px-6 py-6">
            <h2 class="text-lg font-semibold text-gray-900">Cancel subscription</h2>
            <p class="mt-1 text-sm text-gray-500">Your HOA will keep access until the end of the current billing period.</p>
            <form method="POST" action="{{ route('organizations.settings.billing.cancel', $organization->slug) }}" class="mt-4">
                @csrf
                <button type="submit" class="px-4 py-2 rounded-lg bg-red-50 text-red-700 hover:bg-red-100 text-sm font-semibold border border-red-200">
                    Cancel subscription
                </button>
            </form>
        </div>
    @endif
</div>

@endsection