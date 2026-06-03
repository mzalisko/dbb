@if($restoreSiteId)
    <div class="confirm-backdrop" wire:click="cancelRestoreSite"></div>
    <div class="confirm-dialog" role="dialog" aria-modal="true" aria-labelledby="restore-site-title">
        <div class="confirm-dialog__icon">
            <x-icon.refresh width="22" height="22" />
        </div>

        <div class="confirm-dialog__body">
            <div class="confirm-dialog__eyebrow">Відновлення сайту</div>
            <div class="confirm-dialog__title" id="restore-site-title">Відновити сайт?</div>
            <div class="confirm-dialog__subject mono">{{ $restoreSiteName }}</div>
            <div class="confirm-dialog__text">
                Сайт видалений. Щоб перейти до нього, спочатку відновіть цю сутність.
            </div>
        </div>

        <div class="confirm-dialog__footer">
            <button type="button" class="btn btn-secondary btn-sm" wire:click="cancelRestoreSite">Скасувати</button>
            <button type="button" class="btn btn-primary btn-sm" wire:click="confirmRestoreSite">
                <x-icon.refresh width="13" height="13" /> Відновити
            </button>
        </div>
    </div>
@endif
