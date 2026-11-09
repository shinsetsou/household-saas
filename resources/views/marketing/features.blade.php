@extends('layouts.layout')

@section('title', 'HOA Dues Collection Features')

@section('content')
<div class="space-y-10">
    <x-page-header
        eyebrow="Product features"
        title="Everything BarangayBooks does for your HOA"
        subtitle="From monthly dues billing to photo-proof verification, audit-ready reports, and role-based access — built for Philippine homeowners associations."
        tone="brand"
        icon="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6 4h6a2 2 0 002-2v-4H7v4a2 2 0 002 2z"/>

    <div class="max-w-3xl">
        <p class="text-lg text-slate-700 dark:text-slate-300 leading-relaxed">
            <a href="{{ url('/') }}" class="text-brand-600 dark:text-brand-400 font-semibold hover:underline">BarangayBooks</a> replaces the messy GCash group-chat threads and dog-eared notebooks with a clean, online ledger. Treasurers set dues once, generate statements in one click, and let members upload proof of payment from GCash, Maya, or bank deposits. Every record is timestamped, attached to a user, and ready for audit.
        </p>
    </div>

    <section class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 sm:p-8 shadow-sm">
        <h2 class="text-2xl font-bold text-slate-900 dark:text-white">What does BarangayBooks do for HOA treasurers?</h2>
        <p class="mt-3 text-slate-700 dark:text-slate-300 leading-relaxed">
            Treasurers stop chasing receipts. The app creates a recurring dues schedule, auto-generates a statement of account per household, and highlights delinquencies in real time. When a member pays, they upload a receipt photo; the treasurer verifies it in two taps and the balance updates instantly.
        </p>
        <ul class="mt-4 grid sm:grid-cols-2 gap-3 text-slate-700 dark:text-slate-300">
            <li class="flex items-start gap-2"><span class="text-brand-600">✓</span> Recurring monthly dues billing</li>
            <li class="flex items-start gap-2"><span class="text-brand-600">✓</span> One-click statement generation</li>
            <li class="flex items-start gap-2"><span class="text-brand-600">✓</span> Photo receipt verification</li>
            <li class="flex items-start gap-2"><span class="text-brand-600">✓</span> Delinquency and collection reports</li>
            <li class="flex items-start gap-2"><span class="text-brand-600">✓</span> PDF, Excel, and CSV exports</li>
            <li class="flex items-start gap-2"><span class="text-brand-600">✓</span> Tamper-evident audit log</li>
        </ul>
    </section>

    <section class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 sm:p-8 shadow-sm">
        <h2 class="text-2xl font-bold text-slate-900 dark:text-white">How does photo-proof verification work?</h2>
        <p class="mt-3 text-slate-700 dark:text-slate-300 leading-relaxed">
            Members log a payment by entering the amount, date, and reference number, then attach a photo of their GCash receipt, Maya confirmation, or bank deposit slip. The treasurer sees the pending contribution, compares the photo against the amount, and marks it verified. The receipt stays attached forever, so audits are painless.
        </p>
    </section>

    <section class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 sm:p-8 shadow-sm">
        <h2 class="text-2xl font-bold text-slate-900 dark:text-white">What reminders and follow-ups are built in?</h2>
        <p class="mt-3 text-slate-700 dark:text-slate-300 leading-relaxed">
            The system tracks due dates and outstanding balances automatically. Officers can send payment reminders by email or through shareable payment links. Each household gets a clear statement of account showing what is due, what has been paid, and what is pending verification.
        </p>
    </section>

    <section class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 sm:p-8 shadow-sm">
        <h2 class="text-2xl font-bold text-slate-900 dark:text-white">What reports can we export?</h2>
        <p class="mt-3 text-slate-700 dark:text-slate-300 leading-relaxed">
            BarangayBooks exports contribution summaries, expense ledgers, delinquency lists, and household directories to PDF, Excel, and CSV. These reports include verification status, reference numbers, and officer notes, so barangay administrators, auditors, and AGM boards get exactly what they need.
        </p>
    </section>

    <section class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 sm:p-8 shadow-sm">
        <h2 class="text-2xl font-bold text-slate-900 dark:text-white">How do barangay admins use BarangayBooks?</h2>
        <p class="mt-3 text-slate-700 dark:text-slate-300 leading-relaxed">
            Barangay administrators can review association-wide compliance, compare collections across periods, and download standardized reports for filing or verification. Role-based access means barangay staff see only the data their permissions allow, keeping household records private and secure.
        </p>
    </section>

    <section class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 sm:p-8 shadow-sm">
        <h2 class="text-2xl font-bold text-slate-900 dark:text-white">What do board members get out of it?</h2>
        <p class="mt-3 text-slate-700 dark:text-slate-300 leading-relaxed">
            Board members gain visibility without micromanaging. They can view real-time collection status, see who is delinquent, and review a full audit trail of every change. That transparency builds trust with homeowners and makes turnover smooth when a new board takes over.
        </p>
    </section>

    <section class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 sm:p-8 shadow-sm">
        <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Is it secure enough for handling member dues?</h2>
        <p class="mt-3 text-slate-700 dark:text-slate-300 leading-relaxed">
            Records and uploaded receipts are encrypted at rest and in transit. Role-based access limits what each officer can see and do, and a tamper-evident audit log records every state change with the user and timestamp. Your HOA owns its data and can export everything at any time.
        </p>
    </section>

    <div class="bg-brand-50 dark:bg-slate-900 border border-brand-100 dark:border-slate-800 rounded-2xl p-6 sm:p-8">
        <h2 class="text-xl font-bold text-slate-900 dark:text-white">Ready to clean up your HOA collections?</h2>
        <p class="mt-2 text-slate-700 dark:text-slate-300">
            <a href="{{ route('register') }}" class="text-brand-600 dark:text-brand-400 font-semibold hover:underline">Create a free account</a> and start collecting verified dues the same afternoon. No credit card is needed during beta, and founding HOAs lock in launch pricing.
        </p>
    </div>
</div>
@endsection