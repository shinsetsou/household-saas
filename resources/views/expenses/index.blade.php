@extends('layouts.layout')

@section('title', 'Expenses for ' . strtoupper($organization->name))

@section('content')
    <div class="container mx-auto p-4">
        <h1 class="text-4xl text-center text-cyan-600 dark:text-white/100 mb-10">Expenses Overview</h1>
        <div class="mt-2 text-left">
            <button onclick="window.history.back();" class="text-white bg-gray-500 hover:bg-gray-700 font-bold py-2 px-4 rounded">
                Back
            </button>
        </div>

        <form action="{{ route('expenses.index', $organization) }}" method="GET" class="mb-10">
            <div class="flex justify-center mb-4">
                <div class="mx-2">
                    <label for="month" class="block text-cyan-600 dark:text-white/100">Month:</label>
                    <select name="month" id="month" class="form-control text-black dark:text-white dark:bg-gray-700">
                        <option value="">All</option>
                        @for ($i = 1; $i <= 12; $i++)
                            <option value="{{ $i }}" {{ request('month') == $i ? 'selected' : '' }}>
                                {{ Carbon\Carbon::create()->month($i)->format('F') }}
                            </option>
                        @endfor
                    </select>
                </div>
                <div class="mx-2">
                    <label for="year" class="block text-cyan-600 dark:text-white/100">Year:</label>
                    <select name="year" id="year" class="form-control text-black dark:text-white dark:bg-gray-700">
                        <option value="">All</option>
                        @for ($i = now()->year; $i >= 2000; $i--)
                            <option value="{{ $i }}" {{ request('year') == $i ? 'selected' : '' }}>
                                {{ $i }}
                            </option>
                        @endfor
                    </select>
                </div>
                <div class="flex items-end mx-2">
                    <button type="submit" class="text-white bg-blue-500 hover:bg-blue-700 font-bold py-2 px-4 rounded">
                        Filter
                    </button>
                </div>
            </div>
        </form>

        <div class="overflow-x-auto">
            @php
                $columns = [
                    'created_at' => 'Date',
                    'amount' => 'Amount',
                    'description' => 'Description',
                ];
            @endphp
            <x-dynamic-table :columns="$columns" :items="$expenses" :itemsPerPage="$itemsPerPage" />
        </div>
    </div>
@endsection
