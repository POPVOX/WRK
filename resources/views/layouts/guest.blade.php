<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'WRKBench') }}</title>
    <meta name="description" content="WRKBench workspace for POPVOX Foundation.">
    <meta name="theme-color" content="#faf7f1">

    <link rel="icon" type="image/png" href="{{ asset('images/wrk favicon.png') }}">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=newsreader:400,400i,500,500i,600|public-sans:400,500,600,700|ibm-plex-mono:400,500&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased">
    <div class="min-h-screen flex items-center justify-center p-4 bg-transparent">
        <div class="w-full max-w-md">
            <a href="/" wire:navigate aria-label="WRKBench home" class="mb-6 flex items-center justify-center">
                <x-wrkbench-logo class="h-16 w-auto" />
            </a>

            <section class="app-surface p-6 sm:p-7">
                {{ $slot }}
            </section>
        </div>
    </div>
</body>
</html>
