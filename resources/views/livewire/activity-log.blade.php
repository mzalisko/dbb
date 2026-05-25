<div>
    {{-- Header --}}
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
        <h1 style="font-size:24px;">Activity Log</h1>
    </div>

    {{-- Search --}}
    <div style="margin-bottom:16px;max-width:320px;">
        <x-ui.input wire:model.live.debounce.300ms="search" placeholder="Search by action..." />
    </div>

    {{-- Activity table --}}
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
                            <div style="display:flex;align-items:center;gap:8px;">
                                <x-ui.avatar :initials="$log->user?->name ?? '?'" />
                                <span>{{ $log->user?->name ?? 'System' }}</span>
                            </div>
                        </td>
                        <td>
                            <x-ui.pill>{{ $log->action }}</x-ui.pill>
                        </td>
                        <td style="color:var(--ink-7);">
                            @if ($log->subject_type)
                                {{ class_basename($log->subject_type) }} #{{ $log->subject_id }}
                            @else
                                --
                            @endif
                        </td>
                        <td class="mono" style="font-size:12px;color:var(--ink-5);">
                            {{ $log->created_at->diffForHumans() }}
                        </td>
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

    {{-- Pagination --}}
    <div style="margin-top:16px;">
        {{ $logs->links() }}
    </div>
</div>
