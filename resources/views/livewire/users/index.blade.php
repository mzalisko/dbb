<div style="flex:1; display:flex; flex-direction:column; overflow-y:auto;">
    <x-ui.topbar :crumbs="['Team']">
        @if (auth()->user()->isAdmin())
            <x-ui.button size="sm" wire:click="$dispatch('open-modal','invite-user')">
                <x-icon.plus width="13" height="13" /> Invite Member
            </x-ui.button>
        @endif
    </x-ui.topbar>

    <x-ui.page-head
        eyebrow="Team"
        title="{{ $users->total() }} members"
        sub="Manage your team and their access levels." />

    <div style="padding:0 40px 64px;">
        {{-- Flash --}}
        @if (session('message'))
            <x-ui.alert variant="success" style="margin-bottom:16px;">{{ session('message') }}</x-ui.alert>
        @endif

        {{-- Search --}}
        <div style="margin-bottom:20px; max-width:400px;">
            <div style="display:flex; align-items:center; gap:10px; height:44px; padding:0 18px; border-radius:999px; background:var(--card); border:1px solid var(--ink-3);">
                <x-icon.search width="15" height="15" style="color:var(--ink-5);" />
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search team…"
                    style="flex:1; font:14.5px var(--font-sans); color:var(--ink-7); background:transparent; border:0; outline:none;" />
            </div>
        </div>

        {{-- Table --}}
        <x-ui.card :padding="false">
            <x-ui.table>
                <thead>
                    <tr>
                        <th>Member</th>
                        <th>Role</th>
                        <th>Last Login</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                        <tr>
                            <td>
                                <div style="display:flex; align-items:center; gap:10px;">
                                    <x-ui.avatar :initials="$user->name" :src="$user->avatar_path" />
                                    <div>
                                        <div style="font-weight:500;">{{ $user->name }}</div>
                                        <div style="font-size:12px; color:var(--ink-5);">{{ $user->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <x-ui.pill :status="$user->role === 'owner' ? 'info' : ($user->role === 'admin' ? 'warn' : '')">
                                    {{ ucfirst($user->role) }}
                                </x-ui.pill>
                            </td>
                            <td class="mono" style="font-size:12px; color:var(--ink-5);">
                                {{ $user->last_login_at?->diffForHumans() ?? 'Never' }}
                            </td>
                            <td>
                                @if (auth()->user()->isAdmin() && $user->id !== auth()->id())
                                    <x-ui.dropdown align="right">
                                        <x-slot:trigger><x-icon.more-v width="16" height="16" /></x-slot:trigger>
                                        <button class="dropdown-item" style="color:var(--bad);"
                                            x-on:click="if(confirm('Remove this member from the team?')) $wire.removeUser({{ $user->id }})">
                                            Remove
                                        </button>
                                    </x-ui.dropdown>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-ui.table>
        </x-ui.card>

        <div style="margin-top:16px;">{{ $users->links() }}</div>
    </div>

    @livewire('users.invite-form')
</div>
