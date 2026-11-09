@extends('layouts.layout')

@section('title', 'BarangayBooks Pricing')

@section('content')
<div class="space-y-10">
    <x-page-header
        eyebrow="Pricing"
        title="Simple plans for every HOA size"
        subtitle="Community at ₱199/month, Barangay at ₱499/month, and City at ₱999/month. Start with a 14-day free trial; free during beta for founding HOAs."
        tone="brand"
        icon="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>

    <div class="max-w-3xl">
        <p class="text-lg text-slate-700 dark:text-slate-300 leading-relaxed">
            <a href="{{ url('/') }}" class="text-brand-600 dark:text-brand-400 font-semibold hover:underline">BarangayBooks</a> keeps HOA dues collection affordable as your community grows. Every plan includes photo-verified payments, PDF/Excel/CSV exports, CSV household import, and email support. You can cancel anytime, and founding HOAs that join during beta lock in launch pricing.
        </p>
    </div>

    <div class="grid md:grid-cols-3 gap-6">
        {{-- Community --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm flex flex-col">
            <h3 class="text-xl font-bold text-slate-900 dark:text-white">Community</h3>
            <p class="mt-2 text-3xl font-extrabold text-slate-900 dark:text-white">₱199 <span class="text-base font-medium text-slate-500">/ month</span></p>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">Best for small villages and single-phase subdivisions.</p>
            <ul class="mt-4 space-y-2 text-sm text-slate-700 dark:text-slate-300 flex-1">
                <li class="flex items-start gap-2"><span class="text-brand-600">✓</span> Up to 50 households</li>
                <li class="flex items-start gap-2"><span class="text-brand-600">✓</span> Photo-verified payments</li>
                <li class="flex items-start gap-2"><span class="text-brand-600">✓</span> PDF / Excel / CSV reports</li>
                <li class="flex items-start gap-2"><span class="text-brand-600">✓</span> CSV household import</li>
            </ul>
            <p class="mt-4 text-xs text-slate-500">14-day free trial. Card required after trial.</p>
        </div>

        {{-- Barangay --}}
        <div class="bg-white dark:bg-slate-900 border-2 border-brand-500 rounded-2xl p-6 shadow-lg shadow-brand-900/10 flex flex-col relative">
            <span class="absolute -top-3 left-6 bg-brand-600 text-white text-xs font-bold px-3 py-1 rounded-full">Most popular</span>
            <h3 class="text-xl font-bold text-slate-900 dark:text-white">Barangay</h3>
            <p class="mt-2 text-3xl font-extrabold text-slate-900 dark:text-white">₱499 <span class="text-base font-medium text-slate-500">/ month</span></p>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">For active HOAs with multiple blocks and regular collections.</p>
            <ul class="mt-4 space-y-2 text-sm text-slate-700 dark:text-slate-300 flex-1">
                <li class="flex items-start gap-2"><span class="text-brand-600">✓</span> Up to 250 households</li>
                <li class="flex items-start gap-2"><span class="text-brand-600">✓</span> Photo-verified payments</li>
                <li class="flex items-start gap-2"><span class="text-brand-600">✓</span> PDF / Excel / CSV reports</li>
                <li class="flex items-start gap-2"><span class="text-brand-600">✓</span> CSV household import</li>
            </ul>
            <p class="mt-4 text-xs text-slate-500">14-day free trial. Card required after trial.</p>
        </div>

        {{-- City --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm flex flex-col">
            <h3 class="text-xl font-bold text-slate-900 dark:text-white">City</h3>
            <p class="mt-2 text-3xl font-extrabold text-slate-900 dark:text-white">₱999 <span class="text-base font-medium text-slate-500">/ month</span></p>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">For large associations and property-management groups.</p>
            <ul class="mt-4 space-y-2 text-sm text-slate-700 dark:text-slate-300 flex-1">
                <li class="flex items-start gap-2"><span class="text-brand-600">✓</span> Unlimited households</li>
                <li class="flex items-start gap-2"><span class="text-brand-600">✓</span> Photo-verified payments</li>
                <li class="flex items-start gap-2"><span class="text-brand-600">✓</span> PDF / Excel / CSV reports</li>
                <li class="flex items-start gap-2"><span class="text-brand-600">✓</span> CSV household import</li>
                <li class="flex items-start gap-2"><span class="text-brand-600">✓</span> API access</li>
            </ul>
            <p class="mt-4 text-xs text-slate-500">14-day free trial. Card required after trial.</p>
        </div>
    </div>

    <section class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 sm:p-8 shadow-sm">
        <h2 class="text-2xl font-bold text-slate-900 dark:text-white">What is included in every plan?</h2>
        <p class="mt-3 text-slate-700 dark:text-slate-300 leading-relaxed">
            Every tier supports monthly dues billing, photo-proof verification, automated reminders, delinquency tracking, and one-click exports. The only difference is the number of households you can manage and whether you need API access for integrations. You can upgrade or downgrade as your association grows.
        </p>
    </section>

    <section class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 sm:p-8 shadow-sm">
        <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Is there a free trial or beta offer?</h2>
        <p class="mt-3 text-slate-700 dark:text-slate-300 leading-relaxed">
            Yes. Every plan starts with a 14-day free trial so your board can test the full workflow. During beta, founding HOAs can use BarangayBooks without charge and lock in launch pricing before billing begins. No surprise charges and no long-term contract.
        </p>
    </section>

    <section class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 sm:p-8 shadow-sm">
        <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-4">Frequently asked questions</h2>
        <dl class="space-y-6">
            <div>
                <dt class="font-semibold text-slate-900 dark:text-white">Who owns the data?</dt>
                <dd class="mt-1 text-slate-700 dark:text-slate-300">Your HOA does. Always. We are the custodian, not the owner. Every record — households, contributions, photos, reports — belongs to your association.</dd>
            </div>
            <div>
                <dt class="font-semibold text-slate-900 dark:text-white">Can we export everything?</dt>
                <dd class="mt-1 text-slate-700 dark:text-slate-300">Yes. Contributions, expenses, delinquency, and household lists export to PDF, Excel, and CSV with one click. Even if you leave us, you walk away with your full ledger.</dd>
            </div>
            <div>
                <dt class="font-semibold text-slate-900 dark:text-white">Is it secure enough for handling member dues?</dt>
                <dd class="mt-1 text-slate-700 dark:text-slate-300">Records and uploaded receipts are encrypted at rest and in transit. Role-based access limits what each officer can see and do. A tamper-evident audit log records every state change with the user and timestamp.</dd>
            </div>
            <div>
                <dt class="font-semibold text-slate-900 dark:text-white">How fast can we onboard?</dt>
                <dd class="mt-1 text-slate-700 dark:text-slate-300">Most HOAs are live the same afternoon. Sign up, upload a CSV of your households, and you are collecting verified payments within the hour.</dd>
            </div>
            <div>
                <dt class="font-semibold text-slate-900 dark:text-white">Do you support GCash, Maya, or local bank integrations?</dt>
                <dd class="mt-1 text-slate-700 dark:text-slate-300">Not yet — that is on the roadmap. Today, members log payments with a photo of their GCash receipt or deposit slip, and officers verify in two taps. The proof is attached to every record.</dd>
            </div>
        </dl>
    </section>

    <div class="bg-brand-50 dark:bg-slate-900 border border-brand-100 dark:border-slate-800 rounded-2xl p-6 sm:p-8">
        <h2 class="text-xl font-bold text-slate-900 dark:text-white">Start your free trial today</h2>
        <p class="mt-2 text-slate-700 dark:text-slate-300">
            <a href="{{ route('register') }}" class="text-brand-600 dark:text-brand-400 font-semibold hover:underline">Create your account</a> in minutes, import your household list, and collect your first verified dues before the end of the day.
        </p>
    </div>
</div>
@endsection