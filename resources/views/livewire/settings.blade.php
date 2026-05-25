<div x-data="{ tab: @entangle('activeTab') }">
    <h1 style="font-size:24px;margin-bottom:24px;">Settings</h1>

    <x-ui.tabs style="margin-bottom:24px;">
        <button class="tab" :class="tab === 'profile' && 'active'" @click="tab = 'profile'" type="button">
            <x-icon.user width="16" height="16" /> Profile
        </button>
        <button class="tab" :class="tab === 'organization' && 'active'" @click="tab = 'organization'" type="button">
            <x-icon.settings width="16" height="16" /> Organization
        </button>
        <button class="tab" :class="tab === 'password' && 'active'" @click="tab = 'password'" type="button">
            <x-icon.lock width="16" height="16" /> Password
        </button>
    </x-ui.tabs>

    {{-- Profile tab --}}
    <div x-show="tab === 'profile'" x-cloak>
        <x-ui.card style="max-width:560px;">
            <form wire:submit="saveProfile">
                @if (session('profile-saved'))
                    <x-ui.alert variant="success" style="margin-bottom:16px;">Profile updated successfully.</x-ui.alert>
                @endif

                <div style="display:flex;flex-direction:column;gap:20px;">
                    <x-ui.input wire:model="name" label="Name" name="settings_name"
                        :error="$errors->first('name')" />

                    <x-ui.input wire:model="email" label="Email" name="settings_email" type="email"
                        :error="$errors->first('email')" />

                    <x-ui.input wire:model="phone" label="Phone" name="settings_phone"
                        :error="$errors->first('phone')" />

                    <div>
                        <x-ui.button type="submit">Save Profile</x-ui.button>
                    </div>
                </div>
            </form>
        </x-ui.card>
    </div>

    {{-- Organization tab --}}
    <div x-show="tab === 'organization'" x-cloak>
        <x-ui.card style="max-width:560px;">
            <form wire:submit="saveOrganization">
                @if (session('org-saved'))
                    <x-ui.alert variant="success" style="margin-bottom:16px;">Organization updated successfully.</x-ui.alert>
                @endif

                <div style="display:flex;flex-direction:column;gap:20px;">
                    <x-ui.input wire:model="organization_name" label="Organization Name" name="org_name"
                        :error="$errors->first('organization_name')" />

                    <div>
                        <x-ui.button type="submit">Save</x-ui.button>
                    </div>
                </div>
            </form>
        </x-ui.card>
    </div>

    {{-- Password tab --}}
    <div x-show="tab === 'password'" x-cloak>
        <x-ui.card style="max-width:560px;">
            <form wire:submit="changePassword">
                @if (session('password-changed'))
                    <x-ui.alert variant="success" style="margin-bottom:16px;">Password changed successfully.</x-ui.alert>
                @endif

                <div style="display:flex;flex-direction:column;gap:20px;">
                    <x-ui.input wire:model="current_password" label="Current Password" name="current_password"
                        type="password" :error="$errors->first('current_password')" />

                    <x-ui.input wire:model="new_password" label="New Password" name="new_password"
                        type="password" :error="$errors->first('new_password')" />

                    <x-ui.input wire:model="new_password_confirmation" label="Confirm New Password"
                        name="new_password_confirmation" type="password" />

                    <div>
                        <x-ui.button type="submit">Change Password</x-ui.button>
                    </div>
                </div>
            </form>
        </x-ui.card>
    </div>
</div>
