<?php
use App\Core\{I18n, Region};
$links = [['nav.software', '/#software'], ['nav.erp', '/#erp'], ['nav.projects', '/projects'], ['nav.cyber', '/cyber-security'], ['nav.services', '/services'], ['nav.about', '/about'], ['nav.contact', '/contact'], ['nav.track', '/track-order']];
$back = urlencode(parse_url($path, PHP_URL_PATH) ?: '/');
?>
<header class="header">
  <div class="header-bar"></div>
  <div class="container header-in">
    <a href="<?= url('/') ?>" class="logo" aria-label="Quantum Nexis home">
      <?php if (!empty($site['company_logo_url'])): ?><img src="<?= e(img($site['company_logo_url'])) ?>" alt="<?= e($company) ?>"><?php else: ?><div class="logo-fallback">QN</div><?php endif; ?>
    </a>
    <nav class="nav"><?php foreach ($links as [$k, $to]): ?><a href="<?= url($to) ?>"><?= e(t($k)) ?></a><?php endforeach; ?></nav>
    <div class="flex items-center gap-2">
      <div class="prefs">
        <button class="prefs-btn" type="button" aria-label="Language and region"><?= icon('Globe', 'ico') ?><span class="only-desktop"><?= e($regionCode) ?> · <?= e(I18n::LANGUAGES[$lang]['label']) ?></span><?= icon('ChevronDown', 'ico xs') ?></button>
        <div class="prefs-menu">
          <div class="eyebrow"><?= e(t('prefs.language')) ?></div>
          <div class="prefs-grid g4"><?php foreach (I18n::LANGUAGES as $c => $l): ?><a class="pill<?= $c === $lang ? ' active' : '' ?>" href="<?= url("/prefs?lang=$c&back=$back") ?>" title="<?= e($l['name']) ?>"><?= e($l['label']) ?></a><?php endforeach; ?></div>
          <div class="eyebrow mt-4"><?= e(t('prefs.region')) ?></div>
          <div class="prefs-grid g2"><?php foreach (Region::all() as $c => $r): ?><a class="rbox<?= $c === $regionCode ? ' active' : '' ?>" href="<?= url("/prefs?region=$c&back=$back") ?>" title="<?= e($r['name']) ?>"><b><?= e($c) ?></b><small><?= e($r['city']) ?></small></a><?php endforeach; ?></div>
        </div>
      </div>
      <a href="<?= url('/admin') ?>" class="btn btn-outline btn-sm btn-round only-desktop"><?= icon('Shield') ?> <?= e(t('nav.admin')) ?></a>
      <button class="burger only-mobile" type="button" aria-label="Toggle menu"><?= icon('Menu') ?></button>
    </div>
  </div>
  <div class="mobile-nav only-mobile">
    <?php foreach ($links as [$k, $to]): ?><a href="<?= url($to) ?>"><?= e(t($k)) ?></a><?php endforeach; ?>
    <a href="<?= url('/admin') ?>" class="btn btn-outline btn-sm mt-3" style="width:100%"><?= icon('Shield') ?> <?= e(t('nav.admin')) ?></a>
  </div>
</header>
