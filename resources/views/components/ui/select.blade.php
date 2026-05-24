@props([
    'label'       => null,
    'name'        => null,
    'error'       => null,
    'placeholder' => null,
])

<div>
    @if ($label)
        <label @if($name) for="{{ $name }}" @endif class="label">{{ $label }}</label>
    @endif
    <div class="select-wrap">
        <select
            @if($name) id="{{ $name }}" name="{{ $name }}" @endif
            {{ $attributes->merge(['class' => 'select']) }}
        >
            @if ($placeholder)
                <option value="" disabled selected>{{ $placeholder }}</option>
            @endif
            {{ $slot }}
        </select>
    </div>
    @if ($error)
        <p class="mono" style="font-size:11.5px;color:var(--bad);margin-top:6px;">{{ $error }}</p>
    @endif
</div>