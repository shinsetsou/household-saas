@extends('layouts.layout')

@section('title', 'Import Households')

@section('content')
<div class="mb-8">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Import Households</h1>
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Upload a CSV with name, block, and lot columns.</p>
</div>

<div class="max-w-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm px-6 py-6">
    <form action="{{ route('households.import.store', $organization) }}" method="POST" enctype="multipart/form-data" class="space-y-5">
        @csrf
        <div>
            <label for="csv" class="block text-sm font-medium text-gray-700 dark:text-gray-300">CSV file</label>
            <input id="csv" name="csv" type="file" accept=".csv,text/csv"
                   class="mt-2 block w-full text-sm text-gray-700 dark:text-gray-300">
            @error('csv')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-3">
            <div class="flex items-center justify-between">
                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Sample format</p>
                <a href="{{ route('households.import.sample', $organization) }}"
                   class="text-sm font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">Download sample CSV</a>
            </div>
            <pre class="mt-2 overflow-x-auto rounded bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 px-3 py-2 text-xs text-gray-700 dark:text-gray-300"><code>name,block,lot
Dela Cruz Residence,1,1
Reyes Residence,1,2
Santos Residence,2,5</code></pre>
            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">First row must be the header. <code>block</code> and <code>lot</code> must be whole numbers.</p>
        </div>

        <div class="flex gap-3">
            <a href="{{ route('households.index', $organization) }}" class="px-4 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600">Cancel</a>
            <button type="submit" class="px-4 py-2 text-sm font-semibold rounded-lg bg-indigo-600 text-white">Import</button>
        </div>
    </form>
</div>
@endsection
