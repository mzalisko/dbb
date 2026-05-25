<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'DataBridge CRM') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body style="display:flex; min-height:100vh;">

    {{-- Sidebar --}}
    <aside class="sidebar">
        <div class="sidebar-logo">
            <x-icon.logo width="28" height="28" />
            <span style="font-weight:500;font-size:14px;">DataBridge</span>
        </div>

        <nav class="sidebar-nav">
            <a href="{{ route('dashboard') }}" wire:navigate
               class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <x-icon.dash width="18" height="18" />
                Dashboard
            </a>
            <a href="{{ route('clients.index') }}" wire:navigate
               class="sidebar-link {{ request()->routeIs('clients.*') ? 'active' : '' }}">
                <x-icon.groups width="18" height="18" />
                Clients
            </a>
            <a href="{{ route('sites.index') }}" wire:navigate
               class="sidebar-link {{ request()->routeIs('sites.*') ? 'active' : '' }}">
                <x-icon.sites width="18" height="18" />
                Sites
            </a>
            <a href="{{ route('users.index') }}" wire:navigate
               class="sidebar-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
                <x-icon.team width="18" height="18" />
                Team
            </a>
            <a href="{{ route('activity.index') }}" wire:navigate
               class="sidebar-link {{ request()->routeIs('activity.*') ? 'active' : '' }}">
                <x-icon.logs width="18" height="18" />
                Activity
            </a>
        </nav>

        <div class="sidebar-footer">
            <a href="{{ route('settings') }}" wire:navigate
               class="sidebar-link {{ request()->routeIs('settings') ? 'active' : '' }}">
                <x-icon.settings width="18" height="18" />
                Settings
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="sidebar-link">
                    <x-icon.logout width="18" height="18" />
                    Log out
                </button>
            </form>
        </div>
    </aside>

    {{-- Main content --}}
    <main style="flex:1; padding:32px 40px; overflow-y:auto;">
        {{ $slot }}
    </main>

    @livewireScripts
</body>
</html>
