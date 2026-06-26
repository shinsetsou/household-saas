@extends('layouts.layout')

@section('title', 'Organization settings — ' . $organization->name)

@section('content')

{{-- Breadcrumb --}}
<nav class="text-sm text-gray-500 mb-3">
    <a href="{{ route('organizations.index', $organization->slug) }}" class="hover:text-gray-700">Dashboard</a>
    <span class="mx-2">/</span>
    <span class="text-gray-700">Settings</span>
</nav>

{{-- Page header --}}
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Organization settings</h1>
    <p class="mt-1 text-sm text-gray-500">Manage {{ $organization->name }}'s configuration.</p>
</div>

{{-- Tab bar --}}
<div class="border-b border-gray-200 mb-6">
    <nav class="flex gap-1" aria-label="Settings tabs">
        <a href="{{ route('organizations.settings.show', $organization->slug) }}"
           class="px-4 py-2 text-sm font-semibold border-b-2 -mb-px {{ ($activeTab ?? 'general') === 'general' ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-gray-600 hover:text-gray-900' }}">
            General
        </a>
        <a href="{{ route('organizations.settings.members.show', $organization->slug) }}"
           class="px-4 py-2 text-sm font-semibold border-b-2 -mb-px {{ ($activeTab ?? null) === 'members' ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-gray-600 hover:text-gray-900' }}">
            Members
        </a>
        <a href="{{ route('organizations.settings.billing.show', $organization->slug) }}"
           class="px-4 py-2 text-sm font-semibold border-b-2 -mb-px {{ ($activeTab ?? null) === 'billing' ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-gray-600 hover:text-gray-900' }}">
            Billing
        </a>
    </nav>
</div>

@yield('tab-content')

@endsection