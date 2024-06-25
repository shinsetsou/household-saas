<!-- contributions.verify.blade.php -->

@extends('layouts.layout')

@section('title', 'Contribution Verification')

@section('content')
    <div class="container mx-auto">
        <h1 class="text-5xl text-center justify-center text-cyan-600 dark:text-white/100 mb-20">{{ $organization->name }}</h1>

        <div class="bg-white dark:bg-gray-800 p-6 rounded-md shadow-md">
            <h2 class="text-3xl text-cyan-600 dark:text-white/100 mb-4">Pending Verifications</h2>
            @if (session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
            @endif
            <ul>
                @foreach ($contributions as $contribution)
                    <li class="mb-2">
                        Reference Number: {{ $contribution->reference_number }} - Total Amount: {{ $contribution->total_amount  }}
                        <form action="{{ route('contributions.verify.store', [$organization, $contribution->reference_number]) }}" method="POST" style="display:inline">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="text-sm text-secondary px-2 py-1 rounded-md shadow-md dark:bg-green-500 dark:text-white">Verify</button>
                        </form>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
@endsection
