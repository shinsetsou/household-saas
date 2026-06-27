@extends('layouts.layout')

@section('title', 'Invitation revoked — BarangayBooks')

@section('content')
<div class="max-w-lg mx-auto mt-12 px-4">
    <div class="rounded-2xl bg-white dark:bg-slate-900 ring-1 ring-slate-200 dark:ring-slate-700 shadow-sm overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-700 flex items-start gap-3">
            <span class="flex-shrink-0 inline-flex items-center justify-center w-10 h-10 rounded-xl bg-rose-100 text-rose-700">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4a2 2 0 00-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z"/></svg>
            </span>
            <div>
                <h1 class="text-lg font-semibold text-slate-900 dark:text-white">Your invitation was revoked</h1>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    We can't add you to this HOA right now.
                </p>
            </div>
        </div>

        <div class="px-6 py-6 space-y-4 text-sm text-slate-700 dark:text-slate-200">
            <p>
                You were invited to
                @if ($user->invitation_origin_org_slug)
                    <strong>{{ $user->invitation_origin_org_slug }}</strong>
                @else
                    an organization
                @endif
                as
                <strong>{{ ucfirst($user->invitation_origin_role ?? 'a member') }}</strong>,
                but that invitation was revoked before you accepted.
            </p>

            @if ($user->invitation_origin_inviter_email)
                <p>
                    To rejoin, ask
                    <a href="mailto:{{ $user->invitation_origin_inviter_email }}" class="font-semibold text-brand-600 hover:text-brand-700">
                        {{ $user->invitation_origin_inviter_email }}
                    </a>
                    to send you a fresh invite.
                </p>
            @endif

            <p class="text-xs text-slate-500 dark:text-slate-400">
                Revoked on {{ $user->invitation_origin_revoked_at?->format('F j, Y g:i A') }}.
                Once you accept a new invite, this lock clears automatically.
            </p>
        </div>

        <div class="px-6 py-5 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-100 dark:border-slate-700 flex flex-wrap items-center gap-3">
            <form method="POST" action="{{ route('logout') }}" class="inline">
                @csrf
                <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-white dark:bg-slate-900 ring-1 ring-slate-200 dark:ring-slate-700 px-4 py-2.5 text-sm font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                    Sign out
                </button>
            </form>

            <a href="{{ route('onboarding.organization.create') }}" class="text-xs text-slate-500 hover:text-slate-700 hover:underline ml-auto">
                Create your own HOA instead →
            </a>
        </div>
    </div>
</div>
@endsection
