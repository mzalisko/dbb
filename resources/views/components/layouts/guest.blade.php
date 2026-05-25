<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'DataBridge CRM') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600&family=Geist+Mono:wght@400;500&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body style="display:flex; align-items:center; justify-content:center; min-height:100vh;">
    <div style="width:100%; max-width:400px; padding:40px 24px;">
        {{-- Logo --}}
        <div style="text-align:center; margin-bottom:32px;">
            <x-icon.logo style="width:40px; height:40px; margin:0 auto;" />
            <h1 style="margin-top:12px; font-size:18px;">{{ config('app.name', 'DataBridge CRM') }}</h1>
        </div>

        {{ $slot }}
    </div>

    @livewireScripts
</body>
</html>
