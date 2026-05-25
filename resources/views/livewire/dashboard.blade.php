<div style="flex:1; display:flex; flex-direction:column; overflow-y:auto;">
    <x-ui.topbar :crumbs="['Dashboard']">
        <x-ui.button size="sm" wire:click="$dispatch('open-modal','site-form')">
            <x-icon.plus width="13" height="13" /> Add Site
        </x-ui.button>
    </x-ui.topbar>

    <x-ui.page-head
        eyebrow="Overview"
        :title="$totalSites . ' sites'"
        sub="Workspace status. Click a client to see their sites." />

    <div style="padding:0 40px 64px;">
        {{-- Two-column layout --}}
        <div style="display:grid; grid-template-columns:1.5fr 1fr; gap:48px;">

            {{-- Recent Clients --}}
            <div>
                <header style="display:flex; align-items:baseline; margin-bottom:12px; padding-bottom:12px; border-bottom:1px solid var(--ink-3);">
                    <h3 style="font:400 18px/1 var(--font-sans); color:var(--ink-9); flex:1;">Clients</h3>
                    <a href="{{ route('clients.index') }}" wire:navigate class="btn btn-ghost btn-sm" style="padding:0;">All →</a>
                </header>
                @forelse ($recentClients as $client)
                    <a href="{{ route('clients.show', $client) }}" wire:navigate
                       style="display:grid; grid-template-columns:28px 1fr auto; gap:14px; padding:14px 0; border-bottom:1px solid var(--ink-3); align-items:center; transition:background .12s;"
                       onmouseover="this.style.background='var(--paper-2)'" onmouseout="this.style.background='transparent'">
                        <x-ui.avatar :initials="$client->initials" square />
                        <div>
                            <div class="mono" style="font:13.5px var(--font-mono); color:var(--ink-9);">{{ $client->company_name }}</div>
                            <div style="margin-top:3px; font:12px var(--font-sans); color:var(--ink-5);">
                                {{ $client->sites->count() }} {{ Str::plural('site', $client->sites->count()) }}
                            </div>
                        </div>
                        <x-ui.pill :status="$client->status === 'active' ? 'ok' : 'warn'">{{ $client->status }}</x-ui.pill>
                    </a>
                @empty
                    <x-ui.empty-state icon="groups" title="No clients yet" description="Add your first client to get started." />
                @endforelse
            </div>

            {{-- Recent Activity --}}
            <div>
                <header style="display:flex; align-items:baseline; margin-bottom:12px; padding-bottom:12px; border-bottom:1px solid var(--ink-3);">
                    <h3 style="font:400 18px/1 var(--font-sans); color:var(--ink-9); flex:1;">Activity</h3>
                    <a href="{{ route('activity.index') }}" wire:navigate class="btn btn-ghost btn-sm" style="padding:0;">All →</a>
                </header>
                @forelse ($recentActivity as $log)
                    <div style="padding:12px 0; border-bottom:1px solid var(--ink-3);">
                        <div style="display:flex; justify-content:space-between; align-items:baseline; gap:12px;">
                            <div style="font:13px/1.4 var(--font-sans); color:var(--ink-8);">
                                <span class="dot {{ $log->action === 'created' ? 'dot-ok' : '' }}"></span>
                                <span class="mono" style="color:var(--ink-9); font-size:12.5px;">{{ $log->user?->name ?? 'System' }}</span>
                                <span style="color:var(--ink-5); margin-left:6px;">{{ $log->action }}</span>
                                @if ($log->subject_type)
                                    <span style="color:var(--ink-5);">{{ class_basename($log->subject_type) }}</span>
                                @endif
                            </div>
                            <span class="mono" style="font:11px var(--font-mono); color:var(--ink-4); white-space:nowrap;">{{ $log->created_at->diffForHumans() }}</span>
                        </div>
                    </div>
                @empty
                    <x-ui.empty-state icon="logs" title="No activity yet" />
                @endforelse
            </div>
        </div>
    </div>
</div>
