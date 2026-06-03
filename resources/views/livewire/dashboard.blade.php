<div class="page">
    <x-ui.topbar :crumbs="['Дашборд']" />

    <x-ui.page-head :title="'Привіт, ' . (auth()->user()->name ? explode(' ', auth()->user()->name)[0] : 'друже')" />

    <div class="dash-body">

        {{-- Обрані --}}
        @if ($favourites->isNotEmpty())
        <div class="dash-section">
            <header class="section-head">
                <h3 class="section-head__title section-head__title--with-icon">
                    <x-icon.star class="section-head__icon" width="17" height="17" aria-hidden="true" />
                    <span>Обрані</span>
                </h3>
                <span class="mono section-head__meta">{{ $favourites->count() }}/{{ $totalSites }}</span>
            </header>
            <div class="fav-strip"
                 x-data
                 x-on:mousedown.prevent="$el.dataset.dragging='1'; $el.dataset.startX=($event.pageX - $el.offsetLeft); $el.dataset.scrollLeft=$el.scrollLeft"
                 x-on:mouseleave="$el.dataset.dragging='0'"
                 x-on:mouseup="$el.dataset.dragging='0'"
                 x-on:mousemove="if($el.dataset.dragging==='1'){ const x=$event.pageX-$el.offsetLeft; $el.scrollLeft=$el.dataset.scrollLeft-(x-$el.dataset.startX); }">
                @foreach ($favourites as $fav)
                    @php
                        $favDot   = match($fav->status) { 'active' => 'dot-ok', 'maintenance' => 'dot-warn', default => 'dot-bad' };
                        $favLabel = match($fav->status) { 'active' => 'Активний', 'maintenance' => 'Пауза', default => 'Помилка' };
                        $phones   = $fav->contactEntries->where('type', 'phone')->where('visible', true)->count();
                        $msgs     = $fav->contactEntries->where('type', 'messenger')->where('visible', true)->count();
                    @endphp
                    {{-- x-show hides card instantly on un-favourite; star sits outside <a> --}}
                    <div class="fav-card" x-data="{ isFav: true }" x-show="isFav">
                        <button class="fav-card__star" title="Прибрати з обраних"
                                @click.stop="isFav = false; $wire.toggleFavourite({{ $fav->id }})">
                            <x-icon.star width="15" height="15" style="color:var(--warn); display:block;" />
                        </button>

                        <a href="{{ route('sites.show', $fav) }}" wire:navigate class="fav-card__link">
                            <div class="fav-card__head">
                                <span class="avatar avatar-sq avatar-sm">{{ strtoupper(substr($fav->name, 0, 1)) }}</span>
                                <div style="min-width:0; flex:1;">
                                    <div class="mono fav-card__name">{{ $fav->name }}</div>
                                    <div class="fav-card__status"><span class="dot {{ $favDot }}"></span>{{ $favLabel }}</div>
                                </div>
                            </div>
                            <div class="fav-card__foot">
                                @if ($fav->group)
                                    <span class="fav-card__group">
                                        <span class="group-dot" style="background:{{ $fav->group_color ?? '#a39d8c' }};"></span>
                                        <span>{{ ucfirst($fav->group) }}</span>
                                    </span>
                                @endif
                                @if($canPhones)
                                    <span class="fav-card__chip"><x-icon.phone width="10" height="10" /> {{ $phones }}</span>
                                @endif
                                @if($canMessengers)
                                    <span class="fav-card__chip"><x-icon.chat width="10" height="10" /> {{ $msgs }}</span>
                                @endif
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
        @endif

        <div class="dash-grid">

            {{-- Sites list --}}
            <div x-data="{ activeGroup: localStorage.getItem('db-dash-group') || 'all', setGroup(g){ this.activeGroup=g; localStorage.setItem('db-dash-group',g); } }">
                <header class="section-head" style="padding-bottom:8px;">
                    <h3 class="section-head__title">Сайти</h3>
                    <a href="{{ route('sites.index') }}" wire:navigate class="btn btn-ghost btn-sm" style="padding:0;">Усі →</a>
                </header>

                {{-- Group filter strip — first 4 inline, rest in overflow menu --}}
                @php
                    $dashVisible  = $groups->take(4);
                    $dashOverflow = $groups->slice(4);
                @endphp
                <div class="dash-filter">
                    <button class="filter-pill-sm" :class="activeGroup === 'all' ? 'is-active' : ''" x-on:click="setGroup('all')">
                        Усі
                    </button>
                    @foreach ($dashVisible as $group)
                        <button class="filter-pill-sm" :class="activeGroup === '{{ $group->group }}' ? 'is-active' : ''" x-on:click="setGroup('{{ $group->group }}')">
                            <span class="pill-dot" style="background:{{ $group->group_color }};"></span>
                            {{ ucfirst($group->group) }}
                        </button>
                    @endforeach

                    @if ($dashOverflow->isNotEmpty())
                        @php $dashOverflowNames = $dashOverflow->pluck('group')->values(); @endphp
                        <div class="filter-more" x-data="{ moreOpen: false }" @click.outside="moreOpen = false">
                            <button class="filter-pill-sm"
                                    :class="{{ Illuminate\Support\Js::from($dashOverflowNames) }}.includes(activeGroup) ? 'is-active' : ''"
                                    @click="moreOpen = !moreOpen" title="Ще групи">
                                <x-icon.grid width="12" height="12" />
                            </button>
                            <div class="dropdown" x-show="moreOpen" x-cloak>
                                @foreach ($dashOverflow as $group)
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

                <div class="thin-scroll dash-list">
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
                                default       => 'Помилка sync',
                            };
                        @endphp
                        <div class="dash-site-row" data-site-group="{{ $site->group }}" x-show="activeGroup === 'all' || '{{ $site->group }}' === activeGroup">
                            <a href="{{ route('sites.show', $site) }}" wire:navigate class="dash-site-row__link">
                                <span class="avatar avatar-sq">{{ strtoupper(substr($site->name, 0, 1)) }}</span>
                                <div>
                                    <div class="mono dash-site-row__name">{{ $site->name }}</div>
                                    <div class="dash-site-row__status {{ $site->status === 'offline' ? 'is-offline' : '' }}">
                                        <span class="dot {{ $statusClass }}"></span>{{ $statusLabel }}
                                    </div>
                                </div>
                                <span class="mono dash-site-row__when">
                                    {{ $site->last_checked_at?->diffForHumans(null, true) ?? 'Ніколи' }}
                                </span>
                                <x-icon.arrow width="14" height="14" style="color:var(--ink-4);" />
                            </a>
                        </div>
                    @empty
                        <div class="dash-empty">Немає сайтів. Додайте перший.</div>
                    @endforelse
                </div>
            </div>

            {{-- System logs --}}
            <div>
                <header class="section-head" style="padding-bottom:12px;">
                    <h3 class="section-head__title">Системні логи</h3>
                    <a href="{{ route('activity.index') }}" wire:navigate class="btn btn-ghost btn-sm" style="padding:0;">Усі →</a>
                </header>

                @forelse ($recentLogs as $log)
                    @php
                        $dotClass = $log->severity === 2 ? 'dot-bad' : ($log->severity === 1 ? 'dot-warn' : 'dot-ok');
                        $logType = str_contains($log->actionCode, 'creat') ? 'create'
                            : (str_contains($log->actionCode, 'delet') || str_contains($log->actionCode, 'purg') ? 'delete'
                            : (str_contains($log->actionCode, 'failover') ? 'failover' : 'update'));
                        $logAnchor = '#activity/' . $logType . '/' . $log->source . '-' . $log->id;
                        $isDeletedSiteLog = $log->siteId && in_array((int) $log->siteId, $deletedSiteIds, true);
                    @endphp
                    @if($isDeletedSiteLog)
                        <button type="button" wire:click="requestRestoreSite({{ $log->siteId }})" class="log-row log-row--button" title="Відновити сайт">
                            <div class="log-row__inner">
                                <div class="log-row__text">
                                    <span class="dot {{ $dotClass }}"></span>
                                    <span class="mono log-row__subj">{{ $log->targetName($logSiteNames) }}</span>
                                    <span class="log-row__action">{{ $log->label() }}</span>
                                </div>
                                <span class="mono log-row__time">{{ $log->occurredAt->format('H:i:s') }}</span>
                            </div>
                        </button>
                    @else
                    <a href="{{ $log->siteId ? route('sites.show', $log->siteId).$logAnchor : route('activity.index') }}" wire:navigate class="log-row" style="text-decoration:none;" title="Перейти в сайт">
                        <div class="log-row__inner">
                            <div class="log-row__text">
                                <span class="dot {{ $dotClass }}"></span>
                                <span class="mono log-row__subj">{{ $log->targetName($logSiteNames) }}</span>
                                <span class="log-row__action">{{ $log->label() }}</span>
                            </div>
                            <span class="mono log-row__time">{{ $log->occurredAt->format('H:i:s') }}</span>
                        </div>
                    </a>
                    @endif
                @empty
                    <div class="dash-empty">Немає подій.</div>
                @endforelse

                @if($recentLogs->hasPages())
                    <div class="dash-log-pager">
                        {{ $recentLogs->links('livewire.quiet-pagination') }}
                    </div>
                @endif
            </div>

        </div>
    </div>

    @include('livewire.partials.restore-site-modal')
</div>
