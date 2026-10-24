<?php $key = $d['key']; $statusField = null; foreach ($d['fields'] as $f) if ($f['name'] === 'status') $statusField = $f;
$cols = array_values(array_filter($d['fields'], fn($f) => !in_array($f['type'], ['textarea', 'html'], true) && $f['name'] !== 'image_url'));
$cols = array_slice($cols, 0, 6); $imgField = null; foreach ($d['fields'] as $f) if ($f['type'] === 'image') { $imgField = $f['name']; break; } ?>
<div class="topbar"><div><h1><?= e($d['title']) ?></h1><p class="muted small"><?= e($d['subtitle']) ?> · <?= $total ?> records</p></div>
<div class="flex gap-2 wrap">
  <form method="get" class="flex gap-2"><input class="input" style="min-height:2.5rem;width:14rem" type="search" name="q" placeholder="Search…" value="<?= e($q) ?>">
  <?php if ($statusField): ?><select class="select" style="min-height:2.5rem;width:auto" name="status" onchange="this.form.submit()"><option value="">All statuses</option><?php foreach ($statusField['options'] as $o): ?><option <?= $status === $o ? 'selected' : '' ?>><?= e($o) ?></option><?php endforeach; ?></select><?php endif; ?>
  <button class="btn btn-ghost btn-sm"><?= icon('Search') ?></button></form>
  <a class="btn btn-ghost btn-sm" href="<?= url("/admin/$key/export.csv") ?>"><?= icon('Download') ?> CSV</a>
  <?php if (empty($d['inbox'])): ?><a class="btn btn-primary btn-sm" href="<?= url("/admin/$key/new") ?>"><?= icon('Plus') ?> New</a><?php endif; ?>
</div></div>
<div class="table-wrap"><table><thead><tr><?php if ($imgField): ?><th></th><?php endif; ?><?php foreach ($cols as $c): ?><th><?= e($c['label']) ?></th><?php endforeach; ?><th>Created</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $r): ?><tr>
<?php if ($imgField): ?><td><?php if ($r[$imgField]): ?><img class="thumb" src="<?= e(img($r[$imgField])) ?>" alt=""><?php endif; ?></td><?php endif; ?>
<?php foreach ($cols as $c): $v = $r[$c['name']] ?? ''; ?><td>
<?php if ($c['name'] === 'status' && $statusField): ?>
  <form method="post" action="<?= url("/admin/$key/{$r['id']}/status") ?>" class="inline"><?= csrf_field() ?><select name="status" class="status s-<?= e($v) ?>" style="border:0;cursor:pointer" onchange="this.form.submit()"><?php foreach ($statusField['options'] as $o): ?><option <?= $v === $o ? 'selected' : '' ?>><?= e($o) ?></option><?php endforeach; ?></select></form>
<?php elseif ($c['type'] === 'boolean'): ?><?= $v ? '<span class="status s-completed">yes</span>' : '<span class="status">no</span>' ?>
<?php elseif (in_array($c['type'], ['list', 'multiselect'], true)): ?><span class="xs muted"><?= e(implode(', ', json_list($v))) ?></span>
<?php elseif ($c['type'] === 'decimal'): ?>$<?= number_format((float)$v, 2) ?>
<?php elseif (!empty($c['tag'])): ?><span class="tag"><?= e($v) ?></span>
<?php else: ?><?= e(str_limit((string)$v, 60)) ?><?php endif; ?></td><?php endforeach; ?>
<td class="xs muted mono" style="white-space:nowrap"><?= e(substr((string)$r['created_at'], 0, 10)) ?></td>
<td><div class="actions"><a class="btn btn-ghost" href="<?= url("/admin/$key/{$r['id']}/edit") ?>"><?= icon('Pencil') ?> <?= !empty($d['inbox']) ? 'View' : 'Edit' ?></a>
<form method="post" action="<?= url("/admin/$key/{$r['id']}/delete") ?>" data-confirm="Delete this record permanently?"><?= csrf_field() ?><button class="btn btn-ghost" style="color:var(--destructive)"><?= icon('Trash') ?></button></form></div></td>
</tr><?php endforeach; ?>
<?php if (!$rows): ?><tr><td colspan="9" class="center muted" style="padding:3rem">No records found.</td></tr><?php endif; ?>
</tbody></table></div>
<?php if ($pages > 1): ?><nav class="pagination"><?php for ($p = 1; $p <= $pages; $p++): ?><?php if ($p === $page): ?><span class="active"><?= $p ?></span><?php else: ?><a href="?<?= http_build_query(['q' => $q, 'status' => $status, 'page' => $p]) ?>"><?= $p ?></a><?php endif; ?><?php endfor; ?></nav><?php endif; ?>
