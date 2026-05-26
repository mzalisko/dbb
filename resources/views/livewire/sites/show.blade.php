<div style="flex:1; display:flex; flex-direction:column; overflow-y:auto;" x-data="{ tab: 'data', settingsSub: 'failover', actFilter: 'all' }">

    <x-ui.topbar :crumbs="['Сайти', $site->name]">
        <button onclick="history.back()" class="btn btn-ghost btn-sm">
            &larr; Назад
        </button>
        <button class="btn btn-ghost btn-sm" style="margin-left:4px;">
            &#8635; Sync
        </button>
        <button class="btn btn-primary btn-sm" style="margin-left:4px;">
            + Додати
        </button>
    </x-ui.topbar>

    {{-- Page head --}}
    <div style="padding:28px 40px 0; flex-shrink:0;">
        @php
            $groupLabel = $site->group ? strtoupper($site->group) : 'NO GROUP';
            $statusLabel = match($site->status) { 'active'=>'АКТИВНИЙ','maintenance'=>'ПАУЗА',default=>'ПОМИЛКА' };
            $statusColor = match($site->status) { 'active'=>'var(--ok)','maintenance'=>'var(--warn)',default=>'var(--bad)' };
        @endphp
        <div style="display:flex; align-items:center; gap:10px; font:11px var(--font-mono); letter-spacing:.08em; color:var(--ink-5); margin-bottom:14px;">
            <span style="display:inline-flex; align-items:center; gap:5px;">
                <span style="width:7px;height:7px;border-radius:999px;background:var(--ok);"></span>
                {{ $groupLabel }}
            </span>
            <span style="color:var(--ink-3);">&middot;</span>
            <span style="display:inline-flex; align-items:center; gap:5px;">
                <span style="width:7px;height:7px;border-radius:999px;background:{{ $statusColor }};"></span>
                {{ $statusLabel }}
            </span>
        </div>
        <h1 style="font:400 36px/1.05 var(--font-sans); letter-spacing:-0.03em; color:var(--ink-9);">{{ $site->name }}</h1>
        <p style="margin-top:12px; font:14px/1.6 var(--font-sans); color:var(--ink-5); max-width:640px;">
            Кілька номерів одночасно. Кожен видимий за своїм гео-правилом — нижче переключіть «Перегляд» щоб побачити, що бачить відвідувач з певної країни.
        </p>
    </div>

    {{-- Tabs --}}
    <div style="padding:0 40px; margin-top:24px; border-bottom:1px solid var(--ink-3); display:flex; gap:0; flex-shrink:0;">
        @php $dataCount = $phoneCount + $msgCount; @endphp
        @foreach ([
            ['key'=>'overview','label'=>'Огляд','count'=>null],
            ['key'=>'data','label'=>'Дані','count'=>$dataCount],
            ['key'=>'activity','label'=>'Активність','count'=>null],
            ['key'=>'settings','label'=>'Налаштування','count'=>null],
        ] as $t)
        <button @click="tab='{{ $t['key'] }}'"
            style="display:inline-flex; align-items:center; gap:6px; padding:12px 20px; margin-bottom:-1px;
                border-bottom:2px solid transparent; font:13.5px var(--font-sans); cursor:pointer; background:transparent;"
            :style="tab==='{{ $t['key'] }}' ? 'border-bottom-color:var(--ink-9);color:var(--ink-9);font-weight:500;' : 'color:var(--ink-5);'">
            {{ $t['label'] }}
            @if ($t['count'] !== null)
                <span style="display:inline-flex;align-items:center;justify-content:center;min-width:20px;height:20px;padding:0 5px;border-radius:999px;font:500 11px var(--font-mono);"
                    :style="tab==='{{ $t['key'] }}' ? 'background:var(--ink-9);color:var(--paper);' : 'background:var(--ink-2);color:var(--ink-6);'">
                    {{ $t['count'] }}
                </span>
            @endif
        </button>
        @endforeach
    </div>

    @include('livewire.sites.partials.tab-overview')
    @include('livewire.sites.partials.tab-data')
    @include('livewire.sites.partials.tab-activity')
    @include('livewire.sites.partials.tab-settings')
    @include('livewire.sites.partials.phone-drawer')

</div>
