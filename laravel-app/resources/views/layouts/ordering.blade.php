<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Order Online') | Desi Zaika</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-gray-50 text-gray-900">
    <header class="border-b border-gray-200 bg-white">
        <div class="mx-auto flex max-w-screen-2xl items-center justify-between gap-4 px-5 py-4">
            <a href="{{ route('customer.menu') }}" class="text-lg font-bold text-gray-900">Desi Zaika</a>
            <a href="{{ route('login') }}" class="text-sm font-medium text-gray-600 hover:text-amber-700">Staff sign in</a>
        </div>
    </header>
    <main class="mx-auto max-w-screen-2xl px-5 py-6">
        @yield('content')
    </main>
</body>
</html>
