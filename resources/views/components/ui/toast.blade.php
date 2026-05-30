<div
    x-data="{
        toasts: [],
        add(detail) {
            const id = Date.now() + Math.random();
            const t = {
                id,
                type: detail.type ?? 'success',
                message: detail.message ?? '',
                action: detail.action ?? null,
                actionLabel: detail.actionLabel ?? 'Відмінити',
                actionData: detail.actionData ?? {},
            };
            this.toasts.push(t);
            const ttl = detail.duration ?? (t.action ? 8000 : 4000);
            setTimeout(() => this.remove(id), ttl);
        },
        remove(id) {
            this.toasts = this.toasts.filter(t => t.id !== id);
        },
        fire(t) {
            if (t.action && window.Livewire) {
                window.Livewire.dispatch(t.action, t.actionData);
            }
            this.remove(t.id);
        }
    }"
    @toast.window="add($event.detail || {})"
    class="toast-container"
    style="position:fixed; top:20px; right:20px; z-index:9999; display:flex; flex-direction:column; gap:8px; pointer-events:none;"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            class="toast"
            :class="{
                'toast--success': toast.type === 'success',
                'toast--error':   toast.type === 'error',
                'toast--info':    toast.type === 'info'
            }"
            x-transition:enter="toast-enter"
            x-transition:enter-start="toast-enter-start"
            x-transition:enter-end="toast-enter-end"
            x-transition:leave="toast-leave"
            x-transition:leave-start="toast-leave-start"
            x-transition:leave-end="toast-leave-end"
            @click="remove(toast.id)"
            style="pointer-events:auto; cursor:pointer;"
        >
            <span x-text="toast.message"></span>
            <template x-if="toast.action">
                <button class="toast__action" @click.stop="fire(toast)" x-text="toast.actionLabel"></button>
            </template>
        </div>
    </template>
</div>
