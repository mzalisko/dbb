{{-- ─── Tab: Огляд ─────────────────────────────────────── --}}
<div x-show="tab==='overview'" class="tab-pane">

    {{-- ── Що бачать відвідувачі ── --}}
    <div class="card ov-card">
        <div class="ov-card__head">
            <span class="eyebrow eyebrow-xs">Що бачать відвідувачі</span>
        </div>
        <div class="ov-visitor-scroll ov-visitor-scroll-clean">
            <div class="ov-visitor-table">
                <div class="ov-visitor-title">&#1053;&#1086;&#1084;&#1077;&#1088;&#1080;</div>
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
                                            <div class="ov-reserve-label">&#1056;&#1077;&#1079;&#1077;&#1088;&#1074;&#1080; &middot; {{ $phone->backups->count() }}</div>
                                        @endif
                                    </div>
                                @endforeach
                            @else
                                <div class="ov-empty">&mdash;</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="ov-visitor-table">
                <div class="ov-visitor-title">&#1052;&#1077;&#1089;&#1077;&#1085;&#1076;&#1078;&#1077;&#1088;&#1080;</div>
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
                                            <div class="ov-reserve-label">&#1056;&#1077;&#1079;&#1077;&#1088;&#1074;&#1080; &middot; {{ $msg->backups->count() }}</div>
                                        @endif
                                    </div>
                                @endforeach
                            @else
                                <div class="ov-empty">&mdash;</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="ov-visitor-scroll">
            <div class="ov-visitor-table">
                <div class="ov-visitor-title">РќРѕРјРµСЂРё</div>
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
                                            <div class="ov-reserve-label">Р РµР·РµСЂРІРё В· {{ $phone->backups->count() }}</div>
                                        @endif
                                    </div>
                                @endforeach
                            @else
                                <div class="ov-empty">вЂ”</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="ov-visitor-table">
                <div class="ov-visitor-title">РњРµСЃРµРЅРґР¶РµСЂРё</div>
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
                                            <div class="ov-reserve-label">Р РµР·РµСЂРІРё В· {{ $msg->backups->count() }}</div>
                                        @endif
                                    </div>
                                @endforeach
                            @else
                                <div class="ov-empty">вЂ”</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="ov-cols ov-cols--legacy">
            @foreach($overviewByGeo as $geoKey => $col)
                <div class="ov-col {{ $geoKey === 'all' ? 'ov-col--all' : '' }}">
                    <div class="ov-col__label">{{ $col['label'] }}</div>

                    {{-- Телефон --}}
                    <div class="ov-col__sect">
                        <div class="eyebrow eyebrow-xxs">Телефон</div>
                        @php
                            $phones = $col['phones'] ?? collect(($col['primaryPhone'] ?? null) ? [$col['primaryPhone']] : []);
                        @endphp
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

                    {{-- Месенджер --}}
                    <div class="ov-col__sect">
                        <div class="eyebrow eyebrow-xxs">Месенджер</div>
                        @php
                            $messengers = $col['messengers'] ?? collect(($col['primaryMsg'] ?? null) ? [$col['primaryMsg']] : []);
                        @endphp
                        @if($messengers->count())
                            @foreach($messengers as $msg)
                                @php $mk = \App\Models\ContactEntry::MSG_KINDS[$msg->kind] ?? ['short' => '?', 'color' => '#888']; @endphp
                                <div class="ov-entry">
                                    <div class="ov-val">{{ $msg->value }}</div>
                                    <div class="ov-sub">
                                        <span class="ov-msg-tag" style="color:{{ $mk['color'] }};">{{ $mk['short'] }}</span>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="ov-empty">—</div>
                        @endif
                    </div>
                </div>
            @endforeach
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

    {{-- ── Правила ізоляції ── --}}
    @if(false)
    <div class="card ov-rules-card">
        <div class="ov-card__head">
            <span class="eyebrow eyebrow-xs">Правила ізоляції</span>
        </div>

        <div class="ov-rule-help">
            <b>&#1051;&#1086;&#1075;&#1110;&#1082;&#1072;:</b>
            &#1074;&#1082;&#1072;&#1078;&#1110;&#1090;&#1100; &#1076;&#1074;&#1110; &#1075;&#1088;&#1091;&#1087;&#1080; &#1075;&#1077;&#1086;, &#1103;&#1082;&#1110; &#1085;&#1077; &#1084;&#1072;&#1102;&#1090;&#1100; &#1073;&#1072;&#1095;&#1080;&#1090;&#1080; &#1086;&#1076;&#1085;&#1072; &#1086;&#1076;&#1085;&#1091;.
            &#1053;&#1072;&#1087;&#1088;., <span class="ov-rule-help__tag">UA</span> &#8596;
            <span class="ov-rule-help__tag">RU &middot; BY &middot; PL</span>
            &#1086;&#1079;&#1085;&#1072;&#1095;&#1072;&#1108;: &#1074;&#1110;&#1076;&#1074;&#1110;&#1076;&#1091;&#1074;&#1072;&#1095; &#1079; UA &#1085;&#1077; &#1084;&#1072;&#1108; &#1073;&#1072;&#1095;&#1080;&#1090;&#1080; &#1082;&#1086;&#1085;&#1090;&#1072;&#1082;&#1090;&#1080; RU, BY, PL &#1110; &#1085;&#1072;&#1074;&#1087;&#1072;&#1082;&#1080;. &#1071;&#1082;&#1097;&#1086; &#1072;&#1082;&#1090;&#1080;&#1074;&#1085;&#1080;&#1081; &#1082;&#1086;&#1085;&#1090;&#1072;&#1082;&#1090; &#1087;&#1086;&#1090;&#1088;&#1072;&#1087;&#1083;&#1103;&#1108; &#1074; &#1086;&#1073;&#1080;&#1076;&#1074;&#1110; &#1075;&#1088;&#1091;&#1087;&#1080;, &#1085;&#1080;&#1078;&#1095;&#1077; &#1079;'&#1103;&#1074;&#1080;&#1090;&#1100;&#1089;&#1103; &#1082;&#1086;&#1085;&#1092;&#1083;&#1110;&#1082;&#1090;.
        </div>

        {{-- Existing rules --}}
        @if(!empty($geoRules))
            <div class="ov-rules-list">
                @foreach($geoRules as $rule)
                    <div class="ov-rule">
                        <button wire:click="requestRemoveGeoRule({{ $rule['id'] }})" class="ov-rule__del ov-rule__del--confirm" title="Видалити правило">
                            <x-icon.trash width="13" height="13" />
                        </button>
                        <span class="ov-rule__tag">{{ implode(' · ', $rule['groups'][0]) }}</span>
                        <span class="ov-rule__sep">↔</span>
                        <span class="ov-rule__tag">{{ implode(' · ', $rule['groups'][1]) }}</span>
                        <span class="ov-rule__desc">ізольовано</span>
                        <button wire:click="removeGeoRule({{ $rule['id'] }})" class="ov-rule__del" title="Видалити правило">×</button>
                    </div>
                @endforeach
            </div>
        @else
            <div class="ov-rules-empty">Правила не задано — всі номери видно всім.</div>
        @endif

        {{-- Add rule --}}
        <div class="ov-rule-add" x-data="{open:false}">
            <button @click="open=!open" class="btn btn-ghost btn-sm" style="margin-top:10px;">
                + Додати правило
            </button>
            <div x-show="open" x-cloak class="ov-rule-form ov-rule-form--pills">
                <div class="ov-rule-picker">
                    <div class="ov-rule-picker__label">&#1043;&#1088;&#1091;&#1087;&#1072; A</div>
                    <div class="ov-rule-picker__pills">
                        @foreach($geoTabs as $gc)
                            @php $disabled = in_array($gc, $newRuleB ?? [], true); @endphp
                            <label class="ov-rule-pill {{ in_array($gc, $newRuleA ?? [], true) ? 'is-active' : '' }} {{ $disabled ? 'is-disabled' : '' }}">
                                <input type="checkbox" wire:model.live="newRuleA" value="{{ $gc }}" @disabled($disabled)>
                                <span>{{ $gc }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
                <span class="ov-rule__sep">в†”</span>
                <div class="ov-rule-picker">
                    <div class="ov-rule-picker__label">&#1043;&#1088;&#1091;&#1087;&#1072; B</div>
                    <div class="ov-rule-picker__pills">
                        @foreach($geoTabs as $gc)
                            @php $disabled = in_array($gc, $newRuleA ?? [], true); @endphp
                            <label class="ov-rule-pill {{ in_array($gc, $newRuleB ?? [], true) ? 'is-active' : '' }} {{ $disabled ? 'is-disabled' : '' }}">
                                <input type="checkbox" wire:model.live="newRuleB" value="{{ $gc }}" @disabled($disabled)>
                                <span>{{ $gc }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
                <button wire:click="addGeoRule()" @click="open=false" class="btn btn-primary btn-sm">&#1044;&#1086;&#1076;&#1072;&#1090;&#1080;</button>
            </div>
            <div x-show="open" x-cloak class="ov-rule-form ov-rule-form--selects">
                <select wire:model="newRuleA" multiple class="input ov-rule-select ov-rule-select--multi">
                    <option value="" disabled>Країна А</option>
                    @foreach($geoTabs as $gc)<option value="{{ $gc }}">{{ $gc }}</option>@endforeach
                </select>
                <span class="ov-rule__sep">↔</span>
                <select wire:model="newRuleB" multiple class="input ov-rule-select ov-rule-select--multi">
                    <option value="" disabled>Країна B</option>
                    @foreach($geoTabs as $gc)<option value="{{ $gc }}">{{ $gc }}</option>@endforeach
                </select>
                <button wire:click="addGeoRule()" @click="open=false" class="btn btn-primary btn-sm">Додати</button>
            </div>
        </div>

        {{-- Conflicts --}}
        @if(!empty($conflicts))
            <div class="ov-conflicts">
                <div class="ov-conflicts__head">
                    <span class="eyebrow eyebrow-xs" style="color:var(--bad);">Конфлікти · {{ count($conflicts) }}</span>
                </div>
                @foreach($conflicts as $c)
                    <div class="ov-conflict">
                        <span class="ov-conflict__icon">⚠</span>
                        <span class="mono ov-conflict__val">{{ $c['phone']->value }}</span>
                        <span class="ov-conflict__desc">
                            видно обом групам:
                            <b>{{ implode(' · ', $c['rule']['groups'][0]) }}</b>
                            і
                            <b>{{ implode(' · ', $c['rule']['groups'][1]) }}</b>
                        </span>
                        <button wire:click="editEntry({{ $c['phone']->id }})" class="btn btn-ghost btn-xs">Виправити</button>
                    </div>
                @endforeach
            </div>
        @else
            @if(!empty($geoRules))
                <div class="ov-no-conflicts">Конфліктів не знайдено</div>
            @endif
        @endif
    </div>

    @endif

    {{-- ── Технічна інформація ── --}}
    @if(false)
    <div class="card geo-stats">
        @foreach([
            ['l' => 'WordPress',        'v' => $site->wp_version ?? 'Unknown'],
            ['l' => 'PHP',              'v' => $site->php_version ?? 'Unknown'],
            ['l' => 'Статус',           'v' => ucfirst($site->status)],
            ['l' => 'Остання перевірка', 'v' => $site->last_checked_at?->format('d M H:i') ?? 'Ніколи'],
        ] as $info)
            <div class="geo-stat">
                <div class="eyebrow eyebrow-xs" style="margin-bottom:8px;">{{ $info['l'] }}</div>
                <div class="geo-stat__val">{{ $info['v'] }}</div>
            </div>
        @endforeach
    </div>
    @endif

</div>
