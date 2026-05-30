<div class="page"
     x-data="{
         tab:         (['overview','data','activity','settings'].includes((location.hash.slice(1)||'').split('/')[0]) ? location.hash.slice(1).split('/')[0] : 'overview'),
         settingsSub: ((location.hash.slice(1).split('/')[1] || 'failover') === 'api' ? 'general' : (location.hash.slice(1).split('/')[1] || 'failover')),
         cat: 'phones', geo: 'all', msgKind: 'all', actFilter: 'all'
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

    <div class="card geo-stats site-top-stats">
        @foreach([
            ['l' => 'WordPress', 'v' => $site->wp_version ?? 'Unknown'],
            ['l' => 'PHP', 'v' => $site->php_version ?? 'Unknown'],
            ['l' => '&#1057;&#1090;&#1072;&#1090;&#1091;&#1089;', 'v' => ucfirst($site->status)],
            ['l' => '&#1054;&#1089;&#1090;&#1072;&#1085;&#1085;&#1103; &#1087;&#1077;&#1088;&#1077;&#1074;&#1110;&#1088;&#1082;&#1072;', 'v' => $site->last_checked_at?->format('d M H:i') ?? html_entity_decode('&#1053;&#1110;&#1082;&#1086;&#1083;&#1080;', ENT_QUOTES, 'UTF-8')],
        ] as $info)
            <div class="geo-stat">
                <div class="eyebrow eyebrow-xs" style="margin-bottom:8px;">{!! $info['l'] !!}</div>
                <div class="geo-stat__val">{{ $info['v'] }}</div>
            </div>
        @endforeach
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
    @include('livewire.sites.partials.messenger-drawer')
    @include('livewire.sites.partials.price-drawer')
    @include('livewire.sites.partials.confirm-action-modal')

</div>
