<!-- resources/views/households/index.blade.php -->

@extends('layouts.layout')

@section('title', 'Households')

@section('content')
    <h1 class="text-6xl text-center justify-center text-cyan-600 dark:text-white/100">Households</h1>

    <a href="{{ route('households.create') }}">Add Household</a>

    @if (session('success'))
        <div>{{ session('success') }}</div>
    @endif

    <ul>
        @foreach ($households as $household)
            <li>
                <a href="{{ route('households.show', $household) }}">{{ $household->name }}</a>
                <form action="{{ route('households.destroy', $household) }}" method="POST" style="display:inline">
                    @csrf
                    @method('DELETE')
                    <button class="text-secondary" type="submit">Delete</button>
                </form>
            </li>
        @endforeach
    </ul>
@endsection
