<?php use App\Core\Auth; $u = Auth::user(); $reg = $registry ?? require APP_PATH . '/config/entities.php'; $cur = $_SERVER['REQUEST_URI'] ?? ''; ?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ?? 'Admin') ?> · Quantum Nexis Admin</title>
<meta name="robots" content="noindex">
<meta name="csrf-token" content="<?= csrf_token() ?>">
<link rel="stylesheet" href="<?= asset('assets/css/app.css') ?>">
</head>
<body>
<div class="admin">
<aside class="side">
  <a class="brand" href="<?= url('/admin') ?>"><?= icon('Shield') ?> Command Console</a>
  <a href="<?= url('/admin') ?>" class="<?= preg_match('~/admin/?(\?|$)~', $cur) ? 'active' : '' ?>"><?= icon('LayoutDashboard') ?>&nbsp; Dashboard</a>
  <div class="grp">Catalog</div>
  <?php foreach ($reg as $k => $d): if (!empty($d['inbox']) || !empty($d['singleton'])) continue; ?>
  <a href="<?= url("/admin/$k") ?>" class="<?= str_contains($cur, "/admin/$k") ? 'active' : '' ?>"><span><?= e($d['label']) ?></span><?php if (isset($counts[$k])): ?><span class="cnt"><?= $counts[$k] ?></span><?php endif; ?></a>
  <?php endforeach; ?>
  <div class="grp">Inbox</div>
  <?php foreach ($reg as $k => $d): if (empty($d['inbox'])) continue; ?>
  <a href="<?= url("/admin/$k") ?>" class="<?= str_contains($cur, "/admin/$k") ? 'active' : '' ?>"><span><?= e($d['label']) ?></span><?php if (isset($counts[$k])): ?><span class="cnt"><?= $counts[$k] ?></span><?php endif; ?></a>
  <?php endforeach; ?>
  <div class="grp">System</div>
  <a href="<?= url('/admin/settings') ?>" class="<?= str_contains($cur, '/admin/settings') ? 'active' : '' ?>">Site Settings</a>
  <a href="<?= url('/admin/account') ?>" class="<?= str_contains($cur, '/admin/account') ? 'active' : '' ?>">My Account</a>
  <a href="<?= url('/') ?>" target="_blank">View website ↗</a>
  <form method="post" action="<?= url('/admin/logout') ?>" style="margin-top:auto"><?= csrf_field() ?><button class="btn btn-ghost btn-sm" style="width:100%;color:#cbd5e1"><?= icon('LogOut') ?> Sign out (<?= e($u['name'] ?? '') ?>)</button></form>
</aside>
<main class="main">
<?php if ($m = flash('success')): ?><div class="alert alert-success mt-2" style="margin-bottom:1rem"><?= e($m) ?></div><?php endif; ?>
<?php if ($m = flash('error')): ?><div class="alert alert-error mt-2" style="margin-bottom:1rem"><?= e($m) ?></div><?php endif; ?>
<?= $content ?>
</main>
</div>
<script src="<?= asset('assets/js/app.js') ?>" defer></script>
</body>
</html>
