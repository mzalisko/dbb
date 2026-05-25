<div>
    {{-- Header --}}
    <div style="margin-bottom:24px;">
        <h1 style="font-size:24px;">Dashboard</h1>
        <p style="margin-top:4px;">Welcome back, {{ auth()->user()->name }}</p>
    </div>

    {{-- Stat cards --}}
    <div class="stat-grid" style="display:grid; grid-template-columns:repeat(4,1fr); gap:16px;">
        <x-ui.card>
            <div style="padding:4px 0;">
                <div style="color:var(--ink-5);margin-bottom:12px;">
                    <x-icon.groups width="20" height="20" />
                </div>
                <div style="font-size:32px;font-weight:400;color:var(--ink-9);line-height:1;">{{ $totalClients }}</div>
                <div class="eyebrow" style="margin-top:8px;">Total Clients</div>
            </div>
        </x-ui.card>

        <x-ui.card>
            <div style="padding:4px 0;">
                <div style="color:var(--ok);margin-bottom:12px;">
                    <x-icon.sites width="20" height="20" />
                </div>
                <div style="font-size:32px;font-weight:400;color:var(--ink-9);line-height:1;">{{ $activeSites }}</div>
                <div class="eyebrow" style="margin-top:8px;">Active Sites</div>
            </div>
        </x-ui.card>

        <x-ui.card>
            <div style="padding:4px 0;">
                <div style="color:var(--warn);margin-bottom:12px;">
                    <x-icon.bolt width="20" height="20" />
                </div>
                <div style="font-size:32px;font-weight:400;color:var(--ink-9);line-height:1;">{{ $maintenanceSites }}</div>
                <div class="eyebrow" style="margin-top:8px;">Maintenance</div>
            </div>
        </x-ui.card>

        <x-ui.card>
            <div style="padding:4px 0;">
                <div style="color:var(--bad);margin-bottom:12px;">
                    <x-icon.globe width="20" height="20" />
                </div>
                <div style="font-size:32px;font-weight:400;color:var(--ink-9);line-height:1;">{{ $offlineSites }}</div>
                <div class="eyebrow" style="margin-top:8px;">Offline</div>
            </div>
        </x-ui.card>
    </div>

    {{-- Two columns --}}
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:24px; margin-top:24px;">

        {{-- Recent Clients --}}
        <x-ui.card>
            <x-slot:header>
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <span>Recent Clients</span>
                    <a href="{{ route('clients.index') }}" wire:navigate style="font-size:12px;color:var(--ink-5);">View all</a>
                </div>
            </x-slot:header>
            @forelse ($recentClients as $client)
                <div class="row" style="grid-template-columns: auto 1fr auto;">
                    <x-ui.avatar :initials="$client->initials" />
                    <div>
                        <a href="{{ route('clients.show', $client) }}" wire:navigate style="font-weight:500;color:var(--ink-9);">
                            {{ $client->company_name }}
                        </a>
                        <div style="font-size:12px;color:var(--ink-5);">
                            {{ $client->sites->count() }} {{ Str::plural('site', $client->sites->count()) }}
                        </div>
                    </div>
                    <x-ui.pill :status="$client->status === 'active' ? 'ok' : 'warn'">
                        {{ $client->status }}
                    </x-ui.pill>
                </div>
            @empty
                <x-ui.empty-state icon="groups" title="No clients yet"
                    description="Add your first client to get started." />
            @endforelse
        </x-ui.card>

        {{-- Activity Log --}}
        <x-ui.card>
            <x-slot:header>
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <span>Recent Activity</span>
                    <a href="{{ route('activity.index') }}" wire:navigate style="font-size:12px;color:var(--ink-5);">View all</a>
                </div>
            </x-slot:header>
            @forelse ($recentActivity as $log)
                <div class="row" style="grid-template-columns: auto 1fr auto;">
                    <x-ui.avatar :initials="$log->user?->name ?? '?'" />
                    <div>
                        <span style="font-weight:500;">{{ $log->user?->name ?? 'System' }}</span>
                        <span style="color:var(--ink-5);">{{ $log->action }}</span>
                        @if ($log->subject)
                            <span>{{ class_basename($log->subject_type) }} #{{ $log->subject_id }}</span>
                        @endif
                    </div>
                    <span class="mono" style="font-size:11px;color:var(--ink-4);">
                        {{ $log->created_at->diffForHumans() }}
                    </span>
                </div>
            @empty
                <x-ui.empty-state icon="logs" title="No activity yet" />
            @endforelse
        </x-ui.card>
    </div>

    <style>
        @media (max-width: 768px) {
            .stat-grid { grid-template-columns: repeat(2, 1fr) !important; }
        }
    </style>
</div>
