<?php if ($record->subject_url) : ?>
    <a href="<?= e($record->subject_url); ?>" target="_blank" rel="noopener noreferrer">
        <span class="icon-arrow-up-right-from-square"></span>&nbsp;
        <?= e($record->subject_name); ?>
    </a>
<?php else : ?>
    <?= e($record->subject_name); ?>
<?php endif; ?>
