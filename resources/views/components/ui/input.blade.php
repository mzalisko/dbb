@props([
    'label' => null,
    'name'  => null,
    'mono'  => false,
    'error' => null,
])

<div>
    @if ($label)
        <label @if($name) for="{{ $name }}" @endif class="label">{{ $label }}</label>
    @endif
    <input
        @if($name) id="{{ $name }}" name="{{ $name }}" @endif
        {{ $attributes->merge(['class' => 'input' . ($mono ? ' mono' : '')]) }}
    />
    @if ($error)
        <p class="mono" style="font-size:11.5px;color:var(--bad);margin-top:6px;">{{ $error }}</p>
    @endif
</div>
