<?php use App\Core\Region; $li = config('site.linkedin_url'); ?>
<footer class="footer">
  <div class="container">
    <div class="footer-grid">
      <div>
        <?php if (!empty($site['company_logo_url'])): ?><img src="<?= e(img($site['company_logo_url'])) ?>" alt="<?= e($company) ?>" style="height:3.5rem;border-radius:.375rem"><?php else: ?><div class="logo-fallback">QN</div><?php endif; ?>
        <p class="mt-4 muted small" style="max-width:26rem">A global command center for enterprise software subscriptions, ERP implementations and IT hardware — engineering digital infrastructure across the UK, the Middle East and South Asia.</p>
        <?php if ($li): ?><div class="mt-6 flex gap-3"><a class="sq" href="<?= e($li) ?>" target="_blank" rel="noopener noreferrer" aria-label="LinkedIn"><?= icon('Linkedin') ?></a></div><?php endif; ?>
      </div>
      <div><h4>Catalog</h4><ul>
        <li><a href="<?= url('/#software') ?>"><?= e(t('nav.software')) ?></a></li><li><a href="<?= url('/#erp') ?>"><?= e(t('nav.erp')) ?></a></li><li><a href="<?= url('/#projects') ?>"><?= e(t('nav.projects')) ?></a></li><li><a href="<?= url('/#services') ?>"><?= e(t('nav.services')) ?></a></li><li><a href="<?= url('/about') ?>"><?= e(t('nav.about')) ?></a></li><li><a href="<?= url('/contact') ?>"><?= e(t('nav.contact')) ?></a></li>
      </ul></div>
      <div><h4>ERP Practice</h4><ul>
        <li><a href="<?= url('/solutions/erpnext') ?>">ERPNext</a></li><li><a href="<?= url('/solutions/odoo') ?>">Odoo</a></li><li><a href="<?= url('/solutions/custom-crm') ?>">Custom CRM</a></li><li><a href="<?= url('/solutions/custom-erp') ?>">Custom ERP</a></li><li><a href="<?= url('/#services') ?>">Hardware Infrastructure</a></li>
      </ul></div>
      <div><h4>Global Offices</h4><ul>
        <?php foreach (Region::all() as $r): ?><li class="footer-off"><div class="flex justify-between"><span style="color:var(--fg);font-weight:500"><?= e($r['city']) ?>, <?= e($r['code']) ?></span><span class="mono xs mint" data-tz="<?= e($r['tz']) ?>"><?= e(local_time($r['tz'])) ?></span></div><div class="flex items-center gap-1 xs mt-1"><?= icon('Phone', 'ico xs') ?> <?= e($r['phone']) ?></div></li><?php endforeach; ?>
      </ul><div class="mt-4 flex items-center gap-1 xs muted"><?= icon('Mail', 'ico xs') ?> info@quantumnexis.si</div></div>
    </div>
    <div class="footer-bottom">
      <div><div>© <?= date('Y') ?> Quantum Nexis LLC — UK · UAE · India · Pakistan. <?= e(t('footer.rights')) ?></div><a href="https://quantumnexis.si/" target="_blank" rel="noopener noreferrer">Managed &amp; developed by Quantum Nexis LLC</a></div>
      <div class="flex gap-4"><a href="<?= url('/about') ?>">Privacy</a><a href="<?= url('/contact') ?>">Terms</a><a href="<?= url('/contact') ?>">Support</a></div>
    </div>
  </div>
</footer>
