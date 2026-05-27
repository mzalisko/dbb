<div style="flex:1; display:flex; flex-direction:column; overflow-y:auto;">
    <x-ui.topbar :crumbs="['Дашборд']" />

    <x-ui.page-head :title="'Привіт, ' . (auth()->user()->name ? explode(' ', auth()->user()->name)[0] : 'друже')" />

    <div style="padding:0 40px 64px;">

        {{-- Обрані --}}
        @if ($favourites->isNotEmpty())
        <div style="margin-bottom:36px;">
            <header style="display:flex; align-items:baseline; margin-bottom:12px; padding-bottom:10px; border-bottom:1px solid var(--ink-3);">
                <h3 style="font:400 18px/1 var(--font-sans); color:var(--ink-9); flex:1;">Обрані</h3>
                <span class="mono" style="font:11px var(--font-mono); color:var(--ink-4);">{{ $favourites->count() }}/{{ $totalSites }}</span>
            </header>
            <div style="display:flex; gap:10px; overflow-x:auto; padding-bottom:4px; scrollbar-width:none; -ms-overflow-style:none; cursor:grab;"
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
                    {{-- Wrapper: relative so star sits outside <a>; x-show hides card instantly on un-favourite --}}
                    <div x-data="{ isFav: true }" x-show="isFav"
                         style="position:relative; flex-shrink:0;">
                        {{-- Star toggle — outside <a>, removes from favourites (optimistic) --}}
                        <button @click.stop="isFav = false; $wire.toggleFavourite({{ $fav->id }})"
                                title="Прибрати з обраних"
                                style="position:absolute; top:9px; right:9px; z-index:1; padding:2px; cursor:pointer; transition:opacity .12s;"
                                onmouseover="this.style.opacity='.7'" onmouseout="this.style.opacity='1'">
                            <x-icon.star width="15" height="15" style="color:var(--warn); display:block;" />
                        </button>

                        <a href="{{ route('sites.show', $fav) }}" wire:navigate
                           style="width:168px; display:flex; flex-direction:column; gap:10px;
                                  padding:12px; background:var(--card); border:1px solid var(--ink-3); border-radius:4px;
                                  cursor:pointer; transition:border-color .15s; text-decoration:none;"
                           onmouseover="this.style.borderColor='var(--ink-9)'"
                           onmouseout="this.style.borderColor='var(--ink-3)'">
                            <div style="display:flex; align-items:center; gap:8px; min-width:0; padding-right:18px;">
                                <span class="avatar avatar-sq" style="width:26px; height:26px; font-size:11px; flex-shrink:0;">{{ strtoupper(substr($fav->name, 0, 1)) }}</span>
                                <div style="min-width:0; flex:1;">
                                    <div class="mono" style="font:12px var(--font-mono); color:var(--ink-9); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $fav->name }}</div>
                                    <div style="margin-top:2px; font:11px var(--font-sans); color:var(--ink-5); white-space:nowrap;">
                                        <span class="dot {{ $favDot }}"></span>{{ $favLabel }}
                                    </div>
                                </div>
                            </div>
                            <div style="padding-top:8px; border-top:1px solid var(--ink-3); display:flex; align-items:center; gap:10px; font:11px var(--font-mono); color:var(--ink-5);">
                                @if ($fav->group)
                                    <span style="display:inline-flex; align-items:center; gap:4px; flex:1; min-width:0; overflow:hidden;">
                                        <span style="width:5px; height:5px; border-radius:999px; background:{{ $fav->group_color ?? '#a39d8c' }}; flex-shrink:0;"></span>
                                        <span style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ ucfirst($fav->group) }}</span>
                                    </span>
                                @endif
                                <span style="display:inline-flex; align-items:center; gap:3px; flex-shrink:0;">
                                    <x-icon.phone width="10" height="10" /> {{ $phones }}
                                </span>
                                <span style="display:inline-flex; align-items:center; gap:3px; flex-shrink:0;">
                                    <x-icon.chat width="10" height="10" /> {{ $msgs }}
                                </span>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
        @endif

        <div style="margin-top:8px; display:grid; grid-template-columns:1.5fr 1fr; gap:48px;">

            {{-- Sites list --}}
            <div x-data="{ activeGroup: localStorage.getItem('db-dash-group') || 'all', setGroup(g){ this.activeGroup=g; localStorage.setItem('db-dash-group',g); } }">
                <header style="display:flex; align-items:baseline; margin-bottom:12px; padding-bottom:8px; border-bottom:1px solid var(--ink-3);">
                    <h3 style="font:400 18px/1 var(--font-sans); color:var(--ink-9); flex:1;">Сайти</h3>
                    <a href="{{ route('sites.index') }}" wire:navigate class="btn btn-ghost btn-sm" style="padding:0;">Усі →</a>
                </header>

                {{-- Group filter strip (Alpine — instant, no round-trip) --}}
                <div style="display:flex; gap:6px; overflow-x:auto; padding:8px 0 14px; scrollbar-width:none; -ms-overflow-style:none;">
                    <button class="filter-pill-sm" :class="activeGroup === 'all' ? 'is-active' : ''" x-on:click="setGroup('all')">
                        Усі
                    </button>
                    @foreach ($groups as $group)
                        <button class="filter-pill-sm" :class="activeGroup === '{{ $group->group }}' ? 'is-active' : ''" x-on:click="setGroup('{{ $group->group }}')">
                            <span style="width:5px; height:5px; border-radius:999px; background:{{ $group->group_color }};"></span>
                            {{ ucfirst($group->group) }}
                        </button>
                    @endforeach
                </div>

                <div class="thin-scroll" style="max-height:427px; overflow-y:auto; padding-right:10px;">
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
                            <a href="{{ route('sites.show', $site) }}" wire:navigate
                               style="display:grid; grid-template-columns:32px 1fr auto auto; gap:14px; padding:14px 0; border-bottom:1px solid var(--ink-3); align-items:center; cursor:pointer; text-align:left; transition:background .12s; background:transparent;"
                               onmouseover="this.style.background='var(--paper-2)'" onmouseout="this.style.background='transparent'">
                                <span class="avatar avatar-sq">{{ strtoupper(substr($site->name, 0, 1)) }}</span>
                                <div>
                                    <div class="mono" style="font:13.5px var(--font-mono); color:var(--ink-9);">{{ $site->name }}</div>
                                    <div style="margin-top:3px; font:12px var(--font-sans); color:{{ $site->status === 'offline' ? 'var(--bad)' : 'var(--ink-5)' }};">
                                        <span class="dot {{ $statusClass }}"></span>{{ $statusLabel }}
                                    </div>
                                </div>
                                <span class="mono" style="font:11.5px var(--font-mono); color:var(--ink-4);">
                                    {{ $site->last_checked_at?->diffForHumans(null, true) ?? 'Ніколи' }}
                                </span>
                                <x-icon.arrow width="14" height="14" style="color:var(--ink-4);" />
                            </a>
                        </div>
                    @empty
                        <div style="padding:48px 0; text-align:center; color:var(--ink-5); font:13.5px var(--font-sans);">
                            Немає сайтів. Додайте перший.
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- System logs --}}
            <div>
                <header style="display:flex; align-items:baseline; margin-bottom:12px; padding-bottom:12px; border-bottom:1px solid var(--ink-3);">
                    <h3 style="font:400 18px/1 var(--font-sans); color:var(--ink-9); flex:1;">Системні логи</h3>
                    <a href="{{ route('activity.index') }}" wire:navigate class="btn btn-ghost btn-sm" style="padding:0;">Усі →</a>
                </header>

                @forelse ($recentLogs as $log)
                    @php
                        $dotClass = str_contains($log->action, 'fail') || str_contains($log->action, 'offline') ? 'dot-bad' : 'dot-ok';
                    @endphp
                    <div style="padding:12px 0; border-bottom:1px solid var(--ink-3);">
                        <div style="display:flex; justify-content:space-between; align-items:baseline; gap:12px;">
                            <div style="font:13px/1.4 var(--font-sans); color:var(--ink-8);">
                                <span class="dot {{ $dotClass }}"></span>
                                <span class="mono" style="color:var(--ink-9); font-size:12.5px;">
                                    {{ $log->subject?->name ?? ($log->subject_type ? class_basename($log->subject_type) : '—') }}
                                </span>
                                <span style="color:var(--ink-5); margin-left:6px;">{{ $log->action }}</span>
                            </div>
                            <span class="mono" style="font:11px var(--font-mono); color:var(--ink-4); white-space:nowrap;">
                                {{ $log->created_at->format('H:i:s') }}
                            </span>
                        </div>
                    </div>
                @empty
                    <div style="padding:48px 0; text-align:center; color:var(--ink-5); font:13px var(--font-sans);">
                        Немає подій.
                    </div>
                @endforelse
            </div>

        </div>
    </div>
</div>
