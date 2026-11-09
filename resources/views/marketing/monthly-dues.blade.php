@extends('layouts.layout')

@section('title', 'Monthly Dues Billing for Philippine HOAs')

@section('content')
<div class="space-y-10">
    <x-page-header
        eyebrow="Monthly dues"
        title="Automated monthly dues billing for Philippine HOAs"
        subtitle="Set your rates once, generate statements in one click, track penalties, and verify GCash or Maya payments with photo proof."
        tone="brand"
        icon="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>

    <div class="max-w-3xl">
        <p class="text-lg text-slate-700 dark:text-slate-300 leading-relaxed">
            Chasing homeowners for monthly dues month after month drains volunteer time and creates conflict. <a href="{{ url('/') }}" class="text-brand-600 dark:text-brand-400 font-semibold hover:underline">BarangayBooks</a> automates the billing cycle so your treasurer can focus on governance instead of spreadsheets. Set the dues rate per household, pick a due date, and let the system generate a statement of account for every home in seconds.
        </p>
    </div>

    <section class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 sm:p-8 shadow-sm">
        <h2 class="text-2xl font-bold text-slate-900 dark:text-white">How do you set up dues rates in BarangayBooks?</h2>
        <p class="mt-3 text-slate-700 dark:text-slate-300 leading-relaxed">
            An officer enters the monthly amount once per association or per household type. The rate is applied automatically to every active household in the next billing cycle. You can adjust rates anytime, and the change is tracked in the audit log so the next board always knows what was charged and why.
        </p>
    </section>

    <section class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 sm:p-8 shadow-sm">
        <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Can statements be generated in one click?</h2>
        <p class="mt-3 text-slate-700 dark:text-slate-300 leading-relaxed">
            Yes. When billing day arrives, the treasurer clicks a single button to generate statements for all households. Each statement shows the current balance, any previous unpaid months, penalties, and a unique payment link or instructions. The whole process takes seconds instead of hours.
        </p>
    </section>

    <section class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 sm:p-8 shadow-sm">
        <h2 class="text-2xl font-bold text-slate-900 dark:text-white">How does penalty handling work?</h2>
        <p class="mt-3 text-slate-700 dark:text-slate-300 leading-relaxed">
            Associations can configure a fixed or percentage penalty that applies after the due date. BarangayBooks rolls the penalty into the next statement automatically and flags delinquent households in the delinquency report. Officers can also waive penalties when the board approves an exception.
        </p>
    </section>

    <section class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 sm:p-8 shadow-sm">
        <h2 class="text-2xl font-bold text-slate-900 dark:text-white">What is the photo-proof verification flow?</h2>
        <p class="mt-3 text-slate-700 dark:text-slate-300 leading-relaxed">
            After paying through GCash, Maya, or a bank deposit, the member opens their household link, enters the amount and reference number, and uploads a screenshot or photo of the receipt. The treasurer receives a pending contribution, opens the attached proof, and verifies or rejects it. Once verified, the statement balance updates instantly and the receipt is stored forever.
        </p>
    </section>

    <section class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 sm:p-8 shadow-sm">
        <h2 class="text-2xl font-bold text-slate-900 dark:text-white">How do members know what they owe?</h2>
        <p class="mt-3 text-slate-700 dark:text-slate-300 leading-relaxed">
            Each household has a public lookup page accessible by block and lot. Members can view their statement of account, see which months are paid, and submit a payment with proof in under a minute. Officers can also send reminders through email or shareable links with a single click.
        </p>
    </section>

    <section class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 sm:p-8 shadow-sm">
        <h2 class="text-2xl font-bold text-slate-900 dark:text-white">What reports help with collections?</h2>
        <p class="mt-3 text-slate-700 dark:text-slate-300 leading-relaxed">
            The delinquency report lists every household with an outstanding balance, the months overdue, and total arrears. The contribution report shows verified collections by period, payment method, and officer. Both export to PDF, Excel, and CSV so the treasurer can present clean records to the board, barangay, or auditor.
        </p>
    </section>

    <section class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 sm:p-8 shadow-sm">
        <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Is the data ready for audit and turnover?</h2>
        <p class="mt-3 text-slate-700 dark:text-slate-300 leading-relaxed">
            Every transaction is tied to a user and timestamped. The audit trail shows who created a bill, who verified a payment, and who edited a household record. When a new board takes over, they can export the full ledger in one click instead of deciphering handwritten notebooks.
        </p>
    </section>

    <div class="bg-brand-50 dark:bg-slate-900 border border-brand-100 dark:border-slate-800 rounded-2xl p-6 sm:p-8">
        <h2 class="text-xl font-bold text-slate-900 dark:text-white">Switch from manual billing this month</h2>
        <p class="mt-2 text-slate-700 dark:text-slate-300">
            <a href="{{ route('register') }}" class="text-brand-600 dark:text-brand-400 font-semibold hover:underline">Create a free BarangayBooks account</a>, import your households from CSV, and send your first automated statement of account before your next collection day.
        </p>
    </div>
</div>
@endsection