{{-- ─── Tab: Огляд ─────────────────────────────────────── --}}
<div x-show="tab==='overview'" style="padding:32px 40px 64px;">
    <div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:14px;">
        @foreach ($geos as $geo)
            <div x-data="{expanded:false}" class="card" style="padding:22px; position:relative; overflow:hidden;">
                @if($geo['key']==='PL')
                    <div style="position:absolute;top:0;left:0;right:0;height:3px;background:#dc143c;"></div>
                @elseif($geo['key']==='UA')
                    <div style="position:absolute;top:0;left:0;right:0;height:3px;background:#0057b7;"></div>
                @else
                    <div style="position:absolute;top:0;left:0;right:0;height:3px;background:var(--ink-4);"></div>
                @endif
                <header style="display:flex;align-items:center;gap:10px;margin-bottom:16px;margin-top:4px;">
                    <span style="font:22px var(--font-sans);">{{ $geo['flag'] }}</span>
                    <div>
                        <div style="font:500 14px/1.2 var(--font-sans);color:var(--ink-9);">{{ $geo['label'] }}</div>
                        <div style="font:11.5px var(--font-mono);color:var(--ink-5);margin-top:3px;">
                            {{ $geo['backupPhones']->count()+($geo['primaryPhone']?1:0) }} тел.
                            &middot; {{ $geo['backupMsgs']->count()+($geo['primaryMsg']?1:0) }} месенджерів
                        </div>
                    </div>
                </header>
                @if($geo['primaryPhone'])
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
                        <x-icon.phone width="14" height="14" style="color:var(--ink-5);flex-shrink:0;" />
                        <div style="flex:1;min-width:0;">
                            <div class="mono" style="font:14px var(--font-mono);color:var(--ink-9);">{{ $geo['primaryPhone']->value }}</div>
                            <div style="font:11.5px var(--font-sans);color:var(--ink-5);">{{ $geo['primaryPhone']->label }}</div>
                        </div>
                        @if($geo['backupPhones']->count()>0)
                            <button @click="expanded=!expanded" style="font:11.5px var(--font-mono);color:var(--ink-5);cursor:pointer;"
                                x-text="expanded?'−':'+{{ $geo['backupPhones']->count() }}'"></button>
                        @endif
                    </div>
                    <div x-show="expanded" style="margin-left:24px;margin-bottom:10px;display:flex;flex-direction:column;gap:6px;">
                        @foreach($geo['backupPhones'] as $b)
                            <div style="display:flex;align-items:center;gap:8px;">
                                <span style="width:6px;height:6px;border-radius:999px;background:var(--info);flex-shrink:0;"></span>
                                <span class="mono" style="font:12.5px var(--font-mono);color:var(--ink-7);">{{ $b->value }}</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div style="font:13px var(--font-sans);color:var(--ink-4);margin-bottom:10px;font-style:italic;">Немає телефону</div>
                @endif
                <div style="border-top:1px solid var(--ink-3);margin:10px 0;"></div>
                @if($geo['primaryMsg'])
                    @php $k=\App\Models\ContactEntry::MSG_KINDS[$geo['primaryMsg']->kind]??['label'=>$geo['primaryMsg']->kind,'color'=>'#888','short'=>'??']; @endphp
                    <div style="display:flex;align-items:center;gap:10px;">
                        <span style="width:24px;height:24px;border-radius:4px;display:inline-flex;align-items:center;justify-content:center;font:bold 9.5px var(--font-mono);color:#fff;background:{{ $k['color'] }};flex-shrink:0;">{{ $k['short'] }}</span>
                        <div style="flex:1;min-width:0;">
                            <div class="mono" style="font:13.5px var(--font-mono);color:var(--ink-9);">{{ $geo['primaryMsg']->value }}</div>
                            <div style="font:11.5px var(--font-sans);color:var(--ink-5);">{{ $geo['primaryMsg']->label }}</div>
                        </div>
                    </div>
                @else
                    <div style="font:13px var(--font-sans);color:var(--ink-4);font-style:italic;">Немає месенджера</div>
                @endif
            </div>
        @endforeach
    </div>
    <div class="card" style="margin-top:24px;display:grid;grid-template-columns:repeat(4,1fr);">
        @foreach([
            ['l'=>'WordPress','v'=>$site->wp_version??'Unknown'],
            ['l'=>'PHP','v'=>$site->php_version??'Unknown'],
            ['l'=>'Статус','v'=>ucfirst($site->status)],
            ['l'=>'Остання перевірка','v'=>$site->last_checked_at?->format('d M H:i')??'Ніколи'],
        ] as $i=>$info)
            <div style="padding:16px 20px;{{ $i?'border-left:1px solid var(--ink-3);':'' }}">
                <div class="eyebrow" style="font-size:10px;margin-bottom:8px;">{{ $info['l'] }}</div>
                <div class="mono" style="font:14px var(--font-mono);color:var(--ink-9);">{{ $info['v'] }}</div>
            </div>
        @endforeach
    </div>
</div>
