{{-- ─── Tab: Активність ─────────────────────────────────── --}}
<div x-show="tab==='activity'" style="padding:32px 40px 64px;">
    <div style="display:flex; align-items:flex-end; justify-content:space-between; margin-bottom:8px;">
        <h3 style="font:400 28px/1 var(--font-sans); color:var(--ink-9); letter-spacing:-0.02em;">Журнал змін</h3>
        <span style="font:12px var(--font-mono); color:var(--ink-5);">{{ $activityLogs->count() }} подій &middot; 30 днів</span>
    </div>
    <p style="font:13.5px var(--font-sans); color:var(--ink-5); margin-bottom:20px;">
        Натисніть рядок щоб переглянути повний diff і метадані. Експорт у CSV — справа зверху.
    </p>

    {{-- Filter pills --}}
    <div style="display:flex; align-items:center; gap:6px; margin-bottom:20px; flex-wrap:wrap;">
        @php
            $actTypes = ['all'=>'Усі','change'=>'Зміни','failover'=>'Failover','create'=>'Створення','delete'=>'Видалення','alert'=>'Сповіщення'];
        @endphp
        @foreach($actTypes as $key => $label)
            @php
                $cnt = $key==='all' ? $activityLogs->count() : $activityLogs->filter(fn($l)=>str_contains($l->action,$key))->count();
            @endphp
            <button @click="actFilter='{{ $key }}'" style="
                display:inline-flex; align-items:center; gap:5px; height:30px; padding:0 12px; border-radius:999px; cursor:pointer; font:12.5px var(--font-sans);"
                :style="actFilter==='{{ $key }}' ? 'background:var(--ink-9);color:var(--paper);' : 'background:var(--card);color:var(--ink-7);box-shadow:inset 0 0 0 1px var(--ink-3);'">
                {{ $label }} {{ $cnt }}
            </button>
        @endforeach
        <div style="flex:1;"></div>
        <button style="display:inline-flex;align-items:center;gap:5px;font:12.5px var(--font-sans);color:var(--ink-5);cursor:pointer;">
            &uarr; Експорт
        </button>
    </div>

    {{-- Activity table --}}
    <div class="card" style="overflow:hidden;">
        <div style="display:grid; grid-template-columns:40px 1fr 160px 120px 32px; gap:12px; padding:10px 18px; background:var(--paper-2); border-bottom:1px solid var(--ink-3);">
            <span></span>
            <span class="eyebrow" style="font-size:9.5px;">Подія та об'єкт</span>
            <span class="eyebrow" style="font-size:9.5px;">Хто</span>
            <span class="eyebrow" style="font-size:9.5px;">Коли</span>
            <span></span>
        </div>
        @forelse($activityLogs as $i => $log)
            @php
                $isFail = str_contains($log->action,'fail')||str_contains($log->action,'offline')||str_contains($log->action,'error');
                $isCreate = str_contains($log->action,'creat')||str_contains($log->action,'add');
                $isDelete = str_contains($log->action,'delet')||str_contains($log->action,'remov');
                $dotBg = $isFail ? 'var(--bad)' : ($isCreate ? 'var(--ok)' : ($isDelete ? 'var(--bad)' : 'var(--info)'));
                $iconText = $isFail ? '&#x26A1;' : ($isCreate ? '+' : ($isDelete ? '&#x2715;' : '&#x270E;'));
            @endphp
            <div x-data="{open:false}" style="border-top:{{ $i?'1px solid var(--ink-3)':'none' }};">
                <div @click="open=!open" style="display:grid; grid-template-columns:40px 1fr 160px 120px 32px; gap:12px; padding:14px 18px; align-items:center; cursor:pointer; transition:background .1s;"
                     onmouseover="this.style.background='var(--paper-2)'" onmouseout="this.style.background='transparent'">
                    <span style="width:28px;height:28px;border-radius:6px;background:{{ $dotBg }}20;display:inline-flex;align-items:center;justify-content:center;font:13px var(--font-sans);color:{{ $dotBg }};">{!! $iconText !!}</span>
                    <div>
                        <div style="font:13.5px var(--font-sans);color:var(--ink-9);">{{ ucfirst($log->action) }}</div>
                        @if($log->subject_type)
                            <div style="font:12px var(--font-mono);color:var(--ink-5);margin-top:2px;">{{ class_basename($log->subject_type) }}</div>
                        @endif
                    </div>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <span class="avatar" style="width:22px;height:22px;font-size:10px;background:{{ $log->user?'var(--ink-9)':'var(--ink-4)' }};color:var(--paper);">
                            {{ strtoupper(substr($log->user?->name??'S',0,2)) }}
                        </span>
                        <span style="font:12.5px var(--font-sans);color:var(--ink-7);">{{ $log->user?->name??'Система' }}</span>
                    </div>
                    <span class="mono" style="font:12px var(--font-mono);color:var(--ink-5);">{{ $log->created_at->diffForHumans(null,true) }}</span>
                    <span style="color:var(--ink-4);" x-text="open?'&#x2228;':'&#x203A;'"></span>
                </div>
                {{-- Expandable diff --}}
                <div x-show="open" style="padding:14px 18px 14px 70px; background:var(--paper-2); border-top:1px solid var(--ink-3);">
                    <div style="font:12px var(--font-mono); color:var(--ink-5); margin-bottom:8px;">{{ $log->created_at->format('d M · H:i:s') }}</div>
                    @if($log->properties)
                        @foreach(is_array($log->properties) ? $log->properties : [] as $field => $change)
                            <div style="display:grid;grid-template-columns:120px 1fr 24px 1fr;gap:8px;padding:6px 0;border-top:1px solid var(--ink-3);align-items:center;font:12.5px var(--font-sans);">
                                <span style="color:var(--ink-5);">{{ $field }}</span>
                                <span style="padding:4px 8px;background:#fde8e8;border-radius:3px;font:12px var(--font-mono);color:#c00;">{{ is_array($change)?($change['old']??'—'):$change }}</span>
                                <span style="text-align:center;color:var(--ink-4);">&rarr;</span>
                                <span style="padding:4px 8px;background:#e6f4ea;border-radius:3px;font:12px var(--font-mono);color:#1a7a30;">{{ is_array($change)?($change['new']??'—'):$change }}</span>
                            </div>
                        @endforeach
                    @else
                        <p style="font:13px var(--font-sans);color:var(--ink-5);">Деталі недоступні.</p>
                    @endif
                </div>
            </div>
        @empty
            <div style="padding:64px;text-align:center;color:var(--ink-5);font:13.5px var(--font-sans);">Немає подій для цього сайту.</div>
        @endforelse
    </div>
</div>
