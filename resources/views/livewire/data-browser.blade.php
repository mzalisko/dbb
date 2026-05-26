<div style="flex:1; display:flex; flex-direction:column;">
    <x-ui.topbar :crumbs="['Браузер даних']">
        <x-ui.button variant="secondary" size="sm" style="margin-right:4px;">
            <x-icon.export width="13" height="13" /> Експорт
        </x-ui.button>
        <x-ui.button size="sm">Bulk операції</x-ui.button>
    </x-ui.topbar>

    <x-ui.page-head
        :number="$totalCount"
        label="записів"
        sub="Усі контактні дані з усіх сайтів. Шукайте, фільтруйте, виконуйте групові операції." />

    <div style="padding:0 40px;">
        <div style="display:flex; align-items:center; gap:10px; height:44px; padding:0 18px; border-radius:999px; background:var(--card); border:1px solid var(--ink-3); max-width:600px;">
            <x-icon.search width="15" height="15" style="color:var(--ink-5);" />
            <input wire:model.live.debounce.300ms="search" type="text"
                   placeholder="Пошук по {{ $totalCount }} записах…"
                   style="flex:1; font:14.5px var(--font-sans); color:var(--ink-7); background:transparent; border:0; outline:none;" />
        </div>

        {{-- Type filter --}}
        <div style="display:flex; gap:8px; margin-top:16px;">
            <button wire:click="$set('typeFilter', '')" style="
                display:inline-flex; align-items:center; gap:6px;
                height:30px; padding:0 12px; border-radius:999px;
                background:{{ $typeFilter === '' ? 'var(--ink-9)' : 'var(--card)' }};
                color:{{ $typeFilter === '' ? 'var(--paper)' : 'var(--ink-7)' }};
                box-shadow:{{ $typeFilter === '' ? 'none' : 'inset 0 0 0 1px var(--ink-3)' }};
                font:12.5px var(--font-sans); cursor:pointer;">
                Всі
            </button>
            @foreach ([['key' => 'phone', 'label' => 'Телефони'], ['key' => 'messenger', 'label' => 'Месенджери'], ['key' => 'price', 'label' => 'Ціни']] as $t)
                <button wire:click="$set('typeFilter', '{{ $t['key'] }}')" style="
                    display:inline-flex; align-items:center; gap:6px;
                    height:30px; padding:0 12px; border-radius:999px;
                    background:{{ $typeFilter === $t['key'] ? 'var(--ink-9)' : 'var(--card)' }};
                    color:{{ $typeFilter === $t['key'] ? 'var(--paper)' : 'var(--ink-7)' }};
                    box-shadow:{{ $typeFilter === $t['key'] ? 'none' : 'inset 0 0 0 1px var(--ink-3)' }};
                    font:12.5px var(--font-sans); cursor:pointer;">
                    {{ $t['label'] }}
                </button>
            @endforeach
        </div>
    </div>

    {{-- Bulk action bar --}}
    @if (count($selected) > 0)
        <div style="position:sticky; top:0; z-index:5; margin-top:16px; padding:12px 40px; background:var(--ink-9); color:var(--paper); display:flex; align-items:center; gap:14px;">
            <span class="mono" style="font:500 13px var(--font-mono);">{{ count($selected) }} обрано</span>
            <span style="height:14px; width:1px; background:rgba(255,255,255,.2);"></span>
            <button wire:click="bulkToggleVisibility" style="font:13px var(--font-sans); color:var(--paper); cursor:pointer;">Видимість</button>
            <button style="font:13px var(--font-sans); color:var(--paper); cursor:pointer;">Змінити гео</button>
            <button style="font:13px var(--font-sans); color:var(--paper); cursor:pointer;">Видалити</button>
            <div style="flex:1;"></div>
            <button wire:click="clearSelected" style="font:13px var(--font-sans); color:rgba(250,249,246,.7); cursor:pointer;">Зняти виділення</button>
        </div>
    @endif

    <div style="padding:20px 40px 64px; flex:1; overflow-y:auto;">
        <div class="card" style="overflow:hidden;">
            <div style="display:grid; grid-template-columns:32px 1.4fr 1.6fr 1fr 100px 80px; gap:12px; padding:12px 18px; border-bottom:1px solid var(--ink-3); background:var(--paper-2);">
                <span></span>
                @foreach (['Значення', 'Сайт', 'Мітка', 'Гео', 'Роль'] as $h)
                    <span class="eyebrow" style="font-size:10px;">{{ $h }}</span>
                @endforeach
            </div>

            @forelse ($entries as $i => $entry)
                @php $sel = in_array($entry->id, $selected); @endphp
                <div wire:click="toggleSelected({{ $entry->id }})"
                     style="display:grid; grid-template-columns:32px 1.4fr 1.6fr 1fr 100px 80px; gap:12px; padding:14px 18px; border-top:{{ $i ? '1px solid var(--ink-3)' : 'none' }}; background:{{ $sel ? 'var(--accent-soft)' : 'transparent' }}; align-items:center; cursor:pointer; transition:background .12s;">
                    <span style="width:16px; height:16px; border-radius:3px; background:{{ $sel ? 'var(--ink-9)' : 'transparent' }}; border:1px solid {{ $sel ? 'var(--ink-9)' : 'var(--ink-3)' }}; display:inline-flex; align-items:center; justify-content:center; color:var(--paper);">
                        @if ($sel) <x-icon.check width="11" height="11" /> @endif
                    </span>
                    <span class="mono" style="font:13.5px var(--font-mono); color:var(--ink-9);">{{ $entry->value }}</span>
                    <span class="mono" style="font:12.5px var(--font-mono); color:var(--ink-7);">{{ $entry->site?->name ?? '—' }}</span>
                    <span style="font:12.5px var(--font-sans); color:var(--ink-7);">{{ $entry->label }}</span>
                    <span style="font:12px var(--font-sans); color:var(--ink-5);">{{ $entry->geo_label }}</span>
                    <span style="font:12.5px var(--font-sans);">
                        @if ($entry->role === 'primary')
                            <span class="dot dot-ok"></span> Головний
                        @elseif ($entry->role === 'backup')
                            <span class="dot dot-info"></span> Резерв
                        @else
                            <span class="dot"></span> Архів
                        @endif
                    </span>
                </div>
            @empty
                <div style="padding:80px; text-align:center; color:var(--ink-5); font:13.5px var(--font-sans);">
                    Немає записів для обраного фільтру.
                </div>
            @endforelse
        </div>

        @if ($entries->hasPages())
            <div style="margin-top:16px; display:flex; justify-content:center;">
                {{ $entries->links() }}
            </div>
        @endif
    </div>
</div>
