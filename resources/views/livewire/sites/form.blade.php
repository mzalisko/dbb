<div>
    <x-ui.modal name="site-form">
        <x-slot:title>{{ $siteId ? 'Edit Site' : 'Add Site' }}</x-slot:title>

        <form wire:submit="save">
            <div style="display:flex;flex-direction:column;gap:20px;">
                @unless ($clientLocked)
                    <x-ui.select wire:model="client_id" label="Client" name="client_id"
                        :error="$errors->first('client_id')">
                        <option value="">Select a client...</option>
                        @foreach ($clients as $client)
                            <option value="{{ $client->id }}">{{ $client->company_name }}</option>
                        @endforeach
                    </x-ui.select>
                @endunless

                <x-ui.input wire:model="name" label="Site Name" name="site_name"
                    :error="$errors->first('name')" />

                <x-ui.input wire:model="url" label="URL" name="url" type="url"
                    placeholder="https://example.com"
                    :error="$errors->first('url')" />

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    <x-ui.input wire:model="wp_version" label="WordPress Version" name="wp_version"
                        placeholder="6.7" />
                    <x-ui.input wire:model="php_version" label="PHP Version" name="php_version"
                        placeholder="8.3" />
                </div>

                <x-ui.select wire:model="status" label="Status" name="site_status">
                    <option value="active">Active</option>
                    <option value="maintenance">Maintenance</option>
                    <option value="offline">Offline</option>
                    <option value="archived">Archived</option>
                </x-ui.select>

                <x-ui.textarea wire:model="notes" label="Notes" name="site_notes" rows="3" />
            </div>

            <x-slot:footer>
                <x-ui.button variant="ghost" type="button" x-on:click="$dispatch('close-modal', 'site-form')">
                    Cancel
                </x-ui.button>
                <x-ui.button type="submit">
                    {{ $siteId ? 'Update' : 'Create' }} Site
                </x-ui.button>
            </x-slot:footer>
        </form>
    </x-ui.modal>
</div>
