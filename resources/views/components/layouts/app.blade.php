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
<body style="display:flex; height:100vh; overflow:hidden;">

    {{-- Sidebar --}}
    <aside class="sidebar">
        {{-- Logo --}}
        <div style="display:flex; align-items:center; gap:10px; padding:0 6px 22px;">
            <span style="width:30px; height:30px; border-radius:6px; background:var(--ink-9); color:var(--paper); display:inline-flex; align-items:center; justify-content:center; flex-shrink:0;">
                <x-icon.logo width="16" height="16" />
            </span>
            <div>
                <div style="font:500 14px/1 var(--font-sans); color:var(--ink-9);">DataBridge</div>
                <div style="font:11px/1 var(--font-mono); color:var(--ink-5); margin-top:4px;">CRM</div>
            </div>
        </div>

        {{-- Search --}}
        <div class="sidebar-search">
            <x-icon.search width="13" height="13" />
            <span style="flex:1; font:12.5px var(--font-sans);">Find…</span>
            <span style="font:10.5px var(--font-mono); padding:2px 5px; border-radius:3px; background:var(--paper);">⌘K</span>
        </div>

        {{-- Nav --}}
        <nav class="sidebar-nav">
            <a href="{{ route('dashboard') }}" wire:navigate
               class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <x-icon.dash width="14" height="14" />
                Dashboard
            </a>
            <a href="{{ route('clients.index') }}" wire:navigate
               class="sidebar-link {{ request()->routeIs('clients.*') ? 'active' : '' }}">
                <x-icon.groups width="14" height="14" />
                Clients
            </a>
            <a href="{{ route('sites.index') }}" wire:navigate
               class="sidebar-link {{ request()->routeIs('sites.*') ? 'active' : '' }}">
                <x-icon.sites width="14" height="14" />
                Sites
            </a>
            <a href="{{ route('users.index') }}" wire:navigate
               class="sidebar-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
                <x-icon.team width="14" height="14" />
                Team
            </a>
            <a href="{{ route('activity.index') }}" wire:navigate
               class="sidebar-link {{ request()->routeIs('activity.*') ? 'active' : '' }}">
                <x-icon.logs width="14" height="14" />
                Activity
            </a>
            <a href="{{ route('settings') }}" wire:navigate
               class="sidebar-link {{ request()->routeIs('settings') ? 'active' : '' }}">
                <x-icon.settings width="14" height="14" />
                Settings
            </a>
        </nav>

        <div style="flex:1;"></div>

        {{-- User section --}}
        <div class="sidebar-user">
            <span class="avatar" style="background:var(--accent); color:var(--paper); flex-shrink:0;">
                {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 2)) }}
            </span>
            <div style="flex:1; min-width:0;">
                <div style="font:400 13px/1.2 var(--font-sans); color:var(--ink-9); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ auth()->user()->name ?? '' }}</div>
                <div style="font:11px/1 var(--font-mono); color:var(--ink-5); margin-top:3px;">{{ auth()->user()->role ?? '' }}</div>
            </div>
            <form method="POST" action="{{ route('logout') }}" style="flex-shrink:0;">
                @csrf
                <button type="submit" title="Log out"
                    style="width:28px; height:28px; border-radius:999px; color:var(--ink-5); display:inline-flex; align-items:center; justify-content:center; transition:color .12s;"
                    onmouseover="this.style.color='var(--ink-9)'" onmouseout="this.style.color='var(--ink-5)'">
                    <x-icon.logout width="14" height="14" />
                </button>
            </form>
        </div>
    </aside>

    {{-- Main content --}}
    <main style="flex:1; min-width:0; display:flex; flex-direction:column; overflow-y:auto;">
        {{ $slot }}
    </main>

    @livewireScripts
</body>
</html>
