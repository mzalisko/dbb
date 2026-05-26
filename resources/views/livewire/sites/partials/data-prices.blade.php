{{-- ── Prices sub-section ── --}}
{{-- MULTI-CURRENCY info box --}}
<div style="margin-top:16px; padding:14px 18px; border:1px solid #e8cfa0; background:#fdf5e6; border-radius:4px;">
    <div style="font:500 10.5px var(--font-mono); letter-spacing:.08em; color:#b08020; margin-bottom:6px;">MULTI-CURRENCY</div>
    <p style="font:13px/1.5 var(--font-sans); color:#7a5a10; margin:0;">
        Один <strong>SKU</strong>, кілька цін під різні гео — клієнт у Польщі бачить PLN, у Україні — UAH, решта світу — EUR/USD. Стара ціна показується як <span style="text-decoration:line-through;">перекреслена</span>.
    </p>
</div>

{{-- Stats --}}
@php
    $activePriceCount = $allPricesAll->where('visible',true)->count();
    $currencyCount = $allPricesAll->pluck('currency')->unique()->count();
@endphp
<div style="margin-top:10px; font:12px var(--font-mono); color:var(--ink-5);">
    {{ $activePriceCount }} активних &middot; {{ $currencyCount }} {{ $currencyCount===1?'валюта':'валют' }}
</div>

<div class="card" style="margin-top:14px; overflow:hidden;">
    <div style="display:grid; grid-template-columns:24px 40px 1.4fr 1fr 180px 160px 80px 32px; gap:12px; padding:10px 18px; background:var(--paper-2); border-bottom:1px solid var(--ink-3);">
        <span></span><span></span>
        <span class="eyebrow" style="font-size:9.5px;">Назва &middot; SKU</span>
        <span class="eyebrow" style="font-size:9.5px;">Ціна</span>
        <span class="eyebrow" style="font-size:9.5px;">Валюта &middot; Одиниця</span>
        <span class="eyebrow" style="font-size:9.5px;">Гео-правило</span>
        <span class="eyebrow" style="font-size:9.5px;">Статус</span>
        <span></span>
    </div>
    @foreach($priceBySkuAll as $sku => $prices)
        {{-- SKU group header --}}
        <div style="display:flex; align-items:center; justify-content:space-between; padding:8px 18px; background:var(--paper-2); border-top:1px solid var(--ink-3);">
            <span style="font:11px var(--font-mono); color:var(--ink-5); letter-spacing:.06em;">
                <span class="eyebrow" style="font-size:9px; margin-right:8px;">SKU</span>
                <strong style="color:var(--ink-9);">{{ $sku }}</strong>
                <span style="color:var(--ink-4); margin-left:8px;">&middot; {{ $prices->first()?->label }}</span>
            </span>
            <span style="font:11px var(--font-mono); color:var(--ink-4);">{{ $prices->count() }} {{ $prices->count()===1?'ціна':'цін' }}</span>
        </div>
        @foreach($prices as $j => $price)
            @php
                $currSymbol = match($price->currency) {'PLN'=>'zł','UAH'=>'₴','EUR'=>'€','USD'=>'$',default=>$price->currency};
                $currFlag = match($price->currency) {'PLN'=>'🇵🇱','UAH'=>'🇺🇦','EUR'=>'🇪🇺','USD'=>'🇺🇸',default=>''};
            @endphp
            <div style="display:grid; grid-template-columns:24px 40px 1.4fr 1fr 180px 160px 80px 32px; gap:12px; padding:14px 18px; align-items:center; border-top:1px solid var(--ink-3); cursor:pointer; transition:background .1s;"
                 onmouseover="this.style.background='var(--paper-2)'" onmouseout="this.style.background='transparent'">
                <span style="color:var(--ink-3);font:14px var(--font-mono);">&#x2807;</span>
                <span style="font:11px var(--font-mono);color:var(--ink-4);">#{{ $j+1 }}</span>
                <div>
                    <div style="font:13.5px var(--font-sans);color:var(--ink-9);">{{ $price->label }}</div>
                    <div style="font:11px var(--font-mono);color:var(--ink-4);margin-top:2px;">SKU &middot; {{ $price->sku }}</div>
                </div>
                <div style="display:flex;align-items:baseline;gap:6px;">
                    <span class="mono" style="font:400 18px var(--font-mono);color:var(--ink-9);">
                        {{ number_format($price->price,0,'.',' ') }} {{ $currSymbol }}
                    </span>
                    @if($price->old_price)
                        <span class="mono" style="font:13px var(--font-mono);color:var(--ink-4);text-decoration:line-through;">{{ number_format($price->old_price,0,'.',' ') }}</span>
                    @endif
                </div>
                <span style="font:12.5px var(--font-sans);color:var(--ink-5);">
                    {{ $currFlag }} {{ $price->currency }} /{{ $price->price_unit }}
                </span>
                <span style="font:12.5px var(--font-sans);color:var(--ink-5);">{{ $price->geo_label }}</span>
                <span style="display:inline-flex;align-items:center;gap:5px;font:12px var(--font-sans);">
                    <span style="width:7px;height:7px;border-radius:999px;background:{{ $price->visible?'var(--ok)':'var(--ink-4)' }};"></span>
                </span>
                <span style="color:var(--ink-4);">&rarr;</span>
            </div>
        @endforeach
    @endforeach
    @if($priceBySkuAll->isEmpty())
        <div style="padding:48px;text-align:center;color:var(--ink-5);font:13px var(--font-sans);">Немає цін.</div>
    @endif
</div>
