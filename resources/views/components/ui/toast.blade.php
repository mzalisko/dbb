<div
    x-data="{
        toasts: [],
        add(type, message) {
            const id = Date.now();
            this.toasts.push({ id, type, message });
            setTimeout(() => this.remove(id), 4000);
        },
        remove(id) {
            this.toasts = this.toasts.filter(t => t.id !== id);
        }
    }"
    @toast.window="add($event.detail.type ?? 'success', $event.detail.message)"
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
        </div>
    </template>
</div>
