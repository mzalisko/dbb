{{-- ── Phones sub-section ── --}}
{{-- ЯК ЦЕ ПРАЦЮЄ box --}}
<div class="howto">
    <div class="howto__title">Як це працює</div>
    <div class="howto__text">
        Резервні номери прив'язані до конкретного <b>головного</b> та показані з відступом під ним. Натисніть <b>+ резерв</b> щоб додати запасний до будь-якого головного.
    </div>
</div>

{{-- Phone table --}}
<div class="card ctable">
    {{-- Header --}}
    <div class="crow crow--head">
        <span></span>
        <span class="eyebrow eyebrow-xxs">#</span>
        <span class="eyebrow eyebrow-xxs">Номер</span>
        <span class="eyebrow eyebrow-xxs">Мітка</span>
        <span class="eyebrow eyebrow-xxs">Гео-правило</span>
        <span class="eyebrow eyebrow-xxs">Роль</span>
        <span></span>
    </div>

    @forelse($phonePrimaries as $i => $phone)
        @if($i > 0)
            <div class="crow-sep"></div>
        @endif

        {{-- Primary row --}}
        <div wire:click="openPhone({{ $phone->id }})" class="crow crow--main">
            <span class="cc-drag">&#x2807;</span>
            <span class="cc-num">#{{ $i+1 }}</span>
            <span class="mono cc-val">{{ $phone->value }}</span>
            <span class="cc-label">{{ $phone->label }}</span>
            <span class="cc-geo">{{ $phone->geo_label }}</span>
            <span class="cc-role">
                <span class="role-dot" style="background:var(--ok);"></span> Головний
            </span>
            <span class="cc-arrow">&rarr;</span>
        </div>

        {{-- РЕЗЕРВ section --}}
        @if($phone->backups->count() > 0)
            <div x-data="{open:true}" class="creserve">
                {{-- РЕЗЕРВ header --}}
                <div class="creserve__head" @click="open=!open">
                    <span class="creserve__title">
                        <span x-text="open?'&#x25BE;':'&#x25B8;'"></span>
                        РЕЗЕРВ &middot; {{ $phone->backups->count() }}
                    </span>
                    <button class="creserve__add" @click.stop>+ Додати резерв</button>
                </div>
                {{-- Backup rows --}}
                <div x-show="open">
                    @foreach($phone->backups as $j => $backup)
                        <div class="crow crow--backup">
                            <span class="cc-drag" style="color:var(--ink-4); font-size:12px;">&hookrightarrow;</span>
                            <span class="cc-num">#{{ $j+1 }}</span>
                            <span class="mono cc-val--sub">{{ $backup->value }}</span>
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
        <div class="ctable__empty">Немає телефонів для обраного гео.</div>
    @endforelse

    {{-- Hidden entries --}}
    @foreach($allPhonesAll->filter(fn($e)=>!$e->visible && is_null($e->parent_id)) as $phone)
        <div class="crow crow--hidden">
            <span class="cc-drag">&#x2807;</span>
            <span class="cc-num">#{{ $loop->index+1 }}</span>
            <span class="mono cc-val--sub">{{ $phone->value }}</span>
            <span class="cc-label--muted">{{ $phone->label }}</span>
            <span class="cc-geo">{{ $phone->geo_label }}</span>
            <span class="cc-role cc-role--muted">
                <span class="role-dot" style="background:var(--ink-4);"></span> Сховано
            </span>
            <span class="cc-arrow">&rarr;</span>
        </div>
        @if($phone->backups->count()>0)
            <div class="ctable__hidden-add">+ Додати резерв для цього номера</div>
        @endif
    @endforeach

    {{-- Footer add row --}}
    <div class="ctable__foot">
        <button class="ctable__add">+ Додати головний номер</button>
    </div>
</div>
