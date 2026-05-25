<div>
    {{-- Header --}}
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
        <h1 style="font-size:24px;">Team</h1>
        @if (auth()->user()->isAdmin())
            <x-ui.button wire:click="$dispatch('open-modal','invite-user')">
                <x-icon.plus width="14" height="14" /> Invite Member
            </x-ui.button>
        @endif
    </div>

    {{-- Flash message --}}
    @if (session('message'))
        <x-ui.alert variant="success" style="margin-bottom:16px;">{{ session('message') }}</x-ui.alert>
    @endif

    {{-- Search --}}
    <div style="margin-bottom:16px;max-width:320px;">
        <x-ui.input wire:model.live.debounce.300ms="search" placeholder="Search team..." />
    </div>

    {{-- Users table --}}
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
                            <div style="display:flex;align-items:center;gap:10px;">
                                <x-ui.avatar :initials="$user->name" :src="$user->avatar_path" />
                                <div>
                                    <div style="font-weight:500;">{{ $user->name }}</div>
                                    <div style="font-size:12px;color:var(--ink-5);">{{ $user->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <x-ui.pill :status="$user->role === 'owner' ? 'info' : ($user->role === 'admin' ? 'warn' : '')">
                                {{ ucfirst($user->role) }}
                            </x-ui.pill>
                        </td>
                        <td class="mono" style="font-size:12px;color:var(--ink-5);">
                            {{ $user->last_login_at?->diffForHumans() ?? 'Never' }}
                        </td>
                        <td>
                            @if (auth()->user()->isAdmin() && $user->id !== auth()->id())
                                <x-ui.dropdown align="right">
                                    <x-slot:trigger>
                                        <x-icon.more-v width="16" height="16" />
                                    </x-slot:trigger>
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

    {{-- Pagination --}}
    <div style="margin-top:16px;">
        {{ $users->links() }}
    </div>

    {{-- Invite Modal --}}
    @livewire('users.invite-form')
</div>
