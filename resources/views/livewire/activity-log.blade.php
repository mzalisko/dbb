<div style="flex:1; display:flex; flex-direction:column; overflow-y:auto;">
    <x-ui.topbar :crumbs="['Логи']">
        <x-ui.button variant="secondary" size="sm">
            <x-icon.export width="13" height="13" /> Експорт
        </x-ui.button>
    </x-ui.topbar>

    <x-ui.page-head
        title="Логи"
        sub="Системні події, зміни даних, активність по сайтах." />

    <div style="padding:0 40px 64px;" x-data="{ tab: '{{ $tab }}' }">
        {{-- Sub-tabs --}}
        <div class="tabs" style="margin-bottom:24px;">
            <button @click="tab='system'" :class="{ 'active': tab === 'system' }" class="tab">
                Системні <span class="tab-n">0</span>
            </button>
            <button @click="tab='data'" :class="{ 'active': tab === 'data' }" class="tab">
                По даних <span class="tab-n">{{ $dataLogs->count() }}</span>
            </button>
            <button @click="tab='sites'" :class="{ 'active': tab === 'sites' }" class="tab">
                По сайтах <span class="tab-n">{{ $sites->count() }}</span>
            </button>
        </div>

        {{-- System logs --}}
        <div x-show="tab === 'system'">
            <div class="card" style="padding:80px; text-align:center; color:var(--ink-5); font:13.5px var(--font-sans);">
                Системні логи з'являться тут як тільки почнуть збиратись.
            </div>
        </div>

        {{-- Data logs --}}
        <div x-show="tab === 'data'">
            <div class="card" style="overflow:hidden;">
                <div style="display:grid; grid-template-columns:32px 120px 1.2fr 1fr 1fr 80px; gap:12px; padding:12px 18px; background:var(--paper-2); border-bottom:1px solid var(--ink-3);">
                    <span></span>
                    @foreach (['Час', 'Обʼєкт', 'Дія', 'Користувач', 'Статус'] as $h)
                        <span class="eyebrow" style="font-size:10px;">{{ $h }}</span>
                    @endforeach
                </div>
                @forelse ($dataLogs as $i => $log)
                    @php
                        $isBad = str_contains($log->action, 'fail') || str_contains($log->action, 'error');
                        $dotClass = $isBad ? 'dot-bad' : 'dot-ok';
                    @endphp
                    <div style="display:grid; grid-template-columns:32px 120px 1.2fr 1fr 1fr 80px; gap:12px; padding:14px 18px; border-top:{{ $i ? '1px solid var(--ink-3)' : 'none' }}; align-items:center;">
                        <span class="dot {{ $dotClass }}"></span>
                        <span class="mono" style="font:12.5px var(--font-mono); color:var(--ink-7);">{{ $log->created_at->format('H:i:s') }}</span>
                        <div>
                            @if ($log->subject_type)
                                <span style="font:11.5px var(--font-mono); color:var(--ink-5); text-transform:uppercase; letter-spacing:.06em;">{{ class_basename($log->subject_type) }}</span>
                            @endif
                        </div>
                        <span style="font:13px var(--font-sans); color:var(--ink-9);">{{ $log->action }}</span>
                        <span style="font:12.5px var(--font-sans); color:var(--ink-5);">{{ $log->user?->name ?? 'System' }}</span>
                        <span style="font:11.5px var(--font-mono); color:{{ $isBad ? 'var(--bad)' : 'var(--ok)' }};">{{ $isBad ? 'WARN' : 'OK' }}</span>
                    </div>
                @empty
                    <div style="padding:80px; text-align:center; color:var(--ink-5); font:13.5px var(--font-sans);">
                        Немає подій.
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Sites logs --}}
        <div x-show="tab === 'sites'">
            <div class="card" style="overflow:hidden;">
                <div style="display:grid; grid-template-columns:32px 1.4fr 1fr 100px 80px; gap:12px; padding:12px 18px; background:var(--paper-2); border-bottom:1px solid var(--ink-3);">
                    <span></span>
                    @foreach (['Сайт', 'Остання перевірка', 'Статус', ''] as $h)
                        <span class="eyebrow" style="font-size:10px;">{{ $h }}</span>
                    @endforeach
                </div>
                @forelse ($sites as $i => $site)
                    @php
                        $dotClass = match($site->status) {
                            'active' => 'dot-ok',
                            'maintenance' => 'dot-warn',
                            default => 'dot-bad',
                        };
                    @endphp
                    <div style="display:grid; grid-template-columns:32px 1.4fr 1fr 100px 80px; gap:12px; padding:14px 18px; border-top:{{ $i ? '1px solid var(--ink-3)' : 'none' }}; align-items:center; cursor:pointer; transition:background .12s;"
                         onmouseover="this.style.background='var(--paper-2)'" onmouseout="this.style.background='transparent'">
                        <span class="dot {{ $dotClass }}"></span>
                        <div style="display:flex; align-items:center; gap:10px;">
                            <span class="avatar avatar-sq" style="width:24px; height:24px; font-size:10px;">{{ strtoupper(substr($site->name, 0, 1)) }}</span>
                            <a href="{{ route('sites.show', $site) }}" wire:navigate class="mono" style="font:13px var(--font-mono); color:var(--ink-9);">{{ $site->name }}</a>
                        </div>
                        <span class="mono" style="font:12px var(--font-mono); color:var(--ink-5);">{{ $site->last_checked_at?->diffForHumans(null, true) ?? '—' }}</span>
                        <span style="font:12px var(--font-sans); color:{{ $site->status === 'offline' ? 'var(--bad)' : 'var(--ok)' }};">{{ ucfirst($site->status) }}</span>
                        <a href="{{ route('sites.show', $site) }}" wire:navigate style="display:inline-flex; align-items:center; gap:4px; font:12px var(--font-sans); color:var(--ink-9); justify-self:end;">
                            Логи <x-icon.arrow width="11" height="11" />
                        </a>
                    </div>
                @empty
                    <div style="padding:80px; text-align:center; color:var(--ink-5); font:13.5px var(--font-sans);">
                        Немає сайтів.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
