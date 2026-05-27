<div style="flex:1; display:flex; flex-direction:column; overflow-y:auto;" x-data="{ tab: 'data', settingsSub: 'failover', actFilter: 'all' }">

    <x-ui.topbar :crumbs="['Сайти', $site->name]" :back="true">
        <button class="btn btn-ghost btn-sm">
            <x-icon.refresh width="13" height="13" /> Sync
        </button>
        <button class="btn btn-primary btn-sm">
            <x-icon.plus width="13" height="13" /> Додати
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
                <span style="width:7px;height:7px;border-radius:999px;background:{{ $site->group_color ?? 'var(--ink-4)' }};"></span>
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
    @php $dataCount = $phoneCount + $msgCount; @endphp
    <div class="tabs" style="padding:0 40px; margin-top:24px; flex-shrink:0;">
        @foreach ([
            ['key'=>'overview','label'=>'Огляд','count'=>null],
            ['key'=>'data','label'=>'Дані','count'=>$dataCount],
            ['key'=>'activity','label'=>'Активність','count'=>null],
            ['key'=>'settings','label'=>'Налаштування','count'=>null],
        ] as $t)
        <button class="tab" :class="tab==='{{ $t['key'] }}' ? 'active' : ''" @click="tab='{{ $t['key'] }}'">
            {{ $t['label'] }}
            @if ($t['count'] !== null)
                <span class="tab-n">{{ $t['count'] }}</span>
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
