<?php
use App\Core\{I18n, Region};
$site = App\Models\Repo::settings();
$lang = I18n::lang(); $dir = I18n::dir(); $region = Region::current(); $regionCode = Region::code();
$company = $site['company_name'] ?? 'iCodic';
$pageTitle = isset($title) && $title ? e($title) . ' · iCodic' : 'iCodic — Enterprise Software, Hardware & ERP Solutions | UK · UAE · IN · PK';
$path = $_SERVER['REQUEST_URI'] ?? '/';
?>
<!doctype html>
<html lang="<?= e($lang) ?>" dir="<?= e($dir) ?>" data-base="<?= e(rtrim(url('/'), '/')) ?>" data-region="<?= e($regionCode) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<meta name="description" content="iCodic — Global IT software & hardware company. Enterprise software subscriptions (Adobe, MS Office, AutoCAD, 3ds Max), ERPNext, Odoo & custom ERP solutions across the UK, UAE, India & Pakistan.">
<meta property="og:title" content="iCodic — Enterprise Software, Hardware & ERP Solutions">
<meta property="og:description" content="Global command center for enterprise software subscriptions, ERP implementations and IT hardware across the UK, UAE, India and Pakistan.">
<meta property="og:type" content="website">
<?php if (!empty($site['company_logo_url'])): ?><meta property="og:image" content="<?= e(img($site['company_logo_url'])) ?>"><link rel="icon" href="<?= e(img($site['company_logo_url'])) ?>"><?php endif; ?>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(asset('/assets/css/app.css')) ?>">
<title><?= $pageTitle ?></title>
</head>
<body>
<?= App\Core\View::render('partials.navbar', compact('site', 'lang', 'dir', 'region', 'regionCode', 'company', 'path')) ?>
<main><?= $content ?></main>
<?= App\Core\View::render('partials.footer', compact('site', 'company')) ?>
<?= App\Core\View::render('partials.order_modal', compact('site', 'regionCode')) ?>
<?= App\Core\View::render('partials.chat') ?>
<script src="<?= e(asset('/assets/js/app.js')) ?>" defer></script>
</body>
</html>
