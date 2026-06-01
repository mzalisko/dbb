<div style="flex:1; display:flex; flex-direction:column; overflow-y:auto;">
    <x-ui.topbar :crumbs="['Логи']">
        <button class="btn btn-secondary btn-sm">
            <x-icon.export width="13" height="13" /> Експорт
        </button>
    </x-ui.topbar>

    <x-ui.page-head
        eyebrow="Журнал"
        :number="$dataLogs->count()"
        label="подій"
        sub="Системні події, зміни даних, активність по сайтах." />

    <div style="padding:0 40px 64px;" x-data="{ tab: '{{ $tab }}' }">

        {{-- Tabs --}}
        <div class="tabs" style="margin-bottom:24px;">
            <button class="tab" :class="tab==='system' ? 'active' : ''" @click="tab='system'">
                Системні <span class="tab-n">0</span>
            </button>
            <button class="tab" :class="tab==='data' ? 'active' : ''" @click="tab='data'">
                По даних <span class="tab-n">{{ $dataLogs->count() }}</span>
            </button>
            <button class="tab" :class="tab==='sites' ? 'active' : ''" @click="tab='sites'">
                По сайтах <span class="tab-n">{{ $sites->count() }}</span>
            </button>
        </div>

        {{-- System logs --}}
        <div x-show="tab === 'system'">
            <div class="card" style="overflow:hidden;">
                <div style="display:grid; grid-template-columns:32px 120px 120px 1.4fr 1fr 130px 90px; gap:12px; padding:12px 18px; background:var(--paper-2); border-bottom:1px solid var(--ink-3);">
                    <span></span>
                    @foreach (['Час', 'Категорія', 'Дія', 'Користувач', 'IP', 'Статус'] as $h)
                        <span class="eyebrow" style="font-size:10px;">{{ $h }}</span>
                    @endforeach
                </div>
                <div style="padding:64px; text-align:center; color:var(--ink-5); font:13.5px var(--font-sans);">
                    Системні логи з'являться тут як тільки почнуть збиратись.
                </div>
            </div>
        </div>

        {{-- Data logs --}}
        <div x-show="tab === 'data'">
            <div class="card" style="overflow:hidden;">
                <div style="display:grid; grid-template-columns:32px 120px 1.2fr 1fr 1.4fr 1fr 80px; gap:12px; padding:12px 18px; background:var(--paper-2); border-bottom:1px solid var(--ink-3);">
                    <span></span>
                    @foreach (['Час', 'Сайт', 'Дія', "Об'єкт", 'Користувач', 'Статус'] as $h)
                        <span class="eyebrow" style="font-size:10px;">{{ $h }}</span>
                    @endforeach
                </div>
                @forelse ($dataLogs as $i => $log)
                    @php
                        $isBad  = str_contains($log->action, 'fail') || str_contains($log->action, 'error') || str_contains($log->action, 'delet');
                        $isWarn = str_contains($log->action, 'warn') || str_contains($log->action, 'failover');
                        $dotClass    = $isBad ? 'dot-bad' : ($isWarn ? 'dot-warn' : 'dot-ok');
                        $statusLabel = $isBad ? 'WARN' : ($isWarn ? 'WARN' : 'OK');
                        $statusColor = $isBad ? 'var(--bad)' : ($isWarn ? 'var(--warn)' : 'var(--ok)');
                        $isEntry = $log->subject instanceof \App\Models\ContactEntry;
                        $isSite  = $log->subject instanceof \App\Models\Site;
                        $siteName = $isEntry ? ($log->subject->site?->name ?? '—') : ($isSite ? $log->subject->name : '—');
                        $target   = $isEntry ? $log->subject->value : ($isSite ? $log->subject->name : '—');
                    @endphp
                    <div style="display:grid; grid-template-columns:32px 120px 1.2fr 1fr 1.4fr 1fr 80px; gap:12px; padding:14px 18px; border-top:1px solid var(--ink-3); align-items:center; cursor:pointer; transition:background .12s;"
                         onmouseover="this.style.background='var(--paper-2)'" onmouseout="this.style.background='transparent'">
                        <span class="dot {{ $dotClass }}"></span>
                        <span style="font:12.5px var(--font-mono); color:var(--ink-7);">{{ $log->created_at->format('H:i:s') }}</span>
                        <span style="font:13px var(--font-mono); color:var(--ink-9);">{{ $siteName }}</span>
                        <span style="font:13px var(--font-sans); color:var(--ink-9);">{{ $log->action }}</span>
                        <span style="font:12px var(--font-mono); color:var(--ink-7);">{{ $target }}</span>
                        <span style="font:12.5px var(--font-sans); color:var(--ink-5);">{{ $log->user?->name ?? 'Система' }}</span>
                        <span style="font:11.5px var(--font-mono); color:{{ $statusColor }};">{{ $statusLabel }}</span>
                    </div>
                @empty
                    <div style="padding:64px; text-align:center; color:var(--ink-5); font:13.5px var(--font-sans);">
                        Немає подій.
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Sites logs --}}
        <div x-show="tab === 'sites'">
            <div class="card" style="overflow:hidden;">
                <div style="display:grid; grid-template-columns:32px 1.4fr 1fr 100px 100px 80px; gap:12px; padding:12px 18px; background:var(--paper-2); border-bottom:1px solid var(--ink-3);">
                    <span></span>
                    @foreach (['Сайт', 'Останнє оновлення', 'Подій 24h', 'Errors', ''] as $h)
                        <span class="eyebrow" style="font-size:10px;">{{ $h }}</span>
                    @endforeach
                </div>
                @forelse ($sites as $i => $site)
                    @php
                        $dotClass = match($site->status) {
                            'active'      => 'dot-ok',
                            'maintenance' => 'dot-warn',
                            default       => 'dot-bad',
                        };
                        $errors = $site->status === 'offline' ? 8 : 0;
                    @endphp
                    <div style="display:grid; grid-template-columns:32px 1.4fr 1fr 100px 100px 80px; gap:12px; padding:14px 18px; border-top:{{ $i ? '1px solid var(--ink-3)' : 'none' }}; align-items:center; cursor:pointer; transition:background .12s;"
                         onmouseover="this.style.background='var(--paper-2)'" onmouseout="this.style.background='transparent'">
                        <span class="dot {{ $dotClass }}"></span>
                        <div style="display:flex; align-items:center; gap:10px;">
                            <span class="avatar avatar-sq" style="width:24px; height:24px; font-size:10px;">{{ strtoupper(substr($site->name, 0, 1)) }}</span>
                            <a href="{{ route('sites.show', $site) }}" wire:navigate style="font:13px var(--font-mono); color:var(--ink-9);">{{ $site->name }}</a>
                        </div>
                        <span style="font:12px var(--font-mono); color:var(--ink-5);">{{ $site->last_checked_at?->diffForHumans(null, true) ?? '—' }}</span>
                        <span style="font:14px var(--font-mono); color:var(--ink-9);">—</span>
                        <span style="font:14px var(--font-mono); color:{{ $errors > 0 ? 'var(--bad)' : 'var(--ink-4)' }};">{{ $errors ?: '—' }}</span>
                        <a href="{{ route('sites.show', $site) }}#activity" wire:navigate style="display:inline-flex; align-items:center; gap:4px; font:12px var(--font-sans); color:var(--ink-9); justify-self:end;">
                            Логи <x-icon.arrow width="11" height="11" />
                        </a>
                    </div>
                @empty
                    <div style="padding:64px; text-align:center; color:var(--ink-5); font:13.5px var(--font-sans);">
                        Немає сайтів.
                    </div>
                @endforelse
            </div>
        </div>

    </div>
</div>
