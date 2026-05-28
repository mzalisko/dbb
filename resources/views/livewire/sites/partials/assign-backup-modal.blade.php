{{-- ── Assign-backup modal ── --}}
@if($assigningBackup)
    @php
        $assignPhone       = $allPhonesAll->firstWhere('id', $assignBackupPhoneId);
        $availablePrimaries = $allPhonesAll->filter(
            fn($p) => $p->role === 'primary'
                   && is_null($p->parent_id)
                   && $p->id !== $assignBackupPhoneId
        );
    @endphp
    <div wire:key="assign-modal-{{ $assignBackupPhoneId }}"
         x-data="{ show: false }"
         x-init="$nextTick(() => { show = true })"
         @keydown.escape.window="$wire.closeAssignModal()"
         style="display:contents;">

        {{-- Backdrop --}}
        <div x-show="show" x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="$wire.closeAssignModal()"
             style="position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:70;"></div>

        {{-- Panel --}}
        <div x-show="show" x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 translate-y-2"
             style="position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);width:440px;max-width:calc(100vw - 32px);z-index:71;background:var(--card);border-radius:10px;box-shadow:0 24px 64px rgba(0,0,0,.18),0 0 0 1px var(--ink-3);">

            {{-- Header --}}
            <div style="padding:24px 24px 0;">
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;">
                    <div>
                        <h2 style="font:400 19px/1.15 var(--font-sans);color:var(--ink-9);letter-spacing:-0.02em;">Прив'язати як резерв</h2>
                        <div class="mono" style="margin-top:5px;font-size:12px;color:var(--ink-5);">
                            {{ $assignPhone?->value }}{{ $assignPhone?->label ? ' · '.$assignPhone->label : '' }}
                        </div>
                    </div>
                    <button wire:click="closeAssignModal"
                        style="width:28px;height:28px;border-radius:999px;color:var(--ink-5);display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;cursor:pointer;margin-top:2px;transition:color .12s;"
                        onmouseover="this.style.color='var(--ink-9)'" onmouseout="this.style.color='var(--ink-5)'">
                        <x-icon.close width="16" height="16" />
                    </button>
                </div>
                <p style="margin-top:10px;font-size:13px;color:var(--ink-5);">Оберіть активний номер — {{ $assignPhone?->value }} буде показуватись якщо він недоступний.</p>
                <div style="margin-top:16px;border-bottom:1px solid var(--ink-3);"></div>
            </div>

            {{-- List --}}
            <div style="padding:14px 16px 20px;max-height:56vh;overflow-y:auto;">
                @forelse($availablePrimaries as $primary)
                    <button wire:click="assignAsBackup({{ $assignBackupPhoneId }}, {{ $primary->id }})"
                            class="assign-option">
                        <span class="role-dot" style="background:var(--ok);flex-shrink:0;"></span>
                        <span class="mono" style="font-size:14.5px;color:var(--ink-9);flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $primary->value }}</span>
                        @if($primary->label)
                            <span style="font-size:12px;color:var(--ink-5);flex-shrink:0;">{{ $primary->label }}</span>
                        @endif
                        <span style="font-size:11.5px;color:var(--ink-4);flex-shrink:0;font-family:var(--font-mono);">{{ $primary->geo_label ?: 'Усі' }}</span>
                        @if($primary->backups->count())
                            <span style="font-size:11px;color:var(--info);flex-shrink:0;font-family:var(--font-mono);">+{{ $primary->backups->count() }} рез.</span>
                        @endif
                    </button>
                @empty
                    <p style="padding:16px 8px;font-size:13px;color:var(--ink-5);text-align:center;">Немає доступних активних номерів.</p>
                @endforelse
            </div>

        </div>
    </div>
@endif
