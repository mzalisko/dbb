<div style="flex:1; display:flex; flex-direction:column;">
    <x-ui.topbar :crumbs="['Браузер даних']">
        <x-ui.button variant="secondary" size="sm" wire:click="$toggle('trashed')">
            <x-icon.trash width="13" height="13" /> {{ $trashed ? 'До активних' : 'Кошик' }}
        </x-ui.button>
        @unless ($trashed)
            @if(count($types) > 0)
            <x-ui.button variant="primary" size="sm" wire:click="openCreate">
                <x-icon.plus width="13" height="13" /> Додати
            </x-ui.button>
            @endif
            <x-ui.button variant="secondary" size="sm" wire:click="export">
                <x-icon.export width="13" height="13" /> Експорт
            </x-ui.button>
        @endunless
    </x-ui.topbar>

    <x-ui.page-head
        :number="$totalCount"
        :label="$trashed ? 'у кошику' : 'записів'"
        :sub="$trashed
            ? 'Видалені записи. Відновіть потрібні або очистіть назавжди.'
            : 'Усі контактні дані з усіх сайтів. Шукайте, фільтруйте, виконуйте групові операції.'" />

    <div style="padding:0 40px;">
        <div style="display:flex; align-items:center; gap:14px; flex-wrap:wrap;">
            <div style="display:flex; align-items:center; gap:10px; height:44px; padding:0 18px; border-radius:999px; background:var(--card); border:1px solid var(--ink-3); flex:1; min-width:240px; max-width:520px;">
                <x-icon.search width="15" height="15" style="color:var(--ink-5);" />
                <input wire:model.live.debounce.300ms="search" type="text"
                       placeholder="Пошук по {{ $totalCount }} записах…"
                       style="flex:1; font:14.5px var(--font-sans); color:var(--ink-7); background:transparent; border:0; outline:none;" />
            </div>

            {{-- Axis: organise the left rail by value (find a number/price) or by site --}}
            <div style="display:inline-flex; background:var(--ink-2); border-radius:999px; padding:3px;">
                @foreach (['value' => 'За значенням', 'site' => 'За сайтом'] as $akey => $alabel)
                    <button wire:click="$set('axis', '{{ $akey }}')" style="
                        border:0; padding:6px 14px; border-radius:999px; font:12.5px var(--font-sans); cursor:pointer;
                        background:{{ $axis === $akey ? 'var(--card)' : 'transparent' }};
                        color:{{ $axis === $akey ? 'var(--ink-9)' : 'var(--ink-6)' }};
                        box-shadow:{{ $axis === $akey ? '0 1px 2px rgba(0,0,0,.08)' : 'none' }};">
                        {{ $alabel }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Type filter (data-driven from ContactEntry::TYPES) --}}
        <div style="display:flex; gap:8px; margin-top:16px;">
            @foreach ($types as $key => $label)
                <button wire:click="$set('typeFilter', '{{ $key }}')" style="
                    display:inline-flex; align-items:center; gap:6px;
                    height:30px; padding:0 12px; border-radius:999px;
                    background:{{ $typeFilter === $key ? 'var(--ink-9)' : 'var(--card)' }};
                    color:{{ $typeFilter === $key ? 'var(--paper)' : 'var(--ink-7)' }};
                    box-shadow:{{ $typeFilter === $key ? 'none' : 'inset 0 0 0 1px var(--ink-3)' }};
                    font:12.5px var(--font-sans); cursor:pointer;">
                    {{ $label }}
                </button>
            @endforeach

            {{-- Site filter (value axis only — in the site axis the rail is the site picker) --}}
            @if ($axis === 'value')
            <span style="width:1px; height:18px; background:var(--ink-3); margin:0 4px; align-self:center;"></span>
            <select wire:model.live="siteFilter" aria-label="Фільтр по сайту"
                    style="height:30px; padding:0 30px 0 12px; border-radius:999px;
                           background:{{ $siteFilter === '' ? 'var(--card)' : 'var(--ink-9)' }};
                           color:{{ $siteFilter === '' ? 'var(--ink-7)' : 'var(--paper)' }};
                           box-shadow:{{ $siteFilter === '' ? 'inset 0 0 0 1px var(--ink-3)' : 'none' }};
                           border:0; font:12.5px var(--font-sans); cursor:pointer; outline:none;
                           appearance:none; -webkit-appearance:none;
                           background-image:url('data:image/svg+xml;utf8,<svg xmlns=&quot;http://www.w3.org/2000/svg&quot; width=&quot;12&quot; height=&quot;12&quot; viewBox=&quot;0 0 24 24&quot; fill=&quot;none&quot; stroke=&quot;{{ $siteFilter === '' ? '%2368635a' : '%23faf9f6' }}&quot; stroke-width=&quot;2&quot; stroke-linecap=&quot;round&quot; stroke-linejoin=&quot;round&quot;><path d=&quot;m6 9 6 6 6-6&quot;/></svg>');
                           background-repeat:no-repeat; background-position:right 11px center;">
                <option value="">Усі сайти</option>
                @foreach ($sites as $s)
                    <option value="{{ $s->id }}">{{ $s->name }}</option>
                @endforeach
            </select>
            @endif
        </div>

        {{-- Kind sub-filter (for types with kinds, e.g. messengers) — pick one
             before editing values so Viber/Telegram are never mixed. --}}
        @if (count($kinds) > 0)
            <div style="display:flex; align-items:center; gap:6px; margin-top:10px; flex-wrap:wrap;">
                <span style="font:11px var(--font-mono); color:var(--ink-5); text-transform:uppercase; letter-spacing:.06em; margin-right:2px;">Вид</span>
                @foreach ($kinds as $kkey => $klabel)
                    <button wire:click="$set('kindFilter', '{{ $kkey }}')" style="
                        height:28px; padding:0 11px; border-radius:999px; font:12px var(--font-sans); cursor:pointer;
                        background:{{ $kindFilter === $kkey ? 'var(--ink-9)' : 'transparent' }};
                        color:{{ $kindFilter === $kkey ? 'var(--paper)' : 'var(--ink-6)' }};
                        box-shadow:{{ $kindFilter === $kkey ? 'none' : 'inset 0 0 0 1px var(--ink-3)' }};">
                        {{ $klabel }}
                    </button>
                @endforeach
            </div>
        @endif

        {{-- Стан перенесено у праву панель (мініфільтр над входженнями) --}}
    </div>

    {{-- Bulk actions live in a clean bar at the BOTTOM of the working set (right pane). --}}

    <div style="padding:20px 40px 64px; flex:1; overflow-y:auto;">
        <div style="display:grid; grid-template-columns:300px 1fr; gap:18px; align-items:start;">

            {{-- LEFT: finder rail — value axis lists distinct values; site axis lists sites --}}
            <div class="card" style="overflow:hidden; align-self:start;">
                <div style="padding:12px 16px; border-bottom:1px solid var(--ink-3); background:var(--paper-2);">
                    <span class="eyebrow" style="font-size:10px;">{{ $axis === 'value' ? 'Значення' : 'Сайти' }}</span>
                </div>
                <div style="max-height:560px; overflow-y:auto;">
                    @if ($axis === 'value')
                        @forelse ($valueGroups as $g)
                            @php
                                $gcur = $g->currency ?? '';
                                $gdisp = $typeFilter === 'price' ? rtrim(rtrim(number_format((float) $g->gkey, 2, '.', ' '), '0'), '.') : $g->gkey;
                                $on = $pickedValue !== '' && (string) $pickedValue === (string) $g->gkey && (string) $pickedCurrency === (string) $gcur;
                            @endphp
                            <button type="button" wire:key="vg-{{ md5($g->gkey.'|'.$gcur) }}"
                                    wire:click="pickValue('{{ addslashes($g->gkey) }}', '{{ addslashes($gcur) }}')"
                                    style="display:flex; align-items:center; gap:9px; width:100%; text-align:left; padding:11px 16px; border:0; border-bottom:1px solid var(--ink-2); cursor:pointer;
                                           background:{{ $on ? 'var(--accent-soft)' : 'transparent' }}; box-shadow:{{ $on ? 'inset 3px 0 0 var(--accent)' : 'none' }};">
                                <span class="mono" style="flex:1; min-width:0; font:13px var(--font-mono); color:var(--ink-9); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $gdisp }}{{ $gcur ? ' '.$gcur : '' }}</span>
                                <span class="mono" style="font:11px var(--font-mono); color:var(--ink-6); background:var(--ink-2); border-radius:999px; padding:2px 8px; white-space:nowrap;">×{{ $g->n }}</span>
                                <span style="font:11px var(--font-sans); color:var(--ink-5); white-space:nowrap;">{{ $g->sites }}&nbsp;с.</span>
                            </button>
                        @empty
                            <div style="padding:40px 16px; text-align:center; color:var(--ink-5); font:13px var(--font-sans);">Немає значень.</div>
                        @endforelse
                    @else
                        @forelse ($sites as $s)
                            @php $on = (int) $siteFilter === (int) $s->id; @endphp
                            <button type="button" wire:key="sg-{{ $s->id }}" wire:click="$set('siteFilter', '{{ $s->id }}')"
                                    style="display:flex; align-items:center; gap:9px; width:100%; text-align:left; padding:11px 16px; border:0; border-bottom:1px solid var(--ink-2); cursor:pointer;
                                           background:{{ $on ? 'var(--accent-soft)' : 'transparent' }}; box-shadow:{{ $on ? 'inset 3px 0 0 var(--accent)' : 'none' }};">
                                <span style="flex:1; min-width:0; font:13px var(--font-sans); color:var(--ink-9); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $s->name }}</span>
                            </button>
                        @empty
                            <div style="padding:40px 16px; text-align:center; color:var(--ink-5); font:13px var(--font-sans);">Немає сайтів.</div>
                        @endforelse
                    @endif
                </div>
            </div>

            {{-- RIGHT: working set — occurrences of the picked value / chosen site --}}
            <div class="card" style="overflow:hidden;">
                <div style="display:flex; align-items:center; gap:12px; padding:12px 18px; border-bottom:1px solid var(--ink-3); background:var(--paper-2); flex-wrap:wrap;">
                    <div style="flex:1; min-width:0;">
                        @if ($axis === 'value' && $pickedValue !== '')
                            <span class="mono" style="font:14px var(--font-mono); color:var(--ink-9);">{{ $pickedValue }}{{ $pickedCurrency ? ' '.$pickedCurrency : '' }}</span>
                            <span style="font:12px var(--font-sans); color:var(--ink-5);"> · {{ $entries->total() }} входжень</span>
                        @elseif ($axis === 'site' && $siteFilter !== '')
                            <span class="mono" style="font:13px var(--font-mono); color:var(--ink-9);">{{ optional($sites->firstWhere('id', (int) $siteFilter))->name ?? 'Сайт' }}</span>
                            <span style="font:12px var(--font-sans); color:var(--ink-5);"> · {{ $entries->total() }} записів</span>
                        @else
                            <span style="font:12.5px var(--font-sans); color:var(--ink-5);">{{ $axis === 'value' ? 'Оберіть значення зліва' : 'Оберіть сайт зліва' }}</span>
                        @endif
                    </div>
                    @php
                        $roleOptions = $typeFilter === 'price'
                            ? ['' => 'Усі', 'primary' => 'Активні', 'hidden' => 'Приховані']
                            : ['' => 'Усі', 'primary' => 'Активні', 'backup' => 'Резервні', 'hidden' => 'Приховані'];
                    @endphp
                    <div style="display:inline-flex; gap:6px; flex-wrap:wrap;">
                        @foreach ($roleOptions as $rkey => $rlabel)
                            <button wire:click="$set('roleFilter', '{{ $rkey }}')" style="
                                height:26px; padding:0 10px; border-radius:999px; font:11.5px var(--font-sans); cursor:pointer;
                                display:inline-flex; align-items:center; gap:5px;
                                background:{{ $roleFilter === $rkey ? 'var(--ink-9)' : 'var(--card)' }};
                                color:{{ $roleFilter === $rkey ? 'var(--paper)' : 'var(--ink-6)' }};
                                box-shadow:{{ $roleFilter === $rkey ? 'none' : 'inset 0 0 0 1px var(--ink-3)' }};">
                                @if($rkey === 'hidden')<x-icon.eye-off width="12" height="12" class="state-icon state-icon--hidden" />@endif
                                {{ $rlabel }}
                            </button>
                        @endforeach
                    </div>
                </div>

                @if ($axis === 'site' && $siteFilter !== '')
                    {{-- Site axis: failover groups — a primary with its reserves in
                         queue order; reorder with ↑↓, detach with «зробити основним». --}}
                    @forelse ($siteGroups as $group)
                        @php
                            $headVal = $typeFilter === 'price'
                                ? (rtrim(rtrim(number_format((float) $group->price, 2, '.', ' '), '0'), '.').' '.$group->currency)
                                : $group->value;
                            $resCount = $group->backups->count();
                        @endphp
                        <div style="border-top:1px solid var(--ink-2); padding:14px 18px;">
                            <div style="display:grid; grid-template-columns:14px 1.3fr 1fr auto; gap:12px; align-items:center;">
                                @if ($group->role === 'hidden')
                                    <x-icon.eye-off width="13" height="13" class="state-icon state-icon--hidden" />
                                @else
                                    <span class="dot dot-ok"></span>
                                @endif
                                <span class="mono" style="font:13.5px var(--font-mono); color:var(--ink-9); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $headVal }}</span>
                                <span style="font:12.5px var(--font-sans); color:var(--ink-6); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $group->label }}</span>
                                <span style="font:12px var(--font-sans); color:var(--ink-5); white-space:nowrap;">{{ $group->geo_label }}</span>
                            </div>

                            @foreach ($group->backups as $bi => $b)
                                <div style="display:grid; grid-template-columns:14px 24px 1.3fr 1fr auto; gap:10px; align-items:center; padding:9px 0 0 0; margin-top:9px; border-top:1px dashed var(--ink-3);">
                                    <span style="color:var(--ink-4); text-align:center;">↳</span>
                                    <span class="mono" style="font:11px var(--font-mono); color:var(--ink-5);">{{ $bi + 1 }}</span>
                                    <span class="mono" style="font:13px var(--font-mono); color:var(--ink-7); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $b->value }}</span>
                                    <span style="display:inline-flex; align-items:center; gap:6px; font:11.5px var(--font-sans); color:var(--info);"><span class="dot dot-info"></span> резерв</span>
                                    <span style="display:inline-flex; align-items:center; gap:4px; justify-content:flex-end;">
                                        <button type="button" wire:click="reorderReserve({{ $b->id }}, 'up')" @disabled($bi === 0)
                                                style="width:26px; height:26px; border-radius:6px; border:1px solid var(--ink-3); background:var(--card); color:var(--ink-6); cursor:pointer; {{ $bi === 0 ? 'opacity:.4; cursor:default;' : '' }}" title="Підняти">↑</button>
                                        <button type="button" wire:click="reorderReserve({{ $b->id }}, 'down')" @disabled($bi === $resCount - 1)
                                                style="width:26px; height:26px; border-radius:6px; border:1px solid var(--ink-3); background:var(--card); color:var(--ink-6); cursor:pointer; {{ $bi === $resCount - 1 ? 'opacity:.4; cursor:default;' : '' }}" title="Опустити">↓</button>
                                        @can('update', $b)
                                            <button type="button" wire:click="makePrimary({{ $b->id }})"
                                                    style="margin-left:4px; height:26px; padding:0 9px; border-radius:6px; border:1px solid var(--ink-3); background:var(--card); color:var(--accent); cursor:pointer; font:11.5px var(--font-sans);" title="Зробити основним">→ основним</button>
                                        @endcan
                                    </span>
                                </div>
                            @endforeach

                            @if ($resCount === 0)
                                <div style="padding:8px 0 0 26px; font:11.5px var(--font-sans); color:var(--ink-4);">Без резервів</div>
                            @endif
                        </div>
                    @empty
                        <div style="padding:64px; text-align:center; color:var(--ink-5); font:13.5px var(--font-sans);">Немає записів на цьому сайті.</div>
                    @endforelse
                @elseif ($this->hasWorkingSet())
                    @php $pageAllSelected = $selectAllMatching || ($pageIds && empty(array_diff($pageIds, $selected))); @endphp
                    <div style="display:grid; grid-template-columns:32px 1.4fr 1.6fr 1fr 100px 80px; gap:12px; padding:12px 18px; border-bottom:1px solid var(--ink-3); background:var(--paper-2);">
                        <button type="button" class="row-check row-check--head {{ $pageAllSelected ? 'is-checked' : '' }}"
                                wire:click="selectPage(@js($pageIds))" title="Обрати сторінку">
                            @if ($pageAllSelected) <x-icon.check width="11" height="11" /> @endif
                        </button>
                        @foreach (['Значення', 'Сайт', 'Мітка', 'Гео', $trashed ? 'Видалено' : 'Роль'] as $h)
                            <span class="eyebrow" style="font-size:10px;">{{ $h }}</span>
                        @endforeach
                    </div>

            @forelse ($entries as $i => $entry)
                @php
                    $sel = $selectAllMatching || in_array($entry->id, $selected, true);
                    $isReserveEntry = ! is_null($entry->parent_id);
                @endphp
                <div wire:key="entry-{{ $entry->id }}" wire:click="toggleSelected({{ $entry->id }})"
                     style="display:grid; grid-template-columns:32px 1.4fr 1.6fr 1fr 100px 80px; gap:12px; padding:14px 18px; border-top:{{ $i ? '1px solid var(--ink-3)' : 'none' }}; background:{{ $sel ? 'var(--accent-soft)' : 'transparent' }}; align-items:center; cursor:pointer; transition:background .12s;">
                    <span class="row-check {{ $sel ? 'is-checked' : '' }}">
                        @if ($sel) <x-icon.check width="11" height="11" /> @endif
                    </span>
                    <span class="data-value">
                        @if($entry->type === 'price')
                            @php
                                $priceAmount = is_null($entry->price) ? null : rtrim(rtrim(number_format((float) $entry->price, 2, '.', ' '), '0'), '.');
                                $oldPriceAmount = is_null($entry->old_price) ? null : rtrim(rtrim(number_format((float) $entry->old_price, 2, '.', ' '), '0'), '.');
                            @endphp
                            <span class="data-price-value">
                                <span class="mono data-price-value__sku">{{ $entry->sku ?: $entry->value }}</span>
                                <span class="data-price-value__amount">
                                    <strong>{{ $priceAmount ?? '—' }}</strong>
                                    @if($entry->currency)
                                        <span>{{ $entry->currency }}</span>
                                    @endif
                                    @if($entry->price_unit)
                                        <em>{{ $entry->price_unit }}</em>
                                    @endif
                                    @if($oldPriceAmount !== null)
                                        <del>{{ $oldPriceAmount }}</del>
                                    @endif
                                </span>
                            </span>
                        @else
                            <span class="mono" style="font:13.5px var(--font-mono); color:{{ $trashed ? 'var(--ink-6)' : 'var(--ink-9)' }};">{{ $entry->value }}</span>
                        @endif
                        @if($isReserveEntry && $entry->parent)
                            <span class="data-value__parent" title="Резерв для {{ $entry->parent->value }}">
                                <x-icon.arrow width="11" height="11" />
                                <span class="data-value__parent-label">Резерв для</span>
                                <span>для</span>
                                <strong>{{ $entry->parent->value }}</strong>
                                @if($entry->parent->label)
                                    <em>{{ $entry->parent->label }}</em>
                                @endif
                            </span>
                            @unless($trashed)
                                <button type="button" wire:click.stop="makePrimary({{ $entry->id }})"
                                        style="display:inline-flex; align-items:center; gap:4px; margin-top:5px; border:0; background:transparent; padding:0; font:11.5px var(--font-sans); color:var(--accent); cursor:pointer;"
                                        title="Від'єднати від «{{ $entry->parent->value }}» і зробити окремим основним">
                                    <x-icon.bolt width="11" height="11" /> Зробити основним
                                </button>
                            @endunless
                        @endif
                    </span>
                    <span class="mono" style="font:12.5px var(--font-mono); color:var(--ink-7);">{{ $entry->site?->name ?? '—' }}</span>
                    <span style="font:12.5px var(--font-sans); color:var(--ink-7);">{{ $entry->label }}</span>
                    <span style="font:12px var(--font-sans); color:var(--ink-5);">{{ $entry->geo_label }}</span>
                    @if ($trashed)
                        <span style="font:12px var(--font-sans); color:var(--ink-5);">{{ $entry->deleted_at?->diffForHumans() }}</span>
                    @else
                        <span class="data-role">
                            @if ($entry->role === 'hidden')
                                <span class="data-role__state">
                                    <x-icon.eye-off width="13" height="13" class="state-icon state-icon--hidden" /> Приховано
                                </span>
                                @if($isReserveEntry)
                                    <span class="data-role__state data-role__state--muted">
                                        <span class="dot dot-info"></span> Резерв
                                    </span>
                                @endif
                            @elseif ($isReserveEntry)
                                <span class="data-role__state"><span class="dot dot-info"></span> Резерв</span>
                            @elseif ($entry->role === 'primary')
                                <span class="dot dot-ok"></span> Активний
                            @elseif ($entry->role === 'backup')
                                <span class="dot dot-info"></span> Резерв
                            @else
                                <x-icon.eye-off width="13" height="13" class="state-icon state-icon--hidden" /> Приховано
                            @endif
                        </span>
                    @endif
                </div>
            @empty
                <div style="padding:64px; text-align:center; color:var(--ink-5); font:13.5px var(--font-sans);">
                    {{ $trashed ? 'Кошик порожній.' : 'Немає входжень.' }}
                </div>
            @endforelse

                    <div style="padding:14px 18px;">{{ $entries->links('livewire.quiet-pagination') }}</div>
                @else
                    <div style="padding:72px 24px; text-align:center; color:var(--ink-5); font:13.5px var(--font-sans);">
                        {{ $axis === 'value'
                            ? '← Оберіть значення зліва, щоб побачити всі його входження та змінити вибірково.'
                            : '← Оберіть сайт зліва, щоб побачити його записи.' }}
                    </div>
                @endif

                {{-- Clean action bar pinned to the BOTTOM of the working set (replaces the old dark top bar) --}}
                @if ($this->hasSelection())
                    @php
                        $mixedType = count($selectionEntities) > 1;
                        $valueHint = $mixedType ? 'Лише для одного виду — обрано: '.implode(' + ', $selectionEntities) : '';
                        $actStyle = 'height:30px; padding:0 11px; border-radius:8px; border:1px solid var(--ink-3); background:var(--card); color:var(--ink-8); font:12.5px var(--font-sans); cursor:pointer; white-space:nowrap;';
                        $dangerStyle = $actStyle.' color:var(--bad); border-color:var(--bad-soft);';
                    @endphp
                    <div style="position:sticky; bottom:0; display:flex; align-items:center; gap:8px; flex-wrap:wrap; padding:12px 18px; border-top:1px solid var(--ink-3); background:var(--card); box-shadow:0 -6px 14px -10px rgba(0,0,0,.18);">
                        <span class="mono" style="font:12.5px var(--font-mono); color:var(--ink-8);">{{ $this->selectedCount() }} обрано</span>
                        @if (! $selectAllMatching && $entries->total() > $this->selectedCount())
                            <button type="button" wire:click.stop="selectAllFiltered" style="border:0; background:transparent; color:var(--accent); font:12px var(--font-sans); cursor:pointer;">Обрати всі {{ $entries->total() }}</button>
                        @elseif ($selectAllMatching)
                            <span style="font:12px var(--font-sans); color:var(--ink-5);">усі {{ $entries->total() }} за фільтром</span>
                        @endif
                        <span style="width:1px; height:16px; background:var(--ink-3); margin:0 2px;"></span>
                        <button type="button" style="{{ $actStyle }}" wire:click="openReview">Огляд</button>
                        @if ($trashed)
                            <button type="button" style="{{ $actStyle }}" wire:click="restoreSelected">Відновити</button>
                            <button type="button" style="{{ $dangerStyle }}" wire:click="purgeSelected" wire:confirm="Видалити обрані записи НАЗАВЖДИ? Це не можна відмінити.">Видалити назавжди</button>
                        @else
                            <button type="button" style="{{ $actStyle }}" wire:click="openEdit('value')" @disabled($mixedType) @if ($mixedType) title="{{ $valueHint }}" @endif>Замінити значення</button>
                            <button type="button" style="{{ $actStyle }}" wire:click="openReplace" @disabled($mixedType) @if ($mixedType) title="{{ $valueHint }}" @endif>Підрядок</button>
                            <button type="button" style="{{ $actStyle }}" wire:click="openEdit('label')">Мітка</button>
                            <button type="button" style="{{ $actStyle }}" wire:click="openRole">Стан</button>
                            @if ($typeFilter === 'price')<button type="button" style="{{ $actStyle }}" wire:click="openPriceEdit">Ціна</button>@endif
                            <button type="button" style="{{ $actStyle }}" wire:click="openGeo">Гео</button>
                            <button type="button" style="{{ $actStyle }}" wire:click="openDuplicate">Дублювати</button>
                            <button type="button" style="{{ $actStyle }}" wire:click="openMove">Перемістити</button>
                            @unless ($mixedType)<button type="button" style="{{ $actStyle }}" wire:click="openAttach">Приєднати резерв</button>@endunless
                            <button type="button" style="{{ $dangerStyle }}" wire:click="bulkDelete">Видалити</button>
                        @endif
                        <span style="margin-left:auto; display:inline-flex; align-items:center; gap:10px;">
                            <span style="font:12px var(--font-sans); color:var(--ink-5);">діє лише на {{ $this->selectedCount() }} обрані · з відміною</span>
                            <button type="button" wire:click.stop="clearSelected" style="border:0; background:transparent; color:var(--ink-5); font:12px var(--font-sans); cursor:pointer;">Зняти виділення</button>
                        </span>
                    </div>
                @endif
            </div>

        </div>{{-- /two-pane grid --}}

        {{-- Persistent "who is whose reserve" panel — primaries with their failover queue --}}
        @if ($reserveGroups->isNotEmpty())
            <div style="margin-top:28px;">
                <div style="display:flex; align-items:baseline; gap:10px; margin-bottom:12px; flex-wrap:wrap;">
                    <h2 style="font:600 15px var(--font-sans); color:var(--ink-9); margin:0;">Резерви — хто чий</h2>
                    <span style="font:12px var(--font-sans); color:var(--ink-5);">основний номер і його черга failover · ↑↓ порядок · «зробити основним» від'єднує</span>
                </div>
                <div class="card" style="overflow:hidden;">
                    @foreach ($reserveGroups as $group)
                        @php
                            $headVal = $typeFilter === 'price'
                                ? (rtrim(rtrim(number_format((float) $group->price, 2, '.', ' '), '0'), '.').' '.$group->currency)
                                : $group->value;
                            $resCount = $group->backups->count();
                        @endphp
                        <div style="padding:14px 18px; border-top:{{ $loop->first ? 'none' : '1px solid var(--ink-3)' }};">
                            <div style="display:grid; grid-template-columns:14px 1.2fr 1fr 120px 80px; gap:12px; align-items:center;">
                                @if ($group->role === 'hidden')
                                    <x-icon.eye-off width="13" height="13" class="state-icon state-icon--hidden" />
                                @else
                                    <span class="dot dot-ok"></span>
                                @endif
                                <span class="mono" style="font:13.5px var(--font-mono); color:var(--ink-9); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $headVal }}</span>
                                <span style="font:12.5px var(--font-sans); color:var(--ink-6); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $group->label }}</span>
                                <span class="mono" style="font:11.5px var(--font-mono); color:var(--ink-5); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $group->site?->name }}</span>
                                <span style="font:11px var(--font-sans); color:var(--ink-5); text-align:right;">основний</span>
                            </div>
                            @foreach ($group->backups as $bi => $b)
                                <div style="display:grid; grid-template-columns:14px 24px 1.2fr 1fr auto; gap:10px; align-items:center; padding:9px 0 0; margin-top:9px; border-top:1px dashed var(--ink-3);">
                                    <span style="color:var(--ink-4); text-align:center;">↳</span>
                                    <span class="mono" style="font:11px var(--font-mono); color:var(--ink-5);">{{ $bi + 1 }}</span>
                                    <span class="mono" style="font:13px var(--font-mono); color:var(--ink-7); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $b->value }}</span>
                                    <span style="display:inline-flex; align-items:center; gap:6px; font:11.5px var(--font-sans); color:var(--info);"><span class="dot dot-info"></span> резерв · гео успадковане</span>
                                    <span style="display:inline-flex; align-items:center; gap:4px; justify-content:flex-end;">
                                        <button type="button" wire:click="reorderReserve({{ $b->id }}, 'up')" @disabled($bi === 0)
                                                style="width:26px; height:26px; border-radius:6px; border:1px solid var(--ink-3); background:var(--card); color:var(--ink-6); cursor:pointer; {{ $bi === 0 ? 'opacity:.4; cursor:default;' : '' }}" title="Підняти">↑</button>
                                        <button type="button" wire:click="reorderReserve({{ $b->id }}, 'down')" @disabled($bi === $resCount - 1)
                                                style="width:26px; height:26px; border-radius:6px; border:1px solid var(--ink-3); background:var(--card); color:var(--ink-6); cursor:pointer; {{ $bi === $resCount - 1 ? 'opacity:.4; cursor:default;' : '' }}" title="Опустити">↓</button>
                                        @can('update', $b)
                                            <button type="button" wire:click="makePrimary({{ $b->id }})"
                                                    style="margin-left:4px; height:26px; padding:0 9px; border-radius:6px; border:1px solid var(--ink-3); background:var(--card); color:var(--accent); cursor:pointer; font:11.5px var(--font-sans);" title="Зробити основним">→ основним</button>
                                        @endcan
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>{{-- /page padding --}}

    {{-- Bulk edit drawer (replace value / change label) --}}
    @if ($editingField)
        @php $editTitles = ['value' => 'Замінити значення', 'label' => 'Змінити мітку']; @endphp
        <div wire:key="bulk-edit-{{ $editField }}">
            <x-ui.drawer :open="true"
                         :title="$editTitles[$editField] ?? 'Редагувати'"
                         @drawer-close.window="$wire.closeEdit()">
                <div style="display:flex; flex-direction:column; gap:18px;">
                    {{-- step indicator --}}
                    <div style="display:flex; gap:6px;">
                        <span style="flex:1; height:4px; border-radius:999px; background:var(--accent);"></span>
                        <span style="flex:1; height:4px; border-radius:999px; background:{{ $editStep === 2 ? 'var(--accent)' : 'var(--ink-2)' }};"></span>
                    </div>
                    <div class="eyebrow" style="font-size:10px;">Крок {{ $editStep }} / 2 · {{ $this->selectedCount() }} обрано</div>

                    @if ($editStep === 1)
                        <div>
                            <label style="display:block; font:12px var(--font-mono); color:var(--ink-5); text-transform:uppercase; letter-spacing:.06em;">
                                {{ $editField === 'value' ? 'Нове значення' : 'Нова мітка' }}
                            </label>
                            <input type="text" wire:model="editValue" wire:keydown.enter="editConfirm"
                                   x-init="$nextTick(() => $el.focus())"
                                   placeholder="{{ $editField === 'value' ? 'Напр. +48 22 111 22 33' : 'Напр. Підписка · Pro' }}"
                                   style="margin-top:8px; width:100%; height:44px; padding:0 14px; border-radius:10px; background:var(--card); border:1px solid var(--ink-3); font:14.5px var(--font-sans); color:var(--ink-9); outline:none;" />
                            <div style="margin-top:8px; font:12px var(--font-sans); color:var(--ink-5);">
                                Далі — список, що саме зміниться, перед застосуванням.
                            </div>
                        </div>
                    @else
                        <div style="font:13px var(--font-sans); color:var(--ink-6);">
                            {{ $editField === 'value' ? 'Значення' : 'Мітку' }} буде змінено на
                            <b class="mono" style="color:var(--ink-9);">{{ trim($editValue) !== '' ? $editValue : '—' }}</b>
                            у {{ $this->selectedCount() }} {{ $editField === 'value' ? 'входженнях' : 'записах' }}. Дію можна відмінити.
                        </div>
                        @if ($selectionPreview->isNotEmpty())
                            <div style="display:flex; flex-direction:column; gap:6px; max-height:300px; overflow-y:auto;">
                                @foreach ($selectionPreview as $p)
                                    @php $old = $editField === 'label' ? ($p->label ?: '—') : $p->value; @endphp
                                    <div style="display:flex; align-items:center; gap:8px; padding:8px 12px; border-radius:8px; background:var(--paper-2);">
                                        <span class="mono" style="flex:1; min-width:0; font:12.5px var(--font-mono); color:var(--ink-5); overflow:hidden; text-overflow:ellipsis; white-space:nowrap; text-decoration:line-through;">{{ $old }}</span>
                                        <x-icon.arrow width="12" height="12" style="color:var(--ink-4); flex-shrink:0;" />
                                        <span class="mono" style="flex:1; min-width:0; font:12.5px var(--font-mono); color:var(--ink-9); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ trim($editValue) !== '' ? $editValue : '—' }}</span>
                                        <span class="mono" style="font:11px var(--font-mono); color:var(--ink-5); white-space:nowrap;">{{ $p->site?->name ?? '—' }}</span>
                                    </div>
                                @endforeach
                                @if ($this->selectedCount() > $selectionPreview->count())
                                    <div style="padding:2px 4px; font:12px var(--font-sans); color:var(--ink-5);">…та ще {{ $this->selectedCount() - $selectionPreview->count() }}</div>
                                @endif
                            </div>
                        @endif
                    @endif
                </div>

                <x-slot:footer>
                    @if ($editStep === 1)
                        <button class="btn btn-ghost" wire:click="closeEdit">Скасувати</button>
                        <button class="btn btn-primary" wire:click="editConfirm">Далі →</button>
                    @else
                        <button class="btn btn-ghost" wire:click="editBack">← Назад</button>
                        <button class="btn btn-primary" wire:click="applyEdit">
                            {{ $editField === 'value' ? 'Замінити' : 'Зберегти' }} {{ $this->selectedCount() }}
                        </button>
                    @endif
                </x-slot:footer>
            </x-ui.drawer>
        </div>
    @endif

    {{-- Selection review drawer (see / trim the cross-site set) --}}
    @if ($reviewingSelection)
        <div wire:key="bulk-review">
            <x-ui.drawer :open="true" title="Огляд вибору" :sub="$this->selectedCount() . ' обрано'"
                         @drawer-close.window="$wire.closeReview()">
                @if ($selectAllMatching)
                    <div style="padding:16px; border-radius:10px; background:var(--paper-2); font:13.5px var(--font-sans); color:var(--ink-7);">
                        Обрано <b>усі {{ $this->selectedCount() }}</b> записів за поточним фільтром.
                        Окремі записи можна прибрати лише з ручного вибору — зніміть «обрати всі» й відмітьте потрібні.
                    </div>
                @elseif ($reviewItems->isEmpty())
                    <div style="padding:40px 0; text-align:center; font:13.5px var(--font-sans); color:var(--ink-5);">Нічого не обрано.</div>
                @else
                    <div style="display:flex; flex-direction:column; gap:18px;">
                        @foreach ($reviewItems->groupBy(fn ($e) => $e->site?->name ?? '—') as $siteName => $group)
                            <div>
                                <div class="mono" style="font:11.5px var(--font-mono); color:var(--ink-5); margin-bottom:8px;">{{ $siteName }} · {{ $group->count() }}</div>
                                <div style="display:flex; flex-direction:column; gap:4px;">
                                    @foreach ($group as $item)
                                        <div wire:key="rev-{{ $item->id }}" style="display:flex; align-items:center; gap:10px; padding:8px 10px 8px 12px; border-radius:8px; background:var(--paper-2);">
                                            <span class="mono" style="flex:1; min-width:0; font:12.5px var(--font-mono); color:var(--ink-9); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $item->value }}</span>
                                            @if ($item->label)
                                                <span style="font:11.5px var(--font-sans); color:var(--ink-5); white-space:nowrap;">{{ $item->label }}</span>
                                            @endif
                                            <button wire:click="toggleSelected({{ $item->id }})" title="Прибрати з вибору"
                                                    style="width:24px; height:24px; border-radius:6px; display:inline-flex; align-items:center; justify-content:center; color:var(--ink-5); cursor:pointer; flex-shrink:0; transition:background .12s, color .12s;"
                                                    onmouseover="this.style.background='var(--ink-2)'; this.style.color='var(--bad)'"
                                                    onmouseout="this.style.background='transparent'; this.style.color='var(--ink-5)'">
                                                <x-icon.close width="14" height="14" />
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                <x-slot:footer>
                    <button class="btn btn-danger" wire:click="clearSelected">Зняти все</button>
                    <span style="flex:1;"></span>
                    <button class="btn btn-primary" wire:click="closeReview">Готово</button>
                </x-slot:footer>
            </x-ui.drawer>
        </div>
    @endif

    {{-- Find & replace substring drawer --}}
    @if ($editingReplace)
        <div wire:key="bulk-replace">
            <x-ui.drawer :open="true" title="Замінити підрядок" :sub="$this->selectedCount() . ' обрано'"
                         @drawer-close.window="$wire.closeReplace()">
                <div style="display:flex; flex-direction:column; gap:18px;">
                    <div style="display:flex; gap:6px;">
                        <span style="flex:1; height:4px; border-radius:999px; background:var(--accent);"></span>
                        <span style="flex:1; height:4px; border-radius:999px; background:{{ $replaceStep === 2 ? 'var(--accent)' : 'var(--ink-2)' }};"></span>
                    </div>

                    @if ($replaceStep === 1)
                        <div style="font:13px var(--font-sans); color:var(--ink-6);">
                            Замінює частину значення там, де вона трапляється. Напр. <b>+380</b> → <b>+48</b>. Записи без збігу не змінюються.
                        </div>
                        <div>
                            <label style="display:block; font:12px var(--font-mono); color:var(--ink-5); text-transform:uppercase; letter-spacing:.06em;">Знайти</label>
                            <input type="text" wire:model="findText" x-init="$nextTick(() => $el.focus())" placeholder="+380"
                                   style="margin-top:8px; width:100%; height:44px; padding:0 14px; border-radius:10px; background:var(--card); border:1px solid var(--ink-3); font:14.5px var(--font-mono); color:var(--ink-9); outline:none;" />
                        </div>
                        <div>
                            <label style="display:block; font:12px var(--font-mono); color:var(--ink-5); text-transform:uppercase; letter-spacing:.06em;">Замінити на</label>
                            <input type="text" wire:model="replaceText" wire:keydown.enter="replaceConfirm" placeholder="+48"
                                   style="margin-top:8px; width:100%; height:44px; padding:0 14px; border-radius:10px; background:var(--card); border:1px solid var(--ink-3); font:14.5px var(--font-mono); color:var(--ink-9); outline:none;" />
                            <div style="margin-top:8px; font:12px var(--font-sans); color:var(--ink-5);">Порожнє «Замінити на» — видалить підрядок.</div>
                        </div>
                    @else
                        <div style="font:13px var(--font-sans); color:var(--ink-6);">
                            У значеннях замінимо <b class="mono" style="color:var(--ink-9);">{{ $findText }}</b> →
                            <b class="mono" style="color:var(--ink-9);">{{ $replaceText !== '' ? $replaceText : '∅' }}</b>.
                            Записи без збігу лишаться як є. Дію можна відмінити.
                        </div>
                        @if ($selectionPreview->isNotEmpty())
                            <div style="display:flex; flex-direction:column; gap:6px; max-height:300px; overflow-y:auto;">
                                @foreach ($selectionPreview as $p)
                                    @php $will = str_contains((string) $p->value, $findText); @endphp
                                    <div style="display:flex; align-items:center; gap:8px; padding:8px 12px; border-radius:8px; background:var(--paper-2);">
                                        <span class="mono" style="flex:1; min-width:0; font:12.5px var(--font-mono); color:{{ $will ? 'var(--ink-5)' : 'var(--ink-4)' }}; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; {{ $will ? 'text-decoration:line-through;' : '' }}">{{ $p->value }}</span>
                                        @if ($will)
                                            <x-icon.arrow width="12" height="12" style="color:var(--ink-4); flex-shrink:0;" />
                                            <span class="mono" style="flex:1; min-width:0; font:12.5px var(--font-mono); color:var(--ink-9); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ str_replace($findText, $replaceText, (string) $p->value) }}</span>
                                        @else
                                            <span style="font:11px var(--font-sans); color:var(--ink-4); white-space:nowrap;">без збігу</span>
                                        @endif
                                    </div>
                                @endforeach
                                @if ($this->selectedCount() > $selectionPreview->count())
                                    <div style="padding:2px 4px; font:12px var(--font-sans); color:var(--ink-5);">…та ще {{ $this->selectedCount() - $selectionPreview->count() }}</div>
                                @endif
                            </div>
                        @endif
                    @endif
                </div>

                <x-slot:footer>
                    @if ($replaceStep === 1)
                        <button class="btn btn-ghost" wire:click="closeReplace">Скасувати</button>
                        <button class="btn btn-primary" wire:click="replaceConfirm">Далі →</button>
                    @else
                        <button class="btn btn-ghost" wire:click="replaceBack">← Назад</button>
                        <button class="btn btn-primary" wire:click="applyReplace">Замінити</button>
                    @endif
                </x-slot:footer>
            </x-ui.drawer>
        </div>
    @endif

    {{-- Bulk geo drawer (geo_mode + countries) --}}
    @if ($editingGeo)
        <div wire:key="bulk-geo">
            <x-ui.drawer :open="true" title="Змінити гео" :sub="$this->selectedCount() . ' обрано'"
                         @drawer-close.window="$wire.closeGeo()">
                <div style="display:flex; flex-direction:column; gap:18px;">
                    <div>
                        <label style="display:block; font:12px var(--font-mono); color:var(--ink-5); text-transform:uppercase; letter-spacing:.06em;">Показувати</label>
                        <div style="margin-top:8px; display:flex; gap:8px;">
                            @foreach (['all' => 'Всім', 'only' => 'Тільки', 'except' => 'Крім'] as $m => $lbl)
                                <button type="button" wire:click="$set('geoMode', '{{ $m }}')"
                                        style="flex:1; height:44px; border-radius:10px; font:14px var(--font-sans); cursor:pointer;
                                               background:{{ $geoMode === $m ? 'var(--ink-9)' : 'var(--card)' }};
                                               color:{{ $geoMode === $m ? 'var(--paper)' : 'var(--ink-7)' }};
                                               border:1px solid {{ $geoMode === $m ? 'var(--ink-9)' : 'var(--ink-3)' }};">
                                {{ $lbl }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    @if ($geoMode !== 'all')
                        <div>
                            <label style="display:block; font:12px var(--font-mono); color:var(--ink-5); text-transform:uppercase; letter-spacing:.06em;">Країни (ISO-коди)</label>
                            <input type="text" wire:model="geoCountries" wire:keydown.enter="applyGeo" x-init="$nextTick(() => $el.focus())" placeholder="UA, PL, DE"
                                   style="margin-top:8px; width:100%; height:44px; padding:0 14px; border-radius:10px; background:var(--card); border:1px solid var(--ink-3); font:14.5px var(--font-mono); color:var(--ink-9); text-transform:uppercase; outline:none;" />
                            <div style="margin-top:8px; font:12px var(--font-sans); color:var(--ink-5);">
                                {{ $geoMode === 'only' ? 'Показувати лише у цих країнах.' : 'Показувати всюди, окрім цих країн.' }} Дію можна відмінити.
                            </div>
                        </div>
                    @else
                        <div style="font:13px var(--font-sans); color:var(--ink-6);">Контакти будуть видимі в усіх країнах. Дію можна відмінити.</div>
                    @endif
                </div>

                <x-slot:footer>
                    <button class="btn btn-ghost" wire:click="closeGeo">Скасувати</button>
                    <button class="btn btn-primary" wire:click="applyGeo">Застосувати</button>
                </x-slot:footer>
            </x-ui.drawer>
        </div>
    @endif

    {{-- Bulk role/state drawer (active / hidden — backup is assigned separately) --}}
    @if ($editingRole)
        <div wire:key="bulk-role">
            <x-ui.drawer :open="true" title="Змінити стан" :sub="$this->selectedCount() . ' обрано'"
                         @drawer-close.window="$wire.closeRole()">
                <div style="display:flex; flex-direction:column; gap:18px;">
                    <div>
                        <label style="display:block; font:12px var(--font-mono); color:var(--ink-5); text-transform:uppercase; letter-spacing:.06em;">Новий стан</label>
                        <div style="margin-top:8px; display:flex; gap:8px;">
                            @foreach (['primary' => 'Активний', 'hidden' => 'Приховано'] as $val => $lbl)
                                <button type="button" wire:click="$set('roleValue', '{{ $val }}')"
                                        style="flex:1; height:44px; border-radius:10px; font:14px var(--font-sans); cursor:pointer;
                                               display:inline-flex; align-items:center; justify-content:center; gap:6px;
                                               background:{{ $roleValue === $val ? 'var(--ink-9)' : 'var(--card)' }};
                                               color:{{ $roleValue === $val ? 'var(--paper)' : 'var(--ink-7)' }};
                                               border:1px solid {{ $roleValue === $val ? 'var(--ink-9)' : 'var(--ink-3)' }};">
                                    @if($val === 'hidden')
                                        <x-icon.eye-off width="14" height="14" class="state-icon state-icon--hidden" />
                                    @endif
                                    {{ $lbl }}
                                </button>
                            @endforeach
                        </div>
                        <div style="margin-top:8px; font:12px var(--font-sans); color:var(--ink-5);">
                            «Активний» від'єднує резерв від контакту й зберігає успадковане гео. «Резерв» призначається окремо — потрібен основний запис. Дію можна відмінити.
                        </div>
                    </div>
                </div>
                <x-slot:footer>
                    <button class="btn btn-ghost" wire:click="closeRole">Скасувати</button>
                    <button class="btn btn-primary" wire:click="applyRole">Застосувати</button>
                </x-slot:footer>
            </x-ui.drawer>
        </div>
    @endif

    {{-- Bulk price-fields drawer (currency / unit / price / old price) --}}
    @if ($editingPrice)
        @php $priceFieldLabels = ['currency' => 'Валюта', 'price_unit' => 'Одиниця', 'price' => 'Ціна', 'old_price' => 'Стара ціна']; @endphp
        <div wire:key="bulk-price">
            <x-ui.drawer :open="true" title="Змінити ціни" :sub="$this->selectedCount() . ' обрано'"
                         @drawer-close.window="$wire.closePriceEdit()">
                <div style="display:flex; flex-direction:column; gap:18px;">
                    <div style="display:flex; gap:6px;">
                        <span style="flex:1; height:4px; border-radius:999px; background:var(--accent);"></span>
                        <span style="flex:1; height:4px; border-radius:999px; background:{{ $priceStep === 2 ? 'var(--accent)' : 'var(--ink-2)' }};"></span>
                    </div>
                    <div class="eyebrow" style="font-size:10px;">Крок {{ $priceStep }} / 2 · {{ $this->selectedCount() }} обрано</div>

                    @if ($priceStep === 1)
                        <div>
                            <label style="display:block; font:12px var(--font-mono); color:var(--ink-5); text-transform:uppercase; letter-spacing:.06em;">Поле</label>
                            <div style="margin-top:8px; display:flex; gap:6px; flex-wrap:wrap;">
                                @foreach ($priceFieldLabels as $fkey => $flabel)
                                    <button type="button" wire:click="$set('priceField', '{{ $fkey }}')"
                                            style="height:34px; padding:0 12px; border-radius:8px; font:13px var(--font-sans); cursor:pointer;
                                                   background:{{ $priceField === $fkey ? 'var(--ink-9)' : 'var(--card)' }};
                                                   color:{{ $priceField === $fkey ? 'var(--paper)' : 'var(--ink-7)' }};
                                                   border:1px solid {{ $priceField === $fkey ? 'var(--ink-9)' : 'var(--ink-3)' }};">
                                        {{ $flabel }}
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        @if ($priceField === 'currency')
                            <div>
                                <label style="display:block; font:12px var(--font-mono); color:var(--ink-5); text-transform:uppercase; letter-spacing:.06em;">Валюта</label>
                                <div style="margin-top:8px; display:flex; gap:8px;">
                                    @foreach (['EUR', 'USD', 'PLN', 'UAH'] as $cur)
                                        <button type="button" wire:click="$set('priceValue', '{{ $cur }}')"
                                                style="flex:1; height:44px; border-radius:10px; font:14px var(--font-mono); cursor:pointer;
                                                       background:{{ $priceValue === $cur ? 'var(--ink-9)' : 'var(--card)' }};
                                                       color:{{ $priceValue === $cur ? 'var(--paper)' : 'var(--ink-7)' }};
                                                       border:1px solid {{ $priceValue === $cur ? 'var(--ink-9)' : 'var(--ink-3)' }};">
                                            {{ $cur }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <div>
                                <label style="display:block; font:12px var(--font-mono); color:var(--ink-5); text-transform:uppercase; letter-spacing:.06em;">
                                    {{ $priceFieldLabels[$priceField] }}
                                </label>
                                <input type="text" wire:model="priceValue" wire:keydown.enter="priceConfirm"
                                       x-init="$nextTick(() => $el.focus())"
                                       placeholder="{{ $priceField === 'price_unit' ? 'напр. міс / шт / рік' : 'напр. 199' }}"
                                       style="margin-top:8px; width:100%; height:44px; padding:0 14px; border-radius:10px; background:var(--card); border:1px solid var(--ink-3); font:14.5px var(--font-sans); color:var(--ink-9); outline:none;" />
                                <div style="margin-top:8px; font:12px var(--font-sans); color:var(--ink-5);">
                                    @if (in_array($priceField, ['price', 'old_price']))Порожнє поле — прибрати значення. @endif
                                </div>
                            </div>
                        @endif
                    @else
                        @php $pv = trim($priceValue); @endphp
                        <div style="font:13px var(--font-sans); color:var(--ink-6);">
                            <b style="color:var(--ink-9);">{{ $priceFieldLabels[$priceField] }}</b> буде змінено на
                            <b class="mono" style="color:var(--ink-9);">{{ $pv !== '' ? $pv : (in_array($priceField, ['price','old_price']) ? '∅ (прибрати)' : '—') }}</b>
                            у {{ $this->selectedCount() }} обраних цінах. Дію можна відмінити.
                        </div>
                    @endif
                </div>
                <x-slot:footer>
                    @if ($priceStep === 1)
                        <button class="btn btn-ghost" wire:click="closePriceEdit">Скасувати</button>
                        <button class="btn btn-primary" wire:click="priceConfirm">Далі →</button>
                    @else
                        <button class="btn btn-ghost" wire:click="priceBack">← Назад</button>
                        <button class="btn btn-primary" wire:click="applyPriceEdit">Застосувати {{ $this->selectedCount() }}</button>
                    @endif
                </x-slot:footer>
            </x-ui.drawer>
        </div>
    @endif

    {{-- Create a new entry on one or more sites --}}
    @if ($creating)
        <div wire:key="bulk-create">
            <x-ui.drawer :open="true" title="Додати запис" :sub="$types[$typeFilter] ?? $typeFilter"
                         @drawer-close.window="$wire.closeCreate()">
                <div style="display:flex; flex-direction:column; gap:18px;">
                    @if (count($kinds) > 0)
                        <div>
                            <label style="display:block; font:12px var(--font-mono); color:var(--ink-5); text-transform:uppercase; letter-spacing:.06em;">Вид</label>
                            <div style="margin-top:8px; display:flex; gap:6px; flex-wrap:wrap;">
                                @foreach ($kinds as $kkey => $klabel)
                                    <button type="button" wire:click="$set('createKind', '{{ $kkey }}')"
                                            style="height:34px; padding:0 12px; border-radius:8px; font:13px var(--font-sans); cursor:pointer;
                                                   background:{{ $createKind === $kkey ? 'var(--ink-9)' : 'var(--card)' }};
                                                   color:{{ $createKind === $kkey ? 'var(--paper)' : 'var(--ink-7)' }};
                                                   border:1px solid {{ $createKind === $kkey ? 'var(--ink-9)' : 'var(--ink-3)' }};">{{ $klabel }}</button>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    <div>
                        <label style="display:block; font:12px var(--font-mono); color:var(--ink-5); text-transform:uppercase; letter-spacing:.06em;">Значення</label>
                        <input type="text" wire:model="createValue" wire:keydown.enter="applyCreate" x-init="$nextTick(() => $el.focus())"
                               placeholder="Напр. +48 22 111 22 33"
                               style="margin-top:8px; width:100%; height:44px; padding:0 14px; border-radius:10px; background:var(--card); border:1px solid var(--ink-3); font:14.5px var(--font-sans); color:var(--ink-9); outline:none;" />
                    </div>
                    <div>
                        <label style="display:block; font:12px var(--font-mono); color:var(--ink-5); text-transform:uppercase; letter-spacing:.06em;">Мітка</label>
                        <input type="text" wire:model="createLabel" placeholder="Напр. Підтримка"
                               style="margin-top:8px; width:100%; height:44px; padding:0 14px; border-radius:10px; background:var(--card); border:1px solid var(--ink-3); font:14.5px var(--font-sans); color:var(--ink-9); outline:none;" />
                    </div>
                    <div>
                        <label style="display:block; font:12px var(--font-mono); color:var(--ink-5); text-transform:uppercase; letter-spacing:.06em;">Стан</label>
                        <div style="margin-top:8px; display:flex; gap:8px;">
                            @foreach (['primary' => 'Активний', 'hidden' => 'Приховано'] as $val => $lbl)
                                <button type="button" wire:click="$set('createRole', '{{ $val }}')"
                                        style="flex:1; height:44px; border-radius:10px; font:14px var(--font-sans); cursor:pointer;
                                               background:{{ $createRole === $val ? 'var(--ink-9)' : 'var(--card)' }};
                                               color:{{ $createRole === $val ? 'var(--paper)' : 'var(--ink-7)' }};
                                               border:1px solid {{ $createRole === $val ? 'var(--ink-9)' : 'var(--ink-3)' }};">{{ $lbl }}</button>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <label style="display:block; font:12px var(--font-mono); color:var(--ink-5); text-transform:uppercase; letter-spacing:.06em;">Сайти · обрано {{ count($createSites) }}</label>
                        <div style="margin-top:8px; display:flex; flex-direction:column; gap:4px; max-height:240px; overflow-y:auto;">
                            @foreach ($sites as $s)
                                @php $on = in_array($s->id, $createSites); @endphp
                                <button type="button" wire:click="toggleCreateSite({{ $s->id }})"
                                        style="display:flex; align-items:center; gap:10px; width:100%; padding:9px 12px; border-radius:8px; cursor:pointer; text-align:left;
                                               background:{{ $on ? 'var(--accent-soft)' : 'var(--paper-2)' }}; border:1px solid {{ $on ? 'var(--ink-9)' : 'transparent' }};">
                                    <span class="row-check {{ $on ? 'is-checked' : '' }}">@if($on)<x-icon.check width="11" height="11" />@endif</span>
                                    <span style="font:13px var(--font-sans); color:var(--ink-8);">{{ $s->name }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
                <x-slot:footer>
                    <button class="btn btn-ghost" wire:click="closeCreate">Скасувати</button>
                    <button class="btn btn-primary" wire:click="applyCreate">Створити</button>
                </x-slot:footer>
            </x-ui.drawer>
        </div>
    @endif

    {{-- Duplicate selection onto other sites (multi-target) --}}
    @if ($duplicating)
        <div wire:key="bulk-duplicate">
            <x-ui.drawer :open="true" title="Дублювати на сайти" :sub="$this->selectedCount() . ' обрано'"
                         @drawer-close.window="$wire.closeDuplicate()">
                <div style="display:flex; flex-direction:column; gap:14px;">
                    <div style="font:13px var(--font-sans); color:var(--ink-6);">Копії стануть самостійними активними записами на обраних сайтах. Резерв копіюється як активний. Дію можна відмінити.</div>
                    <div>
                        <label style="display:block; font:12px var(--font-mono); color:var(--ink-5); text-transform:uppercase; letter-spacing:.06em;">Куди · обрано {{ count($dupSites) }}</label>
                        <div style="margin-top:8px; display:flex; flex-direction:column; gap:4px; max-height:300px; overflow-y:auto;">
                            @foreach ($sites as $s)
                                @php $on = in_array($s->id, $dupSites); @endphp
                                <button type="button" wire:click="toggleDupSite({{ $s->id }})"
                                        style="display:flex; align-items:center; gap:10px; width:100%; padding:9px 12px; border-radius:8px; cursor:pointer; text-align:left;
                                               background:{{ $on ? 'var(--accent-soft)' : 'var(--paper-2)' }}; border:1px solid {{ $on ? 'var(--ink-9)' : 'transparent' }};">
                                    <span class="row-check {{ $on ? 'is-checked' : '' }}">@if($on)<x-icon.check width="11" height="11" />@endif</span>
                                    <span style="font:13px var(--font-sans); color:var(--ink-8);">{{ $s->name }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
                <x-slot:footer>
                    <button class="btn btn-ghost" wire:click="closeDuplicate">Скасувати</button>
                    <button class="btn btn-primary" wire:click="applyDuplicate">Дублювати</button>
                </x-slot:footer>
            </x-ui.drawer>
        </div>
    @endif

    {{-- Move selection to another site (single target) --}}
    @if ($moving)
        <div wire:key="bulk-move">
            <x-ui.drawer :open="true" title="Перемістити на сайт" :sub="$this->selectedCount() . ' обрано'"
                         @drawer-close.window="$wire.closeMove()">
                <div style="display:flex; flex-direction:column; gap:14px;">
                    <div style="font:13px var(--font-sans); color:var(--ink-6);">Записи змінять сайт. Активний переноситься разом зі своїми резервами. Дію можна відмінити.</div>
                    <div>
                        <label style="display:block; font:12px var(--font-mono); color:var(--ink-5); text-transform:uppercase; letter-spacing:.06em;">Сайт призначення</label>
                        <div style="margin-top:8px; display:flex; flex-direction:column; gap:4px; max-height:320px; overflow-y:auto;">
                            @foreach ($sites as $s)
                                @php $on = (int) $moveSite === (int) $s->id; @endphp
                                <button type="button" wire:click="$set('moveSite', '{{ $s->id }}')"
                                        style="display:flex; align-items:center; gap:10px; width:100%; padding:9px 12px; border-radius:8px; cursor:pointer; text-align:left;
                                               background:{{ $on ? 'var(--accent-soft)' : 'var(--paper-2)' }}; border:1px solid {{ $on ? 'var(--ink-9)' : 'transparent' }};">
                                    <span class="row-check {{ $on ? 'is-checked' : '' }}">@if($on)<x-icon.check width="11" height="11" />@endif</span>
                                    <span style="font:13px var(--font-sans); color:var(--ink-8);">{{ $s->name }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
                <x-slot:footer>
                    <button class="btn btn-ghost" wire:click="closeMove">Скасувати</button>
                    <button class="btn btn-primary" wire:click="applyMove">Перемістити</button>
                </x-slot:footer>
            </x-ui.drawer>
        </div>
    @endif

    {{-- Attach selection as reserves of a chosen primary (same site+type+kind) --}}
    @if ($attaching)
        <div wire:key="bulk-attach">
            <x-ui.drawer :open="true" title="Приєднати як резерв" :sub="$this->selectedCount() . ' обрано'"
                         @drawer-close.window="$wire.closeAttach()">
                <div style="display:flex; flex-direction:column; gap:14px;">
                    <div style="font:13px var(--font-sans); color:var(--ink-6);">Обрані стануть резервами активного запису того ж сайту й виду. Гео успадкується від активного. Дію можна відмінити.</div>
                    @if ($attachCandidates->isEmpty())
                        <div style="padding:16px; border-radius:10px; background:var(--paper-2); font:13px var(--font-sans); color:var(--ink-6);">
                            Немає активних записів того ж сайту й виду, до яких можна приєднати.
                        </div>
                    @else
                        <div>
                            <label style="display:block; font:12px var(--font-mono); color:var(--ink-5); text-transform:uppercase; letter-spacing:.06em;">Активний запис</label>
                            <div style="margin-top:8px; display:flex; flex-direction:column; gap:4px; max-height:300px; overflow-y:auto;">
                                @foreach ($attachCandidates as $c)
                                    @php $on = (int) $attachParent === (int) $c->id; @endphp
                                    <button type="button" wire:click="$set('attachParent', '{{ $c->id }}')"
                                            style="display:flex; align-items:center; gap:10px; width:100%; padding:9px 12px; border-radius:8px; cursor:pointer; text-align:left;
                                                   background:{{ $on ? 'var(--accent-soft)' : 'var(--paper-2)' }}; border:1px solid {{ $on ? 'var(--ink-9)' : 'transparent' }};">
                                        <span class="row-check {{ $on ? 'is-checked' : '' }}">@if($on)<x-icon.check width="11" height="11" />@endif</span>
                                        <span class="mono" style="font:12.5px var(--font-mono); color:var(--ink-9);">{{ $c->value }}</span>
                                        @if($c->label)<span style="font:12px var(--font-sans); color:var(--ink-5);">{{ $c->label }}</span>@endif
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
                <x-slot:footer>
                    <button class="btn btn-ghost" wire:click="closeAttach">Скасувати</button>
                    <button class="btn btn-primary" wire:click="applyAttach" @disabled($attachCandidates->isEmpty())>Приєднати</button>
                </x-slot:footer>
            </x-ui.drawer>
        </div>
    @endif
</div>
