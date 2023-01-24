<div
    class="field-activitylog <?= $this->previewMode ? 'form-group-preview' : '' ?>"
    data-control="field-activitylog"
    data-alias="<?= $this->alias; ?>"
>
    <div class="row">
        <?php if ($toolbar) : ?>
            <?= $toolbar->render() ?>
        <?php endif ?>
        <?php if ($filter) : ?>
            <?= $filter->render() ?>
        <?php endif ?>
    </div>

    <div class="row">
        <?= $list->render() ?>
    </div>
</div>
