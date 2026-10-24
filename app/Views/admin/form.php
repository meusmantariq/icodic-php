<?php $key = $d['key']; $isNew = empty($row['id']); $errors = flash('errors', []); $old = App\Core\Session::get('_old', []); $val = fn($n) => $old[$n] ?? $row[$n] ?? ''; ?>
<div class="topbar"><div><a class="small muted" href="<?= url("/admin/$key") ?>">← <?= e($d['title']) ?></a><h1><?= $isNew ? 'New' : (!empty($d['inbox']) ? 'View' : 'Edit') ?> <?= e(rtrim($d['label'], 's')) ?></h1></div>
<?php if (!$isNew && empty($d['singleton'])): ?><form method="post" action="<?= url("/admin/$key/{$row['id']}/delete") ?>" data-confirm="Delete this record permanently?"><?= csrf_field() ?><button class="btn btn-ghost btn-sm" style="color:var(--destructive)"><?= icon('Trash') ?> Delete</button></form><?php endif; ?></div>
<?php if ($errors): ?><div class="alert alert-error" style="margin-bottom:1rem"><ul><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form method="post" action="<?= url($isNew ? "/admin/$key" : "/admin/$key/{$row['id']}") ?>" class="card" style="padding:1.5rem"><?= csrf_field() ?>
<div class="form-grid">
<?php foreach ($d['fields'] as $f): $n = $f['name']; $v = $val($n); $wide = in_array($f['type'], ['textarea', 'html', 'list', 'multiselect'], true); $ro = !empty($f['readonly']); ?>
<div class="field <?= $wide ? 'span2' : '' ?>"><span class="label"><?= e($f['label']) ?><?= !empty($f['required']) ? ' *' : '' ?></span>
<?php switch ($f['type']): case 'textarea': ?><textarea class="textarea" name="<?= $n ?>" <?= $ro ? 'readonly' : '' ?>><?= e($v) ?></textarea>
<?php break; case 'html': ?><textarea class="textarea" name="<?= $n ?>" style="min-height:12rem;font-family:var(--font-mono);font-size:.8rem"><?= e($v) ?></textarea>
<?php break; case 'select': ?><select class="select" name="<?= $n ?>"><?php foreach ($f['options'] as $o): ?><option value="<?= e($o) ?>" <?= (string)$v === (string)$o ? 'selected' : '' ?>><?= e($o) ?></option><?php endforeach; ?></select>
<?php break; case 'boolean': ?><label class="switch"><input type="checkbox" name="<?= $n ?>" value="1" <?= $v ? 'checked' : '' ?>> <?= e($f['label']) ?></label>
<?php break; case 'multiselect': $sel = is_array($v) ? $v : json_list($v); ?><div><?php foreach ($f['options'] as $o): ?><label class="chk"><input type="checkbox" name="<?= $n ?>[]" value="<?= e($o) ?>" <?= in_array($o, $sel, true) ? 'checked' : '' ?>> <?= e($o) ?></label><?php endforeach; ?></div>
<?php break; case 'list': ?><input class="input" name="<?= $n ?>" value="<?= e(is_array($v) ? implode(', ', $v) : implode(', ', json_list($v) ?: ($v ? [$v] : []))) ?>" placeholder="Comma separated">
<?php break; case 'image': ?><div class="upload-box" data-upload><img src="<?= e(img($v) ?: 'data:image/gif;base64,R0lGODlhAQABAAAAACw=') ?>" alt="" data-preview><div style="flex:1"><input class="input" type="text" name="<?= $n ?>" value="<?= e($v) ?>" placeholder="/uploads/… or https://…"><input type="file" accept="image/*" class="mt-2 small"><span class="up-msg xs muted"></span></div></div>
<?php break; case 'number': ?><input class="input" type="number" step="1" name="<?= $n ?>" value="<?= e($v) ?>" <?= $ro ? 'readonly' : '' ?>>
<?php break; case 'decimal': ?><input class="input" type="number" step="0.01" min="0" name="<?= $n ?>" value="<?= e($v) ?>" <?= $ro ? 'readonly' : '' ?>>
<?php break; case 'icon': ?><select class="select" name="<?= $n ?>"><?php foreach (App\Core\Icons::names() as $o): ?><option <?= $v === $o ? 'selected' : '' ?>><?= e($o) ?></option><?php endforeach; ?></select>
<?php break; default: ?><input class="input" type="<?= $f['type'] === 'email' ? 'email' : 'text' ?>" name="<?= $n ?>" value="<?= e($v) ?>" <?= !empty($f['required']) ? 'required' : '' ?> <?= $ro ? 'readonly' : '' ?>>
<?php endswitch; ?>
<?php if (!empty($f['help'])): ?><div class="xs muted mt-1"><?= e($f['help']) ?></div><?php endif; ?></div>
<?php endforeach; ?>
</div>
<?php if (!$isNew): ?><p class="xs muted mono mt-6">ID <?= (int)$row['id'] ?><?= !empty($row['legacy_id']) ? ' · Base44 ' . e($row['legacy_id']) : '' ?> · created <?= e($row['created_at'] ?? '') ?> · updated <?= e($row['updated_at'] ?? '') ?></p><?php endif; ?>
<div class="flex gap-2 mt-6"><button class="btn btn-primary">Save</button><a class="btn btn-ghost" href="<?= url("/admin/$key") ?>">Cancel</a></div>
</form>
