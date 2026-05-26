{{-- ── Phones sub-section ── --}}
{{-- ЯК ЦЕ ПРАЦЮЄ box --}}
<div style="margin-top:16px; padding:14px 18px; border:1px solid #e8cfa0; background:#fdf5e6; border-radius:4px;">
    <div style="font:500 10.5px var(--font-mono); letter-spacing:.08em; color:#b08020; margin-bottom:6px;">ЯК ЦЕ ПРАЦЮЄ</div>
    <p style="font:13px/1.5 var(--font-sans); color:#7a5a10; margin:0;">
        Резервні номери прив'язані до конкретного <strong>головного</strong> та показані з відступом під ним. Натисніть <strong>+ резерв</strong> щоб додати запасний до будь-якого головного.
    </p>
</div>

{{-- Phone table --}}
<div class="card" style="margin-top:16px; overflow:hidden;">
    {{-- Header --}}
    <div style="display:grid; grid-template-columns:24px 40px 1fr 1fr 160px 120px 32px; gap:12px; padding:10px 18px; background:var(--paper-2); border-bottom:1px solid var(--ink-3);">
        <span></span>
        <span class="eyebrow" style="font-size:9.5px;">#</span>
        <span class="eyebrow" style="font-size:9.5px;">Номер</span>
        <span class="eyebrow" style="font-size:9.5px;">Мітка</span>
        <span class="eyebrow" style="font-size:9.5px;">Гео-правило</span>
        <span class="eyebrow" style="font-size:9.5px;">Роль</span>
        <span></span>
    </div>

    @forelse($phonePrimaries as $i => $phone)
        @if($i > 0)
            <div style="border-top:1px solid var(--ink-3);"></div>
        @endif

        {{-- Primary row --}}
        <div wire:click="openPhone({{ $phone->id }})"
             style="display:grid; grid-template-columns:24px 40px 1fr 1fr 160px 120px 32px; gap:12px; padding:14px 18px; align-items:center; cursor:pointer; transition:background .1s;"
             onmouseover="this.style.background='var(--paper-2)'" onmouseout="this.style.background='transparent'">
            <span style="color:var(--ink-3); font:14px var(--font-mono); cursor:grab;">&#x2807;</span>
            <span style="font:11px var(--font-mono); color:var(--ink-4);">#{{ $i+1 }}</span>
            <span class="mono" style="font:14px var(--font-mono); color:var(--ink-9);">{{ $phone->value }}</span>
            <span style="font:13px var(--font-sans); color:var(--ink-7);">{{ $phone->label }}</span>
            <span style="font:12.5px var(--font-sans); color:var(--ink-5);">{{ $phone->geo_label }}</span>
            <span style="display:inline-flex; align-items:center; gap:5px; font:12.5px var(--font-sans);">
                <span style="width:7px;height:7px;border-radius:999px;background:var(--ok);"></span> Головний
            </span>
            <span style="color:var(--ink-4);">&rarr;</span>
        </div>

        {{-- РЕЗЕРВ section --}}
        @if($phone->backups->count() > 0)
            <div x-data="{open:true}" style="background:var(--paper-2); border-top:1px solid var(--ink-3);">
                {{-- РЕЗЕРВ header --}}
                <div style="display:flex; align-items:center; justify-content:space-between; padding:8px 18px 8px 64px; cursor:pointer;" @click="open=!open">
                    <span style="display:inline-flex; align-items:center; gap:6px; font:11px var(--font-mono); color:var(--ink-5); letter-spacing:.06em;">
                        <span x-text="open?'&#x25BE;':'&#x25B8;'"></span>
                        РЕЗЕРВ &middot; {{ $phone->backups->count() }}
                    </span>
                    <button style="font:11.5px var(--font-sans); color:var(--ink-5); cursor:pointer;" @click.stop>
                        + Додати резерв
                    </button>
                </div>
                {{-- Backup rows --}}
                <div x-show="open">
                    @foreach($phone->backups as $j => $backup)
                        <div style="display:grid; grid-template-columns:24px 40px 1fr 1fr 160px 120px 32px; gap:12px; padding:10px 18px 10px 64px; align-items:center; border-top:1px solid var(--ink-3); cursor:pointer; transition:background .1s;"
                             onmouseover="this.style.background='var(--paper-2)'" onmouseout="this.style.background='transparent'">
                            <span style="color:var(--ink-4); font:12px var(--font-mono);">&hookrightarrow;</span>
                            <span style="font:11px var(--font-mono); color:var(--ink-4);">#{{ $j+1 }}</span>
                            <span class="mono" style="font:13px var(--font-mono); color:var(--ink-7);">{{ $backup->value }}</span>
                            <span style="font:12.5px var(--font-sans); color:var(--ink-5);">{{ $backup->label }}</span>
                            <span style="font:12px var(--font-sans); color:var(--ink-5);">{{ $backup->geo_label }}</span>
                            <span style="display:inline-flex; align-items:center; gap:5px; font:12px var(--font-sans); color:var(--ink-5);">
                                <span style="width:7px;height:7px;border-radius:999px;background:var(--info);"></span> Резерв
                            </span>
                            <span style="color:var(--ink-4);">&rarr;</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    @empty
        <div style="padding:48px; text-align:center; color:var(--ink-5); font:13px var(--font-sans);">Немає телефонів для обраного гео.</div>
    @endforelse

    {{-- Hidden entries --}}
    @foreach($allPhonesAll->filter(fn($e)=>!$e->visible && is_null($e->parent_id)) as $phone)
        <div style="display:grid; grid-template-columns:24px 40px 1fr 1fr 160px 120px 32px; gap:12px; padding:14px 18px; align-items:center; border-top:1px solid var(--ink-3); opacity:.5;">
            <span style="color:var(--ink-3); font:14px var(--font-mono);">&#x2807;</span>
            <span style="font:11px var(--font-mono); color:var(--ink-4);">#{{ $loop->index+1 }}</span>
            <span class="mono" style="font:14px var(--font-mono); color:var(--ink-7);">{{ $phone->value }}</span>
            <span style="font:13px var(--font-sans); color:var(--ink-5);">{{ $phone->label }}</span>
            <span style="font:12.5px var(--font-sans); color:var(--ink-5);">{{ $phone->geo_label }}</span>
            <span style="display:inline-flex; align-items:center; gap:5px; font:12.5px var(--font-sans); color:var(--ink-5);">
                <span style="width:7px;height:7px;border-radius:999px;background:var(--ink-4);"></span> Сховано
            </span>
            <span style="color:var(--ink-4);">&rarr;</span>
        </div>
        @if($phone->backups->count()>0)
            <div style="padding:6px 18px 6px 64px; border-top:1px solid var(--ink-3); font:11.5px var(--font-sans); color:var(--ink-4);">
                + Додати резерв для цього номера
            </div>
        @endif
    @endforeach

    {{-- Footer add row --}}
    <div style="padding:12px 18px; border-top:1px solid var(--ink-3); display:flex; align-items:center;">
        <button style="font:13px var(--font-sans); color:var(--ink-5); cursor:pointer; display:inline-flex; align-items:center; gap:6px;">
            + Додати головний номер
        </button>
    </div>
</div>
