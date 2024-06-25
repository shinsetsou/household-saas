<!-- resources/views/expenses/create.blade.php -->

@extends('layouts.layout')

@section('title', 'Expenses Management')

@section('content')
    <div class="container mx-auto">
        <h1 class="text-5xl text-center justify-center text-cyan-600 dark:text-white/100 mb-20">{{ $organization->name }}</h1>

        <form action="{{ route('expenses.store', $organization) }}" method="POST" class="bg-gray-100 dark:bg-gray-800 p-6 rounded-md shadow-md">
            @csrf
            <div class="form-group mb-4">
                <label for="description" class="block text-lg text-cyan-600 dark:text-white/100">Description:</label>
                <input type="text" id="description" name="description" class="w-full p-2 rounded-md dark:bg-gray-700 dark:text-white" required>
            </div>
            <div class="form-group mb-4">
                <label for="amount" class="block text-lg text-cyan-600 dark:text-white/100">Amount:</label>
                <input type="number" id="amount" name="amount" class="w-full p-2 rounded-md dark:bg-gray-700 dark:text-white" required>
            </div>
            <button type="submit" class="text-xl text-secondary px-4 py-2 bg-cyan-600 text-white rounded-md shadow-md dark:bg-blue-500 dark:text-white">Add Expense</button>
        </form>
    </div>
@endsection
