{{-- ── Messengers sub-section ── --}}
{{-- ЯК ЦЕ ПРАЦЮЄ box --}}
<div style="margin-top:16px; padding:14px 18px; border:1px solid #e8cfa0; background:#fdf5e6; border-radius:4px;">
    <div style="font:500 10.5px var(--font-mono); letter-spacing:.08em; color:#b08020; margin-bottom:6px;">ЯК ЦЕ ПРАЦЮЄ</div>
    <p style="font:13px/1.5 var(--font-sans); color:#7a5a10; margin:0;">
        Один <strong>головний месенджер</strong> на гео-пул, інші — резерв. Можна змішувати Telegram / Viber / WhatsApp в одному пулі — клієнт обере зручний.
    </p>
</div>

{{-- Platform pills --}}
@if($msgByKind->count() > 0)
    <div style="margin-top:14px; display:flex; gap:6px; flex-wrap:wrap;">
        @foreach($msgByKind as $kindKey => $kindEntries)
            @php $kd = \App\Models\ContactEntry::MSG_KINDS[$kindKey] ?? ['label'=>$kindKey,'color'=>'#888','short'=>'??']; @endphp
            <span style="display:inline-flex; align-items:center; gap:6px; height:28px; padding:0 12px; border-radius:999px; font:12.5px var(--font-sans);
                background:{{ $kd['color'] }}20; color:{{ $kd['color'] }}; border:1px solid {{ $kd['color'] }}40;">
                <span style="width:18px;height:18px;border-radius:3px;background:{{ $kd['color'] }};display:inline-flex;align-items:center;justify-content:center;font:bold 8px var(--font-mono);color:#fff;">{{ $kd['short'] }}</span>
                {{ $kd['label'] }} {{ $kindEntries->count() }}
            </span>
        @endforeach
    </div>
@endif

{{-- Messenger table --}}
<div class="card" style="margin-top:14px; overflow:hidden;">
    <div style="display:grid; grid-template-columns:24px 40px 1.5fr 1fr 160px 120px 32px; gap:12px; padding:10px 18px; background:var(--paper-2); border-bottom:1px solid var(--ink-3);">
        <span></span>
        <span class="eyebrow" style="font-size:9.5px;">#</span>
        <span class="eyebrow" style="font-size:9.5px;">Контакт</span>
        <span class="eyebrow" style="font-size:9.5px;">Мітка</span>
        <span class="eyebrow" style="font-size:9.5px;">Гео-правило</span>
        <span class="eyebrow" style="font-size:9.5px;">Роль</span>
        <span></span>
    </div>
    @forelse($msgPrimaries as $i => $msg)
        @php $k=\App\Models\ContactEntry::MSG_KINDS[$msg->kind]??['label'=>$msg->kind,'color'=>'#888','short'=>'??']; @endphp
        @if($i>0)<div style="border-top:1px solid var(--ink-3);"></div>@endif
        <div style="display:grid; grid-template-columns:24px 40px 1.5fr 1fr 160px 120px 32px; gap:12px; padding:14px 18px; align-items:center; cursor:pointer; transition:background .1s;"
             onmouseover="this.style.background='var(--paper-2)'" onmouseout="this.style.background='transparent'">
            <span style="color:var(--ink-3);font:14px var(--font-mono);">&#x2807;</span>
            <span style="font:11px var(--font-mono);color:var(--ink-4);">#{{ $i+1 }}</span>
            <div style="display:flex;align-items:center;gap:8px;">
                <span style="width:28px;height:28px;border-radius:6px;display:inline-flex;align-items:center;justify-content:center;font:bold 10px var(--font-mono);color:#fff;background:{{ $k['color'] }};flex-shrink:0;">{{ $k['short'] }}</span>
                <div>
                    <div class="mono" style="font:13.5px var(--font-mono);color:var(--ink-9);">{{ $msg->value }}</div>
                    <div style="font:11px var(--font-sans);color:var(--ink-5);">{{ $k['label'] }}</div>
                </div>
            </div>
            <span style="font:13px var(--font-sans);color:var(--ink-7);">{{ $msg->label }}</span>
            <span style="font:12.5px var(--font-sans);color:var(--ink-5);">{{ $msg->geo_label }}</span>
            <span style="display:inline-flex;align-items:center;gap:5px;font:12.5px var(--font-sans);">
                <span style="width:7px;height:7px;border-radius:999px;background:var(--ok);"></span> Головний
            </span>
            <span style="color:var(--ink-4);">&rarr;</span>
        </div>
        @if($msg->backups->count()>0)
            <div x-data="{open:true}" style="background:var(--paper-2);border-top:1px solid var(--ink-3);">
                <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 18px 8px 64px;cursor:pointer;" @click="open=!open">
                    <span style="font:11px var(--font-mono);color:var(--ink-5);letter-spacing:.06em;">
                        <span x-text="open?'&#x25BE;':'&#x25B8;'"></span> РЕЗЕРВ &middot; {{ $msg->backups->count() }}
                    </span>
                    @php
                        $backupKinds = $msg->backups->groupBy('kind');
                        $kindShorts = $backupKinds->keys()->map(fn($k)=>(\App\Models\ContactEntry::MSG_KINDS[$k]??['short'=>'??'])['short'])->join(' · ');
                    @endphp
                    <span style="font:11px var(--font-mono);color:var(--ink-4);">{{ $kindShorts }}</span>
                </div>
                <div x-show="open">
                    @foreach($msg->backups as $j=>$backup)
                        @php $bk=\App\Models\ContactEntry::MSG_KINDS[$backup->kind]??['label'=>$backup->kind,'color'=>'#888','short'=>'??']; @endphp
                        <div style="display:grid;grid-template-columns:24px 40px 1.5fr 1fr 160px 120px 32px;gap:12px;padding:10px 18px 10px 64px;align-items:center;border-top:1px solid var(--ink-3);">
                            <span style="color:var(--ink-4);font:12px var(--font-mono);">&hookrightarrow;</span>
                            <span style="font:11px var(--font-mono);color:var(--ink-4);">#{{ $j+1 }}</span>
                            <div style="display:flex;align-items:center;gap:8px;">
                                <span style="width:22px;height:22px;border-radius:4px;display:inline-flex;align-items:center;justify-content:center;font:bold 9px var(--font-mono);color:#fff;background:{{ $bk['color'] }};opacity:.8;">{{ $bk['short'] }}</span>
                                <span class="mono" style="font:13px var(--font-mono);color:var(--ink-7);">{{ $backup->value }}</span>
                            </div>
                            <span style="font:12.5px var(--font-sans);color:var(--ink-5);">{{ $backup->label }}</span>
                            <span style="font:12px var(--font-sans);color:var(--ink-5);">{{ $backup->geo_label }}</span>
                            <span style="display:inline-flex;align-items:center;gap:5px;font:12px var(--font-sans);color:var(--ink-5);">
                                <span style="width:7px;height:7px;border-radius:999px;background:var(--info);"></span> Резерв
                            </span>
                            <span style="color:var(--ink-4);">&rarr;</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    @empty
        <div style="padding:48px;text-align:center;color:var(--ink-5);font:13px var(--font-sans);">Немає месенджерів.</div>
    @endforelse
    <div style="padding:12px 18px;border-top:1px solid var(--ink-3);">
        <button style="font:13px var(--font-sans);color:var(--ink-5);cursor:pointer;">+ Додати головний месенджер</button>
    </div>
</div>
