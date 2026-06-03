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
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Profile</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage your account information and password.</p>
    </div>

    {{-- Content card --}}
    <div class="rounded-xl shadow-sm bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 px-6 py-6">
        <div class="space-y-6">
            <div class="p-4 sm:p-8 bg-gray-50 dark:bg-gray-900/40 rounded-xl">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-gray-50 dark:bg-gray-900/40 rounded-xl">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-gray-50 dark:bg-gray-900/40 rounded-xl">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
@endsection
