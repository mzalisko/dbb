@php
$sitesForAlpine = $sites->map(fn($s) => [
    'group' => $s->group ?? '',
    'name'  => strtolower($s->name),
])->values();

// Server-side initial state — eliminates FOUC: URL is the single source of truth.
$initialGroup = $urlGroup ?: 'all';
$initialCount = $initialGroup === 'all'
    ? $sites->count()
    : $sites->filter(fn($s) => ($s->group ?? '') === $initialGroup)->count();
@endphp

<div x-data="{
        sites: {{ json_encode($sitesForAlpine) }},
        activeGroup: {{ json_encode($initialGroup) }},
        search: '',
        showCreate: false,
        visibleCount() {
            const q = this.search.toLowerCase();
            return this.sites.filter(s =>
                (this.activeGroup === 'all' || s.group === this.activeGroup) &&
                (q === '' || s.name.includes(q))
            ).length;
        },
        setGroup(g) {
            this.activeGroup = g;
            const url = new URL(window.location);
            if (g === 'all') url.searchParams.delete('group');
            else url.searchParams.set('group', g);
            history.replaceState(null, '', url);
        },
        uaWord(n, one, few, many) {
            const a = Math.abs(n), m = a % 100, d = a % 10;
            if (m >= 11 && m <= 19) return many;
            if (d === 1) return one;
            if (d >= 2 && d <= 4) return few;
            return many;
        }
     }"
     x-init="document.addEventListener('keydown', e => {
         if (e.key === '/' && !['INPUT','TEXTAREA'].includes(document.activeElement.tagName)) {
             e.preventDefault(); $refs.searchInput.focus();
         }
     })"
     @site-created.window="showCreate = false"
     style="flex:1; display:flex; flex-direction:column; overflow-y:auto;">

    <x-ui.topbar :crumbs="['Сайти']">
        <x-ui.button variant="secondary" size="sm" style="margin-right:4px;">
            <x-icon.export width="13" height="13" /> Експорт
        </x-ui.button>
        <x-ui.button size="sm" @click="showCreate = true">
            <x-icon.plus width="13" height="13" /> Додати сайт
        </x-ui.button>
    </x-ui.topbar>

    {{-- Page head with reactive count --}}
    <header style="padding:40px 40px 28px; flex-shrink:0;">
        <h1 style="font:400 36px/1.05 var(--font-sans); letter-spacing:-0.03em;">
            <span x-text="visibleCount()" style="color:var(--ink-9);">{{ $initialCount }}</span><span x-text="' ' + uaWord(visibleCount(), 'сайт', 'сайти', 'сайтів')" style="color:var(--ink-5);"> {{ ua_word($initialCount, 'сайт', 'сайти', 'сайтів') }}</span>
        </h1>
    </header>

    {{-- Group filter --}}
    <div style="padding:0 40px 16px; display:flex; gap:8px; flex-wrap:wrap;">
        <button class="filter-pill {{ $initialGroup === 'all' ? 'is-active' : '' }}" :class="activeGroup === 'all' ? 'is-active' : ''" x-on:click="setGroup('all')">
            Усі
        </button>
        @foreach ($groups as $group)
            <button class="filter-pill {{ $initialGroup === $group->group ? 'is-active' : '' }}" :class="activeGroup === '{{ $group->group }}' ? 'is-active' : ''" x-on:click="setGroup('{{ $group->group }}')">
                <span style="width:6px; height:6px; border-radius:999px; background:{{ $group->group_color }};"></span>
                {{ ucfirst($group->group) }}
            </button>
        @endforeach
    </div>

    {{-- Search bar --}}
    <div style="padding:0 40px 24px;">
        <div style="position:relative; display:flex; align-items:center; gap:10px;
                    height:42px; padding:0 14px;
                    background:var(--paper-2); border:1px solid var(--ink-3); border-radius:6px;
                    transition:border-color .15s;"
             :style="{ borderColor: search ? 'var(--ink-7)' : 'var(--ink-3)',
                        background:  search ? 'var(--card)' : 'var(--paper-2)' }">
            <x-icon.search width="15" height="15" style="color:var(--ink-4); flex-shrink:0;" />
            <input type="text" x-model="search" x-ref="searchInput"
                   placeholder="Пошук сайтів…"
                   style="flex:1; background:transparent; border:none; outline:none;
                          font:14px var(--font-sans); color:var(--ink-9);"
                   @keydown.escape="search = ''" />
            <span x-show="!search"
                  style="font:11px var(--font-mono); color:var(--ink-4);
                         padding:2px 6px; border:1px solid var(--ink-3); border-radius:3px;
                         flex-shrink:0; user-select:none;">/</span>
            <button x-show="search" @click="search = ''; $refs.searchInput.focus()"
                    style="color:var(--ink-5); display:flex; align-items:center; flex-shrink:0;
                           width:20px; height:20px; border-radius:999px; background:var(--ink-3);
                           justify-content:center; transition:background .12s;"
                    onmouseover="this.style.background='var(--ink-5)'; this.style.color='var(--paper)'"
                    onmouseout="this.style.background='var(--ink-3)'; this.style.color='var(--ink-5)'">
                <x-icon.close width="10" height="10" style="display:block;" />
            </button>
        </div>
    </div>

    {{-- Site cards grid --}}
    <div style="padding:0 40px 64px; display:grid; grid-template-columns:repeat(3, 1fr); gap:14px; align-items:start;">
        @forelse ($sites as $site)
            @php
                $statusClass = match($site->status) {
                    'active'      => 'dot-ok',
                    'maintenance' => 'dot-warn',
                    default       => 'dot-bad',
                };
                $statusLabel = match($site->status) {
                    'active'      => 'Активний',
                    'maintenance' => 'Пауза',
                    default       => 'Помилка',
                };
            @endphp
            @php $hiddenInit = $initialGroup !== 'all' && ($site->group ?? '') !== $initialGroup; @endphp
            <div class="sites-card-wrap"
                 data-group="{{ $site->group }}"
                 data-name="{{ strtolower($site->name) }}"
                 x-show="(activeGroup === 'all' || $el.dataset.group === activeGroup) &&
                          (search === '' || $el.dataset.name.includes(search.toLowerCase()))"
                 style="height:100%; position:relative;{{ $hiddenInit ? ' display:none;' : '' }}">

                {{-- Star: optimistic toggle — Alpine updates instantly, $wire saves to DB without re-render --}}
                <button x-data="{ isFav: {{ $site->is_favourite ? 'true' : 'false' }} }"
                        @click="isFav = !isFav; $wire.toggleFavourite({{ $site->id }})"
                        :title="isFav ? 'Прибрати з обраних' : 'Додати до обраних'"
                        style="position:absolute; top:16px; right:16px; z-index:1; padding:4px;
                               border-radius:4px; transition:opacity .12s;"
                        onmouseover="this.style.opacity='.7'" onmouseout="this.style.opacity='1'">
                    <svg x-show="isFav" width="14" height="14" viewBox="0 0 24 24"
                         fill="currentColor" stroke="currentColor" stroke-width="1.5"
                         stroke-linecap="round" stroke-linejoin="round"
                         style="color:var(--warn); display:none;">
                        <path d="m12 3 2.7 5.6 6.3.8-4.6 4.3 1.2 6.1L12 17l-5.6 2.8 1.2-6.1L3 9.4l6.3-.8Z"/>
                    </svg>
                    <svg x-show="!isFav" width="14" height="14" viewBox="0 0 24 24"
                         fill="currentColor" stroke="currentColor" stroke-width="1.5"
                         stroke-linecap="round" stroke-linejoin="round"
                         style="color:var(--ink-3); display:none;">
                        <path d="m12 3 2.7 5.6 6.3.8-4.6 4.3 1.2 6.1L12 17l-5.6 2.8 1.2-6.1L3 9.4l6.3-.8Z"/>
                    </svg>
                </button>

                <a href="{{ route('sites.show', $site) }}" wire:navigate
                   style="text-align:left; padding:18px 42px 18px 18px; background:var(--card);
                          border:1px solid var(--ink-3); border-radius:4px; cursor:pointer;
                          transition:border-color .15s; display:flex; flex-direction:column; height:100%;"
                   onmouseover="this.style.borderColor='var(--ink-9)'"
                   onmouseout="this.style.borderColor='var(--ink-3)'">

                    <div style="flex:1;">
                        <div style="display:flex; align-items:flex-start; gap:10px;">
                            <span class="avatar avatar-sq">{{ strtoupper(substr($site->name, 0, 1)) }}</span>
                            <div style="flex:1; min-width:0;">
                                <div class="mono" style="font:13.5px var(--font-mono); color:var(--ink-9); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $site->name }}</div>
                                <div style="margin-top:4px; font:12px var(--font-sans); color:{{ $site->status === 'offline' ? 'var(--bad)' : 'var(--ink-5)' }};">
                                    <span class="dot {{ $statusClass }}"></span>{{ $statusLabel }}
                                    <span style="color:var(--ink-4); margin-left:6px;">· {{ $site->last_checked_at?->diffForHumans(null, true) ?? 'Ніколи' }}</span>
                                </div>
                            </div>
                        </div>

                        @if ($site->status === 'offline')
                            <div style="margin-top:12px; padding:8px 10px; border-left:2px solid var(--bad); background:var(--bad-soft); font:12px var(--font-mono); color:var(--bad);">
                                Connection refused
                            </div>
                        @endif
                    </div>

                    <div style="margin-top:14px; padding-top:12px; border-top:1px solid var(--ink-3); display:flex; gap:18px; font:12px var(--font-mono); color:var(--ink-5);">
                        @if ($site->group)
                            <span>
                                <span style="width:6px; height:6px; border-radius:999px; background:{{ $site->group_color ?? '#a39d8c' }}; display:inline-block; margin-right:6px;"></span>
                                {{ ucfirst($site->group) }}
                            </span>
                        @endif
                        <span style="display:inline-flex;align-items:center;gap:4px;">
                            <x-icon.phone width="11" height="11" />
                            {{ $site->phones_count }}
                        </span>
                        <span style="display:inline-flex;align-items:center;gap:4px;">
                            <x-icon.chat width="11" height="11" />
                            {{ $site->messengers_count }}
                        </span>
                    </div>
                </a>
            </div>
        @empty
            <div style="grid-column:1/-1; padding:80px 40px; text-align:center; color:var(--ink-5); font:13.5px var(--font-sans);">
                Немає сайтів. Додайте перший.
            </div>
        @endforelse
    </div>

    {{-- Empty search state — display:none initially (search always empty on load) so it never flashes --}}
    <div x-show="search !== '' && visibleCount() === 0"
         style="padding:0 40px 64px; text-align:center; color:var(--ink-5); font:13.5px var(--font-sans); display:none;">
        Нічого не знайдено за запитом «<span x-text="search" style="color:var(--ink-7);"></span>»
    </div>

    {{-- ══ Create Site Drawer ══ --}}
    <div x-show="showCreate" x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="showCreate = false"
         style="position:fixed; inset:0; background:rgba(0,0,0,.4); z-index:60;"></div>

    <div x-show="showCreate" x-cloak
         x-transition:enter="transition ease-out duration-250"
         x-transition:enter-start="opacity-0 translate-x-5"
         x-transition:enter-end="opacity-100 translate-x-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-x-0"
         x-transition:leave-end="opacity-0 translate-x-5"
         style="position:fixed; top:0; right:0; bottom:0; width:480px; max-width:100vw;
                background:var(--card); border-left:1px solid var(--ink-3); z-index:61;
                display:flex; flex-direction:column;"
         @keydown.escape.window="showCreate = false">

        {{-- Header --}}
        <div style="padding:28px 28px 0; flex-shrink:0;">
            <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:12px;">
                <h2 style="font:400 22px/1.1 var(--font-sans); color:var(--ink-9); letter-spacing:-0.02em;">Додати сайт</h2>
                <button @click="showCreate = false"
                        style="width:28px; height:28px; border-radius:999px; color:var(--ink-5);
                               display:inline-flex; align-items:center; justify-content:center;
                               flex-shrink:0; transition:color .12s; margin-top:2px;"
                        onmouseover="this.style.color='var(--ink-9)'" onmouseout="this.style.color='var(--ink-5)'">
                    <x-icon.close width="16" height="16" style="display:block;" />
                </button>
            </div>
            <div style="margin-top:20px; border-bottom:1px solid var(--ink-3);"></div>
        </div>

        {{-- Body --}}
        <div style="flex:1; overflow-y:auto; padding:24px 28px;">

            {{-- Name --}}
            <div style="margin-bottom:24px;">
                <label class="label">Назва</label>
                <input type="text" wire:model="createName" class="input" placeholder="my-site.com"
                       @keydown.enter="$wire.createSite()" />
                @error('createName')
                    <span style="font:12px var(--font-sans); color:var(--bad); margin-top:6px; display:block;">{{ $message }}</span>
                @enderror
            </div>

            {{-- URL --}}
            <div style="margin-bottom:24px;">
                <label class="label">URL</label>
                <input type="url" wire:model="createUrl" class="input" placeholder="https://my-site.com" />
                @error('createUrl')
                    <span style="font:12px var(--font-sans); color:var(--bad); margin-top:6px; display:block;">{{ $message }}</span>
                @enderror
            </div>

            {{-- Group --}}
            <div style="margin-bottom:24px;">
                <label class="label">Група</label>
                <select wire:model="createGroupId"
                        style="width:100%; height:40px; font:14px var(--font-sans); color:var(--ink-9);
                               background:transparent; border:0; border-bottom:1px solid var(--ink-3);
                               outline:none; cursor:pointer;">
                    <option value="">— без групи —</option>
                    @foreach($siteGroups as $sg)
                        <option value="{{ $sg->id }}">{{ ucfirst($sg->name) }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Status --}}
            <div style="margin-bottom:24px;">
                <label class="label" style="margin-bottom:12px;">Статус</label>
                <div style="display:flex; flex-direction:column; gap:8px;">
                    @foreach(['active' => 'Активний', 'maintenance' => 'Пауза', 'offline' => 'Помилка'] as $val => $lbl)
                        <label style="display:flex; align-items:center; gap:10px; cursor:pointer;
                                      font:13.5px var(--font-sans); color:var(--ink-8);">
                            <input type="radio" wire:model="createStatus" value="{{ $val }}"
                                   style="width:16px; height:16px; cursor:pointer;" />
                            {{ $lbl }}
                        </label>
                    @endforeach
                </div>
            </div>

        </div>

        {{-- Footer --}}
        <div style="flex-shrink:0; padding:16px 28px; border-top:1px solid var(--ink-3);
                    display:flex; align-items:center; justify-content:flex-end; gap:8px;
                    background:var(--paper-2);">
            <x-ui.button variant="ghost" @click="showCreate = false">Скасувати</x-ui.button>
            <x-ui.button wire:click="createSite" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="createSite">Зберегти</span>
                <span wire:loading wire:target="createSite">Збереження…</span>
            </x-ui.button>
        </div>
    </div>
</div>
