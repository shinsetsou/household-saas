

@extends('layouts.layout')

@section('title', strtoupper($household->name))

@section('content')
    <div class="grid grid-cols-2 gap-4">
        <div class="my-6">
        <a class="px-4 py-2 rounded-md shadow-md text-white bg-cyan-600 dark:bg-blue-500 dark:text-white" href="{{ route('contributions.create', [$organization, $household ]) }}">Pay Contribution</a>
        <a class="px-4 py-2 rounded-md shadow-md text-white bg-cyan-600 dark:bg-blue-500 dark:text-white" href="{{ route('home', [ $organization ]) }}">Home</a>
        </div>
        <div class="my-2 text-center">
            <h2 class="text-4xl text-cyan-600 dark:text-white/100 uppercase">Organization Standing</h2>
            <p class="mt-2 text-lg text-cyan-600 dark:text-white/100">Total Collected Monthly Dues: <strong class="text-green-500">${{ number_format($totalContributions, 2) }}</strong></p>
            <a href="{{ route('expenses.index', [ $organization ]) }}"><p class="text-lg text-cyan-600 dark:text-white/100">Total Expenses: <strong class="text-red-600">${{ number_format($totalExpenses, 2) }}</strong></p></a>
        </div>
    </div>

    @if (session('success'))
        <div>{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="my-2">
    <h2 class="text-4xl text-cyan-600 dark:text-white/100 capitalize">Contributions</h2>
    </div>
    @php
         $columns = [
            'id' => 'ID',
            'amount' => 'Amount',
            'photo' => 'Photo',
            'created_at' => 'Date',
            'status' => 'Status',
            'admin_verified' => 'Verified'
        ];
    @endphp
    <x-dynamic-table :columns="$columns" :items="$contributions" :itemsPerPage="$itemsPerPage"/>

@endsection
