<div style="flex:1; display:flex; flex-direction:column; overflow-y:auto;">
    <x-ui.topbar :crumbs="['Логи']">
        <x-ui.button variant="secondary" size="sm" wire:click="export">
            <x-icon.export width="13" height="13" /> Експорт
        </x-ui.button>
    </x-ui.topbar>

    <x-ui.page-head
        eyebrow="Журнал"
        :number="$counts['total']"
        label="подій"
        sub="Усі зміни даних, авторизація та групові операції — зі старими й новими значеннями." />

    <div style="padding:0 40px 64px;">
        {{-- Filter bar --}}
        <div style="display:flex; flex-wrap:wrap; align-items:center; gap:10px; margin-bottom:14px;">
            <div style="display:flex; align-items:center; gap:8px; height:38px; padding:0 14px; border-radius:999px; background:var(--card); border:1px solid var(--ink-3); min-width:240px;">
                <x-icon.search width="14" height="14" style="color:var(--ink-5);" />
                <input wire:model.live.debounce.300ms="search" placeholder="Пошук по подіях…"
                       style="flex:1; border:0; background:transparent; outline:none; font:13.5px var(--font-sans); color:var(--ink-7);">
            </div>
            <select wire:model.live="filterSite"
                    style="height:38px; padding:0 30px 0 14px; border-radius:999px; background:var(--card); border:1px solid var(--ink-3); font:12.5px var(--font-sans); color:var(--ink-7); cursor:pointer; outline:none; appearance:none; -webkit-appearance:none;">
                <option value="">Усі сайти</option>
                @foreach ($sites as $s)
                    <option value="{{ $s->id }}">{{ $s->name }}</option>
                @endforeach
            </select>
            @if ($filterDomain || $filterSeverity !== '' || $filterSite || $search)
                <button wire:click="clearFilters"
                        style="height:38px; padding:0 14px; border-radius:999px; background:transparent; border:1px solid var(--ink-3); font:12.5px var(--font-sans); color:var(--ink-6); cursor:pointer;">
                    × Очистити
                </button>
            @endif
        </div>

        {{-- Domain pills --}}
        <div style="display:flex; gap:6px; flex-wrap:wrap; margin-bottom:8px;">
            <button wire:click="$set('filterDomain', '')" class="filter-pill {{ $filterDomain === '' ? 'is-active' : '' }}">
                Усі <span class="pill-count">{{ $counts['total'] }}</span>
            </button>
            @foreach ($domains as $key => $label)
                <button wire:click="$set('filterDomain', '{{ $key }}')" class="filter-pill {{ $filterDomain === $key ? 'is-active' : '' }}">
                    {{ $label }} <span class="pill-count">{{ $counts['byDomain'][$key] ?? 0 }}</span>
                </button>
            @endforeach
        </div>

        {{-- Severity pills --}}
        <div style="display:flex; gap:6px; flex-wrap:wrap; margin-bottom:18px;">
            <button wire:click="$set('filterSeverity', '')" class="filter-pill {{ $filterSeverity === '' ? 'is-active' : '' }}">Будь-яка важливість</button>
            @foreach ($severities as $val => $label)
                @php $d = $val === 2 ? 'dot-bad' : ($val === 1 ? 'dot-warn' : 'dot-ok'); @endphp
                <button wire:click="$set('filterSeverity', '{{ $val }}')" class="filter-pill {{ (string) $filterSeverity === (string) $val ? 'is-active' : '' }}">
                    <span class="dot {{ $d }}"></span> {{ $label }} <span class="pill-count">{{ $counts['bySeverity'][$val] ?? 0 }}</span>
                </button>
            @endforeach
        </div>

        {{-- Events --}}
        <div class="card" style="overflow:hidden;">
            <div style="display:grid; grid-template-columns:24px 88px 1.5fr 1fr 1fr 86px 24px; gap:12px; padding:12px 18px; background:var(--paper-2); border-bottom:1px solid var(--ink-3);">
                <span></span>
                @foreach (['Час', 'Подія', 'Сайт', 'Користувач', 'Важливість', ''] as $h)
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
                     style="display:grid; grid-template-columns:24px 88px 1.5fr 1fr 1fr 86px 24px; gap:12px; padding:13px 18px; border-top:1px solid var(--ink-3); align-items:center; cursor:pointer; transition:background .12s;"
                     onmouseover="this.style.background='var(--paper-2)'" onmouseout="this.style.background='transparent'">
                    <span class="dot {{ $dot }}"></span>
                    <span class="mono" style="font:12px var(--font-mono); color:var(--ink-6);">{{ $e->occurredAt->format('H:i:s') }}</span>
                    <span style="display:inline-flex; align-items:center; gap:8px; font:13px var(--font-sans); color:var(--ink-9); min-width:0;">
                        <x-dynamic-component :component="'icon.' . $e->icon()" width="13" height="13" style="color:var(--ink-5); flex-shrink:0;" />
                        <span style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $e->label() }}</span>
                    </span>
                    <span class="mono" style="font:12px var(--font-mono); color:var(--ink-7); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $siteNames[$e->siteId] ?? '—' }}</span>
                    <span style="font:12.5px var(--font-sans); color:var(--ink-5);">{{ $e->userName ?? 'Система' }}</span>
                    <span style="font:11px var(--font-mono); color:{{ $sevColor }};">{{ $e->severityLabel() }}</span>
                    <x-icon.arrow width="13" height="13" style="color:var(--ink-4);" />
                </div>
            @empty
                <div style="padding:64px; text-align:center; color:var(--ink-5); font:13.5px var(--font-sans);">Немає подій за цими фільтрами.</div>
            @endforelse
        </div>

        <div style="margin-top:16px;">{{ $events->links('livewire.quiet-pagination') }}</div>
    </div>

    {{-- Detail drawer (old → new diff) --}}
    @if ($detail)
        <div wire:key="log-detail">
            <x-ui.drawer :open="true" :title="$detail['label']" :sub="$detail['occurredAt']"
                         @drawer-close.window="$wire.closeDetail()">
                <div style="display:flex; flex-direction:column; gap:18px;">
                    <div style="font:12.5px var(--font-mono); color:var(--ink-5);">
                        {{ $siteNames[$detail['siteId']] ?? '—' }} · {{ $detail['actionCode'] }}
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
                            <div style="display:grid; grid-template-columns:1fr 1fr 16px 1fr; gap:8px; padding:0 2px 6px;">
                                <span class="eyebrow" style="font-size:10px;">Поле</span>
                                <span class="eyebrow" style="font-size:10px;">Було</span>
                                <span></span>
                                <span class="eyebrow" style="font-size:10px;">Стало</span>
                            </div>
                            @foreach ($detail['changes'] as $c)
                                <div style="display:grid; grid-template-columns:1fr 1fr 16px 1fr; gap:8px; align-items:center; padding:9px 2px; border-top:1px solid var(--ink-2);">
                                    <span style="font:12px var(--font-sans); color:var(--ink-7);">{{ \App\Support\AuditEntry::humanField($c['field']) }}</span>
                                    <span style="font:12px var(--font-sans); color:var(--bad); word-break:break-word;">{{ \App\Support\AuditEntry::humanValue($c['field'], $c['old']) }}</span>
                                    <x-icon.arrow width="12" height="12" style="color:var(--ink-4);" />
                                    <span style="font:12px var(--font-sans); color:var(--ok); word-break:break-word;">{{ \App\Support\AuditEntry::humanValue($c['field'], $c['new']) }}</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div style="font:13px var(--font-sans); color:var(--ink-5);">Подія без змінених полів.</div>
                    @endif

                    <div style="display:flex; flex-direction:column; gap:9px; padding-top:14px; border-top:1px solid var(--ink-3);">
                        <div style="display:flex; align-items:center; gap:8px; font:12.5px var(--font-sans); color:var(--ink-6);">
                            <x-icon.user width="13" height="13" style="color:var(--ink-4);" /> {{ $detail['userName'] }}
                        </div>
                        <div style="display:flex; align-items:center; gap:8px; font:12.5px var(--font-sans); color:var(--ink-6);">
                            <x-icon.clock width="13" height="13" style="color:var(--ink-4);" /> {{ $detail['occurredAt'] }}
                        </div>
                        @if ($detail['ip'])
                            <div style="display:flex; align-items:center; gap:8px; font:12.5px var(--font-sans); color:var(--ink-6);">
                                <x-icon.globe width="13" height="13" style="color:var(--ink-4);" /> {{ $detail['ip'] }} · {{ $detail['context'] ?? 'web' }}
                            </div>
                        @endif
                        <div style="font:11px var(--font-mono); color:var(--ink-4);">
                            джерело: {{ $detail['source'] === 'audit' ? 'audits' : 'activity_log' }}@if ($detail['batchId']) · batch {{ substr($detail['batchId'], 0, 8) }}@endif
                        </div>
                    </div>
                </div>

                <x-slot:footer>
                    <button class="btn btn-ghost" disabled title="Відкат — поза скоупом">Відкотити</button>
                    <span style="flex:1;"></span>
                    <button class="btn btn-primary" wire:click="closeDetail">Закрити</button>
                </x-slot:footer>
            </x-ui.drawer>
        </div>
    @endif
</div>
