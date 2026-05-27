{{-- ─── Tab: Огляд ─────────────────────────────────────── --}}
<div x-show="tab==='overview'" class="tab-pane">
    <div class="geo-grid">
        @foreach ($geos as $geo)
            @php
                $isoCode = $geo['key'] === 'world' ? 'ZZ' : strtoupper($geo['key']);
            @endphp
            <article x-data="{ openKind: null }" class="card geo-card">

                {{-- Header: flag + label + ISO + spacer + status dot --}}
                <header class="geo-card__head">
                    <span class="geo-flag">{{ $geo['flag'] }}</span>
                    <span class="geo-name">{{ $geo['label'] }}</span>
                    <span class="geo-iso">{{ $isoCode }}</span>
                    <div style="flex:1;"></div>
                    <span class="dot dot-ok"></span>
                </header>

                {{-- Phone section --}}
                <div class="geo-sect">
                    <div class="eyebrow eyebrow-xs">Телефон</div>
                    <div class="geo-row">
                        <x-icon.phone width="14" height="14" style="color:var(--ink-5); flex-shrink:0;" />
                        <span class="geo-phone">{{ $geo['primaryPhone']?->value ?? '—' }}</span>
                        @if ($geo['backupPhones']->count() > 0)
                            <button class="geo-more" :class="{ 'is-open': openKind === 'phone' }"
                                    @click="openKind = openKind === 'phone' ? null : 'phone'">
                                +{{ $geo['backupPhones']->count() }}
                                <span class="geo-more__chev"><x-icon.chev-d width="10" height="10" /></span>
                            </button>
                        @endif
                    </div>
                    @if ($geo['primaryPhone']?->label)
                        <div class="geo-label">{{ $geo['primaryPhone']->label }}</div>
                    @endif

                    {{-- Backup phones --}}
                    <div x-show="openKind === 'phone'" class="geo-backup">
                        <div class="eyebrow eyebrow-xs" style="margin-bottom:8px;">Резерв · {{ $geo['backupPhones']->count() }}</div>
                        @foreach ($geo['backupPhones'] as $k => $b)
                            <div class="geo-backup__row">
                                <span class="geo-backup__num">#{{ $k + 1 }}</span>
                                <span class="geo-backup__val">{{ $b->value }}</span>
                                <span class="geo-backup__label">{{ $b->label }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Messenger section --}}
                @if ($geo['primaryMsg'])
                    @php $mk = \App\Models\ContactEntry::MSG_KINDS[$geo['primaryMsg']->kind] ?? ['label' => $geo['primaryMsg']->kind, 'color' => '#888', 'short' => '??']; @endphp
                    <div class="geo-sect geo-sect--msg">
                        <div class="eyebrow eyebrow-xs">Месенджер</div>
                        <div class="geo-row">
                            <x-icon.chat width="14" height="14" style="color:var(--ink-5); flex-shrink:0;" />
                            <span class="geo-msg">{{ $geo['primaryMsg']->value }}</span>
                            <span class="geo-msg-tag">{{ $mk['short'] }}</span>
                            <div style="flex:1;"></div>
                            @if ($geo['backupMsgs']->count() > 0)
                                <button class="geo-more" :class="{ 'is-open': openKind === 'chat' }"
                                        @click="openKind = openKind === 'chat' ? null : 'chat'">
                                    +{{ $geo['backupMsgs']->count() }}
                                    <span class="geo-more__chev"><x-icon.chev-d width="10" height="10" /></span>
                                </button>
                            @endif
                        </div>

                        {{-- Backup messengers --}}
                        <div x-show="openKind === 'chat'" class="geo-backup">
                            <div class="eyebrow eyebrow-xs" style="margin-bottom:8px;">Резерв · {{ $geo['backupMsgs']->count() }}</div>
                            @foreach ($geo['backupMsgs'] as $k => $b)
                                @php $bk = \App\Models\ContactEntry::MSG_KINDS[$b->kind] ?? ['short' => '??']; @endphp
                                <div class="geo-backup__row">
                                    <span class="geo-backup__num">#{{ $k + 1 }}</span>
                                    <span class="geo-backup__val">{{ $b->value }}</span>
                                    <span class="geo-backup__tag">{{ $bk['short'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

            </article>
        @endforeach
    </div>

    {{-- Meta stats bar --}}
    <div class="card geo-stats">
        @foreach([
            ['l' => 'WordPress',       'v' => $site->wp_version ?? 'Unknown'],
            ['l' => 'PHP',             'v' => $site->php_version ?? 'Unknown'],
            ['l' => 'Статус',          'v' => ucfirst($site->status)],
            ['l' => 'Остання перевірка','v' => $site->last_checked_at?->format('d M H:i') ?? 'Ніколи'],
        ] as $info)
            <div class="geo-stat">
                <div class="eyebrow eyebrow-xs" style="margin-bottom:8px;">{{ $info['l'] }}</div>
                <div class="geo-stat__val">{{ $info['v'] }}</div>
            </div>
        @endforeach
    </div>
</div>
