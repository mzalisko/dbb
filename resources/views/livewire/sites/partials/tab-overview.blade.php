<div x-show="tab==='overview'" x-cloak class="tab-pane" x-data="{
    validOverviewSubs: ['contacts', 'addresses', 'prices', 'socials', 'custom'],
    defaultOverviewSub: @js(($phoneCount + $msgCount) > 0 ? 'contacts' : ($addressCount > 0 ? 'addresses' : ($priceCount > 0 ? 'prices' : ($socialCount > 0 ? 'socials' : ($customCount > 0 ? 'custom' : ''))))),
    overviewSub: (() => {
        const p = (location.hash.slice(1) || '').split('/');
        return p[0] === 'overview' && ['contacts', 'addresses', 'prices', 'socials', 'custom'].includes(p[1])
            ? p[1]
            : @js(($phoneCount + $msgCount) > 0 ? 'contacts' : ($addressCount > 0 ? 'addresses' : ($priceCount > 0 ? 'prices' : ($socialCount > 0 ? 'socials' : ($customCount > 0 ? 'custom' : '')))));
    })(),
    setOverviewSub(value) {
        this.overviewSub = value;
        history.replaceState(null, '', location.pathname + '#overview/' + value);
    }
}">
    @php
        $overviewTabs = [
            ['key' => 'contacts', 'label' => 'Контакти', 'count' => $phoneCount + $msgCount, 'show' => ($phoneCount + $msgCount) > 0],
            ['key' => 'addresses', 'label' => 'Адреси', 'count' => $addressCount, 'show' => $addressCount > 0],
            ['key' => 'prices', 'label' => 'Ціни', 'count' => $priceCount, 'show' => $priceCount > 0],
            ['key' => 'socials', 'label' => 'Соц. мережі', 'count' => $socialCount, 'show' => $socialCount > 0],
            ['key' => 'custom', 'label' => 'Custom', 'count' => $customCount, 'show' => $customCount > 0],
        ];

        $extraMeta = [
            'addresses' => ['title' => 'Адреси', 'empty' => 'Немає адрес для цього гео'],
            'prices' => ['title' => 'Ціни', 'empty' => 'Немає цін для цього гео'],
            'socials' => ['title' => 'Соц. мережі', 'empty' => 'Немає соц. мереж для цього гео'],
            'custom' => ['title' => 'Custom', 'empty' => 'Немає custom-даних для цього гео'],
        ];
    @endphp

    <div class="overview-subtabs" role="tablist" aria-label="Огляд даних">
        @foreach($overviewTabs as $item)
            @if($item['show'])
                <button type="button"
                        class="overview-subtab"
                        :class="{ 'is-active': overviewSub === '{{ $item['key'] }}' }"
                        @click="setOverviewSub('{{ $item['key'] }}')">
                    <span>{{ $item['label'] }}</span>
                    <span class="pill-count">{{ $item['count'] }}</span>
                </button>
            @endif
        @endforeach
    </div>

    <div class="overview-panel" :class="{ 'is-hidden': overviewSub !== 'contacts' }">
        <div class="card ov-card">
            @php
                $maxPhoneRows = collect($overviewByGeo)->map(fn ($col) => ($col['phones'] ?? collect())->count())->max() ?: 0;
                $maxMessengerRows = collect($overviewByGeo)->map(fn ($col) => ($col['messengers'] ?? collect())->count())->max() ?: 0;
            @endphp
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
                                        @php $serving = $phone->failoverServing(); @endphp
                                        <div class="ov-entry">
                                            @if($phone->failover_down && $serving && $serving->id !== $phone->id)
                                                {{-- Основний у збої → відвідувач бачить резерв --}}
                                                <div class="ov-val mono">{{ $serving->value }}</div>
                                                <div class="ov-sub" style="color:var(--warn);">резерв · основний у збої</div>
                                            @elseif($phone->failover_down)
                                                <div class="ov-val mono" style="text-decoration:line-through; color:var(--ink-5);">{{ $phone->value }}</div>
                                                <div class="ov-sub" style="color:var(--bad);">збій · немає робочого номера</div>
                                            @else
                                                <div class="ov-val mono">{{ $phone->value }}</div>
                                                @if($phone->label)
                                                    <div class="ov-sub">{{ $phone->label }}</div>
                                                @endif
                                            @endif
                                            @if($phone->backups->count() > 0)
                                                <div class="ov-reserve-label">Резерви · {{ $phone->backups->count() }}</div>
                                            @endif
                                        </div>
                                    @endforeach
                                @else
                                    <div class="ov-empty">—</div>
                                @endif
                                @for($i = 0; $i < max(0, $maxPhoneRows - $phones->count()); $i++)
                                    <div class="ov-entry ov-entry--placeholder" aria-hidden="true"></div>
                                @endfor
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
                                        @php
                                            $servingMsg = $msg->failoverServing();
                                            $shown = ($msg->failover_down && $servingMsg && $servingMsg->id !== $msg->id) ? $servingMsg : $msg;
                                            $mk = \App\Models\ContactEntry::MSG_KINDS[$shown->kind] ?? [
                                                'short' => strtoupper(substr(preg_replace('/[^a-z0-9]/i', '', (string) $shown->kind), 0, 2) ?: '?'),
                                                'color' => '#888',
                                            ];
                                        @endphp
                                        <div class="ov-entry">
                                            @if($msg->failover_down && $servingMsg && $servingMsg->id !== $msg->id)
                                                <div class="ov-val">{{ $servingMsg->value }}</div>
                                                <div class="ov-sub">
                                                    <span class="ov-msg-tag" style="color:{{ $mk['color'] }};">{{ $mk['short'] }}</span>
                                                    <span style="color:var(--warn);">резерв · основний у збої</span>
                                                </div>
                                            @elseif($msg->failover_down)
                                                <div class="ov-val" style="text-decoration:line-through; color:var(--ink-5);">{{ $msg->value }}</div>
                                                <div class="ov-sub" style="color:var(--bad);">збій · немає робочого</div>
                                            @else
                                                <div class="ov-val">{{ $msg->value }}</div>
                                                <div class="ov-sub">
                                                    <span class="ov-msg-tag" style="color:{{ $mk['color'] }};">{{ $mk['short'] }}</span>
                                                    @if($msg->label)
                                                        <span>{{ $msg->label }}</span>
                                                    @endif
                                                </div>
                                            @endif
                                            @if($msg->backups->count() > 0)
                                                <div class="ov-reserve-label">Резерви · {{ $msg->backups->count() }}</div>
                                            @endif
                                        </div>
                                    @endforeach
                                @else
                                    <div class="ov-empty">—</div>
                                @endif
                                @for($i = 0; $i < max(0, $maxMessengerRows - $messengers->count()); $i++)
                                    <div class="ov-entry ov-entry--placeholder" aria-hidden="true"></div>
                                @endfor
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="card ov-matrix-card">
            <div class="ov-card__head">
                <span class="eyebrow eyebrow-xs">Матриця видимості номерів</span>
                @if(count($conflictPhoneIds) > 0)
                    <span class="ov-conflict-badge">{{ count($conflictPhoneIds) }} конфл.</span>
                @endif
            </div>
            <div class="thin-scroll" style="overflow-x:auto;">
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
                                        <span class="geo-conflict-dot" title="Конфлікт ізоляції">!</span>
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

    @foreach($extraMeta as $typeKey => $meta)
        <div class="overview-panel" :class="{ 'is-hidden': overviewSub !== '{{ $typeKey }}' }">
            @php
                $maxRows = collect($overviewExtrasByGeo)
                    ->map(fn ($col) => ($col[$typeKey] ?? collect())->count())
                    ->max() ?: 0;
            @endphp
            <div class="card ov-card">
                <div class="ov-card__head">
                    <span class="eyebrow eyebrow-xs">{{ $meta['title'] }} · що бачать відвідувачі</span>
                </div>
                <div class="ov-visitor-scroll ov-visitor-scroll-clean">
                    <div class="ov-visitor-table">
                        <div class="ov-visitor-title">По країнах</div>
                        <div class="ov-cols ov-cols--compact">
                            @foreach($overviewExtrasByGeo as $geoKey => $col)
                                @php $entries = $col[$typeKey] ?? collect(); @endphp
                                <div class="ov-col {{ $geoKey === 'all' ? 'ov-col--all' : '' }}">
                                    <div class="ov-col__label">{{ $col['label'] }}</div>
                                    @if($entries->count())
                                        @foreach($entries as $entry)
                                            @php
                                                $kindMeta = $typeKey === 'socials'
                                                    ? (\App\Models\ContactEntry::SOCIAL_KINDS[$entry->kind] ?? null)
                                                    : null;
                                                $rawPriceValue = trim((string) $entry->value);
                                                $priceValueUsesCustomText = $rawPriceValue !== '' && $rawPriceValue !== trim((string) $entry->sku);
                                                $priceValue = $priceValueUsesCustomText
                                                    ? \App\Support\PriceHtml::text($rawPriceValue)
                                                    : ($entry->price !== null
                                                        ? rtrim(rtrim(number_format((float) $entry->price, 2, '.', ' '), '0'), '.')
                                                        : '');
                                                $oldPrice = $entry->old_price !== null
                                                    ? rtrim(rtrim(number_format((float) $entry->old_price, 2, '.', ' '), '0'), '.')
                                                    : null;
                                            @endphp
                                            <div class="ov-entry ov-entry--wide" wire:click="editEntry({{ $entry->id }})">
                                                @if($typeKey === 'prices')
                                                    <div class="ov-val ov-price-line">
                                                        <span>{{ $priceValue ?: '—' }}</span>
                                                        @if(!$priceValueUsesCustomText && $entry->currency)
                                                            <span>{{ $entry->currency }}</span>
                                                        @endif
                                                        @if($oldPrice)
                                                            <span class="ov-old-price">{{ $oldPrice }}</span>
                                                        @endif
                                                    </div>
                                                    <div class="ov-sub">
                                                        @if($entry->sku)
                                                            <span class="mono">{{ $entry->sku }}</span>
                                                        @endif
                                                        @if($entry->label)
                                                            <span>{{ $entry->label }}</span>
                                                        @endif
                                                        @if($entry->price_unit)
                                                            <span>{{ $entry->price_unit }}</span>
                                                        @endif
                                                    </div>
                                                @elseif($typeKey === 'socials')
                                                    <div class="ov-val">{{ $entry->value }}</div>
                                                    <div class="ov-sub">
                                                        @if($kindMeta)
                                                            <span class="ov-msg-tag" style="color:{{ $kindMeta['color'] }};">{{ $kindMeta['short'] }}</span>
                                                        @endif
                                                        @if($entry->label)
                                                            <span>{{ $entry->label }}</span>
                                                        @elseif($kindMeta)
                                                            <span>{{ $kindMeta['label'] }}</span>
                                                        @endif
                                                    </div>
                                                @else
                                                    <div class="ov-val">{{ $entry->value ?: ($entry->label ?: '—') }}</div>
                                                    @if($entry->label && $entry->label !== $entry->value)
                                                        <div class="ov-sub">{{ $entry->label }}</div>
                                                    @elseif($entry->kind)
                                                        <div class="ov-sub">{{ $entry->kind }}</div>
                                                    @endif
                                                @endif
                                                <div class="ov-geo-rule">{{ $entry->geo_label }}</div>
                                            </div>
                                        @endforeach
                                    @else
                                        <div class="ov-empty">{{ $meta['empty'] }}</div>
                                    @endif
                                    @for($i = 0; $i < max(0, $maxRows - $entries->count()); $i++)
                                        <div class="ov-entry ov-entry--placeholder" aria-hidden="true"></div>
                                    @endfor
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>
