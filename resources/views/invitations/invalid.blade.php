@extends('layouts.layout')

@section('title', 'Invitation unavailable')

@section('content')
<div class="max-w-md mx-auto mt-12">
    <div class="rounded-xl shadow-sm bg-white px-6 py-8 text-center">
        <h1 class="text-xl font-bold text-gray-900">Invitation unavailable</h1>
        @if($invitation->isAccepted())
            <p class="mt-3 text-sm text-gray-600">This invitation has already been accepted.</p>
        @elseif($invitation->isRevoked())
            <p class="mt-3 text-sm text-gray-600">This invitation has been revoked.</p>
        @elseif($invitation->isExpired())
            <p class="mt-3 text-sm text-gray-600">This invitation expired on {{ $invitation->expires_at->format('F j, Y') }}. Ask the inviter to send a new one.</p>
        @endif
        <a href="{{ route('dashboard') }}" class="mt-6 inline-block px-5 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold">
            Go to dashboard
        </a>
    </div>
</div>
@endsection