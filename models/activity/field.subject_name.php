<span class="form-control">
    <?php if ($model->subject_url) : ?>
        <span class="icon-arrow-up-right-from-square"></span>&nbsp;
        <a href="<?= e($model->subject_url); ?>" target="_blank" rel="noopener noreferrer">
            <?= e($model->subject_name); ?>
        </a>
    <?php else : ?>
        <?= e($model->subject_name); ?>
    <?php endif; ?>
</span>
