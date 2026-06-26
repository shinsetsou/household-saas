@extends('layouts.layout')

@section('title', 'Add Contribution — ' . $household->name)

@section('content')

{{-- Breadcrumb --}}
<nav class="flex items-center gap-2 text-xs text-gray-500 mb-6">
    <a href="{{ route('organizations.index', $organization->slug) }}" class="hover:text-indigo-600 transition-colors">Dashboard</a>
    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
    <a href="{{ route('home', $organization->slug) }}" class="hover:text-indigo-600 transition-colors">Households</a>
    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
    <a href="{{ route('households.show', [$organization, $household]) }}" class="hover:text-indigo-600 transition-colors">{{ $household->name }}</a>
    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
    <span class="text-gray-700 font-medium">Add Contribution</span>
</nav>

<div class="max-w-lg mx-auto">
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">

        {{-- Card header --}}
        <div class="px-6 py-5 border-b border-gray-100">
            <div class="flex items-center gap-3">
                <span class="flex items-center justify-center w-9 h-9 rounded-lg bg-indigo-100 text-indigo-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z"/>
                        <path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" clip-rule="evenodd"/>
                    </svg>
                </span>
                <div>
                    <h1 class="text-base font-semibold text-gray-900">Add Contribution</h1>
                    <p class="text-xs text-gray-500 capitalize">{{ $household->name }}</p>
                </div>
            </div>
        </div>

        {{-- Form --}}
        <form action="{{ URL::signedRoute('contributions.store', [$organization, $household]) }}" method="POST"
              enctype="multipart/form-data" id="contribution-form" class="px-6 py-6 space-y-5">
            @csrf

            {{-- Amount --}}
            <div>
                <label for="amount" class="form-label">
                    Amount
                    <span class="text-red-500">*</span>
                </label>
                <div class="relative mt-1.5">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400 font-medium text-sm pointer-events-none">&#8369;</span>
                    <input type="number" name="amount" id="amount"
                           value="{{ old('amount') }}"
                           placeholder="0.00"
                           step="0.01" min="0"
                           class="form-input pl-8 @error('amount') form-input-error @enderror"
                           required>
                </div>
                <p class="mt-1.5 text-xs text-gray-500">Monthly due is &#8369;200. Overpayments are automatically split into months.</p>
                @error('amount')
                    <p class="mt-1.5 text-xs text-red-600 flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Photo upload --}}
            <div>
                <label for="photo" class="form-label">
                    Proof of Payment
                    <span class="text-red-500">*</span>
                </label>
                <p class="text-xs text-gray-500 mb-2">Upload a photo of the receipt or payment slip (JPG, PNG, GIF — max 2MB).</p>

                {{-- Upload area --}}
                <label for="photo" id="upload-area"
                       class="relative flex flex-col items-center justify-center w-full h-36 border-2 border-dashed border-gray-300 rounded-xl cursor-pointer hover:border-indigo-400 bg-gray-50 hover:bg-gray-100 transition-colors">
                    <div id="upload-placeholder" class="flex flex-col items-center gap-2 pointer-events-none">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"/>
                        </svg>
                        <p class="text-sm text-gray-500"><span class="font-medium text-indigo-600">Click to upload</span> or drag & drop</p>
                        <p class="text-xs text-gray-400">JPEG, PNG, GIF up to 2MB</p>
                    </div>
                    <img id="imagePreview" class="absolute inset-0 w-full h-full object-contain rounded-xl hidden p-1" alt="Preview">
                    <input type="file" name="photo" id="photo" accept="image/jpeg,image/png,image/gif"
                           class="sr-only" required onchange="previewImage(event)">
                </label>

                @error('photo')
                    <p class="mt-1.5 text-xs text-red-600 flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Actions --}}
            <div class="flex gap-3 pt-2">
                <a href="{{ route('households.show', [$organization, $household]) }}"
                   class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-medium rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 transition-colors">
                    Cancel
                </a>
                <button type="submit" id="submit-btn"
                        class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-semibold rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 active:bg-indigo-800 transition-colors shadow-sm disabled:opacity-60 disabled:cursor-not-allowed">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" id="submit-icon" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/>
                    </svg>
                    <span id="submit-label">Submit Contribution</span>
                </button>
            </div>
        </form>
    </div>
</div>

<style>
    .form-label { @apply block text-sm font-medium text-gray-700 }
    .form-input {
        @apply w-full px-3 py-2 text-sm rounded-lg border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition;
    }
    .form-input-error { @apply border-red-400 focus:ring-red-500; }
</style>

<script>
    function previewImage(event) {
        const input = event.target;
        const reader = new FileReader();
        const preview = document.getElementById('imagePreview');
        const placeholder = document.getElementById('upload-placeholder');

        reader.onload = function () {
            preview.src = reader.result;
            preview.classList.remove('hidden');
            placeholder.classList.add('hidden');
        };

        if (input.files && input.files[0]) {
            reader.readAsDataURL(input.files[0]);
        }
    }

    // Loading state on submit
    document.getElementById('contribution-form').addEventListener('submit', function () {
        const btn = document.getElementById('submit-btn');
        const label = document.getElementById('submit-label');
        const icon = document.getElementById('submit-icon');
        btn.disabled = true;
        label.textContent = 'Submitting…';
        icon.innerHTML = '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>';
    });
</script>

@endsection
