<!-- resources/views/households/create.blade.php -->

@extends('layouts.layout')

@section('title', 'Add Household')

@section('content')
    <h1>Add Household</h1>

    <form action="{{ route('households.store') }}" method="POST">
        @csrf
        <label for="name">Name:</label>
        <input type="text" id="name" name="name" required>
        <button type="submit">Add Household</button>
    </form>
@endsection
