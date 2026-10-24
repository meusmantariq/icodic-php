<div class="topbar"><div><h1>Command Console</h1><p class="muted small">Manage catalog, ERP, projects, services &amp; messages</p></div>
<a class="btn btn-outline btn-sm" href="<?= url('/') ?>" target="_blank">View site ↗</a></div>
<div class="tiles">
  <div class="tile"><div class="k"><?= $stats['orders_new'] ?></div><div class="l">New orders</div></div>
  <div class="tile"><div class="k"><?= $stats['messages_new'] ?></div><div class="l">Unread messages</div></div>
  <div class="tile"><div class="k"><?= $stats['assess_new'] ?></div><div class="l">New assessments</div></div>
  <div class="tile"><div class="k">$<?= number_format($stats['revenue'], 0) ?></div><div class="l">Confirmed order value</div></div>
</div>
<div class="tiles mt-6">
<?php foreach ($registry as $k => $d): ?>
  <a class="tile card-hover" href="<?= url("/admin/$k") ?>"><div class="k"><?= $counts[$k] ?></div><div class="l"><?= e($d['title']) ?></div></a>
<?php endforeach; ?>
</div>
<div class="grid lg-2 mt-8">
<div><h3 class="mono xs upper muted" style="margin-bottom:.5rem">Recent orders</h3>
<div class="table-wrap"><table><thead><tr><th>Order</th><th>Customer</th><th>Product</th><th>Total</th><th>Status</th></tr></thead><tbody>
<?php foreach ($recentOrders as $o): ?><tr><td><a class="mono text-primary" href="<?= url("/admin/orders/{$o['id']}/edit") ?>"><?= e($o['order_id']) ?></a></td><td><?= e($o['customer_name']) ?><br><span class="xs muted"><?= e($o['email']) ?></span></td><td><?= e($o['product_name']) ?> × <?= (int)$o['quantity'] ?></td><td>$<?= number_format((float)$o['total_usd'], 2) ?></td><td><span class="status s-<?= e($o['status']) ?>"><?= e($o['status']) ?></span></td></tr><?php endforeach; ?>
<?php if (!$recentOrders): ?><tr><td colspan="5" class="muted center">No orders yet.</td></tr><?php endif; ?>
</tbody></table></div></div>
<div><h3 class="mono xs upper muted" style="margin-bottom:.5rem">Recent messages</h3>
<div class="table-wrap"><table><thead><tr><th>From</th><th>Message</th><th>Status</th></tr></thead><tbody>
<?php foreach ($recentMessages as $m): ?><tr><td><a class="text-primary" href="<?= url("/admin/messages/{$m['id']}/edit") ?>"><?= e($m['name']) ?></a><br><span class="xs muted"><?= e($m['email']) ?></span></td><td class="small"><?= e(str_limit((string)$m['message'], 80)) ?></td><td><span class="status s-<?= e($m['status']) ?>"><?= e($m['status']) ?></span></td></tr><?php endforeach; ?>
<?php if (!$recentMessages): ?><tr><td colspan="3" class="muted center">No messages yet.</td></tr><?php endif; ?>
</tbody></table></div>
<h3 class="mono xs upper muted mt-6" style="margin-bottom:.5rem">Activity log</h3>
<div class="table-wrap"><table><tbody>
<?php foreach ($log as $l): ?><tr><td class="xs muted mono" style="white-space:nowrap"><?= e(substr($l['created_at'], 0, 16)) ?></td><td class="small"><b><?= e($l['user_name'] ?? 'system') ?></b> <?= e($l['action']) ?> <?= e($l['entity']) ?> #<?= (int)$l['record_id'] ?></td></tr><?php endforeach; ?>
</tbody></table></div></div>
</div>
