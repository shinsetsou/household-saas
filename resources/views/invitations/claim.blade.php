@extends('layouts.layout')

@section('title', 'Confirm your invitation')

@section('content')
<div class="max-w-md mx-auto mt-12">
    <div class="rounded-xl shadow-sm bg-white px-6 py-8">
        <h1 class="text-xl font-bold text-gray-900">Join {{ $invitation->organization->name }}</h1>
        <p class="mt-2 text-sm text-gray-600">
            {{ $invitation->inviter->name ?? 'Someone' }} invited
            <strong>{{ $invitation->email }}</strong>
            to join as
            <strong>{{ $role->label() }}</strong>.
        </p>
        <p class="mt-1 text-xs text-gray-500">{{ $role->description() }}</p>
        <p class="mt-3 text-xs text-gray-500">
            This invitation expires {{ $invitation->expires_at->format('F j, Y') }}.
        </p>

        <div class="mt-5 rounded-lg bg-indigo-50 px-4 py-3 text-xs text-indigo-900">
            For your security, confirm the 6-digit code from your invitation email before joining.
        </div>

        <form method="POST"
              action="{{ URL::signedRoute('invitations.claim', ['invitation' => $invitation->token]) }}"
              class="mt-5 space-y-4">
            @csrf
            <div>
                <label for="email" class="block text-xs font-medium text-gray-700">Email</label>
                <input id="email" name="email" type="email" inputmode="email" autocomplete="email"
                       value="{{ old('email') }}"
                       class="mt-1 block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                       required>
                @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="code" class="block text-xs font-medium text-gray-700">6-digit code</label>
                <input id="code" name="code" type="text" inputmode="numeric" pattern="\d{6}" maxlength="6"
                       autocomplete="one-time-code"
                       value="{{ old('code') }}"
                       class="mt-1 block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm font-mono tracking-widest"
                       required>
                @error('code')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <button type="submit"
                    class="w-full px-5 py-2.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold">
                Confirm and continue
            </button>
        </form>

        <p class="mt-4 text-xs text-gray-500">
            Lost the code? Ask the inviter to resend it from the members settings page.
        </p>
    </div>
</div>
@endsection
