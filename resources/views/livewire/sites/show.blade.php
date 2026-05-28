<div class="page"
     x-data="{
         tab:         (['overview','data','activity','settings'].includes((location.hash.slice(1)||'').split('/')[0]) ? location.hash.slice(1).split('/')[0] : 'data'),
         settingsSub: (location.hash.slice(1).split('/')[1] || 'failover'),
         cat: 'phones', geo: 'all', actFilter: 'all'
     }"
     x-init="
         $watch('tab',         t => history.replaceState(null,'',location.pathname+'#'+t+(t==='settings'?'/'+settingsSub:'')));
         $watch('settingsSub', s => { if(tab==='settings') history.replaceState(null,'',location.pathname+'#settings/'+s); });
     ">

    <x-ui.topbar :crumbs="['Сайти', $site->name]" :back="true">
        <button class="btn btn-ghost btn-sm">
            <x-icon.refresh width="13" height="13" /> Sync
        </button>
        <button class="btn btn-primary btn-sm"
                x-show="tab === 'data'"
                x-cloak
                x-on:click="
                    const tag = (geo && geo !== 'all') ? geo : null;
                    if (cat === 'phones') $wire.addEntry('phone', null, tag);
                    else if (cat === 'messengers') $wire.addEntry('messenger', null, tag);
                    else if (cat === 'prices') $wire.addEntry('price');
                ">
            <x-icon.plus width="13" height="13" /> Додати
        </button>
    </x-ui.topbar>

    {{-- Page head --}}
    <div class="site-head">
        @php
            $groupLabel = $site->group ? strtoupper($site->group) : 'NO GROUP';
            $statusLabel = match($site->status) { 'active'=>'АКТИВНИЙ','maintenance'=>'ПАУЗА',default=>'ПОМИЛКА' };
            $statusColor = match($site->status) { 'active'=>'var(--ok)','maintenance'=>'var(--warn)',default=>'var(--bad)' };
        @endphp
        <div class="site-head__meta">
            <span class="site-head__meta-item">
                <span class="site-head__dot" style="background:{{ $site->group_color ?? 'var(--ink-4)' }};"></span>
                {{ $groupLabel }}
            </span>
            <span class="site-head__sep">&middot;</span>
            <span class="site-head__meta-item">
                <span class="site-head__dot" style="background:{{ $statusColor }};"></span>
                {{ $statusLabel }}
            </span>
        </div>
        <h1 class="site-head__title">{{ $site->name }}</h1>
    </div>

    {{-- Tabs --}}
    @php $dataCount = $phoneCount + $msgCount; @endphp
    <div class="tabs site-tabs">
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
