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
        <span class="px-4 py-2 text-sm font-medium text-gray-400 cursor-not-allowed flex items-center gap-1.5" title="Coming soon">
            Members
            <span class="text-[10px] uppercase tracking-wider bg-gray-100 text-gray-500 rounded px-1.5 py-0.5">Coming soon</span>
        </span>
        <span class="px-4 py-2 text-sm font-medium text-gray-400 cursor-not-allowed flex items-center gap-1.5" title="Coming soon">
            Billing
            <span class="text-[10px] uppercase tracking-wider bg-gray-100 text-gray-500 rounded px-1.5 py-0.5">Coming soon</span>
        </span>
    </nav>
</div>

@yield('tab-content')

@endsection
