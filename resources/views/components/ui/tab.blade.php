@props([
    'active' => false,
    'href'   => '#',
    'count'  => null,
])

<a href="{{ $href }}" {{ $attributes->merge(['class' => 'tab' . ($active ? ' active' : '')]) }}>
    {{ $slot }}
    @if ($count !== null)
        <span class="tab-n">{{ $count }}</span>
    @endif
</a>
