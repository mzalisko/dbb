{{-- ─── Tab: Огляд ─────────────────────────────────────── --}}
<div x-show="tab==='overview'" style="padding:32px 40px 64px;">
    <div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:14px;">
        @foreach ($geos as $geo)
            @php
                $isoCode = $geo['key'] === 'world' ? 'ZZ' : strtoupper($geo['key']);
            @endphp
            <article x-data="{ openKind: null }" class="card" style="padding:18px;">

                {{-- Header: flag + label + ISO + spacer + status dot --}}
                <header style="display:flex; align-items:center; gap:10px;">
                    <span style="font:20px var(--font-sans);">{{ $geo['flag'] }}</span>
                    <span style="font:13.5px var(--font-sans); color:var(--ink-9);">{{ $geo['label'] }}</span>
                    <span style="font:11px var(--font-mono); color:var(--ink-4); letter-spacing:0.06em;">{{ $isoCode }}</span>
                    <div style="flex:1;"></div>
                    <span class="dot dot-ok"></span>
                </header>

                {{-- Phone section --}}
                <div style="margin-top:18px;">
                    <div class="eyebrow" style="font-size:10px;">Телефон</div>
                    <div style="margin-top:8px; display:flex; align-items:center; gap:8px;">
                        <x-icon.phone width="14" height="14" style="color:var(--ink-5); flex-shrink:0;" />
                        <span style="font:400 17px var(--font-mono); color:var(--ink-9); flex:1;">
                            {{ $geo['primaryPhone']?->value ?? '—' }}
                        </span>
                        @if ($geo['backupPhones']->count() > 0)
                            <button @click="openKind = openKind === 'phone' ? null : 'phone'"
                                style="display:inline-flex; align-items:center; gap:4px; padding:3px 8px; border-radius:999px; font:11px var(--font-mono); cursor:pointer; transition:all .12s;"
                                :style="openKind === 'phone' ? 'background:var(--ink-9);color:var(--paper);' : 'background:var(--paper-2);color:var(--ink-7);box-shadow:inset 0 0 0 1px var(--ink-3);'">
                                +{{ $geo['backupPhones']->count() }}
                                <span :style="openKind === 'phone' ? 'display:inline-flex;transform:rotate(180deg);transition:transform .15s;' : 'display:inline-flex;transition:transform .15s;'">
                                    <x-icon.chev-d width="10" height="10" />
                                </span>
                            </button>
                        @endif
                    </div>
                    @if ($geo['primaryPhone']?->label)
                        <div style="margin-top:4px; font:12px var(--font-sans); color:var(--ink-5); padding-left:22px;">
                            {{ $geo['primaryPhone']->label }}
                        </div>
                    @endif

                    {{-- Backup phones --}}
                    <div x-show="openKind === 'phone'"
                         style="margin-top:10px; margin-left:22px; padding:10px 12px; background:var(--paper-2); border-radius:4px; border:1px solid var(--ink-3);">
                        <div class="eyebrow" style="font-size:10px; margin-bottom:8px;">Резерв · {{ $geo['backupPhones']->count() }}</div>
                        @foreach ($geo['backupPhones'] as $k => $b)
                            <div style="display:flex; align-items:center; gap:8px; padding:5px 0;">
                                <span style="font:10.5px var(--font-mono); color:var(--ink-4); width:16px;">#{{ $k + 1 }}</span>
                                <span style="font:12.5px var(--font-mono); color:var(--ink-7);">{{ $b->value }}</span>
                                <span style="font:11px var(--font-sans); color:var(--ink-5); margin-left:auto;">{{ $b->label }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Messenger section --}}
                @if ($geo['primaryMsg'])
                    @php $mk = \App\Models\ContactEntry::MSG_KINDS[$geo['primaryMsg']->kind] ?? ['label' => $geo['primaryMsg']->kind, 'color' => '#888', 'short' => '??']; @endphp
                    <div style="margin-top:16px; padding-top:14px; border-top:1px solid var(--ink-3);">
                        <div class="eyebrow" style="font-size:10px;">Месенджер</div>
                        <div style="margin-top:8px; display:flex; align-items:center; gap:8px;">
                            <x-icon.chat width="14" height="14" style="color:var(--ink-5); flex-shrink:0;" />
                            <span style="font:400 14px var(--font-mono); color:var(--ink-9);">{{ $geo['primaryMsg']->value }}</span>
                            <span style="font:10.5px var(--font-mono); padding:2px 6px; border-radius:3px; background:var(--ink-2); color:var(--ink-5);">{{ $mk['short'] }}</span>
                            <div style="flex:1;"></div>
                            @if ($geo['backupMsgs']->count() > 0)
                                <button @click="openKind = openKind === 'chat' ? null : 'chat'"
                                    style="display:inline-flex; align-items:center; gap:4px; padding:3px 8px; border-radius:999px; font:11px var(--font-mono); cursor:pointer; transition:all .12s;"
                                    :style="openKind === 'chat' ? 'background:var(--ink-9);color:var(--paper);' : 'background:var(--paper-2);color:var(--ink-7);box-shadow:inset 0 0 0 1px var(--ink-3);'">
                                    +{{ $geo['backupMsgs']->count() }}
                                    <span :style="openKind === 'chat' ? 'display:inline-flex;transform:rotate(180deg);transition:transform .15s;' : 'display:inline-flex;transition:transform .15s;'">
                                        <x-icon.chev-d width="10" height="10" />
                                    </span>
                                </button>
                            @endif
                        </div>

                        {{-- Backup messengers --}}
                        <div x-show="openKind === 'chat'"
                             style="margin-top:10px; margin-left:22px; padding:10px 12px; background:var(--paper-2); border-radius:4px; border:1px solid var(--ink-3);">
                            <div class="eyebrow" style="font-size:10px; margin-bottom:8px;">Резерв · {{ $geo['backupMsgs']->count() }}</div>
                            @foreach ($geo['backupMsgs'] as $k => $b)
                                @php $bk = \App\Models\ContactEntry::MSG_KINDS[$b->kind] ?? ['short' => '??']; @endphp
                                <div style="display:flex; align-items:center; gap:8px; padding:5px 0;">
                                    <span style="font:10.5px var(--font-mono); color:var(--ink-4); width:16px;">#{{ $k + 1 }}</span>
                                    <span style="font:12.5px var(--font-mono); color:var(--ink-7);">{{ $b->value }}</span>
                                    <span style="font:10px var(--font-mono); padding:1px 5px; border-radius:3px; background:var(--ink-2); color:var(--ink-5); margin-left:auto;">{{ $bk['short'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

            </article>
        @endforeach
    </div>

    {{-- Meta stats bar --}}
    <div class="card" style="margin-top:24px; display:grid; grid-template-columns:repeat(4,1fr);">
        @foreach([
            ['l' => 'WordPress',       'v' => $site->wp_version ?? 'Unknown'],
            ['l' => 'PHP',             'v' => $site->php_version ?? 'Unknown'],
            ['l' => 'Статус',          'v' => ucfirst($site->status)],
            ['l' => 'Остання перевірка','v' => $site->last_checked_at?->format('d M H:i') ?? 'Ніколи'],
        ] as $i => $info)
            <div style="padding:16px 20px;{{ $i ? 'border-left:1px solid var(--ink-3);' : '' }}">
                <div class="eyebrow" style="font-size:10px; margin-bottom:8px;">{{ $info['l'] }}</div>
                <div style="font:14px var(--font-mono); color:var(--ink-9);">{{ $info['v'] }}</div>
            </div>
        @endforeach
    </div>
</div>
