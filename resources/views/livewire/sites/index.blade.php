@php
$sitesForAlpine = $sites->map(fn($s) => [
    'id' => $s->id,
    'group' => strtolower($s->group ?? ''),
    'name'  => strtolower($s->name),
])->values();

// Server-side initial state — eliminates FOUC: URL is the single source of truth.
$initialGroup = strtolower($urlGroup ?: 'all');
$initialCount = $initialGroup === 'all'
    ? $sites->count()
    : $sites->filter(fn($s) => strtolower($s->group ?? '') === $initialGroup)->count();
@endphp

<div class="page"
     x-data="{
        sites: {{ json_encode($sitesForAlpine) }},
        activeGroup: {{ json_encode($initialGroup) }},
        search: '',
        showCreate: false,
        normalizeGroup(g) {
            return (g || '').toString().toLowerCase();
        },
        visibleCount() {
            const q = this.search.toLowerCase();
            return this.sites.filter(s =>
                (this.activeGroup === 'all' || s.group === this.activeGroup) &&
                (q === '' || s.name.includes(q))
            ).length;
        },
        setGroup(g) {
            g = this.normalizeGroup(g);
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
     @site-group-updated.window="
        const group = normalizeGroup($event.detail.group);
        const site = sites.find(s => s.id === $event.detail.id);
        if (site) site.group = group;
        const card = $el.querySelector(`[data-site-id='${$event.detail.id}']`);
        if (card) card.dataset.group = group;
     ">

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
        <button type="button" class="filter-pill {{ $initialGroup === 'all' ? 'is-active' : '' }}" wire:click="setGroupFilter('all')" @click="activeGroup = 'all'">
            Усі
        </button>
        @foreach ($visibleGroups as $group)
            @php $groupKey = strtolower($group->group); @endphp
            <button type="button" class="filter-pill {{ $initialGroup === $groupKey ? 'is-active' : '' }}" wire:click="setGroupFilter('{{ $groupKey }}')" @click="activeGroup = '{{ $groupKey }}'">
                <span class="pill-dot" style="background:{{ $group->group_color }};"></span>
                {{ ucfirst($group->group) }}
            </button>
        @endforeach

        @if ($overflowGroups->isNotEmpty())
            @php $overflowNames = $overflowGroups->pluck('group')->map(fn($g) => strtolower($g))->values(); @endphp
            <div class="filter-more" x-data="{ moreOpen: false }" @click.outside="moreOpen = false">
                <button type="button" class="filter-pill {{ $overflowNames->contains($initialGroup) ? 'is-active' : '' }}"
                        @click="moreOpen = !moreOpen" title="Ще групи">
                    <x-icon.grid width="13" height="13" />
                    @if($overflowNames->contains($initialGroup))
                        <span class="capitalize">{{ $initialGroup }}</span>
                    @endif
                </button>
                <div class="dropdown" x-show="moreOpen" x-cloak>
                    @foreach ($overflowGroups as $group)
                        @php $groupKey = strtolower($group->group); @endphp
                        <button type="button" class="pill-menu-item {{ $initialGroup === $groupKey ? 'is-active' : '' }}"
                                wire:click="setGroupFilter('{{ $groupKey }}')"
                                @click="activeGroup = '{{ $groupKey }}'; moreOpen = false">
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
                <x-icon.close width="10" height="10" />
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
                $enabledCategories = array_merge(['phones', 'messengers'], $site->data_categories ?? ['phones', 'messengers', 'prices']);
                $pricesEnabled = in_array('prices', $enabledCategories, true);
                $hiddenInit = $initialGroup !== 'all' && strtolower($site->group ?? '') !== $initialGroup;
            @endphp
            <div class="site-card"
                 data-site-id="{{ $site->id }}"
                 data-group="{{ strtolower($site->group ?? '') }}"
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

                <div class="site-card__actions" x-data="{ open: false }" @click.outside="open = false">
                    <button type="button"
                            class="site-card__menu"
                            title="Дії сайту"
                            @click.stop="open = !open">
                        <x-icon.dots-horizontal width="15" height="15" />
                    </button>

                    <div class="dropdown site-card__dropdown" x-show="open" x-cloak @click.stop>
                        <div class="site-card__dropdown-label">Група</div>
                        @foreach($siteGroups as $sg)
                            <button type="button"
                                    class="pill-menu-item {{ $site->group === $sg->name ? 'is-active' : '' }}"
                                    wire:click="assignSiteGroup({{ $site->id }}, {{ $sg->id }})"
                                    @click="open = false">
                                <span class="pill-dot" style="background:{{ $sg->color }};"></span>
                                <span>{{ ucfirst($sg->name) }}</span>
                                @if($site->group === $sg->name)
                                    <x-icon.check width="12" height="12" class="site-card__dropdown-check" />
                                @endif
                            </button>
                        @endforeach

                        <div class="site-card__dropdown-sep"></div>

                        <button type="button"
                                class="dropdown-item dropdown-item--danger"
                                wire:click="requestDeleteSite({{ $site->id }})"
                                @click="open = false">
                            <x-icon.trash width="13" height="13" />
                            Видалити сайт
                        </button>
                    </div>
                </div>

                <a href="{{ route('sites.show', $site) }}" wire:navigate class="site-card__link">
                    <div class="site-card__body">
                        <div class="site-card__head">
                            <span class="avatar avatar-sq">{{ strtoupper(substr($site->name, 0, 1)) }}</span>
                            <div class="site-card__info">
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
                        <span class="site-card__metric" title="Активні телефони{{ $site->backup_phones_count ? ' · резерви: ' . $site->backup_phones_count : '' }}">
                            <x-icon.phone width="11" height="11" />
                            <strong>{{ $site->active_phones_count }}</strong>
                            <span>активн.</span>
                            @if($site->backup_phones_count)
                                <em>+{{ $site->backup_phones_count }} рез.</em>
                            @endif
                        </span>
                        <span class="site-card__metric" title="Активні месенджери{{ $site->backup_messengers_count ? ' · резерви: ' . $site->backup_messengers_count : '' }}">
                            <x-icon.chat width="11" height="11" />
                            <strong>{{ $site->active_messengers_count }}</strong>
                            <span>активн.</span>
                            @if($site->backup_messengers_count)
                                <em>+{{ $site->backup_messengers_count }} рез.</em>
                            @endif
                        </span>
                        @if($pricesEnabled)
                            <span class="site-card__metric" title="Активні ціни">
                                <x-icon.tag width="11" height="11" />
                                <strong>{{ $site->active_prices_count }}</strong>
                                <span>цін</span>
                            </span>
                        @endif
                    </div>
                </a>
            </div>
        @empty
            <div class="list-empty">Немає сайтів. Додайте перший.</div>
        @endforelse
    </div>

    {{-- Empty search state — display:none initially (search always empty on load) so it never flashes --}}
    <div class="search-empty" style="display:none;" x-show="search !== '' && visibleCount() === 0">
        Нічого не знайдено за запитом «<span x-text="search" class="search-match"></span>»
    </div>

    @if($confirmDeleteSiteId)
        <div class="confirm-backdrop" wire:click="cancelDeleteSite"></div>
        <div class="confirm-dialog" role="dialog" aria-modal="true" aria-labelledby="delete-site-title">
            <div class="confirm-dialog__icon">
                <x-icon.trash width="18" height="18" />
            </div>
            <div class="confirm-dialog__body">
                <div class="confirm-dialog__eyebrow">Видалення сайту</div>
                <div class="confirm-dialog__title" id="delete-site-title">Видалити сайт?</div>
                <div class="confirm-dialog__subject">{{ $confirmDeleteSiteName }}</div>
                <div class="confirm-dialog__text">
                    Щоб підтвердити видалення, введіть точну назву сайту.
                </div>
                <div class="field" style="margin-top:12px;">
                    <label class="label">Назва сайту</label>
                    <input type="text"
                           class="input"
                           wire:model.live="confirmDeleteSiteTypedName"
                           placeholder="{{ $confirmDeleteSiteName }}"
                           @keydown.enter="$wire.confirmDeleteSite()" />
                    @error('confirmDeleteSiteTypedName') <span class="field-error">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="confirm-dialog__footer">
                <button type="button" class="btn btn-ghost btn-sm" wire:click="cancelDeleteSite">Скасувати</button>
                <button type="button" class="btn btn-danger-fill btn-sm" wire:click="confirmDeleteSite">
                    Видалити
                </button>
            </div>
        </div>
    @endif

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
                    <x-icon.close width="16" height="16" />
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
                @php
                    $createGroup = $createGroupId !== '' ? $siteGroups->firstWhere('id', (int) $createGroupId) : null;
                @endphp
                <div class="group-select group-select--field" x-data="{ open: false }" @click.outside="open = false">
                    <button type="button" class="group-select__button" @click="open = !open">
                        @if($createGroup)
                            <span class="pill-dot" style="background:{{ $createGroup->color }};"></span>
                            <span>{{ ucfirst($createGroup->name) }}</span>
                        @else
                            <span class="pill-dot pill-dot--empty"></span>
                            <span>— без групи —</span>
                        @endif
                        <x-icon.chevron-down width="13" height="13" />
                    </button>
                    <div class="dropdown group-select__dropdown" x-show="open" x-cloak>
                        <button type="button"
                                class="pill-menu-item {{ $createGroupId === '' ? 'is-active' : '' }}"
                                wire:click="$set('createGroupId', '')"
                                @click="open = false">
                            <span class="pill-dot pill-dot--empty"></span>
                            <span>— без групи —</span>
                            @if($createGroupId === '')
                                <x-icon.check width="12" height="12" class="group-select__check" />
                            @endif
                        </button>
                        @foreach($siteGroups as $sg)
                            <button type="button"
                                    class="pill-menu-item {{ (string) $createGroupId === (string) $sg->id ? 'is-active' : '' }}"
                                    wire:click="$set('createGroupId', '{{ $sg->id }}')"
                                    @click="open = false">
                                <span class="pill-dot" style="background:{{ $sg->color }};"></span>
                                <span>{{ ucfirst($sg->name) }}</span>
                                @if((string) $createGroupId === (string) $sg->id)
                                    <x-icon.check width="12" height="12" class="group-select__check" />
                                @endif
                            </button>
                        @endforeach
                    </div>
                </div>
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
