<?php if (\BackendAuth::getUser()->hasAccess('luketowers.easyaudit.activities.view_all')) : ?>
    <button
        class="btn btn-danger"
        data-request="onEmptyLog"
        data-request-confirm="<?= e(trans('luketowers.easyaudit::lang.settings.empty_log_confirm')); ?>"
        data-request-flash="true"
        >
        <?= e(trans('luketowers.easyaudit::lang.settings.empty_log')); ?>
    </button>
<?php endif; ?>
<div data-control="toolbar"></div>
