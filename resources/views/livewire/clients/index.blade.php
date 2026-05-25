<div style="flex:1; display:flex; flex-direction:column; overflow-y:auto;">
    <x-ui.topbar :crumbs="['Clients']">
        <x-ui.button size="sm" wire:click="$dispatch('open-modal','client-form')">
            <x-icon.plus width="13" height="13" /> Add Client
        </x-ui.button>
    </x-ui.topbar>

    <x-ui.page-head
        eyebrow="Clients"
        title="{{ $clients->total() }} clients"
        sub="All your client companies and their WordPress sites." />

    <div style="padding:0 40px 64px;">
        {{-- Flash --}}
        @if (session('message'))
            <x-ui.alert variant="success" style="margin-bottom:16px;">{{ session('message') }}</x-ui.alert>
        @endif

        {{-- Filters --}}
        <div style="display:flex; gap:12px; align-items:flex-end; margin-bottom:20px;">
            <div style="display:flex; align-items:center; gap:10px; height:44px; padding:0 18px; border-radius:999px; background:var(--card); border:1px solid var(--ink-3); max-width:400px; flex:1;">
                <x-icon.search width="15" height="15" style="color:var(--ink-5);" />
                <input wire:model.live.debounce.300ms="search" type="text"
                    placeholder="Search clients…"
                    style="flex:1; font:14.5px var(--font-sans); color:var(--ink-7); background:transparent; border:0; outline:none;"
                    placeholder-style="color:var(--ink-4);" />
            </div>
            <x-ui.select wire:model.live="status" style="width:160px;">
                <option value="">All statuses</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
                <option value="archived">Archived</option>
            </x-ui.select>
        </div>

        {{-- Table --}}
        <x-ui.card :padding="false">
            <x-ui.table>
                <thead>
                    <tr>
                        <th wire:click="sort('company_name')" style="cursor:pointer;">
                            Company @if($sortBy === 'company_name')<span>{{ $sortDir === 'asc' ? '↑' : '↓' }}</span>@endif
                        </th>
                        <th>Contact</th>
                        <th>Sites</th>
                        <th wire:click="sort('status')" style="cursor:pointer;">
                            Status @if($sortBy === 'status')<span>{{ $sortDir === 'asc' ? '↑' : '↓' }}</span>@endif
                        </th>
                        <th wire:click="sort('created_at')" style="cursor:pointer;">
                            Created @if($sortBy === 'created_at')<span>{{ $sortDir === 'asc' ? '↑' : '↓' }}</span>@endif
                        </th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($clients as $client)
                        <tr>
                            <td>
                                <a href="{{ route('clients.show', $client) }}" wire:navigate
                                   style="display:flex; align-items:center; gap:10px; color:var(--ink-9); font-weight:500;">
                                    <x-ui.avatar :initials="$client->initials" square />
                                    {{ $client->company_name }}
                                </a>
                            </td>
                            <td>
                                <div>{{ $client->contact_name }}</div>
                                <div style="font-size:12px; color:var(--ink-5);">{{ $client->contact_email }}</div>
                            </td>
                            <td class="num">{{ $client->sites_count }}</td>
                            <td>
                                <x-ui.pill :status="$client->status === 'active' ? 'ok' : ($client->status === 'inactive' ? 'warn' : 'info')">
                                    {{ $client->status }}
                                </x-ui.pill>
                            </td>
                            <td class="mono" style="font-size:12px; color:var(--ink-5);">
                                {{ $client->created_at->format('M d, Y') }}
                            </td>
                            <td>
                                <x-ui.dropdown align="right">
                                    <x-slot:trigger><x-icon.more-v width="16" height="16" /></x-slot:trigger>
                                    <a href="{{ route('clients.show', $client) }}" wire:navigate class="dropdown-item">View</a>
                                    <button class="dropdown-item" wire:click="$dispatch('edit-client', { id: {{ $client->id }} })">Edit</button>
                                    <button class="dropdown-item" style="color:var(--bad);"
                                        x-on:click="if(confirm('Delete this client?')) $wire.deleteClient({{ $client->id }})">Delete</button>
                                </x-ui.dropdown>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <x-ui.empty-state icon="groups" title="No clients found"
                                    description="Add your first client to get started.">
                                    <x-slot:action>
                                        <x-ui.button wire:click="$dispatch('open-modal','client-form')">
                                            <x-icon.plus width="14" height="14" /> Add Client
                                        </x-ui.button>
                                    </x-slot:action>
                                </x-ui.empty-state>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </x-ui.table>
        </x-ui.card>

        <div style="margin-top:16px;">{{ $clients->links() }}</div>
    </div>

    @livewire('clients.form')
</div>
