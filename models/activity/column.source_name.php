<?php if ($record->source_url) : ?>
    <a href="<?= e($record->source_url); ?>" target="_blank" rel="noopener noreferrer">
        <span class="icon-arrow-up-right-from-square"></span>&nbsp;
        <?= e($record->source_name); ?>
    </a>
<?php else : ?>
    <?= e($record->source_name); ?>
<?php endif; ?>
