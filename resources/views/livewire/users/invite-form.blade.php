<div>
    <x-ui.modal name="invite-user">
        <x-slot:title>Запросити учасника</x-slot:title>

        <form wire:submit="save">
            <div style="display:flex;flex-direction:column;gap:20px;">
                <x-ui.input wire:model="name" label="Ім'я" name="invite_name"
                    :error="$errors->first('name')" />

                <x-ui.input wire:model="email" label="Email" name="invite_email" type="email"
                    :error="$errors->first('email')" />

                <x-ui.select wire:model="role" label="Роль" name="invite_role">
                    <option value="member">Учасник</option>
                    <option value="admin">Адміністратор</option>
                </x-ui.select>
            </div>

            <x-slot:footer>
                <x-ui.button variant="ghost" type="button" x-on:click="$dispatch('close-modal', 'invite-user')">
                    Скасувати
                </x-ui.button>
                <x-ui.button type="submit">
                    Запросити
                </x-ui.button>
            </x-slot:footer>
        </form>
    </x-ui.modal>
</div>
