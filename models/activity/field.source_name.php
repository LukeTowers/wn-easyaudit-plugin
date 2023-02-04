<span class="form-control">
    <?php if ($model->source_url) : ?>
        <span class="icon-arrow-up-right-from-square"></span>&nbsp;
        <a href="<?= e($model->source_url); ?>" target="_blank" rel="noopener noreferrer">
            <?= e($model->source_name); ?>
        </a>
    <?php else : ?>
        <?= e($model->source_name); ?>
    <?php endif; ?>
</span>
