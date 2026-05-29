<div>
    <x-ui.modal name="invite-user">
        <x-slot:title>Запросити учасника</x-slot:title>

        <form wire:submit="save">
            <div style="display:flex; flex-direction:column; gap:20px;">
                <x-ui.input wire:model="name" label="Ім'я" name="invite_name"
                    :error="$errors->first('name')" />

                <x-ui.input wire:model="email" label="Email" name="invite_email" type="email"
                    :error="$errors->first('email')" />

                <div>
                    <span class="label">Роль</span>
                    <div class="role-cards" style="margin-top:10px;">
                        @foreach ([
                            ['admin',   'Admin',   'Повний доступ'],
                            ['manager', 'Manager', 'Дані + сайти'],
                            ['viewer',  'Viewer',  'Тільки читання'],
                        ] as [$k, $l, $d])
                            <button type="button"
                                wire:click="$set('role', '{{ $k }}')"
                                class="role-card {{ $role === $k ? 'role-card--active' : '' }}">
                                <div class="role-card__name">{{ $l }}</div>
                                <div class="role-card__desc">{{ $d }}</div>
                            </button>
                        @endforeach
                    </div>
                    @error('role')
                        <p style="margin-top:6px; font:12px var(--font-mono); color:var(--bad);">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Footer inside form (must be inside <form> for submit button to work) --}}
            <div style="display:flex; align-items:center; justify-content:flex-end; gap:8px;
                        margin: 20px -24px -20px; padding: 16px 24px 20px;
                        border-top: 1px solid var(--ink-3); background: var(--paper-2);">
                <button type="button" class="btn btn-ghost"
                    @click="$dispatch('close-modal', 'invite-user')">
                    Скасувати
                </button>
                <button type="submit" class="btn btn-primary">
                    Запросити
                </button>
            </div>
        </form>
    </x-ui.modal>
</div>
