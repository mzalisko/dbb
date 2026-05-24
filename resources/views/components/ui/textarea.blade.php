@props([
    'label' => null,
    'name'  => null,
    'error' => null,
    'rows'  => 4,
])

<div>
    @if ($label)
        <label @if($name) for="{{ $name }}" @endif class="label">{{ $label }}</label>
    @endif
    <textarea
        @if($name) id="{{ $name }}" name="{{ $name }}" @endif
        rows="{{ $rows }}"
        {{ $attributes->merge(['class' => 'textarea']) }}
    >{{ $slot }}</textarea>
    @if ($error)
        <p class="mono" style="font-size:11.5px;color:var(--bad);margin-top:6px;">{{ $error }}</p>
    @endif
</div>