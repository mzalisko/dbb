<div>
    <x-ui.modal name="client-form">
        <x-slot:title>{{ $clientId ? 'Edit Client' : 'Add Client' }}</x-slot:title>

        <form wire:submit="save">
            <div style="display:flex;flex-direction:column;gap:20px;">
                <x-ui.input wire:model="company_name" label="Company Name" name="company_name"
                    :error="$errors->first('company_name')" />

                <x-ui.input wire:model="contact_name" label="Contact Name" name="contact_name"
                    :error="$errors->first('contact_name')" />

                <x-ui.input wire:model="contact_email" label="Contact Email" name="contact_email" type="email"
                    :error="$errors->first('contact_email')" />

                <x-ui.input wire:model="contact_phone" label="Phone" name="contact_phone"
                    :error="$errors->first('contact_phone')" />

                <x-ui.select wire:model="status" label="Status" name="status">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="archived">Archived</option>
                </x-ui.select>

                <x-ui.textarea wire:model="notes" label="Notes" name="notes" rows="3"
                    :error="$errors->first('notes')" />
            </div>

            <x-slot:footer>
                <x-ui.button variant="ghost" type="button" x-on:click="$dispatch('close-modal', 'client-form')">
                    Cancel
                </x-ui.button>
                <x-ui.button type="submit">
                    {{ $clientId ? 'Update' : 'Create' }} Client
                </x-ui.button>
            </x-slot:footer>
        </form>
    </x-ui.modal>
</div>
