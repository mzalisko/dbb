<div style="flex:1; display:flex; flex-direction:column; overflow-y:auto;">
    <x-ui.topbar :crumbs="['Sites', $site->client->company_name, $site->name]">
        <x-ui.button variant="secondary" size="sm" wire:click="$dispatch('edit-site', { id: {{ $site->id }} })">
            <x-icon.edit width="13" height="13" /> Edit
        </x-ui.button>
    </x-ui.topbar>

    <x-ui.page-head
        eyebrow="Site"
        :title="$site->name">
        <x-slot:actions>
            <x-ui.pill :status="$site->status_color" style="margin-bottom:4px;">{{ $site->status }}</x-ui.pill>
        </x-slot:actions>
    </x-ui.page-head>

    <div style="padding:0 40px 64px;">
        <p style="font:14.5px/1 var(--font-sans); color:var(--ink-5); margin-bottom:32px; margin-top:-16px;">
            <a href="{{ $site->url }}" target="_blank" rel="noopener"
               style="display:inline-flex; align-items:center; gap:4px; color:var(--ink-5);">
                {{ $site->url }} <x-icon.external-link width="12" height="12" />
            </a>
        </p>

        {{-- Info grid --}}
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:24px;">
            <x-ui.card>
                <x-slot:header>Technical Details</x-slot:header>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                    <div>
                        <div class="eyebrow" style="margin-bottom:4px;">WordPress</div>
                        <div class="mono" style="color:var(--ink-9);">{{ $site->wp_version ?? 'Unknown' }}</div>
                    </div>
                    <div>
                        <div class="eyebrow" style="margin-bottom:4px;">PHP</div>
                        <div class="mono" style="color:var(--ink-9);">{{ $site->php_version ?? 'Unknown' }}</div>
                    </div>
                    <div>
                        <div class="eyebrow" style="margin-bottom:4px;">Last Checked</div>
                        <div class="mono" style="color:var(--ink-9); font-size:13px;">{{ $site->last_checked_at?->format('M d, Y H:i') ?? 'Never' }}</div>
                    </div>
                    <div>
                        <div class="eyebrow" style="margin-bottom:4px;">Status</div>
                        <x-ui.pill :status="$site->status_color">{{ $site->status }}</x-ui.pill>
                    </div>
                </div>
            </x-ui.card>

            <x-ui.card>
                <x-slot:header>Client</x-slot:header>
                <div style="display:flex; align-items:center; gap:10px;">
                    <x-ui.avatar :initials="$site->client->initials" square />
                    <div>
                        <a href="{{ route('clients.show', $site->client) }}" wire:navigate
                           style="font-weight:500; color:var(--ink-9);">
                            {{ $site->client->company_name }}
                        </a>
                        @if ($site->client->contact_email)
                            <div style="font-size:12px; color:var(--ink-5);">{{ $site->client->contact_email }}</div>
                        @endif
                    </div>
                </div>
            </x-ui.card>
        </div>

        @if ($site->notes)
            <x-ui.card>
                <x-slot:header>Notes</x-slot:header>
                <p style="color:var(--ink-7);">{{ $site->notes }}</p>
            </x-ui.card>
        @endif
    </div>

    @livewire('sites.form')
</div>
