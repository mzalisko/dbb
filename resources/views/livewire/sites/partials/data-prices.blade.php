{{-- ── Prices sub-section ── --}}
{{-- MULTI-CURRENCY info box --}}
<div class="howto">
    <div class="howto__title">Multi-currency</div>
    <div class="howto__text">
        Один <b>SKU</b>, кілька цін під різні гео — клієнт у Польщі бачить PLN, у Україні — UAH, решта світу — EUR/USD. Стара ціна показується як <span style="text-decoration:line-through;">перекреслена</span>.
    </div>
</div>

{{-- Stats --}}
@php
    $activePriceCount = $allPricesAll->where('visible',true)->count();
    $currencyCount = $allPricesAll->pluck('currency')->unique()->count();
@endphp
<div class="price-stats">
    {{ $activePriceCount }} активних &middot; {{ $currencyCount }} {{ $currencyCount===1?'валюта':'валют' }}
</div>

<div class="card ctable">
    <div class="crow crow--price crow--head">
        <span></span><span></span>
        <span class="eyebrow eyebrow-xxs">Назва &middot; SKU</span>
        <span class="eyebrow eyebrow-xxs">Ціна</span>
        <span class="eyebrow eyebrow-xxs">Валюта &middot; Одиниця</span>
        <span class="eyebrow eyebrow-xxs">Гео-правило</span>
        <span class="eyebrow eyebrow-xxs">Статус</span>
        <span></span>
    </div>
    @foreach($priceBySkuAll as $sku => $prices)
        {{-- SKU group header --}}
        <div class="sku-head">
            <span class="sku-head__label">
                <span class="eyebrow eyebrow-xxs" style="margin-right:8px;">SKU</span>
                <strong style="color:var(--ink-9);">{{ $sku }}</strong>
                <span style="color:var(--ink-4); margin-left:8px;">&middot; {{ $prices->first()?->label }}</span>
            </span>
            <span class="sku-head__count">{{ $prices->count() }} {{ $prices->count()===1?'ціна':'цін' }}</span>
        </div>
        @foreach($prices as $j => $price)
            @php
                $currSymbol = match($price->currency) {'PLN'=>'zł','UAH'=>'₴','EUR'=>'€','USD'=>'$',default=>$price->currency};
                $currFlag = match($price->currency) {'PLN'=>'🇵🇱','UAH'=>'🇺🇦','EUR'=>'🇪🇺','USD'=>'🇺🇸',default=>''};
            @endphp
            <div class="crow crow--price crow--main crow--bordered">
                <span class="cc-drag">&#x2807;</span>
                <span class="cc-num">#{{ $j+1 }}</span>
                <div>
                    <div class="price-name">{{ $price->label }}</div>
                    <div class="price-sku">SKU &middot; {{ $price->sku }}</div>
                </div>
                <div class="price-amount">
                    <span class="mono price-val">{{ number_format($price->price,0,'.',' ') }} {{ $currSymbol }}</span>
                    @if($price->old_price)
                        <span class="mono price-old">{{ number_format($price->old_price,0,'.',' ') }}</span>
                    @endif
                </div>
                <span class="price-cur">{{ $currFlag }} {{ $price->currency }} /{{ $price->price_unit }}</span>
                <span class="cc-geo">{{ $price->geo_label }}</span>
                <span class="cc-role">
                    <span class="role-dot" style="background:{{ $price->visible?'var(--ok)':'var(--ink-4)' }};"></span>
                </span>
                <span class="cc-actions">
                    <button class="cc-delete"
                            wire:click.stop="requestDeleteEntry({{ $price->id }})"
                            title="Видалити">
                        <x-icon.trash width="13" height="13" />
                    </button>
                </span>
            </div>
        @endforeach
    @endforeach
    @if($priceBySkuAll->isEmpty())
        <div class="ctable__empty">Немає цін.</div>
    @endif
</div>
