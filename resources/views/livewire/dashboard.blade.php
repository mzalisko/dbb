<div style="flex:1; display:flex; flex-direction:column; overflow-y:auto;">
    <x-ui.topbar :crumbs="['Дашборд']">
        <a href="{{ route('sites.index') }}" wire:navigate class="btn btn-primary btn-sm">
            <x-icon.plus width="13" height="13" /> Додати сайт
        </a>
    </x-ui.topbar>

    <x-ui.page-head
        eyebrow="Огляд"
        :number="$totalSites"
        label="сайтів"
        sub="Стан робочого простору. Натисніть на сайт щоб відкрити його." />

    <div style="padding:0 40px 64px;">
        <div style="margin-top:8px; display:grid; grid-template-columns:1.5fr 1fr; gap:48px;">

            {{-- Sites list --}}
            <div>
                <header style="display:flex; align-items:baseline; margin-bottom:12px; padding-bottom:12px; border-bottom:1px solid var(--ink-3);">
                    <h3 style="font:400 18px/1 var(--font-sans); color:var(--ink-9); flex:1;">Сайти</h3>
                    <a href="{{ route('sites.index') }}" wire:navigate class="btn btn-ghost btn-sm" style="padding:0;">Усі →</a>
                </header>

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
                @empty
                    <div style="padding:48px 0; text-align:center; color:var(--ink-5); font:13.5px var(--font-sans);">
                        Немає сайтів. Додайте перший.
                    </div>
                @endforelse
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
