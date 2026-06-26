@extends('layouts.layout')

@section('title', 'Organization settings — ' . $organization->name)

@section('content')

{{-- Breadcrumb --}}
<nav class="text-sm text-gray-500 dark:text-gray-400 mb-3">
    <a href="{{ route('organizations.index', $organization->slug) }}" class="hover:text-indigo-600 transition-colors">Dashboard</a>
    <span class="mx-2">/</span>
    <span class="text-gray-700 dark:text-gray-200">Settings</span>
</nav>

{{-- Page header --}}
<div class="mb-6 flex items-center gap-4">
    <span class="flex-shrink-0 inline-flex items-center justify-center w-12 h-12 rounded-xl bg-gradient-to-br from-brand-500 to-violet-600 text-white shadow-sm">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
    </span>
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Organization settings</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage {{ $organization->name }}'s configuration.</p>
    </div>
</div>

{{-- Tab bar --}}
<div class="border-b border-gray-200 dark:border-gray-700 mb-6">
    <nav class="flex gap-1 overflow-x-auto" aria-label="Settings tabs">
        @php
            $tabs = [
                'general' => ['label' => 'General', 'route' => 'organizations.settings.show'],
                'members' => ['label' => 'Members', 'route' => 'organizations.settings.members.show'],
                'billing' => ['label' => 'Billing', 'route' => 'organizations.settings.billing.show'],
            ];
            $active = $activeTab ?? 'general';
        @endphp
        @foreach($tabs as $key => $tab)
            <a href="{{ route($tab['route'], $organization->slug) }}"
               class="px-4 py-2.5 text-sm font-semibold border-b-2 -mb-px whitespace-nowrap transition
                      {{ $key === $active
                            ? 'border-transparent text-white bg-gradient-to-r from-brand-600 to-violet-600 rounded-t-lg shadow-sm'
                            : 'border-transparent text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-50 dark:hover:bg-gray-800 rounded-t-lg' }}">
                {{ $tab['label'] }}
            </a>
        @endforeach
    </nav>
</div>

@yield('tab-content')

@endsection