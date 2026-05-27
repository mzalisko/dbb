{{-- ─── PhoneDrawer ─────────────────────────────────────── --}}
@if ($openPhoneId)
    @php $ph = $allPhones->firstWhere('id', $openPhoneId); @endphp
    @if ($ph)
        <x-ui.drawer :open="true" :title="$ph->value" :sub="$ph->label . ' · ' . $ph->geo_label" @drawer-close.window="$wire.closePhone()">
            <div class="drawer-stack">
                <div><label class="label">Номер</label><div class="mono field-value-lg">{{ $ph->value }}</div></div>
                <div><label class="label">Мітка</label><input class="input" value="{{ $ph->label }}" readonly /></div>
                <div>
                    <label class="label">Роль</label>
                    <div class="role-pills">
                        @foreach([['primary','Головний'],['backup','Резерв'],['archive','Архів']] as [$k,$l])
                            <span class="role-pill {{ $ph->role===$k ? 'is-active' : '' }}">{{ $l }}</span>
                        @endforeach
                    </div>
                </div>
                <div><label class="label">Гео-правило</label><div class="field-value">{{ $ph->geo_label ?: 'Усі гео' }}</div></div>
            </div>
            <x-slot:footer>
                <button class="btn btn-ghost" wire:click="closePhone">Закрити</button>
                <button class="btn btn-primary" disabled>Редагувати (незабаром)</button>
            </x-slot:footer>
        </x-ui.drawer>
    @endif
@endif
