@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="flex flex-col items-center justify-center min-h-[60vh] text-center">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">Welcome to the Dashboard</h1>
        <p class="text-gray-500 max-w-md mx-auto">
            This page is currently under development by the team.
            For now, you can use the <a href="{{ route('admin.assistant.index') }}" class="font-semibold text-amber-600 hover:underline">Smart Business Assistant</a> to get quick insights!
        </p>
    </div>
@endsection
