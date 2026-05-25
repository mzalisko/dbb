<div style="flex:1; display:flex; flex-direction:column; overflow-y:auto;">
    <x-ui.topbar :crumbs="['Activity']" />

    <x-ui.page-head
        eyebrow="Activity"
        title="System Logs"
        sub="All actions performed across the CRM." />

    <div style="padding:0 40px 64px;">
        {{-- Search --}}
        <div style="margin-bottom:20px; max-width:400px;">
            <div style="display:flex; align-items:center; gap:10px; height:44px; padding:0 18px; border-radius:999px; background:var(--card); border:1px solid var(--ink-3);">
                <x-icon.search width="15" height="15" style="color:var(--ink-5);" />
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search by action…"
                    style="flex:1; font:14.5px var(--font-sans); color:var(--ink-7); background:transparent; border:0; outline:none;" />
            </div>
        </div>

        <x-ui.card :padding="false">
            <x-ui.table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Action</th>
                        <th>Subject</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr>
                            <td>
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <x-ui.avatar :initials="$log->user?->name ?? '?'" />
                                    <span>{{ $log->user?->name ?? 'System' }}</span>
                                </div>
                            </td>
                            <td><x-ui.pill>{{ $log->action }}</x-ui.pill></td>
                            <td style="color:var(--ink-7);">
                                @if ($log->subject_type)
                                    {{ class_basename($log->subject_type) }} #{{ $log->subject_id }}
                                @else --
                                @endif
                            </td>
                            <td class="mono" style="font-size:12px; color:var(--ink-5);">{{ $log->created_at->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">
                                <x-ui.empty-state icon="logs" title="No activity yet"
                                    description="Actions will appear here as they happen." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </x-ui.table>
        </x-ui.card>

        <div style="margin-top:16px;">{{ $logs->links() }}</div>
    </div>
</div>
