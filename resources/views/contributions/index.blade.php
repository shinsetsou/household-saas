<!-- resources/views/contributions/create.blade.php -->

@extends('layouts.layout')

@section('title', 'Add Contribution')

@section('content')
<div class="container">
    <h1>Contributions</h1>
    <table class="table">
        <thead>
            <tr>
                <th>Household</th>
                <th>Amount</th>
                <th>Photo</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($contributions as $contribution)
                <tr>
                    <td>{{ $contribution->household->name }}</td>
                    <td>{{ $contribution->amount }}</td>
                    <td>
                        @if($contribution->photo)
                            <img src="{{ asset('storage/' . $contribution->photo) }}" alt="Photo" width="100">
                        @else
                            No photo
                        @endif
                    </td>
                    <td>{{ $contribution->created_at }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
