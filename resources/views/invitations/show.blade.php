@extends('layouts.layout')

@section('title', 'Join ' . $invitation->organization->name)

@section('content')
<div class="max-w-md mx-auto mt-12">
    <div class="rounded-xl shadow-sm bg-white px-6 py-8">
        <h1 class="text-xl font-bold text-gray-900">Join {{ $invitation->organization->name }}</h1>
        <p class="mt-2 text-sm text-gray-600">
            {{ $invitation->inviter->name }} invited <strong>{{ $invitation->email }}</strong> to join as <strong>{{ $role->label() }}</strong>.
        </p>
        <p class="mt-1 text-xs text-gray-500">{{ $role->description() }}</p>
        <p class="mt-3 text-xs text-gray-500">This invitation expires {{ $invitation->expires_at->format('F j, Y') }}.</p>

        <form method="POST" action="{{ route('invitations.accept', $invitation->token) }}" class="mt-6 flex gap-2">
            @csrf
            <button type="submit" class="flex-1 px-5 py-2.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold">
                Accept invitation
            </button>
        </form>
        <form method="POST" action="{{ route('invitations.decline', $invitation->token) }}" class="mt-2">
            @csrf
            <button type="submit" class="w-full px-5 py-2 rounded-lg bg-white hover:bg-gray-50 text-gray-700 text-sm font-semibold border border-gray-300">
                Decline
            </button>
        </form>
    </div>
</div>
@endsection