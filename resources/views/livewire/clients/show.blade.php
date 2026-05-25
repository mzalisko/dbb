<div>
    {{-- Back link --}}
    <div style="margin-bottom:16px;">
        <a href="{{ route('clients.index') }}" wire:navigate style="display:inline-flex;align-items:center;gap:6px;color:var(--ink-5);font-size:14px;">
            <x-icon.arrow-left width="14" height="14" /> Back to Clients
        </a>
    </div>

    {{-- Client header --}}
    <div style="display:flex; align-items:center; gap:16px; margin-bottom:24px;">
        <x-ui.avatar :initials="$client->initials" size="lg" />
        <div>
            <h1 style="font-size:24px;">{{ $client->company_name }}</h1>
            <x-ui.pill :status="$client->status === 'active' ? 'ok' : ($client->status === 'inactive' ? 'warn' : 'info')"
                style="margin-top:4px;">
                {{ $client->status }}
            </x-ui.pill>
        </div>
        <div style="margin-left:auto; display:flex; gap:8px;">
            <x-ui.button variant="secondary" wire:click="$dispatch('edit-client', { id: {{ $client->id }} })">
                <x-icon.edit width="14" height="14" /> Edit
            </x-ui.button>
            <x-ui.button variant="danger"
                x-on:click="if(confirm('Delete this client and all their data?')) $wire.deleteClient()">
                <x-icon.trash width="14" height="14" /> Delete
            </x-ui.button>
        </div>
    </div>

    {{-- Contact info card --}}
    <x-ui.card style="margin-bottom:24px;">
        <x-slot:header>Contact Information</x-slot:header>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
            <div>
                <div class="eyebrow" style="margin-bottom:4px;">Contact Name</div>
                <div style="color:var(--ink-9);">{{ $client->contact_name ?? '--' }}</div>
            </div>
            <div>
                <div class="eyebrow" style="margin-bottom:4px;">Email</div>
                <div style="color:var(--ink-9);">
                    @if ($client->contact_email)
                        <a href="mailto:{{ $client->contact_email }}" style="color:var(--accent);">{{ $client->contact_email }}</a>
                    @else
                        --
                    @endif
                </div>
            </div>
            <div>
                <div class="eyebrow" style="margin-bottom:4px;">Phone</div>
                <div style="color:var(--ink-9);">{{ $client->contact_phone ?? '--' }}</div>
            </div>
            <div>
                <div class="eyebrow" style="margin-bottom:4px;">Created by</div>
                <div style="color:var(--ink-9);">{{ $client->user->name }}</div>
            </div>
        </div>
        @if ($client->notes)
            <div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--ink-3);">
                <div class="eyebrow" style="margin-bottom:4px;">Notes</div>
                <p style="color:var(--ink-7);">{{ $client->notes }}</p>
            </div>
        @endif
    </x-ui.card>

    {{-- Sites list --}}
    <x-ui.card :padding="false">
        <x-slot:header>
            <div style="display:flex;justify-content:space-between;align-items:center;">
                <span>WordPress Sites ({{ $client->sites->count() }})</span>
                <x-ui.button size="sm" wire:click="$dispatch('create-site-for-client', { clientId: {{ $client->id }} })">
                    <x-icon.plus width="12" height="12" /> Add Site
                </x-ui.button>
            </div>
        </x-slot:header>
        <x-ui.table>
            <thead>
                <tr>
                    <th>Site</th>
                    <th>WP</th>
                    <th>PHP</th>
                    <th>Status</th>
                    <th>Last Checked</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($client->sites as $site)
                    <tr>
                        <td>
                            <a href="{{ route('sites.show', $site) }}" wire:navigate style="font-weight:500;color:var(--ink-9);">
                                {{ $site->name }}
                            </a>
                            <div style="font-size:12px;color:var(--ink-5);">
                                <a href="{{ $site->url }}" target="_blank" rel="noopener">{{ $site->url }}</a>
                            </div>
                        </td>
                        <td class="mono">{{ $site->wp_version ?? '--' }}</td>
                        <td class="mono">{{ $site->php_version ?? '--' }}</td>
                        <td>
                            <x-ui.pill :status="$site->status_color">{{ $site->status }}</x-ui.pill>
                        </td>
                        <td class="mono" style="font-size:12px;color:var(--ink-5);">
                            {{ $site->last_checked_at?->diffForHumans() ?? 'Never' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">
                            <x-ui.empty-state icon="sites" title="No sites yet"
                                description="Add a WordPress site for this client." />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </x-ui.table>
    </x-ui.card>

    {{-- Client Form Modal --}}
    @livewire('clients.form')

    {{-- Site Form Modal --}}
    @livewire('sites.form')
</div>
