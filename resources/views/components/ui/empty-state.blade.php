@props([
    'icon'        => null,
    'title'       => '',
    'description' => null,
])

<div {{ $attributes->merge(['class' => 'empty-state']) }}>
    @if ($icon)
        <div class="empty-state-icon">
            <x-dynamic-component :component="'icon.' . $icon" width="40" height="40" />
        </div>
    @endif
    <div class="empty-state-title">{{ $title }}</div>
    @if ($description)
        <p class="empty-state-desc">{{ $description }}</p>
    @endif
    @if (isset($action))
        <div class="empty-state-action">{{ $action }}</div>
    @endif
</div>