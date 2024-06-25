<!-- resources/views/organizations/index.blade.php -->

@extends('layouts.layout')


@section('content')
    <div class="container mx-auto">
        <h1 class="text-5xl text-center justify-center text-cyan-600 dark:text-white/100 mb-20">{{ $organization->name }} Overview</h1>

        <form action="{{ route('organizations.index', $organization->slug) }}" method="GET" class="text-center mb-10">
            <div class="form-group inline-block">
                <label for="year" class="text-xl text-cyan-600 dark:text-white/100 uppercase">Select Year:</label>
                <input type="number" id="year" name="year" value="{{ $year }}" class="text-xl w-20 text-black dark:text-white dark:bg-gray-700 rounded-md" min="2000" max="{{ date('Y') + 1 }}">
                <button type="submit" class="text-xl text-secondary px-4 py-2 rounded-md shadow-md  bg-cyan-600 text-white dark:bg-blue-500 dark:text-white">Update</button>
            </div>
        </form>

        <div class="flex justify-center mb-10">
            <a href="{{ route('expenses.create', $organization->slug) }}" class="text-xl px-4 py-2 mr-4 rounded-md shadow-md bg-cyan-600 text-white dark:bg-green-500 dark:text-white">Add Expense</a>
            <a href="{{ route('contributions.verify', $organization->slug) }}" class="text-xl px-4 py-2 rounded-md shadow-md bg-cyan-600 text-white dark:bg-blue-500 dark:text-white">Verify Contributions</a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white dark:bg-gray-800 shadow-md rounded-md p-6 text-center">
                <h2 class="text-3xl text-cyan-600 dark:text-white/100 mb-4">Total Contributions ({{ $year }})</h2>
                <p class="text-4xl text-green-500 dark:text-green-400">{{ number_format($totalContributions, 2) }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 shadow-md rounded-md p-6 text-center">
                <h2 class="text-3xl text-cyan-600 dark:text-white/100 mb-4">Total Expenses ({{ $year }})</h2>
                <p class="text-4xl text-red-500 dark:text-red-400">{{ number_format($totalExpenses, 2) }}</p>
            </div>
        </div>
    </div>
@endsection
