<div>
    {{-- Header --}}
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
        <div>
            <h1 style="font-size:24px;">Sites</h1>
            @if ($clientId)
                <p style="margin-top:4px;font-size:14px;color:var(--ink-5);">
                    Filtered by client
                    <a href="{{ route('sites.index') }}" wire:navigate style="color:var(--accent);">Clear filter</a>
                </p>
            @endif
        </div>
        <x-ui.button wire:click="$dispatch('open-modal','site-form')">
            <x-icon.plus width="14" height="14" /> Add Site
        </x-ui.button>
    </div>

    {{-- Flash message --}}
    @if (session('message'))
        <x-ui.alert variant="success" style="margin-bottom:16px;">{{ session('message') }}</x-ui.alert>
    @endif

    {{-- Filters --}}
    <div style="display:flex; gap:12px; align-items:flex-end; margin-bottom:16px;">
        <div style="flex:1;max-width:320px;">
            <x-ui.input wire:model.live.debounce.300ms="search" placeholder="Search sites..." />
        </div>
        <div style="width:180px;">
            <x-ui.select wire:model.live="status">
                <option value="">All statuses</option>
                <option value="active">Active</option>
                <option value="maintenance">Maintenance</option>
                <option value="offline">Offline</option>
            </x-ui.select>
        </div>
    </div>

    {{-- Sites table --}}
    <x-ui.card :padding="false">
        <x-ui.table>
            <thead>
                <tr>
                    <th wire:click="sort('name')" style="cursor:pointer;">
                        Site
                        @if($sortBy === 'name')
                            <span>{{ $sortDir === 'asc' ? '↑' : '↓' }}</span>
                        @endif
                    </th>
                    <th>Client</th>
                    <th wire:click="sort('wp_version')" style="cursor:pointer;">WP</th>
                    <th>PHP</th>
                    <th wire:click="sort('status')" style="cursor:pointer;">
                        Status
                        @if($sortBy === 'status')
                            <span>{{ $sortDir === 'asc' ? '↑' : '↓' }}</span>
                        @endif
                    </th>
                    <th wire:click="sort('last_checked_at')" style="cursor:pointer;">Last Checked</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($sites as $site)
                    <tr>
                        <td>
                            <div>
                                <a href="{{ route('sites.show', $site) }}" wire:navigate style="font-weight:500;color:var(--ink-9);">
                                    {{ $site->name }}
                                </a>
                                <div style="font-size:12px;">
                                    <a href="{{ $site->url }}" target="_blank" rel="noopener" style="color:var(--ink-5);">
                                        {{ $site->url }} <x-icon.external-link width="10" height="10" style="display:inline;vertical-align:middle;" />
                                    </a>
                                </div>
                            </div>
                        </td>
                        <td>
                            <a href="{{ route('clients.show', $site->client) }}" wire:navigate style="color:var(--ink-7);">
                                {{ $site->client->company_name }}
                            </a>
                        </td>
                        <td class="mono">{{ $site->wp_version ?? '--' }}</td>
                        <td class="mono">{{ $site->php_version ?? '--' }}</td>
                        <td>
                            <x-ui.pill :status="$site->status_color">
                                {{ $site->status }}
                            </x-ui.pill>
                        </td>
                        <td>
                            <span class="mono" style="font-size:12px;color:var(--ink-5);">
                                {{ $site->last_checked_at?->diffForHumans() ?? 'Never' }}
                            </span>
                        </td>
                        <td>
                            <x-ui.dropdown align="right">
                                <x-slot:trigger>
                                    <x-icon.more-v width="16" height="16" />
                                </x-slot:trigger>
                                <a href="{{ route('sites.show', $site) }}" wire:navigate class="dropdown-item">View</a>
                                <button class="dropdown-item" wire:click="$dispatch('edit-site', { id: {{ $site->id }} })">Edit</button>
                                <button class="dropdown-item" style="color:var(--bad);"
                                    x-on:click="if(confirm('Delete this site?')) $wire.deleteSite({{ $site->id }})">
                                    Delete
                                </button>
                            </x-ui.dropdown>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <x-ui.empty-state icon="sites" title="No sites found"
                                description="Add a WordPress site to start monitoring.">
                                <x-slot:action>
                                    <x-ui.button wire:click="$dispatch('open-modal','site-form')">
                                        <x-icon.plus width="14" height="14" /> Add Site
                                    </x-ui.button>
                                </x-slot:action>
                            </x-ui.empty-state>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </x-ui.table>
    </x-ui.card>

    {{-- Pagination --}}
    <div style="margin-top:16px;">
        {{ $sites->links() }}
    </div>

    {{-- Site Form Modal --}}
    @livewire('sites.form')
</div>
