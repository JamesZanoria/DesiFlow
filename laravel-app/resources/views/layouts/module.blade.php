<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Order Module') | DesiFlow</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-gray-100 text-gray-900">
    <header class="border-b border-gray-200 bg-white">
        <div class="mx-auto flex max-w-screen-2xl items-center justify-between gap-4 px-5 py-3">
            <a href="@yield('home-url', route('customer.menu'))" class="font-bold">DesiFlow <span class="font-normal text-gray-500">/ @yield('module-name', 'Orders')</span></a>
            <div class="flex items-center gap-4">
                <span class="hidden text-sm text-gray-500 sm:inline">{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-sm font-medium text-gray-600 hover:text-red-700">Sign out</button>
                </form>
            </div>
        </div>
    </header>
    <main class="mx-auto max-w-screen-2xl px-5 py-6">
        @if (session('status'))
            <div role="status" class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div role="alert" class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                <ul class="list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif
        @yield('content')
    </main>
</body>
</html>
