{{-- v4 full-screen wizard. After Тип the manager picks an intent and the flow
     branches (edit / create / reserve). Steps render by $this->wizKey(). --}}
<div style="padding:8px 40px 72px; flex:1; overflow-y:auto;">

    <div style="display:flex; align-items:flex-start; gap:14px; margin-bottom:16px;">
        <div style="flex:1; min-width:0;">
            <h1 style="font:600 22px var(--font-sans); color:var(--ink-9); margin:0 0 3px;">Дії над даними</h1>
            <div style="font:13px var(--font-sans); color:var(--ink-5);">Зміни, додавай і приєднуй резерви для телефонів та месенджерів — крок за кроком. На сайти відправляється лише після «Готово».</div>
        </div>
        @if ($wizStep > 1)
            <button type="button" wire:click="wizCancel" style="height:32px; padding:0 14px; border-radius:8px; border:1px solid var(--bad-soft); background:var(--card); color:var(--bad); font:12.5px var(--font-sans); cursor:pointer; white-space:nowrap;">✕ Скасувати все</button>
        @endif
    </div>

    {{-- ── STEP RAIL (dynamic per intent) ──────────────────────── --}}
    @php $steps = $this->wizSteps(); @endphp
    <div class="card" style="display:flex; align-items:center; padding:12px 18px; margin-bottom:18px;">
        @foreach ($steps as $idx => $label)
            @php
                $n = $idx + 1; $isOn = $wizStep === $n; $isDone = $wizStep > $n;
                $bBg = $isOn ? 'var(--accent)' : ($isDone ? 'var(--ok)' : 'var(--ink-2)');
                $bFg = ($isOn || $isDone) ? '#fff' : 'var(--ink-6)';
                $tFg = $isOn ? 'var(--ink-9)' : ($isDone ? 'var(--ink-7)' : 'var(--ink-5)');
            @endphp
            <button type="button" wire:click="wizGoto({{ $n }})"
                    style="display:flex; align-items:center; gap:9px; border:0; background:transparent; cursor:pointer; padding:0; min-width:0;">
                <span style="width:26px; height:26px; border-radius:50%; flex-shrink:0; display:inline-flex; align-items:center; justify-content:center; font:12px var(--font-mono); background:{{ $bBg }}; color:{{ $bFg }};">{{ $isDone ? '✓' : $n }}</span>
                <span style="font:12.5px var(--font-sans); color:{{ $tFg }}; font-weight:{{ $isOn ? '600' : '400' }}; white-space:nowrap;">{{ $label }}</span>
            </button>
            @unless ($loop->last)
                <span style="height:2px; flex:1; min-width:12px; margin:0 8px; background:{{ $wizStep > $n ? 'var(--ok)' : 'var(--ink-2)' }};"></span>
            @endunless
        @endforeach
    </div>

    @php
        $key = $this->wizKey();
        $panelHead = 'display:flex; align-items:baseline; gap:10px; padding:15px 20px; border-bottom:1px solid var(--ink-2);';
        $kKey = 'font:11px var(--font-mono); letter-spacing:.07em; text-transform:uppercase; color:var(--accent);';
        $h2 = 'font:600 16px var(--font-sans); margin:0;';
        $hint = 'margin-left:auto; color:var(--ink-5); font:12px var(--font-sans);';
        $pf = 'display:flex; align-items:center; gap:10px; padding:14px 20px; border-top:1px solid var(--ink-2); background:#fbfaf6;';
        $typeIcons = ['phone' => 'phone', 'messenger' => 'chat', 'price' => 'tag', 'social' => 'link', 'address' => 'map', 'custom' => 'settings'];
        $inp = 'height:38px; padding:0 12px; border-radius:9px; border:1px solid var(--ink-3); background:var(--card); color:var(--ink-9); outline:none;';
        $valLabel = ! empty($wizValues) ? (count($wizValues).' значень') : ($pickedValue !== '' ? $pickedValue.($pickedCurrency ? ' '.$pickedCurrency : '') : '');
    @endphp

    {{-- ════ TYPE ════ --}}
    @if ($key === 'type')
        <div class="card" style="overflow:hidden;">
            <div style="{{ $panelHead }}"><span style="{{ $kKey }}">Крок 1</span><h2 style="{{ $h2 }}">Що міняємо?</h2><span style="{{ $hint }}">тип даних</span></div>
            <div style="padding:18px 20px;">
                <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(180px, 1fr)); gap:12px;">
                    @foreach ($types as $tkey => $label)
                        @php $on = $typeFilter === $tkey; @endphp
                        <button type="button" wire:click="$set('typeFilter', '{{ $tkey }}')"
                                style="display:flex; flex-direction:column; gap:5px; padding:16px; border-radius:12px; cursor:pointer; text-align:left;
                                       border:1.5px solid {{ $on ? 'var(--accent)' : 'var(--ink-3)' }}; background:{{ $on ? 'var(--accent-soft)' : 'var(--card)' }};">
                            <span style="display:inline-flex; color:{{ $on ? 'var(--accent)' : 'var(--ink-7)' }};"><x-dynamic-component :component="'icon.'.($typeIcons[$tkey] ?? 'list')" width="22" height="22" /></span>
                            <span style="font:600 14px var(--font-sans); color:var(--ink-9);">{{ $label }}</span>
                        </button>
                    @endforeach
                </div>
                @if (count($kinds) > 0)
                    <div style="display:flex; gap:7px; margin-top:16px; align-items:center; flex-wrap:wrap;">
                        <span style="font:12px var(--font-sans); color:var(--ink-5);">Вид:</span>
                        @foreach ($kinds as $kkey => $klabel)
                            <button type="button" wire:click="$set('kindFilter', '{{ $kkey }}')" style="
                                height:28px; padding:0 12px; border-radius:999px; cursor:pointer; font:12px var(--font-sans);
                                background:{{ $kindFilter === $kkey ? 'var(--ink-9)' : 'var(--card)' }};
                                color:{{ $kindFilter === $kkey ? 'var(--paper)' : 'var(--ink-7)' }};
                                border:1px solid {{ $kindFilter === $kkey ? 'var(--ink-9)' : 'var(--ink-3)' }};">{{ $klabel }}</button>
                        @endforeach
                    </div>
                @endif
            </div>
            <div style="{{ $pf }}"><span style="color:var(--ink-6); font:12.5px var(--font-sans);">Обрано: <b>{{ $types[$typeFilter] ?? $typeFilter }}</b></span><span style="flex:1;"></span><button class="btn btn-primary" wire:click="wizNext">Далі → Намір</button></div>
        </div>
    @endif

    {{-- ════ INTENT ════ --}}
    @if ($key === 'intent')
        @php
            $intentMeta = [
                'edit'    => ['edit', 'Замінити / гео / стан / перемістити / видалити наявні записи'],
                'create'  => ['plus', 'Створити нові записи на одному чи кількох сайтах'],
                'reserve' => ['link', 'Додати новий резерв до основних одразу на N сайтах'],
            ];
        @endphp
        <div class="card" style="overflow:hidden;">
            <div style="{{ $panelHead }}"><span style="{{ $kKey }}">Крок 2</span><h2 style="{{ $h2 }}">Що зробити?</h2><span style="{{ $hint }}">з чого почати</span></div>
            <div style="padding:18px 20px; display:flex; flex-direction:column; gap:10px;">
                @foreach ($this->wizIntentLabels() as $ikey => $ilabel)
                    @php $on = $wizIntent === $ikey; @endphp
                    <button type="button" wire:click="setWizIntent('{{ $ikey }}')"
                            style="display:flex; align-items:center; gap:13px; padding:14px 16px; border-radius:11px; cursor:pointer; text-align:left;
                                   border:1.5px solid {{ $on ? 'var(--accent)' : 'var(--ink-3)' }}; background:{{ $on ? 'var(--accent-soft)' : 'var(--card)' }};">
                        <span style="width:34px; height:34px; border-radius:9px; background:var(--ink-2); display:inline-flex; align-items:center; justify-content:center; color:{{ $on ? 'var(--accent)' : 'var(--ink-7)' }};"><x-dynamic-component :component="'icon.'.$intentMeta[$ikey][0]" width="17" height="17" /></span>
                        <span style="min-width:0;">
                            <span style="display:block; font:600 14px var(--font-sans); color:var(--ink-9);">{{ $ilabel }}</span>
                            <span style="display:block; font:12px var(--font-sans); color:var(--ink-5);">{{ $intentMeta[$ikey][1] }}</span>
                        </span>
                    </button>
                @endforeach
            </div>
            <div style="{{ $pf }}"><button class="btn btn-ghost" wire:click="wizBack">← Назад</button><span style="color:var(--ink-6); font:12.5px var(--font-sans);">Намір: <b>{{ $this->wizIntentLabels()[$wizIntent] }}</b></span><span style="flex:1;"></span><button class="btn btn-primary" wire:click="wizNext">Далі</button></div>
        </div>
    @endif

    {{-- ════ VALUE — multi-select for text types; single for price & reserve ════ --}}
    @if ($key === 'value')
        @php
            $isReserve = $wizIntent === 'reserve';
            $single = ! $this->wizMultiValue();
            $vHead = $isReserve ? 'Який основний запис?' : ($single ? 'Яке значення?' : 'Які значення?');
            $vHint = $isReserve ? 'один основний, до якого додати резерв' : ($single ? 'оберіть одне значення' : 'можна кілька — дія охопить усі їхні входження');
        @endphp
        <div class="card" style="overflow:hidden;">
            <div style="{{ $panelHead }}"><span style="{{ $kKey }}">Крок 3</span><h2 style="{{ $h2 }}">{{ $vHead }}</h2><span style="{{ $hint }}">{{ $vHint }}</span></div>
            <div style="padding:18px 20px;">
                <div style="display:flex; align-items:center; gap:10px; height:42px; padding:0 14px; border-radius:10px; background:var(--paper-2); margin-bottom:14px;">
                    <x-icon.search width="16" height="16" style="color:var(--ink-5);" />
                    <input wire:model.live.debounce.300ms="search" type="text" placeholder="Пошук значення або мітки…"
                           style="flex:1; border:0; outline:none; background:transparent; font:14.5px var(--font-sans); color:var(--ink-7);" />
                </div>
                <div style="border:1px solid var(--ink-2); border-radius:10px; overflow:hidden; max-height:440px; overflow-y:auto;">
                    @forelse ($valueGroups as $g)
                        @php
                            $gcur = $g->currency ?? '';
                            $gdisp = ($typeFilter === 'price' && is_numeric($g->gkey)) ? rtrim(rtrim(number_format((float) $g->gkey, 2, '.', ' '), '0'), '.') : $g->gkey;
                            $on = $single
                                ? ($pickedValue !== '' && (string) $pickedValue === (string) $g->gkey && (string) $pickedCurrency === (string) $gcur)
                                : in_array((string) $g->gkey, $wizValues, true);
                            $prim = (int) ($g->prim ?? 0); $res = (int) ($g->res ?? 0);
                        @endphp
                        <button type="button" wire:key="wv-{{ md5($g->gkey.'|'.$gcur) }}"
                                @if ($single) wire:click="pickValue('{{ addslashes($g->gkey) }}', '{{ addslashes($gcur) }}')" @else wire:click="toggleWizValue('{{ addslashes($g->gkey) }}')" @endif
                                style="display:flex; align-items:center; gap:12px; width:100%; text-align:left; padding:13px 15px; border:0; border-bottom:1px solid var(--ink-2); cursor:pointer;
                                       background:{{ $on ? 'var(--accent-soft)' : 'transparent' }}; box-shadow:{{ $on ? 'inset 3px 0 0 var(--accent)' : 'none' }};">
                            @unless ($single)
                                <span class="row-check {{ $on ? 'is-checked' : '' }}">@if($on)<x-icon.check width="11" height="11" />@endif</span>
                            @endunless
                            <span class="mono" style="flex:1; min-width:0; font:14px var(--font-mono); color:var(--ink-9); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $gdisp }}{{ $gcur ? ' '.$gcur : '' }}</span>
                            <span style="display:inline-flex; align-items:center; gap:9px; white-space:nowrap;">
                                @if ($prim > 0)<span style="display:inline-flex; align-items:center; gap:5px; font:11.5px var(--font-sans); color:var(--ink-6);"><span class="dot dot-ok"></span>{{ $prim }} осн.</span>@endif
                                @if ($res > 0)<span style="display:inline-flex; align-items:center; gap:5px; font:11.5px var(--font-sans); color:var(--info);"><span class="dot dot-info"></span>{{ $res }} рез.</span>@endif
                                <span style="font:11px var(--font-sans); color:var(--ink-4);">{{ $g->sites }}&nbsp;сайтів</span>
                            </span>
                            @if ($single)<span style="display:inline-flex; color:{{ $on ? 'var(--accent)' : 'var(--ink-4)' }};">@if ($on)<x-icon.check width="13" height="13" />@else<x-icon.chev-r width="14" height="14" />@endif</span>@endif
                        </button>
                    @empty
                        <div style="padding:40px; text-align:center; color:var(--ink-5); font:13px var(--font-sans);">{{ $isReserve ? 'Немає основних записів цього типу.' : 'Немає значень для цього типу.' }}</div>
                    @endforelse
                </div>
            </div>
            <div style="{{ $pf }}">
                <button class="btn btn-ghost" wire:click="wizBack">← Назад</button>
                <span style="color:var(--ink-6); font:12.5px var(--font-sans);">
                    @if ($single)
                        @if ($pickedValue !== '')Обрано: <b class="mono">{{ $pickedValue }}{{ $pickedCurrency ? ' '.$pickedCurrency : '' }}</b>@else {{ $isReserve ? 'Оберіть основний' : 'Оберіть значення' }} @endif
                    @else
                        Обрано значень: <b>{{ count($wizValues) }}</b>
                    @endif
                </span>
                <span style="flex:1;"></span>
                <button class="btn btn-primary" wire:click="wizNext" @disabled($single ? $pickedValue === '' : empty($wizValues))>Далі → Сайти</button>
            </div>
        </div>
    @endif

    {{-- ════ SITES / OCCURRENCES (edit + reserve) ════ --}}
    @if ($key === 'sites')
        @php $isReserve = $wizIntent === 'reserve'; @endphp
        <div class="card" style="overflow:hidden;">
            <div style="{{ $panelHead }}"><span style="{{ $kKey }}">Крок 4</span><h2 style="{{ $h2 }}">{{ $isReserve ? 'До яких основних додати резерв?' : 'Де саме застосувати?' }}</h2><span style="{{ $hint }}">{{ $this->selectedCount() }} з {{ $entries->total() }} обрано</span></div>
            <div style="padding:18px 20px;">
                <div style="font:13px var(--font-sans); color:var(--ink-6); margin-bottom:14px;">
                    <b class="mono">{{ $valLabel }}</b> — <b>{{ $entries->total() }} {{ $isReserve ? 'основних' : 'входжень' }}</b>. {{ $isReserve ? 'Познач сайти, де додати резерв.' : 'Познач, де саме застосувати дію — резерви видно під своїм основним.' }}
                </div>
                <div style="display:flex; gap:8px; margin-bottom:14px; flex-wrap:wrap;">
                    <button class="btn btn-ghost" wire:click="selectAllFiltered">☑ Обрати всі {{ $entries->total() }}</button>
                    <button class="btn btn-ghost" wire:click="clearSelected">Зняти</button>
                </div>

                @php $prevSite = null; @endphp
                @forelse ($entries as $i => $e)
                    @php $sel = $selectAllMatching || in_array($e->id, $selected, true); $isRes = ! is_null($e->parent_id); @endphp
                    @if ($e->site_id !== $prevSite)
                        @php
                            $prevSite = $e->site_id;
                            $gIds = collect($entries->items())->where('site_id', $e->site_id)->pluck('id')->map(fn ($x) => (int) $x)->all();
                            $gAll = $selectAllMatching || ($gIds && empty(array_diff($gIds, $selected)));
                        @endphp
                        <div style="display:flex; align-items:center; gap:10px; padding:10px 13px; background:#fbfaf6; border:1px solid var(--ink-2); border-bottom:0; {{ $i ? 'margin-top:10px;' : '' }} border-radius:10px 10px 0 0;">
                            <button type="button" class="row-check {{ $gAll ? 'is-checked' : '' }}" wire:click.stop="selectPage(@js($gIds))" title="Обрати весь сайт">
                                @if ($gAll) <x-icon.check width="11" height="11" /> @endif
                            </button>
                            <span style="font:600 13px var(--font-sans); color:var(--ink-8);">{{ $e->site?->name ?? '—' }}</span>
                            <span style="font:12px var(--font-sans); color:var(--ink-5);">· {{ count($gIds) }} {{ $isReserve ? 'основних' : 'входжень' }}</span>
                        </div>
                    @endif
                    <div wire:key="wo-{{ $e->id }}" wire:click="toggleSelected({{ $e->id }})"
                         style="display:flex; align-items:center; gap:12px; padding:12px 13px; cursor:pointer; border:1px solid var(--ink-2); border-top:0;
                                background:{{ $sel ? 'var(--accent-soft)' : 'var(--card)' }}; {{ $isRes ? 'padding-left:30px;' : '' }}">
                        <span class="row-check {{ $sel ? 'is-checked' : '' }}">@if ($sel) <x-icon.check width="11" height="11" /> @endif</span>
                        <span class="mono" style="flex:1; min-width:0; font:13.5px var(--font-mono); color:var(--ink-9); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $e->value }}</span>
                        <span style="font:12px var(--font-sans); color:var(--ink-5); white-space:nowrap;">{{ $e->label ?: '—' }}</span>
                        <span style="font:12px var(--font-sans); color:var(--ink-5); white-space:nowrap;">{{ $e->geo_label }}</span>
                        <span style="font:11.5px var(--font-sans); white-space:nowrap; display:inline-flex; align-items:center; gap:5px;">
                            @if ($e->role === 'hidden')<span style="color:var(--ink-5);">◌ Прихований</span>
                            @elseif ($isRes)<span class="dot dot-info"></span><span style="color:var(--info);">резерв</span>
                            @else<span class="dot dot-ok"></span><span style="color:var(--ink-6);">основний</span>@endif
                        </span>
                    </div>
                @empty
                    <div style="padding:40px; text-align:center; color:var(--ink-5); font:13px var(--font-sans);">Немає входжень.</div>
                @endforelse

                <div style="margin-top:12px;">{{ $entries->links('livewire.quiet-pagination') }}</div>
            </div>
            <div style="{{ $pf }}">
                <button class="btn btn-ghost" wire:click="wizBack">← Назад</button>
                <span style="color:var(--ink-6); font:12.5px var(--font-sans);">Обрано: <b>{{ $this->selectedCount() }}</b></span>
                <span style="flex:1;"></span>
                <button class="btn btn-primary" wire:click="wizNext" @disabled(! $this->hasSelection())>Далі → {{ $isReserve ? 'Резерви' : 'Дія' }}</button>
            </div>
        </div>
    @endif

    {{-- ════ ACTION (edit) ════ --}}
    @if ($key === 'action')
        @php
            $actMeta = [
                'changes' => ['layers', 'значення + мітка + стан + гео за один раз'],
                'replace' => ['edit', 'нове значення на всіх обраних'], 'substr' => ['refresh', 'напр. код 63 → 67'],
                'label' => ['tag', 'підпис біля значення'], 'geo' => ['globe', 'усім / тільки / крім'],
                'state' => ['bolt', 'активний / прихований / на резерв'], 'move' => ['arrow-right', 'на інший сайт'],
                'duplicate' => ['copy', 'копії на сайти'], 'delete' => ['trash', 'у кошик, оборотно'],
            ];
        @endphp
        <div class="card" style="overflow:hidden;">
            <div style="{{ $panelHead }}"><span style="{{ $kKey }}">Крок 5</span><h2 style="{{ $h2 }}">Яка дія?</h2><span style="{{ $hint }}">над {{ $this->selectedCount() }} обраними</span></div>
            <div style="padding:18px 20px;">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:9px;">
                    @foreach ($this->wizActionLabels() as $akey => $alabel)
                        @php $on = $wizAction === $akey; $bad = $akey === 'delete'; @endphp
                        <button type="button" wire:click="setWizAction('{{ $akey }}')"
                                style="display:flex; align-items:center; gap:11px; padding:12px 13px; border-radius:10px; cursor:pointer; text-align:left;
                                       border:1.5px solid {{ $on ? ($bad ? 'var(--bad)' : 'var(--accent)') : 'var(--ink-3)' }};
                                       background:{{ $on ? ($bad ? 'var(--bad-soft)' : 'var(--accent-soft)') : 'var(--card)' }};">
                            <span style="width:30px; height:30px; border-radius:8px; background:var(--ink-2); display:inline-flex; align-items:center; justify-content:center; color:{{ $on ? ($bad ? 'var(--bad)' : 'var(--accent)') : 'var(--ink-7)' }};"><x-dynamic-component :component="'icon.'.($actMeta[$akey][0] ?? 'list')" width="15" height="15" /></span>
                            <span style="min-width:0;">
                                <span style="display:block; font:13px var(--font-sans); color:var(--ink-9);">{{ $alabel }}</span>
                                <span style="display:block; font:11.5px var(--font-sans); color:var(--ink-5);">{{ $actMeta[$akey][1] ?? '' }}</span>
                            </span>
                        </button>
                    @endforeach
                </div>

                @if ($wizAction !== '')
                    <div style="margin-top:16px; padding:16px; border:1px solid var(--ink-2); border-radius:10px; background:var(--paper-2);">
                        @if ($wizAction === 'changes')
                            @php $chgFields = ['value' => 'Значення', 'label' => 'Мітка', 'state' => 'Стан', 'geo' => 'Гео']; @endphp
                            <div style="font:12px var(--font-sans); color:var(--ink-6); margin-bottom:10px;">Познач, що змінити — застосується разом, одним підтвердженням (одне «Відмінити»):</div>
                            <div style="display:flex; flex-direction:column; gap:9px;">
                                @foreach ($chgFields as $fk => $fl)
                                    @php $fon = in_array($fk, $chg ?? [], true); @endphp
                                    <div style="border:1px solid {{ $fon ? 'var(--accent)' : 'var(--ink-3)' }}; border-radius:10px; padding:10px 12px; background:{{ $fon ? 'var(--accent-soft)' : 'var(--card)' }};">
                                        <button type="button" wire:click="toggleChg('{{ $fk }}')" style="display:flex; align-items:center; gap:9px; width:100%; border:0; background:transparent; cursor:pointer; text-align:left; padding:0;">
                                            <span class="row-check {{ $fon ? 'is-checked' : '' }}">@if($fon)<x-icon.check width="11" height="11" />@endif</span>
                                            <span style="font:13px var(--font-sans); color:var(--ink-9);">{{ $fl }}</span>
                                        </button>
                                        @if ($fon)
                                            <div style="margin-top:10px; padding-left:27px;">
                                                @if ($fk === 'value')
                                                    <input wire:model="editValue" type="text" placeholder="нове значення" style="{{ $inp }} width:100%; font-family:var(--font-mono);" />
                                                @elseif ($fk === 'label')
                                                    <input wire:model="chgLabel" type="text" placeholder="нова мітка (порожнє — прибрати)" style="{{ $inp }} width:100%;" />
                                                @elseif ($fk === 'state')
                                                    @php $sts = ['primary' => ['Активний', 'check'], 'hidden' => ['Прихований (весь набір)', 'eye-off'], 'down' => ['На резерв', 'bolt']]; @endphp
                                                    <div style="display:flex; gap:7px; flex-wrap:wrap;">
                                                        @foreach ($sts as $rk => $rs)
                                                            <button type="button" wire:click="$set('roleValue', '{{ $rk }}')" style="height:32px; padding:0 12px; border-radius:8px; cursor:pointer; font:12.5px var(--font-sans); display:inline-flex; align-items:center; gap:6px; background:{{ $roleValue === $rk ? 'var(--ink-9)' : 'var(--card)' }}; color:{{ $roleValue === $rk ? 'var(--paper)' : 'var(--ink-7)' }}; border:1px solid {{ $roleValue === $rk ? 'var(--ink-9)' : 'var(--ink-3)' }};"><x-dynamic-component :component="'icon.'.$rs[1]" width="12" height="12" /> {{ $rs[0] }}</button>
                                                        @endforeach
                                                    </div>
                                                @elseif ($fk === 'geo')
                                                    <div style="display:flex; gap:7px; margin-bottom:8px;">
                                                        @foreach (['all' => 'Усім', 'only' => 'Тільки', 'except' => 'Крім'] as $mk => $ml)
                                                            <button type="button" wire:click="$set('geoMode', '{{ $mk }}')" style="height:30px; padding:0 12px; border-radius:8px; cursor:pointer; font:12px var(--font-sans); background:{{ $geoMode === $mk ? 'var(--ink-9)' : 'var(--card)' }}; color:{{ $geoMode === $mk ? 'var(--paper)' : 'var(--ink-7)' }}; border:1px solid {{ $geoMode === $mk ? 'var(--ink-9)' : 'var(--ink-3)' }};">{{ $ml }}</button>
                                                        @endforeach
                                                    </div>
                                                    @if ($geoMode !== 'all')<input wire:model="geoCountries" type="text" placeholder="UA, PL, DE" style="{{ $inp }} width:100%; font-family:var(--font-mono);" />@endif
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @elseif ($wizAction === 'replace')
                            <label style="display:block; font:12px var(--font-sans); color:var(--ink-6); margin-bottom:6px;">Нове значення для {{ $this->selectedCount() }} обраних:</label>
                            <input wire:model="editValue" type="text" placeholder="нове значення" style="{{ $inp }} width:100%; font-family:var(--font-mono);" />
                        @elseif ($wizAction === 'label')
                            <label style="display:block; font:12px var(--font-sans); color:var(--ink-6); margin-bottom:6px;">Нова мітка для обраних:</label>
                            <input wire:model="editValue" type="text" placeholder="напр. Менеджер з продажу" style="{{ $inp }} width:100%;" />
                        @elseif ($wizAction === 'substr')
                            <label style="display:block; font:12px var(--font-sans); color:var(--ink-6); margin-bottom:6px;">Замінити підрядок у значенні:</label>
                            <div style="display:flex; align-items:center; gap:10px;"><input wire:model="findText" type="text" placeholder="знайти" style="{{ $inp }} flex:1; font-family:var(--font-mono);" /><span style="color:var(--ink-4); font-size:16px;">→</span><input wire:model="replaceText" type="text" placeholder="замінити на" style="{{ $inp }} flex:1; font-family:var(--font-mono);" /></div>
                        @elseif ($wizAction === 'geo')
                            <label style="display:block; font:12px var(--font-sans); color:var(--ink-6); margin-bottom:8px;">Кому показувати:</label>
                            <div style="display:flex; gap:7px; margin-bottom:10px;">
                                @foreach (['all' => 'Усім', 'only' => 'Тільки', 'except' => 'Крім'] as $mk => $ml)
                                    <button type="button" wire:click="$set('geoMode', '{{ $mk }}')" style="height:32px; padding:0 14px; border-radius:8px; cursor:pointer; font:12.5px var(--font-sans); background:{{ $geoMode === $mk ? 'var(--ink-9)' : 'var(--card)' }}; color:{{ $geoMode === $mk ? 'var(--paper)' : 'var(--ink-7)' }}; border:1px solid {{ $geoMode === $mk ? 'var(--ink-9)' : 'var(--ink-3)' }};">{{ $ml }}</button>
                                @endforeach
                            </div>
                            @if ($geoMode !== 'all')<input wire:model="geoCountries" type="text" placeholder="країни через кому, напр. UA, PL, DE" style="{{ $inp }} width:100%; font-family:var(--font-mono);" />@endif
                        @elseif ($wizAction === 'state')
                            <label style="display:block; font:12px var(--font-sans); color:var(--ink-6); margin-bottom:8px;">Новий стан обраних:</label>
                            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                                @php $states = ['primary' => ['Активний', 'check'], 'hidden' => ['Прихований (весь набір)', 'eye-off'], 'down' => ['На резерв', 'bolt']]; @endphp
                                @foreach ($states as $rk => $rs)
                                    <button type="button" wire:click="$set('roleValue', '{{ $rk }}')" style="height:34px; padding:0 14px; border-radius:8px; cursor:pointer; font:13px var(--font-sans); display:inline-flex; align-items:center; gap:7px; background:{{ $roleValue === $rk ? 'var(--ink-9)' : 'var(--card)' }}; color:{{ $roleValue === $rk ? 'var(--paper)' : 'var(--ink-7)' }}; border:1px solid {{ $roleValue === $rk ? 'var(--ink-9)' : 'var(--ink-3)' }};"><x-dynamic-component :component="'icon.'.$rs[1]" width="13" height="13" /> {{ $rs[0] }}</button>
                                @endforeach
                            </div>
                            <div style="margin-top:8px; font:11.5px var(--font-sans); color:var(--ink-5);">
                                @if ($roleValue === 'hidden')Приховає основний разом з його резервами.
                                @elseif ($roleValue === 'down')Замість основного почне віддаватися його резерв.
                                @else Розкриє набір і зніме позначку збою.@endif
                            </div>
                        @elseif ($wizAction === 'move')
                            <label style="display:block; font:12px var(--font-sans); color:var(--ink-6); margin-bottom:8px;">Сайт призначення:</label>
                            <div style="display:flex; flex-direction:column; gap:4px; max-height:260px; overflow-y:auto;">
                                @foreach ($sites as $s)
                                    @php $on = (int) $moveSite === (int) $s->id; @endphp
                                    <button type="button" wire:click="$set('moveSite', '{{ $s->id }}')" style="display:flex; align-items:center; gap:10px; padding:9px 12px; border-radius:8px; cursor:pointer; text-align:left; background:{{ $on ? 'var(--accent-soft)' : 'var(--card)' }}; border:1px solid {{ $on ? 'var(--ink-9)' : 'var(--ink-3)' }};">
                                        <span class="row-check {{ $on ? 'is-checked' : '' }}">@if($on)<x-icon.check width="11" height="11" />@endif</span><span style="font:13px var(--font-sans); color:var(--ink-8);">{{ $s->name }}</span>
                                    </button>
                                @endforeach
                            </div>
                        @elseif ($wizAction === 'duplicate')
                            <label style="display:block; font:12px var(--font-sans); color:var(--ink-6); margin-bottom:8px;">Куди скопіювати · обрано {{ count($dupSites) }}:</label>
                            <div style="display:flex; flex-direction:column; gap:4px; max-height:260px; overflow-y:auto;">
                                @foreach ($sites as $s)
                                    @php $on = in_array($s->id, $dupSites); @endphp
                                    <button type="button" wire:click="toggleDupSite({{ $s->id }})" style="display:flex; align-items:center; gap:10px; padding:9px 12px; border-radius:8px; cursor:pointer; text-align:left; background:{{ $on ? 'var(--accent-soft)' : 'var(--card)' }}; border:1px solid {{ $on ? 'var(--ink-9)' : 'var(--ink-3)' }};">
                                        <span class="row-check {{ $on ? 'is-checked' : '' }}">@if($on)<x-icon.check width="11" height="11" />@endif</span><span style="font:13px var(--font-sans); color:var(--ink-8);">{{ $s->name }}</span>
                                    </button>
                                @endforeach
                            </div>
                        @elseif ($wizAction === 'delete')
                            <div style="font:13px var(--font-sans); color:var(--ink-7);">Обрані <b>{{ $this->selectedCount() }}</b> переїдуть у <b>кошик</b> — оборотно (кнопка «Відмінити» або кошик).</div>
                        @endif
                    </div>
                @endif
            </div>
            <div style="{{ $pf }}"><button class="btn btn-ghost" wire:click="wizBack">← Назад</button><span style="color:var(--ink-6); font:12.5px var(--font-sans);">@if ($wizAction !== '')Дія: <b>{{ $this->wizActionLabels()[$wizAction] }}</b>@else Оберіть дію @endif</span><span style="flex:1;"></span><button class="btn btn-primary" wire:click="wizNext" @disabled($wizAction === '')>Далі → Підтвердити</button></div>
        </div>
    @endif

    {{-- ════ DATA (create) ════ --}}
    @if ($key === 'data')
        <div class="card" style="overflow:hidden;">
            <div style="{{ $panelHead }}"><span style="{{ $kKey }}">Крок 3</span><h2 style="{{ $h2 }}">Нові дані</h2><span style="{{ $hint }}">{{ $types[$typeFilter] ?? $typeFilter }}@if ($kindFilter) · {{ $kinds[$kindFilter] ?? $kindFilter }}@endif</span></div>
            <div style="padding:18px 20px; display:flex; flex-direction:column; gap:16px;">
                <div><label style="display:block; font:12px var(--font-sans); color:var(--ink-6); margin-bottom:6px;">Значення (номер / контакт):</label><input wire:model="createValue" type="text" placeholder="напр. +380 63 000 11 22" style="{{ $inp }} width:100%; font-family:var(--font-mono); font-size:14.5px;" /></div>
                <div><label style="display:block; font:12px var(--font-sans); color:var(--ink-6); margin-bottom:6px;">Мітка (необов'язково):</label><input wire:model="createLabel" type="text" placeholder="напр. Відділ продажу" style="{{ $inp }} width:100%;" /></div>
                <div>
                    <label style="display:block; font:12px var(--font-sans); color:var(--ink-6); margin-bottom:8px;">Стан:</label>
                    <div style="display:flex; gap:8px;">
                        @foreach (['primary' => '● Активний', 'hidden' => '◌ Прихований'] as $rk => $rl)
                            <button type="button" wire:click="$set('createRole', '{{ $rk }}')" style="height:34px; padding:0 16px; border-radius:8px; cursor:pointer; font:13px var(--font-sans); background:{{ $createRole === $rk ? 'var(--ink-9)' : 'var(--card)' }}; color:{{ $createRole === $rk ? 'var(--paper)' : 'var(--ink-7)' }}; border:1px solid {{ $createRole === $rk ? 'var(--ink-9)' : 'var(--ink-3)' }};">{{ $rl }}</button>
                        @endforeach
                    </div>
                </div>
            </div>
            <div style="{{ $pf }}"><button class="btn btn-ghost" wire:click="wizBack">← Назад</button><span style="flex:1;"></span><button class="btn btn-primary" wire:click="wizNext">Далі → Сайти</button></div>
        </div>
    @endif

    {{-- ════ CSITES (create — where) ════ --}}
    @if ($key === 'csites')
        <div class="card" style="overflow:hidden;">
            <div style="{{ $panelHead }}"><span style="{{ $kKey }}">Крок 4</span><h2 style="{{ $h2 }}">На яких сайтах створити?</h2><span style="{{ $hint }}">обрано {{ count($createSites) }}</span></div>
            <div style="padding:18px 20px;">
                <div style="font:13px var(--font-sans); color:var(--ink-6); margin-bottom:12px;">Запис <b class="mono">{{ $createValue }}</b> буде створено на кожному обраному сайті.</div>
                <div style="display:flex; flex-direction:column; gap:4px; max-height:380px; overflow-y:auto;">
                    @foreach ($sites as $s)
                        @php $on = in_array($s->id, $createSites); @endphp
                        <button type="button" wire:click="toggleCreateSite({{ $s->id }})" style="display:flex; align-items:center; gap:10px; padding:10px 13px; border-radius:9px; cursor:pointer; text-align:left; background:{{ $on ? 'var(--accent-soft)' : 'var(--card)' }}; border:1px solid {{ $on ? 'var(--ink-9)' : 'var(--ink-3)' }};">
                            <span class="row-check {{ $on ? 'is-checked' : '' }}">@if($on)<x-icon.check width="11" height="11" />@endif</span><span style="font:13px var(--font-sans); color:var(--ink-8);">{{ $s->name }}</span>
                        </button>
                    @endforeach
                </div>
            </div>
            <div style="{{ $pf }}"><button class="btn btn-ghost" wire:click="wizBack">← Назад</button><span style="color:var(--ink-6); font:12.5px var(--font-sans);">Сайтів: <b>{{ count($createSites) }}</b></span><span style="flex:1;"></span><button class="btn btn-primary" wire:click="wizNext" @disabled(empty($createSites))>Далі → Підтвердити</button></div>
        </div>
    @endif

    {{-- ════ RESNUMS (reserve — the new numbers) ════ --}}
    @if ($key === 'resnums')
        <div class="card" style="overflow:hidden;">
            <div style="{{ $panelHead }}"><span style="{{ $kKey }}">Крок 5</span><h2 style="{{ $h2 }}">Резервні номери</h2><span style="{{ $hint }}">до {{ $this->selectedCount() }} основних</span></div>
            <div style="padding:18px 20px;">
                <div style="font:13px var(--font-sans); color:var(--ink-6); margin-bottom:10px;">Ці номери стануть резервами основного <b class="mono">{{ $pickedValue }}</b> на кожному з <b>{{ $this->selectedCount() }}</b> обраних сайтів (черга failover, гео успадкується). По одному на рядок.</div>
                <textarea wire:model="reserveNumbers" rows="5" placeholder="+48 22 000 11 22&#10;+49 30 000 11 22"
                          style="width:100%; padding:12px 14px; border-radius:10px; background:var(--card); border:1px solid var(--ink-3); font:14px var(--font-mono); color:var(--ink-9); outline:none; resize:vertical;"></textarea>
            </div>
            <div style="{{ $pf }}"><button class="btn btn-ghost" wire:click="wizBack">← Назад</button><span style="flex:1;"></span><button class="btn btn-primary" wire:click="wizNext">Далі → Підтвердити</button></div>
        </div>
    @endif

    {{-- ════ CONFIRM (all intents) ════ --}}
    @if ($key === 'confirm')
        @php
            $n = $this->selectedCount();
            $resList = collect(preg_split('/[\r\n]+/', trim($reserveNumbers)) ?: [])->map(fn ($v) => trim($v))->filter()->implode(', ');
            if ($wizIntent === 'create') {
                $summary = 'Створити «'.trim($createValue).'»'.(trim($createLabel) !== '' ? ' ('.trim($createLabel).')' : '').' на '.count($createSites).' сайтах як «'.($createRole === 'hidden' ? 'Прихований' : 'Активний').'».';
            } elseif ($wizIntent === 'reserve') {
                $summary = 'Додати резерв(и) «'.$resList.'» до '.$n.' основних «'.$pickedValue.'».';
            } else {
                $summary = match ($wizAction) {
                    'changes'   => 'Кілька змін на '.$n.' входженнях: '.collect($chg ?? [])->map(fn ($f) => ['value' => 'значення', 'label' => 'мітка', 'state' => 'стан', 'geo' => 'гео'][$f] ?? $f)->implode(', ').'.',
                    'replace'   => 'Встановити значення «'.trim($editValue).'» на '.$n.' входженнях.',
                    'label'     => 'Змінити мітку на '.$n.' входженнях на «'.trim($editValue).'».',
                    'substr'    => 'У '.$n.' входженнях замінити підрядок «'.$findText.'» → «'.$replaceText.'».',
                    'geo'       => 'Змінити гео на '.$n.' входженнях ('.($geoMode === 'all' ? 'усім' : ($geoMode === 'only' ? 'тільки: '.$geoCountries : 'крім: '.$geoCountries)).').',
                    'state'     => 'Змінити стан на '.$n.' входженнях: «'.(['primary' => 'Активний', 'hidden' => 'Прихований (весь набір)', 'down' => 'На резерв'][$roleValue] ?? 'Активний').'».',
                    'move'      => 'Перемістити '.$n.' входжень на обраний сайт (з резервами).',
                    'duplicate' => 'Скопіювати '.$n.' входжень на '.count($dupSites).' сайтів.',
                    'delete'    => 'Видалити '.$n.' входжень у кошик.',
                    default     => '',
                };
            }
            $confirmStep = count($steps);
        @endphp
        <div class="card" style="overflow:hidden;">
            <div style="{{ $panelHead }}"><span style="{{ $kKey }}">Крок {{ $confirmStep }}</span><h2 style="{{ $h2 }}">Підтвердити</h2><span style="{{ $hint }}">останній перегляд</span></div>
            <div style="padding:18px 20px;">
                <div style="padding:16px 18px; border:1px solid var(--accent-soft); background:var(--accent-soft); border-radius:12px; margin-bottom:14px;">
                    <div style="font:600 15px var(--font-sans); color:var(--ink-9); line-height:1.5;">{{ $summary }}</div>
                    <div style="font:12.5px var(--font-sans); color:#7a3217; margin-top:6px;">Дію буде записано в журнал і можна одразу <b>відмінити</b>.</div>
                </div>
                @if ($wizIntent !== 'create')
                    <div style="border:1px solid var(--ink-2); border-radius:10px; overflow:hidden; max-height:300px; overflow-y:auto;">
                        @foreach ($entries as $e)
                            @php $sel = $selectAllMatching || in_array($e->id, $selected, true); @endphp
                            @if ($sel)
                                <div style="display:flex; align-items:center; gap:10px; padding:9px 13px; border-bottom:1px solid var(--ink-2); font:12.5px var(--font-sans); color:var(--ink-7);">
                                    <span class="dot {{ is_null($e->parent_id) ? 'dot-ok' : 'dot-info' }}"></span>
                                    <span style="color:var(--ink-6);">{{ $e->site?->name ?? '—' }}</span>
                                    <span class="mono" style="margin-left:auto; color:var(--ink-9);">{{ $e->value }}</span>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @else
                    <div style="border:1px solid var(--ink-2); border-radius:10px; overflow:hidden; max-height:300px; overflow-y:auto;">
                        @foreach ($sites as $s)
                            @if (in_array($s->id, $createSites))
                                <div style="display:flex; align-items:center; gap:10px; padding:9px 13px; border-bottom:1px solid var(--ink-2); font:12.5px var(--font-sans); color:var(--ink-7);">
                                    <span class="dot dot-ok"></span><span style="color:var(--ink-6);">{{ $s->name }}</span>
                                    <span class="mono" style="margin-left:auto; color:var(--ink-9);">{{ $createValue }}</span>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>
            <div style="{{ $pf }}">
                <button class="btn btn-ghost" wire:click="wizBack">← Назад</button>
                <span style="flex:1;"></span>
                @if ($wizIntent !== 'create')
                    <button class="btn btn-ghost" wire:click="wizExport" title="Завантажити обрані у CSV"><x-icon.download width="14" height="14" /> Завантажити</button>
                @endif
                <span style="color:var(--ink-5); font:12px var(--font-sans); margin:0 4px;">з відміною</span>
                <button class="btn btn-primary" wire:click="wizConfirm" style="background:var(--accent); border-color:var(--accent);">Підтвердити та застосувати</button>
            </div>
        </div>
    @endif

    {{-- ════ ORDER (reserve — set the failover queue after attaching) ════ --}}
    @if ($key === 'order')
        @php $mine = $reserveGroups->filter(fn ($g) => trim((string) $g->value) === trim((string) $pickedValue)); @endphp
        <div class="card" style="overflow:hidden;">
            <div style="{{ $panelHead }}"><span style="font:11px var(--font-mono); letter-spacing:.07em; text-transform:uppercase; color:var(--ok);">Готово ✓</span><h2 style="{{ $h2 }}">Порядок резервів</h2><span style="{{ $hint }}">{{ $mine->count() }} основних</span></div>
            <div style="padding:18px 20px;">
                <div style="padding:12px 14px; border:1px solid var(--ok); background:#eef5e8; border-radius:10px; margin-bottom:14px; font:13px var(--font-sans); color:var(--ink-8);">
                    ✓ Резерви додано до <b class="mono">{{ $pickedValue }}</b>. Тепер задай їхній порядок (черга failover): ↑↓ — підняти/опустити, «→ основним» — від'єднати в окремий основний.
                </div>
                @forelse ($mine as $g)
                    @php $rc = $g->backups->count(); @endphp
                    <div style="border:1px solid var(--ink-2); border-radius:10px; margin-bottom:10px; overflow:hidden;">
                        <div style="display:flex; align-items:center; gap:10px; padding:11px 14px; background:#fbfaf6; border-bottom:1px solid var(--ink-2);">
                            <span class="dot dot-ok"></span>
                            <span class="mono" style="font:13.5px var(--font-mono); color:var(--ink-9);">{{ $g->value }}</span>
                            <span style="font:12px var(--font-sans); color:var(--ink-5);">{{ $g->site?->name ?? '—' }} · {{ $rc }} рез.</span>
                        </div>
                        @foreach ($g->backups as $bi => $b)
                            <div style="display:flex; align-items:center; gap:11px; padding:10px 14px; border-bottom:1px solid var(--ink-2);">
                                <span class="mono" style="font:11px var(--font-mono); color:var(--ink-5); width:16px;">{{ $bi + 1 }}</span>
                                <span style="color:var(--ink-4);">↳</span>
                                <span class="mono" style="flex:1; min-width:0; font:13px var(--font-mono); color:var(--ink-7); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $b->value }}</span>
                                <button type="button" wire:click="reorderReserve({{ $b->id }}, 'up')" @disabled($bi === 0) style="width:28px; height:28px; border-radius:7px; border:1px solid var(--ink-3); background:var(--card); color:var(--ink-6); cursor:pointer; {{ $bi === 0 ? 'opacity:.4;' : '' }}" title="Підняти">↑</button>
                                <button type="button" wire:click="reorderReserve({{ $b->id }}, 'down')" @disabled($bi === $rc - 1) style="width:28px; height:28px; border-radius:7px; border:1px solid var(--ink-3); background:var(--card); color:var(--ink-6); cursor:pointer; {{ $bi === $rc - 1 ? 'opacity:.4;' : '' }}" title="Опустити">↓</button>
                                <button type="button" wire:click="makePrimary({{ $b->id }})" style="height:28px; padding:0 10px; border-radius:7px; border:1px solid var(--ink-3); background:var(--card); color:var(--accent); cursor:pointer; font:11.5px var(--font-sans);" title="Від'єднати в окремий основний">→ основним</button>
                            </div>
                        @endforeach
                        @if ($rc === 0)
                            <div style="padding:10px 14px; font:12px var(--font-sans); color:var(--ink-4);">Резервів поки немає.</div>
                        @endif
                    </div>
                @empty
                    <div style="padding:30px; text-align:center; color:var(--ink-5); font:13px var(--font-sans);">Резерви додано. Список з'явиться після оновлення.</div>
                @endforelse
            </div>
            <div style="{{ $pf }}"><span style="color:var(--ink-6); font:12.5px var(--font-sans);">Порядок зберігається одразу при ↑↓</span><span style="flex:1;"></span><button class="btn btn-primary" wire:click="wizFinish" style="background:var(--accent); border-color:var(--accent);">✓ Готово</button></div>
        </div>
    @endif

</div>
