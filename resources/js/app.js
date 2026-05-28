import Sortable from 'sortablejs';

document.addEventListener('alpine:init', () => {

    // Primary phones — vertical reorder
    Alpine.data('sortable', () => ({
        init() {
            new Sortable(this.$el, {
                handle: '.cc-drag',
                animation: 150,
                ghostClass: 'ctable-group--ghost',
                onEnd: (evt) => {
                    if (evt.newIndex === evt.oldIndex) return;
                    const ids = [...this.$el.querySelectorAll(':scope > [data-entry-id]')]
                        .map(el => parseInt(el.dataset.entryId));
                    this.$wire.reorderEntries(ids);
                }
            });
        }
    }));

    // Backup phones within a group — vertical reorder
    Alpine.data('backupSortable', (parentId) => ({
        init() {
            new Sortable(this.$el, {
                handle: '.cc-drag',
                animation: 150,
                ghostClass: 'crow--ghost',
                onEnd: (evt) => {
                    if (evt.newIndex === evt.oldIndex) return;
                    const ids = [...this.$el.querySelectorAll(':scope > [data-backup-id]')]
                        .map(el => parseInt(el.dataset.backupId));
                    this.$wire.reorderBackups(parentId, ids);
                }
            });
        }
    }));

});
