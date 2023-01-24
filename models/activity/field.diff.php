<?php if (empty($value)) : ?>
    <span><?= e(Lang::get('luketowers.easyaudit::lang.models.activity.no_changes')); ?></span>
<?php else : ?>
    <?php foreach ($value as $key => $change) : ?>
        <strong style="display: block; text-transform: uppercase; margin: 1em 0;"><?= $key ?></strong>
        <div style="padding: 15px; display: flex; gap: 1em; border: 1px solid #d0d0d0; border-radius: 6px; background: #f3f3f3">
            <div class="form-group form-group-preview text-field span-left" id="Form-field-<?= $key ?>-group">
                <label for="Form-field-<?= $key ?>"><?= e(Lang::get('luketowers.easyaudit::lang.models.activity.change_from')); ?></label>
                <?php if (is_json($change['from'])) : ?>
                    <pre class="form-control"><?= e(json_encode(json_decode($change['from']), JSON_PRETTY_PRINT)) ?></pre>
                <?php else : ?>
                    <span class="form-control"><?= e($change['from']) ?></span>
                <?php endif; ?>
            </div>
            <div class="form-group form-group-preview text-field span-right" id="Form-field-<?= $key ?>-group">
                <label for="Form-field-<?= $key ?>"><?= e(Lang::get('luketowers.easyaudit::lang.models.activity.change_to')); ?></label>
                <?php if (is_json($change['to'])) : ?>
                    <pre class="form-control"><?= e(json_encode(json_decode($change['to']), JSON_PRETTY_PRINT)) ?></pre>
                <?php else : ?>
                    <span class="form-control"><?= e($change['to']) ?></span>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
