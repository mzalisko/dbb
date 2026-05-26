<div>
    <x-ui.modal name="client-form">
        <x-slot:title>{{ $clientId ? 'Редагувати клієнта' : 'Додати клієнта' }}</x-slot:title>

        <form wire:submit="save">
            <div style="display:flex;flex-direction:column;gap:20px;">
                <x-ui.input wire:model="company_name" label="Назва компанії" name="company_name"
                    :error="$errors->first('company_name')" />

                <x-ui.input wire:model="contact_name" label="Ім'я контакту" name="contact_name"
                    :error="$errors->first('contact_name')" />

                <x-ui.input wire:model="contact_email" label="Email" name="contact_email" type="email"
                    :error="$errors->first('contact_email')" />

                <x-ui.input wire:model="contact_phone" label="Телефон" name="contact_phone"
                    :error="$errors->first('contact_phone')" />

                <x-ui.select wire:model="status" label="Статус" name="status">
                    <option value="active">Активний</option>
                    <option value="inactive">Пауза</option>
                    <option value="archived">Архів</option>
                </x-ui.select>

                <x-ui.textarea wire:model="notes" label="Нотатки" name="notes" rows="3"
                    :error="$errors->first('notes')" />
            </div>

            <x-slot:footer>
                <x-ui.button variant="ghost" type="button" x-on:click="$dispatch('close-modal', 'client-form')">
                    Скасувати
                </x-ui.button>
                <x-ui.button type="submit">
                    {{ $clientId ? 'Зберегти' : 'Створити' }}
                </x-ui.button>
            </x-slot:footer>
        </form>
    </x-ui.modal>
</div>
