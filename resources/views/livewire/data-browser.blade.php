<div style="flex:1; display:flex; flex-direction:column;">
    <x-ui.topbar :crumbs="['Браузер даних']">
        <x-ui.button variant="secondary" size="sm" wire:click="$toggle('trashed')">
            <x-icon.trash width="13" height="13" /> {{ $trashed ? 'До активних' : 'Кошик' }}
        </x-ui.button>
        @unless ($trashed)
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
        <div style="display:flex; align-items:center; gap:10px; height:44px; padding:0 18px; border-radius:999px; background:var(--card); border:1px solid var(--ink-3); max-width:600px;">
            <x-icon.search width="15" height="15" style="color:var(--ink-5);" />
            <input wire:model.live.debounce.300ms="search" type="text"
                   placeholder="Пошук по {{ $totalCount }} записах…"
                   style="flex:1; font:14.5px var(--font-sans); color:var(--ink-7); background:transparent; border:0; outline:none;" />
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

            {{-- Site filter --}}
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

        {{-- Role/state sub-filter — pick Active / Reserve / Hidden, then act on them. --}}
        <div style="display:flex; align-items:center; gap:6px; margin-top:10px; flex-wrap:wrap;">
            <span style="font:11px var(--font-mono); color:var(--ink-5); text-transform:uppercase; letter-spacing:.06em; margin-right:2px;">Стан</span>
            @foreach (['' => 'Всі', 'primary' => 'Активні', 'backup' => 'Резервні', 'hidden' => 'Приховані'] as $rkey => $rlabel)
                <button wire:click="$set('roleFilter', '{{ $rkey }}')" style="
                    height:28px; padding:0 11px; border-radius:999px; font:12px var(--font-sans); cursor:pointer;
                    background:{{ $roleFilter === $rkey ? 'var(--ink-9)' : 'transparent' }};
                    color:{{ $roleFilter === $rkey ? 'var(--paper)' : 'var(--ink-6)' }};
                    box-shadow:{{ $roleFilter === $rkey ? 'none' : 'inset 0 0 0 1px var(--ink-3)' }};">
                    {{ $rlabel }}
                </button>
            @endforeach
        </div>
    </div>

    {{-- Bulk action bar --}}
    @if ($this->hasSelection())
        @php
            $mixedType = count($selectionEntities) > 1;
            $valueHint = $mixedType ? 'Лише для одного виду — обрано: '.implode(' + ', $selectionEntities) : '';
        @endphp
        <div style="margin-top:16px;">
            <x-ui.bulk-bar :count="$this->selectedCount()" :total="$entries->total()" :all-matching="$selectAllMatching">
                <button class="bulk-action" wire:click="openReview">
                    <x-icon.list width="13" height="13" /> Огляд
                </button>
                @if ($trashed)
                    <button class="bulk-action" wire:click="restoreSelected">
                        <x-icon.refresh width="13" height="13" /> Відновити
                    </button>
                    <button class="bulk-action bulk-action--danger" wire:click="purgeSelected"
                            wire:confirm="Видалити обрані записи НАЗАВЖДИ? Це не можна відмінити.">
                        <x-icon.trash width="13" height="13" /> Видалити назавжди
                    </button>
                @else
                    <button class="bulk-action" wire:click="openEdit('value')" @disabled($mixedType) @if ($mixedType) title="{{ $valueHint }}" @endif>
                        <x-icon.edit width="13" height="13" /> Замінити значення
                    </button>
                    <button class="bulk-action" wire:click="openReplace" @disabled($mixedType) @if ($mixedType) title="{{ $valueHint }}" @endif>
                        <x-icon.refresh width="13" height="13" /> Підрядок
                    </button>
                    <button class="bulk-action" wire:click="openEdit('label')">
                        <x-icon.tag width="13" height="13" /> Мітка
                    </button>
                    <button class="bulk-action" wire:click="openRole">
                        <x-icon.bolt width="13" height="13" /> Стан
                    </button>
                    @if ($typeFilter === 'price')
                        <button class="bulk-action" wire:click="openPriceEdit">
                            <x-icon.tag width="13" height="13" /> Ціна
                        </button>
                    @endif
                    <button class="bulk-action" wire:click="openGeo">
                        <x-icon.globe width="13" height="13" /> Гео
                    </button>
                    <button class="bulk-action bulk-action--danger" wire:click="bulkDelete">
                        <x-icon.trash width="13" height="13" /> Видалити
                    </button>
                    @if ($mixedType)
                        <span style="display:inline-flex; align-items:center; gap:5px; font:11.5px var(--font-sans); color:rgba(250,249,246,.55); white-space:nowrap;">
                            <x-icon.info width="12" height="12" /> значення — оберіть один вид
                        </span>
                    @endif
                @endif
            </x-ui.bulk-bar>
        </div>
    @endif

    <div style="padding:20px 40px 64px; flex:1; overflow-y:auto;">
        <div class="card" style="overflow:hidden;">
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
                @php $sel = $selectAllMatching || in_array($entry->id, $selected, true); @endphp
                <div wire:key="entry-{{ $entry->id }}" wire:click="toggleSelected({{ $entry->id }})"
                     style="display:grid; grid-template-columns:32px 1.4fr 1.6fr 1fr 100px 80px; gap:12px; padding:14px 18px; border-top:{{ $i ? '1px solid var(--ink-3)' : 'none' }}; background:{{ $sel ? 'var(--accent-soft)' : 'transparent' }}; align-items:center; cursor:pointer; transition:background .12s;">
                    <span class="row-check {{ $sel ? 'is-checked' : '' }}">
                        @if ($sel) <x-icon.check width="11" height="11" /> @endif
                    </span>
                    <span class="mono" style="font:13.5px var(--font-mono); color:{{ $trashed ? 'var(--ink-6)' : 'var(--ink-9)' }};">{{ $entry->value }}</span>
                    <span class="mono" style="font:12.5px var(--font-mono); color:var(--ink-7);">{{ $entry->site?->name ?? '—' }}</span>
                    <span style="font:12.5px var(--font-sans); color:var(--ink-7);">{{ $entry->label }}</span>
                    <span style="font:12px var(--font-sans); color:var(--ink-5);">{{ $entry->geo_label }}</span>
                    @if ($trashed)
                        <span style="font:12px var(--font-sans); color:var(--ink-5);">{{ $entry->deleted_at?->diffForHumans() }}</span>
                    @else
                        <span style="font:12.5px var(--font-sans);">
                            @if ($entry->role === 'primary')
                                <span class="dot dot-ok"></span> Активний
                            @elseif ($entry->role === 'backup')
                                <span class="dot dot-info"></span> Резерв
                            @else
                                <span class="dot"></span> Приховано
                            @endif
                        </span>
                    @endif
                </div>
            @empty
                <div style="padding:80px; text-align:center; color:var(--ink-5); font:13.5px var(--font-sans);">
                    {{ $trashed ? 'Кошик порожній.' : 'Немає записів для обраного фільтру.' }}
                </div>
            @endforelse
        </div>

        {{ $entries->links('livewire.quiet-pagination') }}
    </div>

    {{-- Bulk edit drawer (replace value / change label) --}}
    @if ($editingField)
        @php $editTitles = ['value' => 'Замінити значення', 'label' => 'Змінити мітку']; @endphp
        <div wire:key="bulk-edit-{{ $editField }}">
            <x-ui.drawer :open="true"
                         :title="$editTitles[$editField] ?? 'Редагувати'"
                         @drawer-close.window="$wire.closeEdit()">
                <div style="display:flex; flex-direction:column; gap:18px;">
                    <div>
                        <div class="eyebrow" style="font-size:10px;">Обрано</div>
                        <div style="margin-top:4px; font:400 20px var(--font-sans); color:var(--ink-9); letter-spacing:-0.01em;">{{ $this->selectedCount() }} записів</div>
                    </div>

                    @if ($selectionPreview->isNotEmpty())
                        <div style="display:flex; flex-direction:column; gap:6px; max-height:220px; overflow-y:auto;">
                            @foreach ($selectionPreview as $p)
                                <div style="display:flex; align-items:center; gap:10px; padding:8px 12px; border-radius:8px; background:var(--paper-2);">
                                    <span class="mono" style="flex:1; min-width:0; font:12.5px var(--font-mono); color:var(--ink-9); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $editField === 'label' ? ($p->label ?: '—') : $p->value }}</span>
                                    <span class="mono" style="font:11.5px var(--font-mono); color:var(--ink-5); white-space:nowrap;">{{ $p->site?->name ?? '—' }}</span>
                                </div>
                            @endforeach
                            @if ($this->selectedCount() > $selectionPreview->count())
                                <div style="padding:2px 4px; font:12px var(--font-sans); color:var(--ink-5);">…та ще {{ $this->selectedCount() - $selectionPreview->count() }}</div>
                            @endif
                        </div>
                    @endif

                    <div>
                        <label style="display:block; font:12px var(--font-mono); color:var(--ink-5); text-transform:uppercase; letter-spacing:.06em;">
                            {{ $editField === 'value' ? 'Нове значення' : 'Нова мітка' }}
                        </label>
                        <input type="text" wire:model="editValue" wire:keydown.enter="applyEdit"
                               x-init="$nextTick(() => $el.focus())"
                               placeholder="{{ $editField === 'value' ? 'Напр. +48 22 111 22 33' : 'Напр. Підписка · Pro' }}"
                               style="margin-top:8px; width:100%; height:44px; padding:0 14px; border-radius:10px; background:var(--card); border:1px solid var(--ink-3); font:14.5px var(--font-sans); color:var(--ink-9); outline:none;" />
                        <div style="margin-top:8px; font:12px var(--font-sans); color:var(--ink-5);">
                            Застосується до всіх {{ $this->selectedCount() }} обраних записів{{ $editField === 'value' ? ' — навіть на різних сайтах' : '' }}. Дію можна відмінити.
                        </div>
                    </div>
                </div>

                <x-slot:footer>
                    <button class="btn btn-ghost" wire:click="closeEdit">Скасувати</button>
                    <button class="btn btn-primary" wire:click="applyEdit">
                        {{ $editField === 'value' ? 'Замінити' : 'Зберегти' }}
                    </button>
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
                        <input type="text" wire:model="replaceText" wire:keydown.enter="applyReplace" placeholder="+48"
                               style="margin-top:8px; width:100%; height:44px; padding:0 14px; border-radius:10px; background:var(--card); border:1px solid var(--ink-3); font:14.5px var(--font-mono); color:var(--ink-9); outline:none;" />
                        <div style="margin-top:8px; font:12px var(--font-sans); color:var(--ink-5);">Порожнє «Замінити на» — видалить підрядок. Дію можна відмінити.</div>
                    </div>
                </div>

                <x-slot:footer>
                    <button class="btn btn-ghost" wire:click="closeReplace">Скасувати</button>
                    <button class="btn btn-primary" wire:click="applyReplace">Замінити</button>
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
                                               background:{{ $roleValue === $val ? 'var(--ink-9)' : 'var(--card)' }};
                                               color:{{ $roleValue === $val ? 'var(--paper)' : 'var(--ink-7)' }};
                                               border:1px solid {{ $roleValue === $val ? 'var(--ink-9)' : 'var(--ink-3)' }};">
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
                            <input type="text" wire:model="priceValue" wire:keydown.enter="applyPriceEdit"
                                   x-init="$nextTick(() => $el.focus())"
                                   placeholder="{{ $priceField === 'price_unit' ? 'напр. міс / шт / рік' : 'напр. 199' }}"
                                   style="margin-top:8px; width:100%; height:44px; padding:0 14px; border-radius:10px; background:var(--card); border:1px solid var(--ink-3); font:14.5px var(--font-sans); color:var(--ink-9); outline:none;" />
                            <div style="margin-top:8px; font:12px var(--font-sans); color:var(--ink-5);">
                                @if (in_array($priceField, ['price', 'old_price']))Порожнє поле — прибрати значення. @endif
                                Застосується до всіх {{ $this->selectedCount() }} обраних. Дію можна відмінити.
                            </div>
                        </div>
                    @endif
                </div>
                <x-slot:footer>
                    <button class="btn btn-ghost" wire:click="closePriceEdit">Скасувати</button>
                    <button class="btn btn-primary" wire:click="applyPriceEdit">Застосувати</button>
                </x-slot:footer>
            </x-ui.drawer>
        </div>
    @endif
</div>
