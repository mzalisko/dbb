<div style="flex:1; display:flex; flex-direction:column; overflow-y:auto;">
    <x-ui.topbar :crumbs="['Логи']">
        <x-ui.button variant="secondary" size="sm" wire:click="export">
            <x-icon.export width="13" height="13" /> Експорт
        </x-ui.button>
    </x-ui.topbar>

    <x-ui.page-head
        eyebrow="Журнал"
        :number="$counts['sites'] + $counts['auth'] + $counts['bulk'] + $counts['perms']"
        label="подій"
        sub="Дайвінг у сайт → вкладка «Активність» показує деталі змін зі старим і новим значенням." />

    <div style="padding:0 40px 64px;">
        {{-- Tabs --}}
        <div class="tabs" style="margin-bottom:18px;">
            @foreach ($tabs as $key => $label)
                <button class="tab {{ $tab === $key ? 'active' : '' }}" wire:click="$set('tab', '{{ $key }}')">
                    {{ $label }} <span class="tab-n">{{ $counts[$key] }}</span>
                </button>
            @endforeach
        </div>

        {{-- Search --}}
        <div style="display:flex; align-items:center; gap:8px; height:38px; padding:0 14px; border-radius:999px; background:var(--card); border:1px solid var(--ink-3); max-width:340px; margin-bottom:18px;">
            <x-icon.search width="14" height="14" style="color:var(--ink-5);" />
            <input wire:model.live.debounce.300ms="search" placeholder="Пошук по подіях…"
                   style="flex:1; border:0; background:transparent; outline:none; font:13.5px var(--font-sans); color:var(--ink-7);">
        </div>

        @if ($tab === 'sites')
            {{-- Sites: one card per site, dive into its Activity tab --}}
            <div style="display:flex; flex-direction:column; gap:8px;">
                @forelse ($sitesSummary as $s)
                    @php
                        $sev = $s->last->severity;
                        $dot = $sev === 2 ? 'dot-bad' : ($sev === 1 ? 'dot-warn' : 'dot-ok');
                        $lastType = str_contains($s->last->actionCode, 'creat') ? 'create'
                            : (str_contains($s->last->actionCode, 'delet') || str_contains($s->last->actionCode, 'purg') ? 'delete'
                            : (str_contains($s->last->actionCode, 'failover') ? 'failover' : 'update'));
                        $lastAnchor = '#activity/' . $lastType . '/' . $s->last->source . '-' . $s->last->id;
                        $isDeletedSite = in_array((int) $s->siteId, $deletedSiteIds, true);
                    @endphp
                    @if($isDeletedSite)
                        <button type="button" wire:click="requestRestoreSite({{ $s->siteId }})"
                                class="card" style="width:100%; border:1px solid var(--ink-3); background:var(--card); display:grid; grid-template-columns:28px 1.4fr 1.6fr 110px 24px; gap:14px; align-items:center; padding:16px 18px; text-decoration:none; text-align:left; transition:background .12s; cursor:pointer;"
                                onmouseover="this.style.background='var(--paper-2)'" onmouseout="this.style.background='var(--card)'"
                                title="Відновити сайт">
                            <span class="avatar avatar-sq" style="width:28px; height:28px; font-size:11px;">{{ strtoupper(substr($s->last->targetName($siteNames), 0, 1)) }}</span>
                            <span class="mono" style="font:13.5px var(--font-mono); color:var(--ink-9); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $s->last->targetName($siteNames) }}</span>
                            <span style="display:inline-flex; align-items:center; gap:8px; min-width:0;">
                                <span class="dot {{ $dot }}"></span>
                                <span style="font:12.5px var(--font-sans); color:var(--ink-7); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $s->last->label() }}</span>
                                <span class="mono" style="font:11px var(--font-mono); color:var(--ink-5);">{{ $s->last->occurredAt->diffForHumans(null, true) }}</span>
                            </span>
                            <span class="mono" style="font:11.5px var(--font-mono); color:var(--ink-5); text-align:right;">{{ $s->count }} змін</span>
                            <x-icon.refresh width="14" height="14" style="color:var(--ink-4);" />
                        </button>
                    @else
                        <a href="{{ route('sites.show', $s->siteId) }}{{ $lastAnchor }}" wire:navigate
                           class="card" style="display:grid; grid-template-columns:28px 1.4fr 1.6fr 110px 24px; gap:14px; align-items:center; padding:16px 18px; text-decoration:none; transition:background .12s;"
                           onmouseover="this.style.background='var(--paper-2)'" onmouseout="this.style.background='var(--card)'">
                            <span class="avatar avatar-sq" style="width:28px; height:28px; font-size:11px;">{{ strtoupper(substr($s->last->targetName($siteNames), 0, 1)) }}</span>
                            <span class="mono" style="font:13.5px var(--font-mono); color:var(--ink-9); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $s->last->targetName($siteNames) }}</span>
                            <span style="display:inline-flex; align-items:center; gap:8px; min-width:0;">
                                <span class="dot {{ $dot }}"></span>
                                <span style="font:12.5px var(--font-sans); color:var(--ink-7); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $s->last->label() }}</span>
                                <span class="mono" style="font:11px var(--font-mono); color:var(--ink-5);">{{ $s->last->occurredAt->diffForHumans(null, true) }}</span>
                            </span>
                            <span class="mono" style="font:11.5px var(--font-mono); color:var(--ink-5); text-align:right;">{{ $s->count }} змін</span>
                            <x-icon.arrow width="14" height="14" style="color:var(--ink-4);" />
                        </a>
                    @endif
                @empty
                    <div style="padding:64px; text-align:center; color:var(--ink-5); font:13.5px var(--font-sans);">Немає змін на сайтах.</div>
                @endforelse
            </div>
        @else
            {{-- Auth / Bulk / Perms: flat event stream + detail drawer --}}
            <div class="card" style="overflow:hidden;">
                <div style="display:grid; grid-template-columns:24px 88px 1.6fr 1.1fr 86px 24px; gap:12px; padding:12px 18px; background:var(--paper-2); border-bottom:1px solid var(--ink-3);">
                    <span></span>
                    @foreach (['Час', 'Подія', 'Користувач', 'Важливість', ''] as $h)
                        <span class="eyebrow" style="font-size:10px;">{{ $h }}</span>
                    @endforeach
                </div>

                @forelse ($events as $e)
                    @php
                        $sev = $e->severity;
                        $dot = $sev === 2 ? 'dot-bad' : ($sev === 1 ? 'dot-warn' : 'dot-ok');
                        $sevColor = $sev === 2 ? 'var(--bad)' : ($sev === 1 ? 'var(--warn)' : 'var(--ok)');
                    @endphp
                    <div wire:key="ev-{{ $e->source }}-{{ $e->id }}" wire:click="openDetail('{{ $e->source }}', {{ $e->id }})"
                         style="display:grid; grid-template-columns:24px 88px 1.6fr 1.1fr 86px 24px; gap:12px; padding:13px 18px; border-top:1px solid var(--ink-3); align-items:center; cursor:pointer; transition:background .12s;"
                         onmouseover="this.style.background='var(--paper-2)'" onmouseout="this.style.background='transparent'">
                        <span class="dot {{ $dot }}"></span>
                        <span class="mono" style="font:12px var(--font-mono); color:var(--ink-6);">{{ $e->occurredAt->format('H:i:s') }}</span>
                        <span style="display:inline-flex; align-items:center; gap:8px; font:13px var(--font-sans); color:var(--ink-9); min-width:0;">
                            <x-dynamic-component :component="'icon.' . $e->icon()" width="13" height="13" style="color:var(--ink-5); flex-shrink:0;" />
                            <span style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $e->label() }}</span>
                        </span>
                        <span style="font:12.5px var(--font-sans); color:var(--ink-5);">{{ $e->userName ?? 'Система' }}</span>
                        <span style="font:11px var(--font-mono); color:{{ $sevColor }};">{{ $e->severityLabel() }}</span>
                        <x-icon.arrow width="13" height="13" style="color:var(--ink-4);" />
                    </div>
                @empty
                    <div style="padding:64px; text-align:center; color:var(--ink-5); font:13.5px var(--font-sans);">Немає подій.</div>
                @endforelse
            </div>

            <div style="margin-top:16px;">{{ $events->links('livewire.quiet-pagination') }}</div>
        @endif
    </div>

    {{-- Detail drawer (old → new diff in plain language) --}}
    @if ($detail)
        <div wire:key="log-detail">
            <x-ui.drawer :open="true" :title="$detail['label']" :sub="$detail['occurredAt']"
                         @drawer-close.window="$wire.closeDetail()">
                <div style="display:flex; flex-direction:column; gap:18px;">
                    <div style="font:12.5px var(--font-mono); color:var(--ink-5);">
                        {{ $detail['targetName'] ?? ($siteNames[$detail['siteId']] ?? '—') }} · {{ $detail['actionCode'] }}
                    </div>

                    @if ($detail['isBulk'])
                        <div style="display:flex; flex-direction:column; gap:6px;">
                            @foreach (['done', 'skipped', 'count'] as $k)
                                @continue (! array_key_exists($k, $detail['summary']))
                                <div style="display:flex; justify-content:space-between; gap:12px; padding:8px 12px; border-radius:8px; background:var(--paper-2);">
                                    <span style="font:12.5px var(--font-sans); color:var(--ink-5);">{{ \App\Support\AuditEntry::humanField($k) }}</span>
                                    <span style="font:13px var(--font-sans); color:var(--ink-9);">{{ $detail['summary'][$k] }}</span>
                                </div>
                            @endforeach
                        </div>
                    @elseif (count($detail['changes']))
                        <div>
                            @foreach ($detail['changes'] as $c)
                                @if ($c['kind'] === 'group')
                                    {{-- Permission matrix: one line per changed toggle --}}
                                    <div style="padding:9px 2px; border-top:1px solid var(--ink-2);">
                                        <span style="font:12px var(--font-sans); color:var(--ink-7);">{{ $c['field'] }}</span>
                                        <div style="margin-top:6px; display:flex; flex-direction:column; gap:5px;">
                                            @foreach ($c['lines'] as $l)
                                                <div style="display:grid; grid-template-columns:1.4fr 1fr 16px 1fr; gap:8px; align-items:center; padding-left:10px;">
                                                    <span style="font:12px var(--font-sans); color:var(--ink-6);">{{ $l['label'] }}</span>
                                                    <span style="font:12px var(--font-sans); color:var(--bad);">{{ $l['old'] }}</span>
                                                    <x-icon.arrow width="12" height="12" style="color:var(--ink-4);" />
                                                    <span style="font:12px var(--font-sans); color:var(--ok);">{{ $l['new'] }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @elseif ($c['kind'] === 'delta')
                                    <div style="display:grid; grid-template-columns:1fr 2.6fr; gap:8px; align-items:center; padding:9px 2px; border-top:1px solid var(--ink-2);">
                                        <span style="font:12px var(--font-sans); color:var(--ink-7);">{{ $c['field'] }}</span>
                                        <span style="font:12px var(--font-sans); color:var(--ink-9); word-break:break-word;">{{ \App\Support\AuditEntry::deltaText($c['added'], $c['removed']) }}</span>
                                    </div>
                                @else
                                    <div style="display:grid; grid-template-columns:1fr 1fr 16px 1fr; gap:8px; align-items:center; padding:9px 2px; border-top:1px solid var(--ink-2);">
                                        <span style="font:12px var(--font-sans); color:var(--ink-7);">{{ $c['field'] }}</span>
                                        <span style="font:12px var(--font-sans); color:{{ $c['oldEmpty'] ? 'var(--ink-4)' : 'var(--bad)' }}; word-break:break-word;">{{ $c['old'] }}</span>
                                        <x-icon.arrow width="12" height="12" style="color:var(--ink-4);" />
                                        <span style="font:12px var(--font-sans); color:{{ $c['newEmpty'] ? 'var(--ink-4)' : 'var(--ok)' }}; word-break:break-word;">{{ $c['new'] }}</span>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    @else
                        <div style="font:13px var(--font-sans); color:var(--ink-5);">Подія без змінених полів.</div>
                    @endif

                    <div style="display:flex; flex-direction:column; gap:9px; padding-top:14px; border-top:1px solid var(--ink-3);">
                        <div style="display:flex; align-items:center; gap:8px; font:12.5px var(--font-sans); color:var(--ink-6);">
                            <x-icon.user width="13" height="13" style="color:var(--ink-4);" /> {{ $detail['userName'] }}
                        </div>
                        @if ($detail['ip'])
                            <div style="display:flex; align-items:center; gap:8px; font:12.5px var(--font-sans); color:var(--ink-6);">
                                <x-icon.globe width="13" height="13" style="color:var(--ink-4);" /> {{ $detail['ip'] }} · {{ $detail['context'] ?? 'web' }}
                            </div>
                        @endif
                        @if ($detail['siteId'])
                            @php
                                $detailType = str_contains($detail['actionCode'], 'creat') ? 'create'
                                    : (str_contains($detail['actionCode'], 'delet') || str_contains($detail['actionCode'], 'purg') ? 'delete'
                                    : (str_contains($detail['actionCode'], 'failover') ? 'failover' : 'update'));
                                $detailAnchor = '#activity/' . $detailType . '/' . $detail['source'] . '-' . $detail['id'];
                                $detailSiteDeleted = in_array((int) $detail['siteId'], $deletedSiteIds, true);
                            @endphp
                            @if($detailSiteDeleted)
                                <button type="button" wire:click="requestRestoreSite({{ $detail['siteId'] }})"
                                        style="display:inline-flex; align-items:center; gap:6px; border:0; background:transparent; padding:0; font:12.5px var(--font-sans); color:var(--ink-9); cursor:pointer;">
                                    <x-icon.refresh width="13" height="13" /> Відновити сайт
                                </button>
                            @else
                                <a href="{{ route('sites.show', $detail['siteId']) }}{{ $detailAnchor }}" wire:navigate
                                   style="display:inline-flex; align-items:center; gap:6px; font:12.5px var(--font-sans); color:var(--ink-9); text-decoration:none;">
                                    <x-icon.arrow width="13" height="13" /> Перейти в сайт
                                </a>
                            @endif
                        @endif
                    </div>
                </div>

                <x-slot:footer>
                    <button class="btn btn-primary" wire:click="closeDetail">Закрити</button>
                </x-slot:footer>
            </x-ui.drawer>
        </div>
    @endif

    @include('livewire.partials.restore-site-modal')
</div>
