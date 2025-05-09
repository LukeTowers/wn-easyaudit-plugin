<?php
function sortJsonKeys(array $value): array
{
    foreach ($value as $k => &$v) {
        if (is_array($v)) {
            $v = sortJsonKeys($v);
        }
    }
    ksort($value);
    return $value;
}

// Decrypt encrypted attributes
$encryptedAttributes = $model->subject?->methodExists('getEncryptableAttributes')
    ? $model->subject->getEncryptableAttributes()
    : [];
if (!empty($encryptedAttributes) && is_array($value)) {
    $crypt = $model->subject->getEncrypter();
    $sources = ['from', 'to'];
    foreach (array_intersect($encryptedAttributes, array_keys($value)) as $key) {
        $comparison = null;
        foreach ($sources as $source) {
            $decrypted = $crypt->decrypt($value[$key][$source]);
            if (!is_scalar($decrypted)) {
                $decrypted = json_encode(sortJsonKeys((array) $decrypted));
            }
            $value[$key][$source] = $decrypted;
            // Ignore unchanged decrypted data
            if (!isset($comparison)) {
                $comparison = $decrypted;
            } elseif ($comparison === $decrypted) {
                unset($value[$key]);
            }
        }
    }
}
?>
<?php if (empty($value)) : ?>
    <span><?= e(Lang::get('luketowers.easyaudit::lang.models.activity.no_changes')); ?></span>
<?php else : ?>
    <?php foreach ($value as $key => $change) : ?>
        <strong style="display: block; text-transform: uppercase; margin: 1em 0;"><?= $key ?></strong>
        <?php if (is_json($change['from'])) : ?>
            <div style="padding: 15px; display: flex; gap: 1em; border: 1px solid #d0d0d0; border-radius: 6px; background: #f3f3f3">
                <div class="form-group form-group-preview text-field" id="Form-field-<?= $key ?>-group">
                    <div id="jsondiff-<?= $key ?>"></div>
                    <script>
                        (function () {
                            const from = JSON.parse(<?= json_encode($change['from']) ?>);
                            const to = JSON.parse(<?= json_encode($change['to']) ?>);
                            const delta = jsondiffpatch.diff(from, to);
                            const html = jsondiffpatchHtml.format(delta, from);
                            jsondiffpatchHtml.hideUnchanged();
                            document.getElementById('jsondiff-<?= $key ?>').innerHTML = html;
                        })();
                    </script>
                </div>
            </div>
        <?php else : ?>
            <div style="padding: 15px; display: flex; gap: 1em; border: 1px solid #d0d0d0; border-radius: 6px; background: #f3f3f3">
                <div class="form-group form-group-preview text-field span-left" id="Form-field-<?= $key ?>-group">
                    <label for="Form-field-<?= $key ?>"><?= e(Lang::get('luketowers.easyaudit::lang.models.activity.change_from')); ?></label>
                    <span class="form-control"><?= e($change['from']) ?></span>
                </div>
                <div class="form-group form-group-preview text-field span-right" id="Form-field-<?= $key ?>-group">
                    <label for="Form-field-<?= $key ?>"><?= e(Lang::get('luketowers.easyaudit::lang.models.activity.change_to')); ?></label>
                    <span class="form-control"><?= e($change['to']) ?></span>
                </div>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
<?php endif; ?>
