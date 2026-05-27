{{-- ── Messengers sub-section ── --}}
{{-- Platform pills --}}
@if($msgByKind->count() > 0)
    <div class="msg-pills">
        @foreach($msgByKind as $kindKey => $kindEntries)
            @php $kd = \App\Models\ContactEntry::MSG_KINDS[$kindKey] ?? ['label'=>$kindKey,'color'=>'#888','short'=>'??']; @endphp
            <span class="msg-pill" style="background:{{ $kd['color'] }}20; color:{{ $kd['color'] }}; border:1px solid {{ $kd['color'] }}40;">
                <span class="msg-pill__badge" style="background:{{ $kd['color'] }};">{{ $kd['short'] }}</span>
                {{ $kd['label'] }} {{ $kindEntries->count() }}
            </span>
        @endforeach
    </div>
@endif

{{-- Messenger table --}}
<div class="card ctable">
    <div class="crow crow--msg crow--head">
        <span></span>
        <span class="eyebrow eyebrow-xxs">#</span>
        <span class="eyebrow eyebrow-xxs">Контакт</span>
        <span class="eyebrow eyebrow-xxs">Мітка</span>
        <span class="eyebrow eyebrow-xxs">Гео-правило</span>
        <span class="eyebrow eyebrow-xxs">Роль</span>
        <span></span>
    </div>
    @forelse($msgPrimaries as $i => $msg)
        @php $k=\App\Models\ContactEntry::MSG_KINDS[$msg->kind]??['label'=>$msg->kind,'color'=>'#888','short'=>'??']; @endphp
        @if($i>0)<div class="crow-sep"></div>@endif
        <div class="crow crow--msg crow--main">
            <span class="cc-drag">&#x2807;</span>
            <span class="cc-num">#{{ $i+1 }}</span>
            <div class="msg-contact">
                <span class="msg-badge" style="background:{{ $k['color'] }};">{{ $k['short'] }}</span>
                <div>
                    <div class="mono cc-val">{{ $msg->value }}</div>
                    <div class="msg-kind">{{ $k['label'] }}</div>
                </div>
            </div>
            <span class="cc-label">{{ $msg->label }}</span>
            <span class="cc-geo">{{ $msg->geo_label }}</span>
            <span class="cc-role">
                <span class="role-dot" style="background:var(--ok);"></span> Головний
            </span>
            <span class="cc-arrow">&rarr;</span>
        </div>
        @if($msg->backups->count()>0)
            <div x-data="{open:false}" class="creserve">
                <div class="creserve__head" @click="open=!open">
                    <span class="creserve__title">
                        <span x-text="open?'&#x25BE;':'&#x25B8;'"></span> РЕЗЕРВ &middot; {{ $msg->backups->count() }}
                    </span>
                    @php
                        $backupKinds = $msg->backups->groupBy('kind');
                        $kindShorts = $backupKinds->keys()->map(fn($k)=>(\App\Models\ContactEntry::MSG_KINDS[$k]??['short'=>'??'])['short'])->join(' · ');
                    @endphp
                    <span class="creserve__kinds">{{ $kindShorts }}</span>
                </div>
                <div x-show="open">
                    @foreach($msg->backups as $j=>$backup)
                        @php $bk=\App\Models\ContactEntry::MSG_KINDS[$backup->kind]??['label'=>$backup->kind,'color'=>'#888','short'=>'??']; @endphp
                        <div class="crow crow--msg crow--sub">
                            <span class="cc-drag" style="color:var(--ink-4); font-size:12px;">&hookrightarrow;</span>
                            <span class="cc-num">#{{ $j+1 }}</span>
                            <div class="msg-contact">
                                <span class="msg-badge msg-badge--sm" style="background:{{ $bk['color'] }};">{{ $bk['short'] }}</span>
                                <span class="mono cc-val--sub">{{ $backup->value }}</span>
                            </div>
                            <span class="cc-label--muted">{{ $backup->label }}</span>
                            <span class="cc-geo">{{ $backup->geo_label }}</span>
                            <span class="cc-role cc-role--muted">
                                <span class="role-dot" style="background:var(--info);"></span> Резерв
                            </span>
                            <span class="cc-arrow">&rarr;</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    @empty
        <div class="ctable__empty">Немає месенджерів.</div>
    @endforelse
    <div class="ctable__foot">
        <button class="ctable__add">+ Додати головний месенджер</button>
    </div>
</div>
