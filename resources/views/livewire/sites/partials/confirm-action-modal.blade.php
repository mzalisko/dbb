@if($confirmingAction)
    <div wire:key="confirm-action-modal"
         x-data="{ show: false }"
         x-init="$nextTick(() => { show = true })"
         @keydown.escape.window="$wire.cancelConfirm()"
         style="display:contents;">
        <div x-show="show"
             x-cloak
             x-transition:enter="transition ease-out duration-160"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-120"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="confirm-backdrop"
             @click="$wire.cancelConfirm()"></div>

        <div x-show="show"
             x-cloak
             x-transition:enter="transition ease-out duration-180"
             x-transition:enter-start="opacity-0 translate-y-2 scale-[.98]"
             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
             x-transition:leave="transition ease-in duration-120"
             x-transition:leave-start="opacity-100 translate-y-0 scale-100"
             x-transition:leave-end="opacity-0 translate-y-2 scale-[.98]"
             class="confirm-dialog"
             role="dialog"
             aria-modal="true">
            <div class="confirm-dialog__icon">
                <x-icon.trash width="18" height="18" />
            </div>

            <div class="confirm-dialog__body">
                <div class="confirm-dialog__eyebrow">Підтвердження</div>
                <h2 class="confirm-dialog__title">{{ $confirmTitle }}</h2>

                @if($confirmSubject)
                    <div class="confirm-dialog__subject mono">{{ $confirmSubject }}</div>
                @endif

                <p class="confirm-dialog__text">{{ $confirmMessage }}</p>
            </div>

            <div class="confirm-dialog__footer">
                <button class="btn btn-ghost" wire:click="cancelConfirm">Скасувати</button>
                <button class="btn btn-danger-fill" wire:click="confirmPendingAction">
                    <x-icon.trash width="13" height="13" />
                    Видалити
                </button>
            </div>
        </div>
    </div>
@endif
