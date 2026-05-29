{{-- ─── Tab: Огляд ─────────────────────────────────────── --}}
<div x-show="tab==='overview'" x-cloak class="tab-pane">

    {{-- ── Що бачать відвідувачі ── --}}
    <div class="card ov-card">
        <div class="ov-card__head">
            <span class="eyebrow eyebrow-xs">Що бачать відвідувачі</span>
        </div>
        <div class="ov-visitor-scroll ov-visitor-scroll-clean">
            <div class="ov-visitor-table">
                <div class="ov-visitor-title">Номери</div>
                <div class="ov-cols ov-cols--compact">
                    @foreach($overviewByGeo as $geoKey => $col)
                        <div class="ov-col {{ $geoKey === 'all' ? 'ov-col--all' : '' }}">
                            <div class="ov-col__label">{{ $col['label'] }}</div>
                            @php $phones = $col['phones'] ?? collect(); @endphp
                            @if($phones->count())
                                @foreach($phones as $phone)
                                    <div class="ov-entry">
                                        <div class="ov-val mono">{{ $phone->value }}</div>
                                        @if($phone->label)
                                            <div class="ov-sub">{{ $phone->label }}</div>
                                        @endif
                                        @if($phone->backups->count() > 0)
                                            <div class="ov-reserve-label">Резерви · {{ $phone->backups->count() }}</div>
                                        @endif
                                    </div>
                                @endforeach
                            @else
                                <div class="ov-empty">—</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="ov-visitor-table">
                <div class="ov-visitor-title">Месенджери</div>
                <div class="ov-cols ov-cols--compact">
                    @foreach($overviewByGeo as $geoKey => $col)
                        <div class="ov-col {{ $geoKey === 'all' ? 'ov-col--all' : '' }}">
                            <div class="ov-col__label">{{ $col['label'] }}</div>
                            @php $messengers = $col['messengers'] ?? collect(); @endphp
                            @if($messengers->count())
                                @foreach($messengers as $msg)
                                    @php $mk = \App\Models\ContactEntry::MSG_KINDS[$msg->kind] ?? ['short' => '?', 'color' => '#888']; @endphp
                                    <div class="ov-entry">
                                        <div class="ov-val">{{ $msg->value }}</div>
                                        <div class="ov-sub">
                                            <span class="ov-msg-tag" style="color:{{ $mk['color'] }};">{{ $mk['short'] }}</span>
                                            @if($msg->label)
                                                <span>{{ $msg->label }}</span>
                                            @endif
                                        </div>
                                        @if($msg->backups->count() > 0)
                                            <div class="ov-reserve-label">Резерви · {{ $msg->backups->count() }}</div>
                                        @endif
                                    </div>
                                @endforeach
                            @else
                                <div class="ov-empty">—</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- ── Матриця видимості ── --}}
    <div class="card ov-matrix-card">
        <div class="ov-card__head">
            <span class="eyebrow eyebrow-xs">Матриця видимості номерів</span>
            @if(count($conflictPhoneIds) > 0)
                <span class="ov-conflict-badge">{{ count($conflictPhoneIds) }} конфл.</span>
            @endif
        </div>
        <div style="overflow-x:auto;">
            <table class="geo-matrix">
                <thead>
                    <tr>
                        <th class="geo-matrix__n">#</th>
                        <th class="geo-matrix__val">Номер</th>
                        <th class="geo-matrix__geo">Гео-правило</th>
                        @foreach($geoTabs as $gc)
                            <th class="geo-matrix__th">{{ $gc }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse($geoMatrix as $i => $row)
                        @php $isConflict = in_array($row['phone']->id, $conflictPhoneIds); @endphp
                        <tr class="{{ $isConflict ? 'geo-matrix__row--conflict' : '' }}"
                            wire:click="editEntry({{ $row['phone']->id }})" style="cursor:pointer;">
                            <td class="geo-matrix__n">{{ $i + 1 }}</td>
                            <td class="mono geo-matrix__val">
                                {{ $row['phone']->value }}
                                @if($row['phone']->label)
                                    <span class="geo-matrix__lbl">{{ $row['phone']->label }}</span>
                                @endif
                                @if($isConflict)
                                    <span class="geo-conflict-dot" title="Конфлікт ізоляції">⚠</span>
                                @endif
                            </td>
                            <td class="geo-matrix__geo">{{ $row['phone']->geo_label }}</td>
                            @foreach($geoTabs as $gc)
                                <td class="geo-matrix__cell">
                                    @if($row['vis'][$gc])
                                        <span class="geo-check">✓</span>
                                    @else
                                        <span class="geo-dash">—</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr><td colspan="{{ 3 + count($geoTabs) }}" class="geo-matrix__empty">Немає телефонів</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
