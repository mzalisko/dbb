{{-- ─── PhoneDrawer ─────────────────────────────────────── --}}
@if ($openPhoneId)
    @php $ph = $allPhones->firstWhere('id', $openPhoneId); @endphp
    @if ($ph)
        <x-ui.drawer :open="true" :title="$ph->value" :sub="$ph->label . ' · ' . $ph->geo_label" @drawer-close.window="$wire.closePhone()">
            <div style="display:flex;flex-direction:column;gap:20px;">
                <div><label class="label">Номер</label><div class="mono" style="font:20px var(--font-mono);color:var(--ink-9);margin-top:8px;">{{ $ph->value }}</div></div>
                <div><label class="label">Мітка</label><input class="input" value="{{ $ph->label }}" readonly /></div>
                <div>
                    <label class="label">Роль</label>
                    <div style="display:flex;gap:8px;margin-top:8px;">
                        @foreach([['primary','Головний'],['backup','Резерв'],['archive','Архів']] as [$k,$l])
                            <span style="flex:1;padding:10px;text-align:center;border-radius:4px;font:13px var(--font-sans);
                                border:1px solid {{ $ph->role===$k?'var(--ink-9)':'var(--ink-3)' }};
                                background:{{ $ph->role===$k?'var(--ink-9)':'transparent' }};
                                color:{{ $ph->role===$k?'var(--paper)':'var(--ink-7)' }};">{{ $l }}</span>
                        @endforeach
                    </div>
                </div>
                <div><label class="label">Гео-правило</label><div style="margin-top:8px;font:13.5px var(--font-sans);color:var(--ink-7);">{{ $ph->geo_label ?: 'Усі гео' }}</div></div>
            </div>
            <x-slot:footer>
                <button class="btn btn-ghost" wire:click="closePhone">Закрити</button>
                <button class="btn btn-primary" disabled style="opacity:.4;">Редагувати (незабаром)</button>
            </x-slot:footer>
        </x-ui.drawer>
    @endif
@endif
