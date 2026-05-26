<div style="flex:1; display:flex; flex-direction:column; overflow-y:auto;">
    <x-ui.topbar :crumbs="['Активність']">
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
        <div style="border-bottom:1px solid var(--ink-3); display:flex; gap:0; margin-bottom:28px;">
            @foreach([
                ['key'=>'system','label'=>'Системні','count'=>0],
                ['key'=>'data','label'=>'По даних','count'=>$dataLogs->count()],
                ['key'=>'sites','label'=>'По сайтах','count'=>$sites->count()],
            ] as $t)
                <button @click="tab='{{ $t['key'] }}'"
                    style="display:inline-flex; align-items:center; gap:6px; padding:12px 20px; margin-bottom:-1px;
                        border-bottom:2px solid transparent; font:13.5px var(--font-sans); cursor:pointer; background:transparent;"
                    :style="tab==='{{ $t['key'] }}' ? 'border-bottom-color:var(--ink-9);color:var(--ink-9);font-weight:500;' : 'color:var(--ink-5);'">
                    {{ $t['label'] }}
                    <span style="display:inline-flex;align-items:center;justify-content:center;min-width:20px;height:20px;padding:0 5px;border-radius:999px;font:500 11px var(--font-mono);"
                        :style="tab==='{{ $t['key'] }}' ? 'background:var(--ink-9);color:var(--paper);' : 'background:var(--ink-2);color:var(--ink-6);'">
                        {{ $t['count'] }}
                    </span>
                </button>
            @endforeach
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
                <div style="display:grid; grid-template-columns:40px 120px 1.4fr 1fr 1fr 80px; gap:12px; padding:10px 18px; background:var(--paper-2); border-bottom:1px solid var(--ink-3);">
                    <span></span>
                    @foreach (['Час', 'Обʼєкт', 'Дія', 'Користувач', 'Статус'] as $h)
                        <span class="eyebrow" style="font-size:9.5px;">{{ $h }}</span>
                    @endforeach
                </div>
                @forelse ($dataLogs as $i => $log)
                    @php
                        $isBad = str_contains($log->action, 'fail') || str_contains($log->action, 'error') || str_contains($log->action, 'offline');
                        $isCreate = str_contains($log->action, 'creat') || str_contains($log->action, 'add');
                        $dotBg = $isBad ? 'var(--bad)' : ($isCreate ? 'var(--ok)' : 'var(--info)');
                        $iconText = $isBad ? '&#x26A1;' : ($isCreate ? '+' : '&#x270E;');
                    @endphp
                    <div style="display:grid; grid-template-columns:40px 120px 1.4fr 1fr 1fr 80px; gap:12px; padding:14px 18px; border-top:{{ $i ? '1px solid var(--ink-3)' : 'none' }}; align-items:center; cursor:pointer; transition:background .1s;"
                         onmouseover="this.style.background='var(--paper-2)'" onmouseout="this.style.background='transparent'">
                        <span style="width:28px;height:28px;border-radius:6px;background:{{ $dotBg }}20;display:inline-flex;align-items:center;justify-content:center;font:13px var(--font-sans);color:{{ $dotBg }};">{!! $iconText !!}</span>
                        <span class="mono" style="font:12px var(--font-mono); color:var(--ink-7);">{{ $log->created_at->format('H:i:s') }}</span>
                        <div>
                            <div style="font:13px var(--font-sans);color:var(--ink-9);">{{ $log->subject?->name ?? ($log->subject_type ? class_basename($log->subject_type) : '—') }}</div>
                            @if($log->subject_type)
                                <div style="font:11px var(--font-mono);color:var(--ink-5);margin-top:2px;">{{ class_basename($log->subject_type) }}</div>
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
                <div style="display:grid; grid-template-columns:40px 1.4fr 1fr 100px 80px; gap:12px; padding:10px 18px; background:var(--paper-2); border-bottom:1px solid var(--ink-3);">
                    <span></span>
                    @foreach (['Сайт', 'Остання перевірка', 'Статус', ''] as $h)
                        <span class="eyebrow" style="font-size:9.5px;">{{ $h }}</span>
                    @endforeach
                </div>
                @forelse ($sites as $i => $site)
                    @php
                        $dotBg = match($site->status) {
                            'active' => 'var(--ok)',
                            'maintenance' => 'var(--warn)',
                            default => 'var(--bad)',
                        };
                        $statusLabel = match($site->status) {
                            'active' => 'Активний',
                            'maintenance' => 'Пауза',
                            default => 'Офлайн',
                        };
                    @endphp
                    <div style="display:grid; grid-template-columns:40px 1.4fr 1fr 100px 80px; gap:12px; padding:14px 18px; border-top:{{ $i ? '1px solid var(--ink-3)' : 'none' }}; align-items:center; cursor:pointer; transition:background .12s;"
                         onmouseover="this.style.background='var(--paper-2)'" onmouseout="this.style.background='transparent'">
                        <span style="width:7px;height:7px;border-radius:999px;background:{{ $dotBg }};"></span>
                        <div style="display:flex; align-items:center; gap:10px;">
                            <span class="avatar avatar-sq" style="width:24px; height:24px; font-size:10px;">{{ strtoupper(substr($site->name, 0, 1)) }}</span>
                            <a href="{{ route('sites.show', $site) }}" wire:navigate class="mono" style="font:13px var(--font-mono); color:var(--ink-9);">{{ $site->name }}</a>
                        </div>
                        <span class="mono" style="font:12px var(--font-mono); color:var(--ink-5);">{{ $site->last_checked_at?->diffForHumans(null, true) ?? '—' }}</span>
                        <span style="display:inline-flex;align-items:center;gap:5px;font:12px var(--font-sans);color:{{ $site->status==='offline'?'var(--bad)':'var(--ink-7)' }};">
                            <span style="width:6px;height:6px;border-radius:999px;background:{{ $dotBg }};"></span>
                            {{ $statusLabel }}
                        </span>
                        <a href="{{ route('sites.show', $site) }}" wire:navigate style="display:inline-flex; align-items:center; gap:4px; font:12px var(--font-sans); color:var(--ink-5); justify-self:end;">
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
