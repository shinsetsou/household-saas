<!-- contributions.verify.blade.php -->

@extends('layouts.layout')

@section('title', 'Contribution Verification')

@section('content')
    <div class="container mx-auto">
        <h1 class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl xl:text-6xl text-center justify-center text-cyan-600 dark:text-white/100 mb-20">{{ $organization->name }}</h1>

        <div class="bg-white dark:bg-gray-800 p-6 rounded-md shadow-md">
            <h2 class="text-l sm:text-xl md:text-2xl lg:text-3xl xl:text-4xl text-cyan-600 dark:text-white/100 mb-4">Pending Verifications</h2>
            @if (session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
            @endif
            @php
                $columns = [
                    'reference_number' => 'Reference Number',
                    'total_amount' => 'Total Amount',
                    'household_name' => 'Household Name',
                    // Add more columns as needed
                ];
                $items = $contributions;
                $actions = [
                    [
                        'route' => 'contributions.verify.store',
                        'method' => 'PATCH',
                        'button_label' => 'Verified',
                        'button_class' => 'bg-green-500 text-white dark:bg-green-500 dark:text-white',
                        'additional_params' => [
                            ['admin_verified' => 1]
                        ]
                    ],
                    [
                        'route' => 'contributions.verify.store',
                        'method' => 'PATCH',
                        'button_label' => 'Disproved',
                        'button_class' => 'bg-red-500 text-white dark:bg-red-500 dark:text-white',
                        'additional_params' => [
                            ['admin_verified' => 3]
                        ]
                    ],
                    // Add more actions as needed
                ];
            @endphp

            @include('components.dynamic-table', [
                'columns' => $columns,
                'items' => $items,
                'itemsPerPage' => $itemsPerPage,
                'organization' => $organization,
                'showActions' => true,
                'actions' => $actions,
            ])
        </div>
    </div>
@endsection
