<!-- resources/views/households/index.blade.php -->

@extends('layouts.layout')


@section('content')
    <div class="max-h-screen items-center justify-center bg-gray-100 dark:bg-gray-800 py-5">
    <h1 class="text-5xl text-center justify-center text-cyan-500 dark:text-white/100 mb-20">{{$organization->name}} Households</h1>
    <form action="{{ '/'.$organization->slug.'/households' }}" method="GET" style="display:inline">
        <div class="form-group text-center item-center justify-center">
            <label for="block" class="text-5xl text-center justify-center text-cyan-500 dark:text-white/100 px-5">Block #</label>
            <input class="text-5xl w-1/6 text-black" type="number" id="block" name="block"/>
            <label for="lot" class="text-5xl text-center justify-center text-cyan-500 dark:text-white/100 px-5">Lot # </label>
            <input class="text-5xl w-1/6 text-black" type="number" id="lot" name="lot"/>
        </div>
        <div class="text-center item-center justify-center mt-20">
        <button class="text-3xl text-secondary px-4 py-4 rounded-md shadow-md bg-cyan-500 text-white dark:bg-blue-500 dark:text-white" type="submit">Search</button>
        </div>
    </form>
</div>
@endsection
