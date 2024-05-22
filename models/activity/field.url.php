<span class="form-control">
    <?php if ($value) : ?>
        <span class="icon-arrow-up-right-from-square"></span>&nbsp;
        <a href="<?= e($value); ?>" target="_blank" rel="noopener noreferrer">
            <?= e($value); ?>
        </a>
    <?php else : ?>
        <?= e($value); ?>
    <?php endif; ?>
</span>
