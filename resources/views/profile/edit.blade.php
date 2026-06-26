@extends('layouts.layout')

@section('title', isset($organization) ? 'Profile - ' . $organization->name : 'Profile')

@section('content')
    {{-- Breadcrumb --}}
    <nav class="text-sm text-gray-500 dark:text-gray-400 mb-3">
        @if($organization)
            <a href="{{ route('organizations.index', $organization) }}" class="hover:text-indigo-600 transition-colors">Dashboard</a>
            <span class="mx-2">/</span>
        @endif
        <span class="text-gray-700 dark:text-gray-200">Profile</span>
    </nav>

    {{-- Page Header --}}
    <div class="mb-6 flex items-center gap-4">
        <span class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-gradient-to-br from-brand-500 to-violet-600 text-white shadow-brand-glow">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
        </span>
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Your profile</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage your account information and password.</p>
        </div>
    </div>

    {{-- Content cards --}}
    <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-2xl bg-white dark:bg-gray-800 ring-1 ring-slate-200 dark:ring-slate-700 shadow-sm p-6 lg:p-8">
            @include('profile.partials.update-profile-information-form')
        </div>
        <div class="rounded-2xl bg-white dark:bg-gray-800 ring-1 ring-slate-200 dark:ring-slate-700 shadow-sm p-6 lg:p-8">
            @include('profile.partials.update-password-form')
        </div>
        <div class="rounded-2xl bg-white dark:bg-gray-800 ring-1 ring-slate-200 dark:ring-slate-700 shadow-sm p-6 lg:p-8 lg:col-span-2">
            @include('profile.partials.delete-user-form')
        </div>
    </div>
@endsection