<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ?? 'Admin') ?> · iCodic</title>
<meta name="robots" content="noindex">
<link rel="stylesheet" href="<?= asset('assets/css/app.css') ?>">
</head>
<body>
<div class="auth-wrap"><div class="auth-card">
<div class="flex items-center gap-2 text-primary"><?= icon('Shield') ?><span class="mono xs upper muted">iCodic Command Console</span></div>
<?= $content ?>
</div></div>
</body>
</html>
