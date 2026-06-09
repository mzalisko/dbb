<div style="flex:1; display:flex; flex-direction:column;">
    <x-ui.topbar :crumbs="['Браузер даних']">
        <x-ui.button variant="secondary" size="sm" wire:click="$toggle('trashed')">
            <x-icon.trash width="13" height="13" /> {{ $trashed ? 'До активних' : 'Кошик' }}
        </x-ui.button>
        @unless ($trashed)
            @if (count($types) > 0 && $mode !== 'wizard')
            <x-ui.button variant="primary" size="sm" wire:click="openCreate">
                <x-icon.plus width="13" height="13" /> Додати
            </x-ui.button>
            @endif
            <x-ui.button variant="secondary" size="sm" wire:click="export">
                <x-icon.export width="13" height="13" /> Експорт
            </x-ui.button>
        @endunless
    </x-ui.topbar>

    {{-- The wizard IS the page. The classic browse list stays only for the trash
         (Кошик) and tests; it's no longer a visible tab. --}}
    @php $wizardOn = $mode === 'wizard' && ! $trashed; @endphp

    @if ($wizardOn)
        @include('livewire.data.wizard')
    @else

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

            {{-- Site filter — single-list (Jira-style): the site picker lives here always, no left rail --}}
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

            {{-- Value picker "Значення ▾" — replaces the old left rail; pick the value to focus on --}}
            @if ($axis === 'value')
                @php
                    $vpRoles = $typeFilter === 'price'
                        ? ['' => 'Усі', 'primary' => 'Основні']
                        : ['' => 'Усі', 'primary' => 'Основні', 'backup' => 'Резерви'];
                @endphp
                <div x-data="{ o:false }" @click.outside="o=false" style="position:relative;">
                    <button type="button" @click="o=!o" aria-label="Фільтр по значенню" style="
                        height:30px; padding:0 12px; border-radius:999px; border:0; cursor:pointer;
                        display:inline-flex; align-items:center; gap:6px; font:12.5px var(--font-sans);
                        background:{{ $pickedValue !== '' ? 'var(--ink-9)' : 'var(--card)' }};
                        color:{{ $pickedValue !== '' ? 'var(--paper)' : 'var(--ink-7)' }};
                        box-shadow:{{ $pickedValue !== '' ? 'none' : 'inset 0 0 0 1px var(--ink-3)' }};">
                        @if ($pickedValue !== '')
                            Значення: <b class="mono">{{ $pickedValue }}{{ $pickedCurrency ? ' '.$pickedCurrency : '' }}</b>
                        @else
                            Значення <span style="opacity:.6;">▾</span>
                        @endif
                    </button>
                    <div x-show="o" x-cloak x-transition.opacity style="position:absolute; z-index:30; top:38px; left:0; width:308px; max-height:400px; overflow-y:auto; background:var(--card); border:1px solid var(--ink-3); border-radius:12px; box-shadow:0 18px 44px -18px rgba(0,0,0,.28);">
                        <div style="position:sticky; top:0; background:var(--card); padding:9px 12px; border-bottom:1px solid var(--ink-2); display:flex; gap:5px; flex-wrap:wrap;">
                            @foreach ($vpRoles as $rk => $rl)
                                <button type="button" wire:click="$set('roleFilter', '{{ $rk }}')" style="
                                    height:22px; padding:0 9px; border-radius:999px; font:11px var(--font-sans); cursor:pointer;
                                    background:{{ $roleFilter === $rk ? 'var(--ink-9)' : 'transparent' }};
                                    color:{{ $roleFilter === $rk ? 'var(--paper)' : 'var(--ink-6)' }};
                                    box-shadow:{{ $roleFilter === $rk ? 'none' : 'inset 0 0 0 1px var(--ink-3)' }};">{{ $rl }}</button>
                            @endforeach
                        </div>
                        @if ($pickedValue !== '')
                            <button type="button" wire:click="clearPick" @click="o=false" style="display:block; width:100%; text-align:left; padding:9px 14px; border:0; border-bottom:1px solid var(--ink-2); background:transparent; cursor:pointer; font:12px var(--font-sans); color:var(--accent);">✕ Очистити вибір значення</button>
                        @endif
                        @forelse ($valueGroups as $g)
                            @php
                                $gcur = $g->currency ?? '';
                                $gdisp = $typeFilter === 'price' ? rtrim(rtrim(number_format((float) $g->gkey, 2, '.', ' '), '0'), '.') : $g->gkey;
                                $on = $pickedValue !== '' && (string) $pickedValue === (string) $g->gkey && (string) $pickedCurrency === (string) $gcur;
                            @endphp
                            <button type="button" wire:key="vgd-{{ md5($g->gkey.'|'.$gcur) }}"
                                    wire:click="pickValue('{{ addslashes($g->gkey) }}', '{{ addslashes($gcur) }}')" @click="o=false"
                                    style="display:flex; align-items:center; gap:9px; width:100%; text-align:left; padding:10px 14px; border:0; border-bottom:1px solid var(--ink-2); cursor:pointer;
                                           background:{{ $on ? 'var(--accent-soft)' : 'transparent' }}; box-shadow:{{ $on ? 'inset 3px 0 0 var(--accent)' : 'none' }};">
                                <span class="mono" style="flex:1; min-width:0; font:12.5px var(--font-mono); color:var(--ink-9); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $gdisp }}{{ $gcur ? ' '.$gcur : '' }}</span>
                                <span class="mono" style="font:10.5px var(--font-mono); color:var(--ink-6); background:var(--ink-2); border-radius:999px; padding:2px 7px; white-space:nowrap;">×{{ $g->n }}</span>
                                <span style="font:10.5px var(--font-sans); color:var(--ink-5); white-space:nowrap;">{{ $g->sites }}&nbsp;с.</span>
                            </button>
                        @empty
                            <div style="padding:26px 14px; text-align:center; color:var(--ink-5); font:12.5px var(--font-sans);">Немає значень.</div>
                        @endforelse
                    </div>
                </div>
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

    {{-- Context header (v3): always make clear WHAT data the manager is working with --}}
    <div style="padding:14px 40px 2px; display:flex; align-items:center; gap:9px; flex-wrap:wrap;">
        @php
            $ctxCount = (($axis === 'value' && $pickedValue !== '') || ($axis === 'site' && $siteFilter !== '')) ? $entries->total() : $totalCount;
            $ctxSite = $siteFilter !== '' ? (optional($sites->firstWhere('id', (int) $siteFilter))->name ?? '') : '';
            $ctxRoles = ['primary' => 'Основні', 'backup' => 'Резерви', 'hidden' => 'Приховані'];
            $chip = 'display:inline-flex; align-items:center; gap:7px; height:25px; padding:0 9px 0 11px; border-radius:999px; background:var(--accent-soft); color:#7a3217; font:12px var(--font-sans); white-space:nowrap;';
            $chipX = 'border:0; background:transparent; color:#7a3217; cursor:pointer; opacity:.6; padding:0; font:12px var(--font-sans);';
        @endphp
        <span style="font:600 16px var(--font-sans); color:var(--ink-9);">{{ $ctxCount }}</span>
        <span style="color:var(--ink-5); font:13px var(--font-sans); margin-right:2px;">{{ $trashed ? 'у кошику' : 'записів' }}</span>

        <span style="{{ $chip }} padding:0 11px;">{{ $types[$typeFilter] ?? $typeFilter }}</span>

        @if ($axis === 'value' && $pickedValue !== '')
            <span style="{{ $chip }}">значення <b class="mono" style="color:var(--ink-9);">{{ $pickedValue }}{{ $pickedCurrency ? ' '.$pickedCurrency : '' }}</b>
                <button type="button" wire:click="clearPick" style="{{ $chipX }}" title="Прибрати фільтр">✕</button>
            </span>
        @endif
        @if ($ctxSite !== '')
            <span style="{{ $chip }}">сайт <b style="color:var(--ink-9);">{{ $ctxSite }}</b>
                @if ($axis === 'value')<button type="button" wire:click="$set('siteFilter', '')" style="{{ $chipX }}" title="Прибрати фільтр">✕</button>@endif
            </span>
        @endif
        @if ($roleFilter !== '' && isset($ctxRoles[$roleFilter]))
            <span style="{{ $chip }}">{{ $ctxRoles[$roleFilter] }}
                <button type="button" wire:click="$set('roleFilter', '')" style="{{ $chipX }}" title="Прибрати фільтр">✕</button>
            </span>
        @endif
        @if ($search !== '')
            <span style="{{ $chip }}">пошук <b style="color:var(--ink-9);">{{ $search }}</b>
                <button type="button" wire:click="$set('search', '')" style="{{ $chipX }}" title="Прибрати">✕</button>
            </span>
        @endif
    </div>

    <div style="padding:14px 40px 64px; flex:1; overflow-y:auto;">
        <div>

            {{-- Single full-width list (Jira-style): no left rail; value picker is "Значення ▾" above --}}
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
                            <span style="font:12.5px var(--font-sans); color:var(--ink-5);">{{ $axis === 'value' ? 'Оберіть значення у фільтрі «Значення ▾»' : 'Оберіть сайт у фільтрі' }}</span>
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
                            $headSelected = $selectAllMatching || in_array($group->id, $selected, true);
                        @endphp
                        <div style="border-top:1px solid var(--ink-2); padding:14px 18px;">
                            <div wire:click="toggleSelected({{ $group->id }})"
                                 style="display:grid; grid-template-columns:32px 14px 1.3fr 1fr auto; gap:12px; align-items:center; margin:-6px -8px; padding:6px 8px; border-radius:6px; cursor:pointer; background:{{ $headSelected ? 'var(--accent-soft)' : 'transparent' }};">
                                <span class="row-check {{ $headSelected ? 'is-checked' : '' }}">
                                    @if ($headSelected) <x-icon.check width="11" height="11" /> @endif
                                </span>
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
                                @php $backupSelected = $selectAllMatching || in_array($b->id, $selected, true); @endphp
                                <div wire:click="toggleSelected({{ $b->id }})"
                                     style="display:grid; grid-template-columns:32px 14px 24px 1.3fr 1fr auto; gap:10px; align-items:center; padding:9px 8px 0 8px; margin:9px -8px 0 -8px; border-top:1px dashed var(--ink-3); border-radius:6px; cursor:pointer; background:{{ $backupSelected ? 'var(--accent-soft)' : 'transparent' }};">
                                    <span class="row-check {{ $backupSelected ? 'is-checked' : '' }}">
                                        @if ($backupSelected) <x-icon.check width="11" height="11" /> @endif
                                    </span>
                                    <span style="color:var(--ink-4); text-align:center;">↳</span>
                                    <span class="mono" style="font:11px var(--font-mono); color:var(--ink-5);">{{ $bi + 1 }}</span>
                                    <span class="mono" style="font:13px var(--font-mono); color:var(--ink-7); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $b->value }}</span>
                                    <span style="display:inline-flex; align-items:center; gap:6px; font:11.5px var(--font-sans); color:var(--info);"><span class="dot dot-info"></span> резерв</span>
                                    <span style="display:inline-flex; align-items:center; gap:4px; justify-content:flex-end;">
                                        <button type="button" wire:click.stop="reorderReserve({{ $b->id }}, 'up')" @disabled($bi === 0)
                                                style="width:26px; height:26px; border-radius:6px; border:1px solid var(--ink-3); background:var(--card); color:var(--ink-6); cursor:pointer; {{ $bi === 0 ? 'opacity:.4; cursor:default;' : '' }}" title="Підняти">↑</button>
                                        <button type="button" wire:click.stop="reorderReserve({{ $b->id }}, 'down')" @disabled($bi === $resCount - 1)
                                                style="width:26px; height:26px; border-radius:6px; border:1px solid var(--ink-3); background:var(--card); color:var(--ink-6); cursor:pointer; {{ $bi === $resCount - 1 ? 'opacity:.4; cursor:default;' : '' }}" title="Опустити">↓</button>
                                        @can('update', $b)
                                            <button type="button" wire:click.stop="makePrimary({{ $b->id }})"
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
                        @php
                            $sortCols = [
                                ['key' => 'value', 'label' => 'Значення'],
                                ['key' => 'site',  'label' => 'Сайт'],
                                ['key' => 'label', 'label' => 'Мітка'],
                                ['key' => null,    'label' => 'Гео'],
                                ['key' => $trashed ? null : 'role', 'label' => $trashed ? 'Видалено' : 'Роль'],
                            ];
                        @endphp
                        @foreach ($sortCols as $c)
                            @if ($c['key'])
                                <button type="button" wire:click.stop="sortBy('{{ $c['key'] }}')"
                                        class="eyebrow" style="font-size:10px; border:0; background:transparent; padding:0; cursor:pointer; display:inline-flex; align-items:center; gap:3px; color:{{ $sortField === $c['key'] ? 'var(--ink-8)' : 'var(--ink-5)' }};"
                                        title="Сортувати">
                                    {{ $c['label'] }}
                                    @if ($sortField === $c['key'])<span style="font:10px var(--font-mono);">{{ $sortDir === 'asc' ? '↑' : '↓' }}</span>@endif
                                </button>
                            @else
                                <span class="eyebrow" style="font-size:10px;">{{ $c['label'] }}</span>
                            @endif
                        @endforeach
                    </div>

            @php $prevSite = null; $groupSizes = collect($entries->items())->countBy('site_id'); @endphp
            @forelse ($entries as $i => $entry)
                @php
                    $sel = $selectAllMatching || in_array($entry->id, $selected, true);
                    $isReserveEntry = ! is_null($entry->parent_id);
                    $firstInGroup = false;
                @endphp
                @if ($groupBySite && $entry->site_id !== $prevSite)
                    @php
                        $prevSite = $entry->site_id; $firstInGroup = true;
                        $gIds = collect($entries->items())->where('site_id', $entry->site_id)->pluck('id')->map(fn ($x) => (int) $x)->all();
                        $gAllSel = $selectAllMatching || ($gIds && empty(array_diff($gIds, $selected)));
                    @endphp
                    <div style="display:flex; align-items:center; gap:10px; padding:9px 18px; background:#fbfaf6; border-top:{{ $i ? '1px solid var(--ink-3)' : 'none' }};">
                        <button type="button" class="row-check {{ $gAllSel ? 'is-checked' : '' }}" wire:click.stop="selectPage(@js($gIds))" title="Обрати весь сайт (на сторінці)">
                            @if ($gAllSel) <x-icon.check width="11" height="11" /> @endif
                        </button>
                        <span class="mono" style="font:12.5px var(--font-mono); color:var(--ink-8); font-weight:600;">{{ $entry->site?->name ?? '—' }}</span>
                        <span style="font:11.5px var(--font-sans); color:var(--ink-5);">· {{ $groupSizes[$entry->site_id] ?? count($gIds) }} записів</span>
                    </div>
                @endif
                <div wire:key="entry-{{ $entry->id }}" wire:click="toggleSelected({{ $entry->id }})"
                     style="display:grid; grid-template-columns:32px 1.4fr 1.6fr 1fr 100px 80px; gap:12px; padding:14px 18px; border-top:{{ ($i && ! $firstInGroup) ? '1px solid var(--ink-3)' : 'none' }}; background:{{ $sel ? 'var(--accent-soft)' : 'transparent' }}; align-items:center; cursor:pointer; transition:background .12s;">
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
                                    @if ($trashed)
                                        <strong>{{ $priceAmount ?? '—' }}</strong>
                                    @else
                                        {{-- Inline edit: click the amount → edit in place --}}
                                        <strong x-data="{ e:false, v:@js($entry->price !== null ? rtrim(rtrim(number_format((float) $entry->price, 2, '.', ''), '0'), '.') : '') }" @click.stop>
                                            <span x-show="!e" @click="e=true;$nextTick(()=>{$refs.pi.focus();$refs.pi.select()})" style="cursor:text; border-bottom:1px dashed transparent;" onmouseover="this.style.borderBottomColor='var(--ink-3)'" onmouseout="this.style.borderBottomColor='transparent'" title="Клік — редагувати ціну">{{ $priceAmount ?? '—' }}</span>
                                            <input x-show="e" x-cloak x-ref="pi" x-model="v" type="text" inputmode="decimal"
                                                   @keydown.enter.stop="$refs.pi.blur()"
                                                   @keydown.escape.stop="e=false"
                                                   @blur="if(e){ $wire.inlineUpdate({{ $entry->id }},'price',v); e=false }"
                                                   style="width:72px; font:13px var(--font-mono); font-weight:600; color:var(--ink-9); border:1px solid var(--ink-3); border-radius:5px; padding:1px 5px; outline:none;" />
                                        </strong>
                                    @endif
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
                        @elseif ($trashed)
                            <span class="mono" style="font:13.5px var(--font-mono); color:var(--ink-6);">{{ $entry->value }}</span>
                        @else
                            {{-- Inline edit: click the value → edit in place (Enter/blur save, Esc cancel) --}}
                            <span x-data="{ e:false, v:@js($entry->value) }" @click.stop style="display:block; min-width:0;">
                                <span x-show="!e" @click="e=true;$nextTick(()=>{$refs.vi.focus();$refs.vi.select()})"
                                      class="mono" style="font:13.5px var(--font-mono); color:var(--ink-9); cursor:text; border-bottom:1px dashed transparent;"
                                      onmouseover="this.style.borderBottomColor='var(--ink-3)'" onmouseout="this.style.borderBottomColor='transparent'"
                                      title="Клік — редагувати">{{ $entry->value }}</span>
                                <input x-show="e" x-cloak x-ref="vi" x-model="v" type="text"
                                       @keydown.enter.stop="$refs.vi.blur()"
                                       @keydown.escape.stop="v=@js($entry->value);e=false"
                                       @blur="if(e){ $wire.inlineUpdate({{ $entry->id }},'value',v); e=false }"
                                       class="mono" style="font:13.5px var(--font-mono); color:var(--ink-9); border:1px solid var(--ink-3); border-radius:6px; padding:2px 6px; outline:none; width:100%;" />
                            </span>
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
                    @if ($trashed)
                        <span style="font:12.5px var(--font-sans); color:var(--ink-7);">{{ $entry->label }}</span>
                    @else
                        {{-- Inline edit: click the label → edit in place --}}
                        <span x-data="{ e:false, v:@js($entry->label ?? '') }" @click.stop style="display:block; min-width:0;">
                            <span x-show="!e" @click="e=true;$nextTick(()=>{$refs.li.focus();$refs.li.select()})"
                                  style="font:12.5px var(--font-sans); color:{{ $entry->label ? 'var(--ink-7)' : 'var(--ink-4)' }}; cursor:text;"
                                  title="Клік — редагувати мітку">{{ $entry->label ?: '— мітка' }}</span>
                            <input x-show="e" x-cloak x-ref="li" x-model="v" type="text" placeholder="мітка"
                                   @keydown.enter.stop="$refs.li.blur()"
                                   @keydown.escape.stop="v=@js($entry->label ?? '');e=false"
                                   @blur="if(e){ $wire.inlineUpdate({{ $entry->id }},'label',v); e=false }"
                                   style="font:12.5px var(--font-sans); color:var(--ink-9); border:1px solid var(--ink-3); border-radius:6px; padding:2px 6px; outline:none; width:100%;" />
                        </span>
                    @endif
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
                            ? 'Оберіть значення у фільтрі «Значення ▾» вгорі, щоб побачити всі входження й змінити вибірково.'
                            : 'Оберіть сайт у фільтрі вгорі, щоб побачити його записи.' }}
                    </div>
                @endif

                {{-- Clean action bar pinned to the BOTTOM of the working set (replaces the old dark top bar) --}}
                @if ($this->hasSelection())
                    @php
                        $mixedType = count($selectionEntities) > 1;
                        $valueHint = $mixedType ? 'Лише для одного виду — обрано: '.implode(' + ', $selectionEntities) : '';
                        $actStyle = 'height:30px; padding:0 11px; border-radius:8px; border:1px solid rgba(255,255,255,.16); background:rgba(255,255,255,.08); color:var(--paper); font:12.5px var(--font-sans); cursor:pointer; white-space:nowrap;';
                        $dangerStyle = $actStyle.' color:#ffb9a3;';
                    @endphp
                    <div style="position:sticky; bottom:0; display:flex; align-items:center; gap:8px; flex-wrap:wrap; padding:11px 16px; background:var(--ink-9); color:var(--paper); box-shadow:0 -8px 22px -12px rgba(0,0,0,.45);">
                        <span class="mono" style="font:600 13px var(--font-mono); color:var(--paper);">{{ $this->selectedCount() }} обрано</span>
                        @if (! $selectAllMatching && $entries->total() > $this->selectedCount())
                            <button type="button" wire:click.stop="selectAllFiltered" style="border:0; background:transparent; color:var(--paper); text-decoration:underline; text-underline-offset:2px; font:12px var(--font-sans); cursor:pointer;">Обрати всі {{ $entries->total() }}</button>
                        @elseif ($selectAllMatching)
                            <span style="font:12px var(--font-sans); color:rgba(255,255,255,.7);">усі {{ $entries->total() }} за фільтром</span>
                        @endif
                        <span style="width:1px; height:16px; background:rgba(255,255,255,.22); margin:0 2px;"></span>
                        <button type="button" style="{{ $actStyle }}" wire:click="openReview">Огляд</button>
                        @if ($trashed)
                            <button type="button" style="{{ $actStyle }}" wire:click="restoreSelected">Відновити</button>
                            <button type="button" style="{{ $dangerStyle }}" wire:click="purgeSelected" wire:confirm="Видалити обрані записи НАЗАВЖДИ? Це не можна відмінити.">Видалити назавжди</button>
                        @else
                            <button type="button" style="{{ $actStyle }} font-weight:500; background:var(--accent); color:var(--paper); border-color:var(--accent);" wire:click="openBulkWizard">⚙ Масова зміна</button>
                            @if (! $selectAllMatching && count($selected) === 1)
                                <button type="button" style="{{ $actStyle }}" wire:click="openAddReserve">+ Додати резерв</button>
                            @endif
                            <button type="button" style="{{ $dangerStyle }}" wire:click="bulkDelete">Видалити</button>
                        @endif
                        <span style="margin-left:auto; display:inline-flex; align-items:center; gap:10px;">
                            <span style="font:12px var(--font-sans); color:rgba(255,255,255,.7);">діє лише на {{ $this->selectedCount() }} обрані · з відміною</span>
                            <button type="button" wire:click.stop="clearSelected" style="border:0; background:transparent; color:rgba(255,255,255,.7); font:12px var(--font-sans); cursor:pointer;">Зняти</button>
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
                            @can('update', $group)
                                <div style="padding:10px 0 0 26px; margin-top:9px; border-top:1px dashed var(--ink-3);">
                                    <button type="button" wire:click="openAddReserveFor({{ $group->id }})"
                                            style="border:0; background:transparent; padding:0; color:var(--accent); font:12px var(--font-sans); cursor:pointer;">+ додати резерв</button>
                                </div>
                            @endcan
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

    {{-- Bulk-change wizard (Jira-style): pick an operation → route to its flow --}}
    @if ($bulkWizard)
        @php
            $wizOps = [
                ['op' => 'field', 'ic' => '⚙', 't' => 'Змінити поле', 'd' => 'Встановити / очистити / знайти-замінити будь-яке поле'],
                ['op' => 'geo', 'ic' => '🌐', 't' => 'Гео-видимість', 'd' => 'Усім / тільки / крім — країни'],
                ['op' => 'state', 'ic' => '⚡', 't' => 'Стан', 'd' => 'Активний / прихований'],
                ['op' => 'move', 'ic' => '↗', 't' => 'Перемістити на сайт', 'd' => 'Разом із резервами'],
                ['op' => 'duplicate', 'ic' => '⧉', 't' => 'Дублювати на сайти', 'd' => 'Копії на інших сайтах'],
                ['op' => 'attach', 'ic' => '🔗', 't' => 'Приєднати як резерв', 'd' => 'Зробити обрані резервами основного'],
                ['op' => 'delete', 'ic' => '🗑', 't' => 'Видалити', 'd' => 'У кошик, з можливістю відновити', 'danger' => true],
            ];
        @endphp
        <div wire:key="bulk-wizard">
            <x-ui.drawer :open="true" title="Масова зміна" :sub="$this->selectedCount() . ' обрано'"
                         @drawer-close.window="$wire.closeBulkWizard()">
                <div style="display:flex; flex-direction:column; gap:8px;">
                    <div style="font:13px var(--font-sans); color:var(--ink-6); margin-bottom:4px;">Що зробити з {{ $this->selectedCount() }} обраними? Дію можна буде відмінити.</div>
                    @foreach ($wizOps as $w)
                        <button type="button" wire:click="wizardTo('{{ $w['op'] }}')"
                                style="display:flex; align-items:center; gap:12px; width:100%; text-align:left; padding:12px 14px; border:1px solid var(--ink-3); border-radius:10px; background:var(--card); cursor:pointer;"
                                onmouseover="this.style.background='var(--paper-2)'" onmouseout="this.style.background='var(--card)'">
                            <span style="width:30px; height:30px; border-radius:8px; background:var(--paper-2); display:inline-flex; align-items:center; justify-content:center; font-size:15px;">{{ $w['ic'] }}</span>
                            <span style="min-width:0;">
                                <span style="display:block; font:13.5px var(--font-sans); color:{{ ($w['danger'] ?? false) ? 'var(--bad)' : 'var(--ink-9)' }};">{{ $w['t'] }}</span>
                                <span style="display:block; font:11.5px var(--font-sans); color:var(--ink-5);">{{ $w['d'] }}</span>
                            </span>
                        </button>
                    @endforeach
                </div>
                <x-slot:footer>
                    <button class="btn btn-ghost" wire:click="closeBulkWizard">Скасувати</button>
                </x-slot:footer>
            </x-ui.drawer>
        </div>
    @endif

    {{-- Generic "change ANY field" drawer — field + operation (set/clear/replace) --}}
    @if ($editingGeneric)
        @php
            $genFields = $this->genericFields();
            $genOps = $this->genericOps($genField);
            $genLabel = $genFields[$genField] ?? $genField;
            $pill = 'height:32px; padding:0 12px; border-radius:8px; font:13px var(--font-sans); cursor:pointer; border:1px solid var(--ink-3); background:var(--card); color:var(--ink-7);';
            $pillOn = 'height:32px; padding:0 12px; border-radius:8px; font:13px var(--font-sans); cursor:pointer; border:1px solid var(--ink-9); background:var(--ink-9); color:var(--paper);';
            $inp = 'margin-top:8px; width:100%; height:44px; padding:0 14px; border-radius:10px; background:var(--card); border:1px solid var(--ink-3); font:14.5px var(--font-sans); color:var(--ink-9); outline:none;';
        @endphp
        <div wire:key="bulk-generic">
            <x-ui.drawer :open="true" title="Змінити поле" :sub="$this->selectedCount() . ' обрано'"
                         @drawer-close.window="$wire.closeGeneric()">
                <div style="display:flex; flex-direction:column; gap:18px;">
                    <div style="display:flex; gap:6px;">
                        <span style="flex:1; height:4px; border-radius:999px; background:var(--accent);"></span>
                        <span style="flex:1; height:4px; border-radius:999px; background:{{ $genStep === 2 ? 'var(--accent)' : 'var(--ink-2)' }};"></span>
                    </div>

                    @if ($genStep === 1)
                        <div>
                            <label style="display:block; font:12px var(--font-mono); color:var(--ink-5); text-transform:uppercase; letter-spacing:.06em;">Поле</label>
                            <div style="margin-top:8px; display:flex; gap:6px; flex-wrap:wrap;">
                                @foreach ($genFields as $fk => $fl)
                                    <button type="button" wire:click="$set('genField', '{{ $fk }}')" style="{{ $genField === $fk ? $pillOn : $pill }}">{{ $fl }}</button>
                                @endforeach
                            </div>
                        </div>
                        <div>
                            <label style="display:block; font:12px var(--font-mono); color:var(--ink-5); text-transform:uppercase; letter-spacing:.06em;">Операція</label>
                            <div style="margin-top:8px; display:flex; gap:6px; flex-wrap:wrap;">
                                @foreach ($genOps as $ok => $ol)
                                    <button type="button" wire:click="$set('genOp', '{{ $ok }}')" style="{{ $genOp === $ok ? $pillOn : $pill }}">{{ $ol }}</button>
                                @endforeach
                            </div>
                        </div>

                        @if ($genOp === 'replace')
                            <div>
                                <label style="display:block; font:12px var(--font-mono); color:var(--ink-5); text-transform:uppercase; letter-spacing:.06em;">Знайти</label>
                                <input type="text" wire:model="genFind" x-init="$nextTick(() => $el.focus())" placeholder="+380" style="{{ $inp }} font-family:var(--font-mono);" />
                            </div>
                            <div>
                                <label style="display:block; font:12px var(--font-mono); color:var(--ink-5); text-transform:uppercase; letter-spacing:.06em;">Замінити на</label>
                                <input type="text" wire:model="genValue" placeholder="+48" style="{{ $inp }} font-family:var(--font-mono);" />
                                <div style="margin-top:8px; font:12px var(--font-sans); color:var(--ink-5);">Порожнє — видалити підрядок. Записи без збігу не зміняться.</div>
                            </div>
                        @elseif ($genOp === 'clear')
                            <div style="font:13px var(--font-sans); color:var(--ink-6);">Поле <b>«{{ $genLabel }}»</b> буде <b>очищено</b> в {{ $this->selectedCount() }} обраних. Дію можна відмінити.</div>
                        @elseif ($genField === 'currency')
                            <div>
                                <label style="display:block; font:12px var(--font-mono); color:var(--ink-5); text-transform:uppercase; letter-spacing:.06em;">Валюта</label>
                                <div style="margin-top:8px; display:flex; gap:8px;">
                                    @foreach (['EUR', 'USD', 'PLN', 'UAH'] as $cur)
                                        <button type="button" wire:click="$set('genValue', '{{ $cur }}')" style="flex:1; height:44px; border-radius:10px; font:14px var(--font-mono); cursor:pointer; border:1px solid {{ $genValue === $cur ? 'var(--ink-9)' : 'var(--ink-3)' }}; background:{{ $genValue === $cur ? 'var(--ink-9)' : 'var(--card)' }}; color:{{ $genValue === $cur ? 'var(--paper)' : 'var(--ink-7)' }};">{{ $cur }}</button>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <div>
                                <label style="display:block; font:12px var(--font-mono); color:var(--ink-5); text-transform:uppercase; letter-spacing:.06em;">Нове значення «{{ $genLabel }}»</label>
                                <input type="text" wire:model="genValue" x-init="$nextTick(() => $el.focus())"
                                       placeholder="{{ in_array($genField, ['price','old_price']) ? 'напр. 199' : 'нове значення' }}" style="{{ $inp }}" />
                                @if (in_array($genField, ['price','old_price','price_unit','label','sku']))
                                    <div style="margin-top:8px; font:12px var(--font-sans); color:var(--ink-5);">Порожнє — прибрати значення.</div>
                                @endif
                            </div>
                        @endif
                    @else
                        <div class="eyebrow" style="font-size:10px;">Крок 2 / 2 · підтвердження</div>
                        <div style="font:13.5px var(--font-sans); color:var(--ink-7); line-height:1.5;">
                            @if ($genOp === 'replace')
                                У полі <b class="mono">«{{ $genLabel }}»</b> замінити
                                <b class="mono" style="color:var(--ink-9);">{{ $genFind }}</b> →
                                <b class="mono" style="color:var(--ink-9);">{{ $genValue !== '' ? $genValue : '∅' }}</b>.
                                Записи без збігу лишаться як є.
                            @elseif ($genOp === 'clear')
                                Поле <b class="mono">«{{ $genLabel }}»</b> буде <b>очищено</b>.
                            @else
                                Поле <b class="mono">«{{ $genLabel }}»</b> →
                                <b class="mono" style="color:var(--ink-9);">{{ trim($genValue) !== '' ? $genValue : '∅ (прибрати)' }}</b>.
                            @endif
                            <br>Застосується до <b>{{ $this->selectedCount() }}</b> обраних. Дію можна відмінити.
                        </div>
                    @endif
                </div>

                <x-slot:footer>
                    @if ($genStep === 1)
                        <button class="btn btn-ghost" wire:click="closeGeneric">Скасувати</button>
                        <button class="btn btn-primary" wire:click="genericConfirm">Далі →</button>
                    @else
                        <button class="btn btn-ghost" wire:click="genericBack">← Назад</button>
                        <button class="btn btn-primary" wire:click="applyGeneric">Застосувати {{ $this->selectedCount() }}</button>
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
                            @php $rstates = ['primary' => ['Активний', 'check'], 'hidden' => ['Прихований', 'eye-off'], 'down' => ['Збій → резерв', 'bolt']]; @endphp
                            @foreach ($rstates as $val => $rs)
                                <button type="button" wire:click="$set('roleValue', '{{ $val }}')"
                                        style="flex:1; height:44px; border-radius:10px; font:13px var(--font-sans); cursor:pointer;
                                               display:inline-flex; align-items:center; justify-content:center; gap:6px;
                                               background:{{ $roleValue === $val ? 'var(--ink-9)' : 'var(--card)' }};
                                               color:{{ $roleValue === $val ? 'var(--paper)' : 'var(--ink-7)' }};
                                               border:1px solid {{ $roleValue === $val ? 'var(--ink-9)' : 'var(--ink-3)' }};">
                                    <x-dynamic-component :component="'icon.'.$rs[1]" width="14" height="14" /> {{ $rs[0] }}
                                </button>
                            @endforeach
                        </div>
                        <div style="margin-top:8px; font:12px var(--font-sans); color:var(--ink-5);">
                            «Прихований» ховає основний <b>разом із резервами</b>. «Збій → резерв» позначає основний як збій — починає віддаватися його резерв. «Активний» розкриває набір і знімає збій (резерв від'єднується в окремий основний). Дію можна відмінити.
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

    {{-- Add brand-new reserve numbers to a primary (type them in) --}}
    @if ($addingReserve)
        <div wire:key="add-reserve">
            <x-ui.drawer :open="true" title="Додати резерв" :sub="'до ' . $reserveParentValue"
                         @drawer-close.window="$wire.closeAddReserve()">
                <div style="display:flex; flex-direction:column; gap:18px;">
                    <div style="font:13px var(--font-sans); color:var(--ink-6);">
                        Нові номери стануть резервами основного
                        <b class="mono" style="color:var(--ink-9);">{{ $reserveParentValue }}</b>
                        на його сайті — у порядку черги failover, гео успадкується. Дію можна відмінити.
                    </div>
                    <div>
                        <label style="display:block; font:12px var(--font-mono); color:var(--ink-5); text-transform:uppercase; letter-spacing:.06em;">Резервні номери · по одному на рядок</label>
                        <textarea wire:model="reserveNumbers" rows="5" x-init="$nextTick(() => $el.focus())"
                                  placeholder="+48 22 000 11 22&#10;+49 30 000 11 22"
                                  style="margin-top:8px; width:100%; padding:12px 14px; border-radius:10px; background:var(--card); border:1px solid var(--ink-3); font:14px var(--font-mono); color:var(--ink-9); outline:none; resize:vertical;"></textarea>
                    </div>
                </div>
                <x-slot:footer>
                    <button class="btn btn-ghost" wire:click="closeAddReserve">Скасувати</button>
                    <button class="btn btn-primary" wire:click="applyAddReserve">Додати</button>
                </x-slot:footer>
            </x-ui.drawer>
        </div>
    @endif

    @endif {{-- /browse mode --}}
</div>
