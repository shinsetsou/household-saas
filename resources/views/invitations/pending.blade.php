@extends('layouts.layout')

@section('title', 'Pending invitations')

@section('content')
<div class="max-w-2xl mx-auto mt-10 px-4">
    <div class="rounded-2xl bg-white dark:bg-slate-900 ring-1 ring-slate-200 dark:ring-slate-700 shadow-sm overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-700">
            <h1 class="text-lg font-semibold text-slate-900 dark:text-white">
                @if ($invitations->count() === 1)
                    You have a pending invitation
                @else
                    You have {{ $invitations->count() }} pending invitations
                @endif
            </h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Click an invitation to review and accept. You can decline from the review page.
            </p>
        </div>

        <ul class="divide-y divide-slate-100 dark:divide-slate-700">
            @forelse ($invitations as $row)
                @php
                    $invitation = $row['invitation'];
                    $role = $row['role'];
                    $signedUrl = $row['signed_url'];
                    $inviter = $row['inviter'];
                    $organization = $row['organization'];
                    $expiresInDays = $row['expires_in_days'];
                @endphp
                <li class="px-6 py-5 flex flex-wrap items-center gap-4">
                    <span class="flex-shrink-0 inline-flex items-center justify-center w-10 h-10 rounded-xl bg-gradient-to-br from-brand-500 to-violet-600 text-white text-sm font-bold">
                        {{ strtoupper(substr($organization->name ?? '?', 0, 1)) }}
                    </span>

                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-slate-900 dark:text-white truncate">
                            {{ $organization->name }}
                        </p>
                        <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                            Invited by {{ $inviter?->name ?? 'an admin' }}
                            <span class="mx-1">·</span>
                            Role: <span class="font-semibold">{{ $role->label() }}</span>
                            <span class="mx-1">·</span>
                            @if ($expiresInDays <= 0)
                                <span class="text-red-600 font-semibold">Expired today</span>
                            @else
                                Expires in {{ $expiresInDays }} {{ \Illuminate\Support\Str::plural('day', $expiresInDays) }}
                            @endif
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <a href="{{ $signedUrl }}"
                           class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold rounded-lg bg-gradient-to-r from-brand-600 to-violet-600 text-white hover:from-brand-700 hover:to-violet-700 transition">
                            Review &amp; accept
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </a>

                        <form method="POST" action="{{ route('invitations.pending.destroy', $invitation->token) }}" onsubmit="return confirm('Decline this invitation from {{ $organization->name }}? This cannot be undone.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-xs text-slate-500 hover:text-slate-700 hover:underline">
                                Decline
                            </button>
                        </form>
                    </div>
                </li>
            @empty
                <li class="px-6 py-12 text-center text-sm text-slate-500 dark:text-slate-400">
                    No pending invitations right now.
                </li>
            @endforelse
        </ul>
    </div>

    @guest
    @else
        <p class="mt-4 text-center text-xs text-slate-500 dark:text-slate-400">
            Don't want to join any of these?
            <form method="POST" action="{{ route('logout') }}" class="inline">
                @csrf
                <button type="submit" class="font-semibold text-brand-600 hover:text-brand-700">Sign out</button>
            </form>
            and try a different email.
        </p>
    @endguest
</div>
@endsection
