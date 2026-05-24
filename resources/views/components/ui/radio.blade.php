@props([
    'label'   => null,
    'name'    => null,
    'value'   => null,
    'checked' => false,
])

<label class="radio-label">
    <input
        type="radio"
        @if($name) name="{{ $name }}" @endif
        @if($value !== null) value="{{ $value }}" @endif
        @if($checked) checked @endif
        {{ $attributes->merge(['class' => 'radio']) }}
    />
    @if ($label)
        <span>{{ $label }}</span>
    @endif
</label>