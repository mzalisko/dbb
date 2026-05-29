@php($previewTag = $entry->preview_geo_label)
@if($previewTag)
    <span class="cc-country-tag" title="Належність до вкладки Перегляд">{{ $previewTag }}</span>
@else
    <span class="cc-country-tag cc-country-tag--muted" title="Без належності до країни">Без</span>
@endif
