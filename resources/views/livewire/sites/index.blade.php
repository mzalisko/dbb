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

<div class="page"
     x-data="{
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
     @site-created.window="showCreate = false">

    <x-ui.topbar :crumbs="['Сайти']">
        <x-ui.button variant="secondary" size="sm">
            <x-icon.export width="13" height="13" /> Експорт
        </x-ui.button>
        <x-ui.button size="sm" @click="showCreate = true">
            <x-icon.plus width="13" height="13" /> Додати сайт
        </x-ui.button>
    </x-ui.topbar>

    {{-- Page head with reactive count --}}
    <header class="list-head">
        <h1 class="list-title">
            <span class="list-title__n" x-text="visibleCount()">{{ $initialCount }}</span><span class="list-title__w" x-text="' ' + uaWord(visibleCount(), 'сайт', 'сайти', 'сайтів')"> {{ ua_word($initialCount, 'сайт', 'сайти', 'сайтів') }}</span>
        </h1>
    </header>

    {{-- Group filter — first 5 inline, rest collapse into an overflow menu --}}
    @php
        $pillLimit      = 5;
        $visibleGroups  = $groups->take($pillLimit);
        $overflowGroups = $groups->slice($pillLimit);
    @endphp
    <div class="filter-row">
        <button class="filter-pill {{ $initialGroup === 'all' ? 'is-active' : '' }}" :class="activeGroup === 'all' ? 'is-active' : ''" x-on:click="setGroup('all')">
            Усі
        </button>
        @foreach ($visibleGroups as $group)
            <button class="filter-pill {{ $initialGroup === $group->group ? 'is-active' : '' }}" :class="activeGroup === '{{ $group->group }}' ? 'is-active' : ''" x-on:click="setGroup('{{ $group->group }}')">
                <span class="pill-dot" style="background:{{ $group->group_color }};"></span>
                {{ ucfirst($group->group) }}
            </button>
        @endforeach

        @if ($overflowGroups->isNotEmpty())
            @php $overflowNames = $overflowGroups->pluck('group')->values(); @endphp
            <div class="filter-more" x-data="{ moreOpen: false }" @click.outside="moreOpen = false">
                <button class="filter-pill"
                        :class="{{ Illuminate\Support\Js::from($overflowNames) }}.includes(activeGroup) ? 'is-active' : ''"
                        @click="moreOpen = !moreOpen" title="Ще групи">
                    <x-icon.grid width="13" height="13" />
                    <span x-show="{{ Illuminate\Support\Js::from($overflowNames) }}.includes(activeGroup)"
                          x-text="activeGroup" style="text-transform:capitalize;"></span>
                </button>
                <div class="dropdown" x-show="moreOpen" x-cloak>
                    @foreach ($overflowGroups as $group)
                        <button class="pill-menu-item" :class="activeGroup === '{{ $group->group }}' ? 'is-active' : ''"
                                @click="setGroup('{{ $group->group }}'); moreOpen = false">
                            <span class="pill-dot" style="background:{{ $group->group_color }};"></span>
                            {{ ucfirst($group->group) }}
                        </button>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    {{-- Search bar --}}
    <div class="search-row">
        <div class="search-box" :class="{ 'is-filled': search }">
            <x-icon.search width="15" height="15" class="search-box__icon" />
            <input type="text" class="search-box__input" x-model="search" x-ref="searchInput"
                   placeholder="Пошук сайтів…" @keydown.escape="search = ''" />
            <span class="search-box__kbd" x-show="!search">/</span>
            <button class="search-box__clear" x-show="search" @click="search = ''; $refs.searchInput.focus()">
                <x-icon.close width="10" height="10" style="display:block;" />
            </button>
        </div>
    </div>

    {{-- Site cards grid --}}
    <div class="sites-grid">
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
                $hiddenInit = $initialGroup !== 'all' && ($site->group ?? '') !== $initialGroup;
            @endphp
            <div class="site-card"
                 data-group="{{ $site->group }}"
                 data-name="{{ strtolower($site->name) }}"
                 x-show="(activeGroup === 'all' || $el.dataset.group === activeGroup) &&
                          (search === '' || $el.dataset.name.includes(search.toLowerCase()))"
                 @if($hiddenInit) style="display:none;" @endif>

                {{-- Star: optimistic toggle — Alpine updates instantly, $wire saves to DB without re-render --}}
                <button class="site-card__fav"
                        x-data="{ isFav: {{ $site->is_favourite ? 'true' : 'false' }} }"
                        @click="isFav = !isFav; $wire.toggleFavourite({{ $site->id }})"
                        :title="isFav ? 'Прибрати з обраних' : 'Додати до обраних'">
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

                <a href="{{ route('sites.show', $site) }}" wire:navigate class="site-card__link">
                    <div class="site-card__body">
                        <div class="site-card__head">
                            <span class="avatar avatar-sq">{{ strtoupper(substr($site->name, 0, 1)) }}</span>
                            <div style="flex:1; min-width:0;">
                                <div class="site-card__title mono">{{ $site->name }}</div>
                                <div class="site-card__status {{ $site->status === 'offline' ? 'is-offline' : '' }}">
                                    <span class="dot {{ $statusClass }}"></span>{{ $statusLabel }}
                                    <span class="site-card__when">· {{ $site->last_checked_at?->diffForHumans(null, true) ?? 'Ніколи' }}</span>
                                </div>
                            </div>
                        </div>

                        @if ($site->status === 'offline')
                            <div class="site-card__banner">Connection refused</div>
                        @endif
                    </div>

                    <div class="site-card__footer">
                        @if ($site->group)
                            <span>
                                <span class="group-dot" style="background:{{ $site->group_color ?? '#a39d8c' }};"></span>
                                {{ ucfirst($site->group) }}
                            </span>
                        @endif
                        <span class="meta-chip">
                            <x-icon.phone width="11" height="11" />
                            {{ $site->phones_count }}
                        </span>
                        <span class="meta-chip">
                            <x-icon.chat width="11" height="11" />
                            {{ $site->messengers_count }}
                        </span>
                    </div>
                </a>
            </div>
        @empty
            <div class="list-empty">Немає сайтів. Додайте перший.</div>
        @endforelse
    </div>

    {{-- Empty search state — display:none initially (search always empty on load) so it never flashes --}}
    <div class="search-empty" style="display:none;" x-show="search !== '' && visibleCount() === 0">
        Нічого не знайдено за запитом «<span x-text="search" style="color:var(--ink-7);"></span>»
    </div>

    {{-- ══ Create Site Drawer ══ --}}
    <div class="drawer-backdrop" x-show="showCreate" x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="showCreate = false"></div>

    <div class="drawer-panel" x-show="showCreate" x-cloak
         x-transition:enter="transition ease-out duration-250"
         x-transition:enter-start="opacity-0 translate-x-5"
         x-transition:enter-end="opacity-100 translate-x-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-x-0"
         x-transition:leave-end="opacity-0 translate-x-5"
         @keydown.escape.window="showCreate = false">

        <div class="drawer-head">
            <div class="drawer-head__row">
                <h2 class="drawer-title">Додати сайт</h2>
                <button class="drawer-close" @click="showCreate = false">
                    <x-icon.close width="16" height="16" style="display:block;" />
                </button>
            </div>
            <div class="drawer-divider"></div>
        </div>

        <div class="drawer-body">
            <div class="field">
                <label class="label">Назва</label>
                <input type="text" wire:model="createName" class="input" placeholder="my-site.com"
                       @keydown.enter="$wire.createSite()" />
                @error('createName') <span class="field-error">{{ $message }}</span> @enderror
            </div>

            <div class="field">
                <label class="label">URL</label>
                <input type="url" wire:model="createUrl" class="input" placeholder="https://my-site.com" />
                @error('createUrl') <span class="field-error">{{ $message }}</span> @enderror
            </div>

            <div class="field">
                <label class="label">Група</label>
                <select wire:model="createGroupId" class="field-select">
                    <option value="">— без групи —</option>
                    @foreach($siteGroups as $sg)
                        <option value="{{ $sg->id }}">{{ ucfirst($sg->name) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="field">
                <label class="label">Статус</label>
                <div class="radio-list">
                    @foreach(['active' => 'Активний', 'maintenance' => 'Пауза', 'offline' => 'Помилка'] as $val => $lbl)
                        <label class="radio-row">
                            <input type="radio" class="radio-native" wire:model="createStatus" value="{{ $val }}" />
                            {{ $lbl }}
                        </label>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="drawer-foot">
            <x-ui.button variant="ghost" @click="showCreate = false">Скасувати</x-ui.button>
            <x-ui.button wire:click="createSite" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="createSite">Зберегти</span>
                <span wire:loading wire:target="createSite">Збереження…</span>
            </x-ui.button>
        </div>
    </div>
</div>
