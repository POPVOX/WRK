<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'WRK') }}</title>
    <meta name="theme-color" content="#1d4f7d">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=ibm-plex-sans:400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased">
    <div class="min-h-screen flex items-center justify-center p-4 bg-transparent">
        <div class="w-full max-w-md">
            <a href="/" wire:navigate class="mb-6 flex items-center justify-center gap-3">
                <img src="{{ asset('images/logo.png') }}" alt="WRK" class="h-10 w-auto">
                <span class="text-sm font-semibold text-gray-900">WRK</span>
            </a>

            <section class="app-surface p-6 sm:p-7">
                {{ $slot }}
            </section>
        </div>
    </div>
</body>
</html>
