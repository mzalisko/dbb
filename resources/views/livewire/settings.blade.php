<div style="flex:1; display:flex; flex-direction:column; overflow-y:auto;" x-data="{ tab: @entangle('activeTab') }">
    <x-ui.topbar :crumbs="['Settings']" />

    <x-ui.page-head
        eyebrow="Settings"
        title="Account &amp; Organization"
        sub="Manage your profile, organization, and security settings." />

    <div style="padding:0 40px 64px;">
        <x-ui.tabs style="margin-bottom:32px;">
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

        {{-- Profile --}}
        <div x-show="tab === 'profile'" x-cloak>
            <x-ui.card style="max-width:560px;">
                <form wire:submit="saveProfile">
                    @if (session('profile-saved'))
                        <x-ui.alert variant="success" style="margin-bottom:16px;">Profile updated successfully.</x-ui.alert>
                    @endif
                    <div style="display:flex; flex-direction:column; gap:20px;">
                        <x-ui.input wire:model="name" label="Name" name="settings_name" :error="$errors->first('name')" />
                        <x-ui.input wire:model="email" label="Email" name="settings_email" type="email" :error="$errors->first('email')" />
                        <x-ui.input wire:model="phone" label="Phone" name="settings_phone" :error="$errors->first('phone')" />
                        <div><x-ui.button type="submit">Save Profile</x-ui.button></div>
                    </div>
                </form>
            </x-ui.card>
        </div>

        {{-- Organization --}}
        <div x-show="tab === 'organization'" x-cloak>
            <x-ui.card style="max-width:560px;">
                <form wire:submit="saveOrganization">
                    @if (session('org-saved'))
                        <x-ui.alert variant="success" style="margin-bottom:16px;">Organization updated successfully.</x-ui.alert>
                    @endif
                    <div style="display:flex; flex-direction:column; gap:20px;">
                        <x-ui.input wire:model="organization_name" label="Organization Name" name="org_name" :error="$errors->first('organization_name')" />
                        <div><x-ui.button type="submit">Save</x-ui.button></div>
                    </div>
                </form>
            </x-ui.card>
        </div>

        {{-- Password --}}
        <div x-show="tab === 'password'" x-cloak>
            <x-ui.card style="max-width:560px;">
                <form wire:submit="changePassword">
                    @if (session('password-changed'))
                        <x-ui.alert variant="success" style="margin-bottom:16px;">Password changed successfully.</x-ui.alert>
                    @endif
                    <div style="display:flex; flex-direction:column; gap:20px;">
                        <x-ui.input wire:model="current_password" label="Current Password" name="current_password" type="password" :error="$errors->first('current_password')" />
                        <x-ui.input wire:model="new_password" label="New Password" name="new_password" type="password" :error="$errors->first('new_password')" />
                        <x-ui.input wire:model="new_password_confirmation" label="Confirm New Password" name="new_password_confirmation" type="password" />
                        <div><x-ui.button type="submit">Change Password</x-ui.button></div>
                    </div>
                </form>
            </x-ui.card>
        </div>
    </div>
</div>
