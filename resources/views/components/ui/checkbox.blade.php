@props([
    'label'   => null,
    'name'    => null,
    'checked' => false,
])

<label class="checkbox-label">
    <input
        type="checkbox"
        @if($name) id="{{ $name }}" name="{{ $name }}" @endif
        @if($checked) checked @endif
        {{ $attributes->merge(['class' => 'checkbox']) }}
    />
    @if ($label)
        <span>{{ $label }}</span>
    @endif
</label>