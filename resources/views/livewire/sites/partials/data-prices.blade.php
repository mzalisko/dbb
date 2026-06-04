{{-- Prices sub-section --}}
@php
    $activePriceCount = $allPricesAll->where('visible', true)->count();
    $priceBlockCount = $priceBySkuAll->count();
    $formatPriceText = function ($price) {
        $rawValue = trim((string) ($price->value ?? ''));
        $sku = trim((string) ($price->sku ?? ''));

        if ($rawValue !== '' && $rawValue !== $sku) {
            return $rawValue;
        }

        if ($price->price !== null) {
            $amount = rtrim(rtrim(number_format((float) $price->price, 2, '.', ' '), '0'), '.');
            $suffix = trim(collect([$price->currency, $price->price_unit])->filter()->join(' '));

            return trim($amount . ' ' . $suffix);
        }

        return '—';
    };
@endphp

<div class="price-note">
    <div class="price-note__title">Цінові блоки</div>
    <div class="price-note__text">
        Значення ціни тепер можна вводити як текст: 3000, 3000грн, 3000с. Блок групує варіанти, а гео-правило показує для кого працює конкретний рядок.
    </div>
</div>

<div class="price-stats">
    {{ $activePriceCount }} активних &middot; {{ $priceBlockCount }} {{ $priceBlockCount === 1 ? 'блок' : 'блоків' }}
</div>

<div class="card ctable ctable--price-groups">
    <div class="price-head">
        <span>Блок</span>
        <span>Ціна</span>
        <span>Мітка</span>
        <span>Гео</span>
        <span>Статус</span>
        <span></span>
    </div>

    @forelse($priceBySkuAll as $sku => $prices)
        @php
            $first = $prices->first();
            $blockName = $sku ?: ($first?->label ?: 'Без блоку');
            $blockGeo = $first?->geo_label ?? 'Усім';
        @endphp
        <div class="price-group">
            <div class="price-group__head">
                <span class="price-group__dot"></span>
                <div class="price-group__main">
                    <div class="price-group__name">{{ $blockName }}</div>
                    <div class="price-group__meta">{{ $prices->count() }} {{ $prices->count() === 1 ? 'варіант' : 'варіантів' }} &middot; гео блоку: {{ $blockGeo }}</div>
                </div>
                <button type="button"
                        class="price-group__add"
                        wire:click="addPriceToSku(@js($sku))"
                        title="Додати ціну до {{ $blockName }}">
                    + ціна
                </button>
            </div>

            @foreach($prices as $j => $price)
                @php
                    $priceText = $formatPriceText($price);
                    $isHidden = ! $price->visible;
                @endphp
                <div wire:click="editEntry({{ $price->id }})" class="price-row {{ $isHidden ? 'price-row--hidden' : '' }}">
                    <span class="price-row__index">{{ $j + 1 }}</span>
                    <span class="mono price-row__value">{{ $priceText }}</span>
                    <span class="price-row__label">{{ $price->label ?: 'Без мітки' }}</span>
                    <span class="price-row__geo">{{ $price->geo_label }}</span>
                    <span class="price-row__status">
                        @if($price->visible)
                            <span class="failover-dot"></span> Активна
                        @else
                            <span class="failover-dot failover-dot--muted"></span> Прихована
                        @endif
                    </span>
                    <span class="cc-actions">
                        <button class="cc-edit" wire:click.stop="editEntry({{ $price->id }})" title="Редагувати">
                            <x-icon.edit width="13" height="13" />
                        </button>
                        <button class="cc-delete"
                                wire:click.stop="requestDeleteEntry({{ $price->id }})"
                                title="Видалити">
                            <x-icon.trash width="13" height="13" />
                        </button>
                    </span>
                </div>
            @endforeach
        </div>
    @empty
        <div class="ctable__empty">Немає цін.</div>
    @endforelse

    <div class="ctable__foot">
        <button class="ctable__add" wire:click="addEntry('price')">+ Додати ціновий блок</button>
    </div>
</div>
