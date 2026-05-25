<div>
    {{-- Breadcrumb --}}
    <div style="margin-bottom:16px;display:flex;align-items:center;gap:6px;font-size:14px;">
        <a href="{{ route('sites.index') }}" wire:navigate style="display:inline-flex;align-items:center;gap:4px;color:var(--ink-5);">
            <x-icon.arrow-left width="14" height="14" /> Sites
        </a>
        <span style="color:var(--ink-4);">/</span>
        <a href="{{ route('clients.show', $site->client) }}" wire:navigate style="color:var(--ink-5);">
            {{ $site->client->company_name }}
        </a>
    </div>

    {{-- Site header --}}
    <div style="display:flex; align-items:center; gap:16px; margin-bottom:24px;">
        <x-ui.avatar :initials="substr($site->name, 0, 2)" square size="lg" />
        <div>
            <h1 style="font-size:24px;">{{ $site->name }}</h1>
            <a href="{{ $site->url }}" target="_blank" rel="noopener"
               style="color:var(--ink-5);font-size:14px;display:inline-flex;align-items:center;gap:4px;">
                {{ $site->url }} <x-icon.external-link width="12" height="12" />
            </a>
        </div>
        <x-ui.pill :status="$site->status_color" style="margin-left:8px;">{{ $site->status }}</x-ui.pill>
        <div style="margin-left:auto;">
            <x-ui.button variant="secondary" wire:click="$dispatch('edit-site', { id: {{ $site->id }} })">
                <x-icon.edit width="14" height="14" /> Edit
            </x-ui.button>
        </div>
    </div>

    {{-- Info cards grid --}}
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:24px;">

        {{-- Technical Info --}}
        <x-ui.card>
            <x-slot:header>Technical Details</x-slot:header>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
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
                    <div class="mono" style="color:var(--ink-9);font-size:13px;">
                        {{ $site->last_checked_at?->format('M d, Y H:i') ?? 'Never' }}
                    </div>
                </div>
                <div>
                    <div class="eyebrow" style="margin-bottom:4px;">Status</div>
                    <x-ui.pill :status="$site->status_color">{{ $site->status }}</x-ui.pill>
                </div>
            </div>
        </x-ui.card>

        {{-- Client Info --}}
        <x-ui.card>
            <x-slot:header>Client</x-slot:header>
            <div style="display:flex;align-items:center;gap:10px;">
                <x-ui.avatar :initials="$site->client->initials" />
                <div>
                    <a href="{{ route('clients.show', $site->client) }}" wire:navigate
                       style="font-weight:500;color:var(--ink-9);">
                        {{ $site->client->company_name }}
                    </a>
                    @if ($site->client->contact_email)
                        <div style="font-size:12px;color:var(--ink-5);">{{ $site->client->contact_email }}</div>
                    @endif
                </div>
            </div>
        </x-ui.card>
    </div>

    {{-- Notes --}}
    @if ($site->notes)
        <x-ui.card>
            <x-slot:header>Notes</x-slot:header>
            <p style="color:var(--ink-7);">{{ $site->notes }}</p>
        </x-ui.card>
    @endif

    {{-- Site Form Modal --}}
    @livewire('sites.form')
</div>
