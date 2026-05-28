{{-- ─── Tab: Огляд ─────────────────────────────────────── --}}
<div x-show="tab==='overview'" class="tab-pane">

    {{-- ── Що бачать відвідувачі ── --}}
    <div class="card ov-card">
        <div class="ov-card__head">
            <span class="eyebrow eyebrow-xs">Що бачать відвідувачі</span>
        </div>
        <div class="ov-cols">
            @foreach($overviewByGeo as $geoKey => $col)
                <div class="ov-col {{ $geoKey === 'all' ? 'ov-col--all' : '' }}">
                    <div class="ov-col__label">{{ $col['label'] }}</div>

                    {{-- Телефон --}}
                    <div class="ov-col__sect">
                        <div class="eyebrow eyebrow-xxs">Телефон</div>
                        @if($col['primaryPhone'])
                            <div class="ov-val mono">{{ $col['primaryPhone']->value }}</div>
                            @if($col['primaryPhone']->label)
                                <div class="ov-sub">{{ $col['primaryPhone']->label }}</div>
                            @endif
                            @if($col['backupCount'] > 0)
                                <div class="ov-sub ov-sub--dim">Резерв: {{ $col['backupCount'] }}</div>
                            @endif
                        @else
                            <div class="ov-empty">—</div>
                        @endif
                    </div>

                    {{-- Месенджер --}}
                    <div class="ov-col__sect">
                        <div class="eyebrow eyebrow-xxs">Месенджер</div>
                        @if($col['primaryMsg'])
                            @php $mk = \App\Models\ContactEntry::MSG_KINDS[$col['primaryMsg']->kind] ?? ['short' => '?', 'color' => '#888']; @endphp
                            <div class="ov-val">{{ $col['primaryMsg']->value }}</div>
                            <div class="ov-sub">
                                <span class="ov-msg-tag" style="color:{{ $mk['color'] }};">{{ $mk['short'] }}</span>
                            </div>
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
    <div class="card ov-rules-card">
        <div class="ov-card__head">
            <span class="eyebrow eyebrow-xs">Правила ізоляції</span>
        </div>

        {{-- Existing rules --}}
        @if(!empty($geoRules))
            <div class="ov-rules-list">
                @foreach($geoRules as $rule)
                    <div class="ov-rule">
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
            <div x-show="open" x-cloak class="ov-rule-form">
                <select wire:model="newRuleA" class="input ov-rule-select">
                    <option value="">Країна А</option>
                    @foreach($geoTabs as $gc)<option value="{{ $gc }}">{{ $gc }}</option>@endforeach
                </select>
                <span class="ov-rule__sep">↔</span>
                <select wire:model="newRuleB" class="input ov-rule-select">
                    <option value="">Країна B</option>
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

    {{-- ── Технічна інформація ── --}}
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

</div>
