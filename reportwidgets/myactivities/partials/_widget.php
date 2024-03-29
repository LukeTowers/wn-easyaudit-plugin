<div class="report-widget">
    <h3><?= $title; ?></h3>

    <div class="table-container control-list list-scrollable">
        <table name="ListMyActivities" class="table data">
            <thead>
                <tr>
                    <th><span><?= e(trans('luketowers.easyaudit::lang.models.activity.event')); ?></span></th>
                    <th><span><?= e(trans('luketowers.easyaudit::lang.models.activity.subject')); ?></span></th>
                    <th><span><?= e(trans('luketowers.easyaudit::lang.models.activity.created_at')); ?></span></th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($records)) : ?>
                    <?php $backendHelper = new \Backend\Helpers\Backend(); ?>
                    <?php foreach ($records as $record) : ?>
                        <tr class="rowlink" onclick="$.wn.activityController.clickActivityRecord(<?= $record->id; ?>, this, '<?= $this->getEventHandler('onViewLogItemDetails'); ?>')">
                            <td><?= $record->event; ?></td>
                            <td><?= $record->subject_name; ?></td>
                            <td><?= $backendHelper->dateTime($record->created_at); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr><td colspan="100%"><?= e(trans('luketowers.easyaudit::lang.widgets.myactivities.no_records')); ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
