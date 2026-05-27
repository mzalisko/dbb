<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      x-data="themeApp()"
      :data-theme="theme"
      x-init="initTheme()">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'DataBridge CRM') }}</title>

    <link rel="preload" href="{{ Vite::asset('resources/fonts/geist-cyrillic.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="{{ Vite::asset('resources/fonts/geist-latin.woff2') }}" as="font" type="font/woff2" crossorigin>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body style="display:flex; height:100vh; overflow:hidden;">

    {{-- Sidebar (persisted across wire:navigate — no re-render, no count re-queries) --}}
    @persist('sidebar')
    <aside class="sidebar">
        {{-- Logo --}}
        <div style="display:flex; align-items:center; gap:12px; padding:0 4px 26px;">
            <span style="width:34px; height:34px; border-radius:8px; background:var(--ink-9); color:var(--paper); display:inline-flex; align-items:center; justify-content:center; flex-shrink:0; box-shadow:0 2px 6px rgba(26,24,20,.18);">
                <x-icon.logo width="18" height="18" />
            </span>
            <div>
                <div style="font:600 14.5px/1 var(--font-sans); color:var(--ink-9); letter-spacing:-.01em;">DataBridge</div>
                <div style="font:11px/1 var(--font-mono); color:var(--ink-5); margin-top:5px; letter-spacing:.04em; text-transform:uppercase;">CRM v2</div>
            </div>
        </div>

        {{-- Nav — active state driven client-side so it stays correct inside @persist --}}
        <nav class="sidebar-nav"
             x-data="{ path: window.location.pathname }"
             x-on:livewire:navigated.window="path = window.location.pathname">
            <a href="{{ route('dashboard') }}" wire:navigate.hover
               class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
               :class="{ 'active': path === '/dashboard' }">
                <x-icon.dash width="14" height="14" />
                <span style="flex:1;">Дашборд</span>
            </a>
            <a href="{{ route('sites.index') }}" wire:navigate.hover
               class="sidebar-link {{ request()->routeIs('sites.*') ? 'active' : '' }}"
               :class="{ 'active': path.startsWith('/sites') }">
                <x-icon.sites width="14" height="14" />
                <span style="flex:1;">Сайти</span>
                <span class="mono" style="font:11px var(--font-mono); color:var(--ink-4);">{{ \App\Models\Site::count() }}</span>
            </a>
            <a href="{{ route('groups.index') }}" wire:navigate.hover
               class="sidebar-link {{ request()->routeIs('groups.*') ? 'active' : '' }}"
               :class="{ 'active': path.startsWith('/groups') }">
                <x-icon.groups width="14" height="14" />
                <span style="flex:1;">Групи сайтів</span>
                <span class="mono" style="font:11px var(--font-mono); color:var(--ink-4);">{{ \App\Models\Site::whereNotNull('group')->distinct('group')->count('group') }}</span>
            </a>
            <a href="{{ route('data.index') }}" wire:navigate.hover
               class="sidebar-link {{ request()->routeIs('data.*') ? 'active' : '' }}"
               :class="{ 'active': path.startsWith('/data') }">
                <x-icon.data width="14" height="14" />
                <span style="flex:1;">Браузер даних</span>
                <span class="mono" style="font:11px var(--font-mono); color:var(--ink-4);">{{ \App\Models\ContactEntry::count() }}</span>
            </a>
            <a href="{{ route('users.index') }}" wire:navigate.hover
               class="sidebar-link {{ request()->routeIs('users.*') ? 'active' : '' }}"
               :class="{ 'active': path.startsWith('/team') }">
                <x-icon.team width="14" height="14" />
                <span style="flex:1;">Команда</span>
                <span class="mono" style="font:11px var(--font-mono); color:var(--ink-4);">{{ \App\Models\User::count() }}</span>
            </a>
            <a href="{{ route('activity.index') }}" wire:navigate.hover
               class="sidebar-link {{ request()->routeIs('activity.*') ? 'active' : '' }}"
               :class="{ 'active': path.startsWith('/activity') }">
                <x-icon.logs width="14" height="14" />
                <span style="flex:1;">Логи</span>
            </a>
            <a href="{{ route('settings') }}" wire:navigate.hover
               class="sidebar-link {{ request()->routeIs('settings') ? 'active' : '' }}"
               :class="{ 'active': path.startsWith('/settings') }">
                <x-icon.settings width="14" height="14" />
                <span style="flex:1;">Налаштування</span>
            </a>
        </nav>

        <div style="flex:1;"></div>

        {{-- User section --}}
        <div class="sidebar-user">
            <span style="position:relative;">
                <span class="avatar" style="background:var(--accent); color:var(--paper); flex-shrink:0;">
                    {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 2)) }}
                </span>
                <span style="position:absolute;right:-1px;bottom:-1px;width:9px;height:9px;border-radius:999px;background:var(--ok);border:2px solid var(--paper-2);"></span>
            </span>
            <div style="flex:1; min-width:0;">
                <div style="font:400 13px/1.2 var(--font-sans); color:var(--ink-9); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ auth()->user()->name ?? '' }}</div>
                <div style="font:11px/1 var(--font-mono); color:var(--ink-5); margin-top:3px;">{{ auth()->user()->role ?? 'admin' }}</div>
            </div>
            {{-- Theme toggle --}}
            <button @click="toggleTheme()"
                :title="theme === 'light' ? 'Темна тема' : 'Світла тема'"
                style="width:28px;height:28px;border-radius:999px;color:var(--ink-5);display:inline-flex;align-items:center;justify-content:center;transition:color .12s;flex-shrink:0;"
                onmouseover="this.style.color='var(--ink-9)'" onmouseout="this.style.color='var(--ink-5)'">
                <template x-if="theme === 'light'"><x-icon.moon width="14" height="14" /></template>
                <template x-if="theme === 'dark'"><x-icon.sun width="14" height="14" /></template>
            </button>
        </div>
    </aside>
    @endpersist

    {{-- Main content --}}
    <main style="flex:1; min-width:0; display:flex; flex-direction:column; overflow-y:auto;">
        {{ $slot }}
    </main>

    @livewireScripts

    <script>
    function themeApp() {
      return {
        theme: 'light',
        initTheme() {
          this.theme = localStorage.getItem('db-theme') || 'light';
        },
        toggleTheme() {
          this.theme = this.theme === 'light' ? 'dark' : 'light';
          localStorage.setItem('db-theme', this.theme);
        }
      }
    }
    </script>

    <x-ui.toast />
</body>
</html>
