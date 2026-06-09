{{-- v4 full-screen step wizard: Тип → Значення → Сайти → Дія → Підтвердити.
     Drives the existing audited apply* methods via WithWizard. --}}
<div style="padding:8px 40px 72px; flex:1; overflow-y:auto;">

    <div style="margin-bottom:16px;">
        <h1 style="font:600 22px var(--font-sans); color:var(--ink-9); margin:0 0 3px;">Дії над даними</h1>
        <div style="font:13px var(--font-sans); color:var(--ink-5);">Зміни телефони та месенджери на ваших сайтах — крок за кроком, з відміною.</div>
    </div>

    {{-- ── STEP RAIL ───────────────────────────────────────────── --}}
    @php
        $steps = [1 => 'Тип', 2 => 'Значення', 3 => 'Сайти', 4 => 'Дія', 5 => 'Підтвердити'];
    @endphp
    <div class="card" style="display:flex; align-items:center; padding:12px 18px; margin-bottom:18px;">
        @foreach ($steps as $n => $label)
            @php
                $isOn = $wizStep === $n; $isDone = $wizStep > $n;
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
                <span style="height:2px; flex:1; min-width:14px; margin:0 8px; background:{{ $wizStep > $n ? 'var(--ok)' : 'var(--ink-2)' }};"></span>
            @endunless
        @endforeach
    </div>

    @php
        $panelHead = 'display:flex; align-items:baseline; gap:10px; padding:15px 20px; border-bottom:1px solid var(--ink-2);';
        $kKey = 'font:11px var(--font-mono); letter-spacing:.07em; text-transform:uppercase; color:var(--accent);';
        $pf = 'display:flex; align-items:center; gap:10px; padding:14px 20px; border-top:1px solid var(--ink-2); background:#fbfaf6;';
        $typeIcons = ['phone' => '📞', 'messenger' => '💬', 'price' => '🏷️', 'social' => '🔗', 'address' => '📍', 'custom' => '⚙️'];
    @endphp

    {{-- ── STEP 1 — ТИП ───────────────────────────────────────── --}}
    @if ($wizStep === 1)
        <div class="card" style="overflow:hidden;">
            <div style="{{ $panelHead }}"><span style="{{ $kKey }}">Крок 1</span><h2 style="font:600 16px var(--font-sans); margin:0;">Що міняємо?</h2><span style="margin-left:auto; color:var(--ink-5); font:12px var(--font-sans);">тип даних</span></div>
            <div style="padding:18px 20px;">
                <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(180px, 1fr)); gap:12px;">
                    @foreach ($types as $key => $label)
                        @php $on = $typeFilter === $key; @endphp
                        <button type="button" wire:click="$set('typeFilter', '{{ $key }}')"
                                style="display:flex; flex-direction:column; gap:5px; padding:16px; border-radius:12px; cursor:pointer; text-align:left;
                                       border:1.5px solid {{ $on ? 'var(--accent)' : 'var(--ink-3)' }}; background:{{ $on ? 'var(--accent-soft)' : 'var(--card)' }};">
                            <span style="font-size:24px;">{{ $typeIcons[$key] ?? '🗂️' }}</span>
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
            <div style="{{ $pf }}"><span style="color:var(--ink-6); font:12.5px var(--font-sans);">Обрано: <b>{{ $typeIcons[$typeFilter] ?? '' }} {{ $types[$typeFilter] ?? $typeFilter }}</b></span><span style="flex:1;"></span><button class="btn btn-primary" wire:click="wizNext">Далі → Значення</button></div>
        </div>
    @endif

    {{-- ── STEP 2 — ЗНАЧЕННЯ ──────────────────────────────────── --}}
    @if ($wizStep === 2)
        <div class="card" style="overflow:hidden;">
            <div style="{{ $panelHead }}"><span style="{{ $kKey }}">Крок 2</span><h2 style="font:600 16px var(--font-sans); margin:0;">Яке значення?</h2><span style="margin-left:auto; color:var(--ink-5); font:12px var(--font-sans);">оберіть для роботи</span></div>
            <div style="padding:18px 20px;">
                <div style="display:flex; align-items:center; gap:10px; height:42px; padding:0 14px; border-radius:10px; background:var(--paper-2); margin-bottom:14px;">
                    <x-icon.search width="16" height="16" style="color:var(--ink-5);" />
                    <input wire:model.live.debounce.300ms="search" type="text" placeholder="Пошук номера або мітки…"
                           style="flex:1; border:0; outline:none; background:transparent; font:14.5px var(--font-sans); color:var(--ink-7);" />
                </div>
                <div style="border:1px solid var(--ink-2); border-radius:10px; overflow:hidden; max-height:440px; overflow-y:auto;">
                    @forelse ($valueGroups as $g)
                        @php
                            $gcur = $g->currency ?? '';
                            $gdisp = $typeFilter === 'price' ? rtrim(rtrim(number_format((float) $g->gkey, 2, '.', ' '), '0'), '.') : $g->gkey;
                            $on = $pickedValue !== '' && (string) $pickedValue === (string) $g->gkey && (string) $pickedCurrency === (string) $gcur;
                        @endphp
                        <button type="button" wire:key="wv-{{ md5($g->gkey.'|'.$gcur) }}"
                                wire:click="pickValue('{{ addslashes($g->gkey) }}', '{{ addslashes($gcur) }}')"
                                style="display:flex; align-items:center; gap:12px; width:100%; text-align:left; padding:13px 15px; border:0; border-bottom:1px solid var(--ink-2); cursor:pointer;
                                       background:{{ $on ? 'var(--accent-soft)' : 'transparent' }}; box-shadow:{{ $on ? 'inset 3px 0 0 var(--accent)' : 'none' }};">
                            <span class="mono" style="flex:1; min-width:0; font:14px var(--font-mono); color:var(--ink-9); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $gdisp }}{{ $gcur ? ' '.$gcur : '' }}</span>
                            <span style="font:11.5px var(--font-sans); color:var(--ink-6); background:var(--ink-2); border-radius:999px; padding:3px 10px; white-space:nowrap;">на {{ $g->sites }}&nbsp;сайтах · {{ $g->n }}&nbsp;входжень</span>
                            <span style="color:{{ $on ? 'var(--accent)' : 'var(--ink-4)' }}; font-size:14px;">{{ $on ? '✓' : '›' }}</span>
                        </button>
                    @empty
                        <div style="padding:40px; text-align:center; color:var(--ink-5); font:13px var(--font-sans);">Немає значень для цього типу.</div>
                    @endforelse
                </div>
            </div>
            <div style="{{ $pf }}">
                <button class="btn btn-ghost" wire:click="wizBack">← Назад</button>
                <span style="color:var(--ink-6); font:12.5px var(--font-sans);">@if ($pickedValue !== '')Обрано: <b class="mono">{{ $pickedValue }}{{ $pickedCurrency ? ' '.$pickedCurrency : '' }}</b>@else Оберіть значення зі списку @endif</span>
                <span style="flex:1;"></span>
                <button class="btn btn-primary" wire:click="wizNext" @disabled($pickedValue === '')>Далі → Сайти</button>
            </div>
        </div>
    @endif

    {{-- ── STEP 3 — САЙТИ / ВХОДЖЕННЯ ─────────────────────────── --}}
    @if ($wizStep === 3)
        <div class="card" style="overflow:hidden;">
            <div style="{{ $panelHead }}"><span style="{{ $kKey }}">Крок 3</span><h2 style="font:600 16px var(--font-sans); margin:0;">Де саме застосувати?</h2><span style="margin-left:auto; color:var(--ink-5); font:12px var(--font-sans);">{{ $this->selectedCount() }} з {{ $entries->total() }} обрано</span></div>
            <div style="padding:18px 20px;">
                <div style="font:13px var(--font-sans); color:var(--ink-6); margin-bottom:14px;">
                    <b class="mono">{{ $pickedValue }}{{ $pickedCurrency ? ' '.$pickedCurrency : '' }}</b> зустрічається у <b>{{ $entries->total() }} входженнях</b>. Познач, де саме застосувати дію — резерви видно під своїм основним.
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
                            <span style="font:12px var(--font-sans); color:var(--ink-5);">· {{ count($gIds) }} входжень</span>
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
                <span style="color:var(--ink-6); font:12.5px var(--font-sans);">Застосувати до <b>{{ $this->selectedCount() }}</b> входжень</span>
                <span style="flex:1;"></span>
                <button class="btn btn-primary" wire:click="wizNext" @disabled(! $this->hasSelection())>Далі → Дія</button>
            </div>
        </div>
    @endif

    {{-- ── STEP 4 — ДІЯ + ДЕТАЛІ ──────────────────────────────── --}}
    @if ($wizStep === 4)
        @php
            $actMeta = [
                'replace'   => ['✎', 'новий номер на всіх обраних'],
                'substr'    => ['⇆', 'напр. код 63 → 67'],
                'label'     => ['🏷️', 'підпис біля номера'],
                'geo'       => ['🌐', 'усім / тільки / крім'],
                'state'     => ['⚡', 'активний / прихований'],
                'move'      => ['↗', 'на інший сайт'],
                'duplicate' => ['⧉', 'копії на сайти'],
                'delete'    => ['🗑', 'у кошик, оборотно'],
            ];
        @endphp
        <div class="card" style="overflow:hidden;">
            <div style="{{ $panelHead }}"><span style="{{ $kKey }}">Крок 4</span><h2 style="font:600 16px var(--font-sans); margin:0;">Яка дія?</h2><span style="margin-left:auto; color:var(--ink-5); font:12px var(--font-sans);">над {{ $this->selectedCount() }} обраними</span></div>
            <div style="padding:18px 20px;">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:9px;">
                    @foreach ($this->wizActionLabels() as $akey => $alabel)
                        @php $on = $wizAction === $akey; $bad = $akey === 'delete'; @endphp
                        <button type="button" wire:click="setWizAction('{{ $akey }}')"
                                style="display:flex; align-items:center; gap:11px; padding:12px 13px; border-radius:10px; cursor:pointer; text-align:left;
                                       border:1.5px solid {{ $on ? ($bad ? 'var(--bad)' : 'var(--accent)') : 'var(--ink-3)' }};
                                       background:{{ $on ? ($bad ? 'var(--bad-soft)' : 'var(--accent-soft)') : 'var(--card)' }};">
                            <span style="width:30px; height:30px; border-radius:8px; background:var(--ink-2); display:inline-flex; align-items:center; justify-content:center; font-size:15px;">{{ $actMeta[$akey][0] ?? '•' }}</span>
                            <span style="min-width:0;">
                                <span style="display:block; font:13px var(--font-sans); color:var(--ink-9);">{{ $alabel }}</span>
                                <span style="display:block; font:11.5px var(--font-sans); color:var(--ink-5);">{{ $actMeta[$akey][1] ?? '' }}</span>
                            </span>
                        </button>
                    @endforeach
                </div>

                {{-- Action detail --}}
                @if ($wizAction !== '')
                    <div style="margin-top:16px; padding:16px; border:1px solid var(--ink-2); border-radius:10px; background:var(--paper-2);">
                        @if ($wizAction === 'replace')
                            <label style="display:block; font:12px var(--font-sans); color:var(--ink-6); margin-bottom:6px;">Замінити «<span class="mono">{{ $pickedValue }}</span>» на новий номер:</label>
                            <div style="display:flex; align-items:center; gap:12px;">
                                <span class="mono" style="color:var(--ink-5);">{{ $pickedValue }}</span><span style="color:var(--ink-4); font-size:16px;">→</span>
                                <input wire:model="editValue" type="text" placeholder="новий номер" style="flex:1; height:38px; padding:0 12px; border-radius:9px; border:1px solid var(--ink-3); background:var(--card); font:14px var(--font-mono); color:var(--ink-9); outline:none;" />
                            </div>
                        @elseif ($wizAction === 'label')
                            <label style="display:block; font:12px var(--font-sans); color:var(--ink-6); margin-bottom:6px;">Нова мітка (підпис) для обраних:</label>
                            <input wire:model="editValue" type="text" placeholder="напр. Менеджер з продажу" style="width:100%; height:38px; padding:0 12px; border-radius:9px; border:1px solid var(--ink-3); background:var(--card); font:14px var(--font-sans); color:var(--ink-9); outline:none;" />
                        @elseif ($wizAction === 'substr')
                            <label style="display:block; font:12px var(--font-sans); color:var(--ink-6); margin-bottom:6px;">Замінити підрядок у значенні:</label>
                            <div style="display:flex; align-items:center; gap:10px;">
                                <input wire:model="findText" type="text" placeholder="знайти" style="flex:1; height:38px; padding:0 12px; border-radius:9px; border:1px solid var(--ink-3); background:var(--card); font:14px var(--font-mono); color:var(--ink-9); outline:none;" />
                                <span style="color:var(--ink-4); font-size:16px;">→</span>
                                <input wire:model="replaceText" type="text" placeholder="замінити на" style="flex:1; height:38px; padding:0 12px; border-radius:9px; border:1px solid var(--ink-3); background:var(--card); font:14px var(--font-mono); color:var(--ink-9); outline:none;" />
                            </div>
                        @elseif ($wizAction === 'geo')
                            <label style="display:block; font:12px var(--font-sans); color:var(--ink-6); margin-bottom:8px;">Кому показувати:</label>
                            <div style="display:flex; gap:7px; margin-bottom:10px;">
                                @foreach (['all' => 'Усім', 'only' => 'Тільки', 'except' => 'Крім'] as $mk => $ml)
                                    <button type="button" wire:click="$set('geoMode', '{{ $mk }}')" style="height:32px; padding:0 14px; border-radius:8px; cursor:pointer; font:12.5px var(--font-sans); background:{{ $geoMode === $mk ? 'var(--ink-9)' : 'var(--card)' }}; color:{{ $geoMode === $mk ? 'var(--paper)' : 'var(--ink-7)' }}; border:1px solid {{ $geoMode === $mk ? 'var(--ink-9)' : 'var(--ink-3)' }};">{{ $ml }}</button>
                                @endforeach
                            </div>
                            @if ($geoMode !== 'all')
                                <input wire:model="geoCountries" type="text" placeholder="країни через кому, напр. UA, PL, DE" style="width:100%; height:38px; padding:0 12px; border-radius:9px; border:1px solid var(--ink-3); background:var(--card); font:14px var(--font-mono); color:var(--ink-9); outline:none;" />
                            @endif
                        @elseif ($wizAction === 'state')
                            <label style="display:block; font:12px var(--font-sans); color:var(--ink-6); margin-bottom:8px;">Новий стан обраних:</label>
                            <div style="display:flex; gap:8px;">
                                @foreach (['primary' => '● Активний', 'hidden' => '◌ Прихований'] as $rk => $rl)
                                    <button type="button" wire:click="$set('roleValue', '{{ $rk }}')" style="height:34px; padding:0 16px; border-radius:8px; cursor:pointer; font:13px var(--font-sans); background:{{ $roleValue === $rk ? 'var(--ink-9)' : 'var(--card)' }}; color:{{ $roleValue === $rk ? 'var(--paper)' : 'var(--ink-7)' }}; border:1px solid {{ $roleValue === $rk ? 'var(--ink-9)' : 'var(--ink-3)' }};">{{ $rl }}</button>
                                @endforeach
                            </div>
                        @elseif ($wizAction === 'move')
                            <label style="display:block; font:12px var(--font-sans); color:var(--ink-6); margin-bottom:8px;">Сайт призначення:</label>
                            <div style="display:flex; flex-direction:column; gap:4px; max-height:260px; overflow-y:auto;">
                                @foreach ($sites as $s)
                                    @php $on = (int) $moveSite === (int) $s->id; @endphp
                                    <button type="button" wire:click="$set('moveSite', '{{ $s->id }}')" style="display:flex; align-items:center; gap:10px; padding:9px 12px; border-radius:8px; cursor:pointer; text-align:left; background:{{ $on ? 'var(--accent-soft)' : 'var(--card)' }}; border:1px solid {{ $on ? 'var(--ink-9)' : 'var(--ink-3)' }};">
                                        <span class="row-check {{ $on ? 'is-checked' : '' }}">@if($on)<x-icon.check width="11" height="11" />@endif</span>
                                        <span style="font:13px var(--font-sans); color:var(--ink-8);">{{ $s->name }}</span>
                                    </button>
                                @endforeach
                            </div>
                        @elseif ($wizAction === 'duplicate')
                            <label style="display:block; font:12px var(--font-sans); color:var(--ink-6); margin-bottom:8px;">Куди скопіювати · обрано {{ count($dupSites) }}:</label>
                            <div style="display:flex; flex-direction:column; gap:4px; max-height:260px; overflow-y:auto;">
                                @foreach ($sites as $s)
                                    @php $on = in_array($s->id, $dupSites); @endphp
                                    <button type="button" wire:click="toggleDupSite({{ $s->id }})" style="display:flex; align-items:center; gap:10px; padding:9px 12px; border-radius:8px; cursor:pointer; text-align:left; background:{{ $on ? 'var(--accent-soft)' : 'var(--card)' }}; border:1px solid {{ $on ? 'var(--ink-9)' : 'var(--ink-3)' }};">
                                        <span class="row-check {{ $on ? 'is-checked' : '' }}">@if($on)<x-icon.check width="11" height="11" />@endif</span>
                                        <span style="font:13px var(--font-sans); color:var(--ink-8);">{{ $s->name }}</span>
                                    </button>
                                @endforeach
                            </div>
                        @elseif ($wizAction === 'delete')
                            <div style="font:13px var(--font-sans); color:var(--ink-7);">Обрані <b>{{ $this->selectedCount() }}</b> записів переїдуть у <b>кошик</b>. Це оборотно — відновиш кнопкою «Відмінити» або з кошика.</div>
                        @endif
                    </div>
                @endif
            </div>
            <div style="{{ $pf }}">
                <button class="btn btn-ghost" wire:click="wizBack">← Назад</button>
                <span style="color:var(--ink-6); font:12.5px var(--font-sans);">@if ($wizAction !== '')Дія: <b>{{ $this->wizActionLabels()[$wizAction] }}</b>@else Оберіть дію @endif</span>
                <span style="flex:1;"></span>
                <button class="btn btn-primary" wire:click="wizNext" @disabled($wizAction === '')>Далі → Підтвердити</button>
            </div>
        </div>
    @endif

    {{-- ── STEP 5 — ПІДТВЕРДИТИ ───────────────────────────────── --}}
    @if ($wizStep === 5)
        @php
            $n = $this->selectedCount();
            $summary = match ($wizAction) {
                'replace'   => 'Замінити значення на '.$n.' входженнях: «'.$pickedValue.'» → «'.trim($editValue).'».',
                'label'     => 'Змінити мітку на '.$n.' входженнях на «'.trim($editValue).'».',
                'substr'    => 'У '.$n.' входженнях замінити підрядок «'.$findText.'» → «'.$replaceText.'».',
                'geo'       => 'Змінити гео-видимість на '.$n.' входженнях ('.($geoMode === 'all' ? 'усім' : ($geoMode === 'only' ? 'тільки: '.$geoCountries : 'крім: '.$geoCountries)).').',
                'state'     => 'Змінити стан на '.$n.' входженнях на «'.($roleValue === 'hidden' ? 'Прихований' : 'Активний').'».',
                'move'      => 'Перемістити '.$n.' входжень на обраний сайт (разом з резервами).',
                'duplicate' => 'Скопіювати '.$n.' входжень на '.count($dupSites).' сайтів.',
                'delete'    => 'Видалити '.$n.' входжень у кошик.',
                default     => '',
            };
        @endphp
        <div class="card" style="overflow:hidden;">
            <div style="{{ $panelHead }}"><span style="{{ $kKey }}">Крок 5</span><h2 style="font:600 16px var(--font-sans); margin:0;">Підтвердити</h2><span style="margin-left:auto; color:var(--ink-5); font:12px var(--font-sans);">останній перегляд</span></div>
            <div style="padding:18px 20px;">
                <div style="padding:16px 18px; border:1px solid var(--accent-soft); background:var(--accent-soft); border-radius:12px; margin-bottom:14px;">
                    <div style="font:600 15px var(--font-sans); color:var(--ink-9); line-height:1.5;">{{ $summary }}</div>
                    <div style="font:12.5px var(--font-sans); color:#7a3217; margin-top:6px;">Незазначені входження не зачіпаються. Дію буде записано в журнал і можна одразу <b>відмінити</b>.</div>
                </div>
                <div style="border:1px solid var(--ink-2); border-radius:10px; overflow:hidden; max-height:320px; overflow-y:auto;">
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
                    @if ($selectAllMatching)
                        <div style="padding:10px 13px; font:12px var(--font-sans); color:var(--ink-5);">…та решта за фільтром ({{ $entries->total() }} усього)</div>
                    @endif
                </div>
            </div>
            <div style="{{ $pf }}">
                <button class="btn btn-ghost" wire:click="wizBack">← Назад</button>
                <span style="flex:1;"></span>
                <span style="color:var(--ink-5); font:12px var(--font-sans); margin-right:8px;">Можна скасувати одразу після виконання</span>
                <button class="btn btn-primary" wire:click="wizConfirm" style="background:var(--accent); border-color:var(--accent);">✓ Підтвердити та застосувати</button>
            </div>
        </div>
    @endif

</div>
