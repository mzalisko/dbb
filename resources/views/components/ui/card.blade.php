@props(['padding' => true])

<div {{ $attributes->merge(['class' => 'card']) }}>
    @if (isset($header))
        <div class="card-header">{{ $header }}</div>
    @endif
    <div class="{{ $padding ? 'card-body' : '' }}">
        {{ $slot }}
    </div>
    @if (isset($footer))
        <div class="card-footer">{{ $footer }}</div>
    @endif
</div>