<!-- resources/views/contributions/create.blade.php -->

@extends('layouts.layout')

@section('title', 'Contribution for ' . strtoupper($household->name))

@section('content')
    <div class="min-h-screen flex items-center justify-center ">
        <div class="bg-white dark:bg-gray-900 p-8 rounded-lg shadow-md w-full max-w-md">
            <h2 class="text-2xl font-bold mb-6 text-center text-gray-800 dark:text-white">Add Contribution</h2>
            @if (session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
            @endif
            @if (session('success'))
                <div>{{ session('success') }}</div>
            @endif
            <form action="{{ route('contributions.store', [$organization, $household]) }}" method="POST"
                enctype="multipart/form-data" class="space-y-6">
                @csrf
                <div class="form-group space-y-2">
                    <label for="amount" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Amount:</label>
                    <input type="text" name="amount" id="amount"
                        class="form-control w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                        required>
                </div>
                <div class="form-group space-y-2">
                    <label for="photo" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Proof of
                        Payment:</label>
                    <input type="file" name="photo" id="photo"
                        class="form-control w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                        required onchange="previewImage(event)">
                    <img id="imagePreview" class="mt-4 hidden w-full max-h-64 object-contain">
                </div>
                <button type="submit"
                    class="w-full px-4 py-2 rounded-md shadow-md bg-blue-500 text-white hover:bg-blue-600">Add
                    Contribution</button>
            </form>
            <a href="{{ url()->previous() }}"
                class="mt-4 inline-block w-full text-center px-4 py-2 rounded-md shadow-md bg-gray-500 text-white hover:bg-gray-600">Back</a>
        </div>
    </div>

    <script>
        function previewImage(event) {
            const input = event.target;
            const reader = new FileReader();

            reader.onload = function() {
                const imagePreview = document.getElementById('imagePreview');
                imagePreview.src = reader.result;
                imagePreview.classList.remove('hidden');
            };

            if (input.files && input.files[0]) {
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
@endsection
