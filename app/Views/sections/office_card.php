<div class="office reveal">
  <div class="office-head"><div class="office-code"><?= e($r['code']) ?></div><div><h3 style="font-size:1.25rem;font-weight:700"><?= e($r['city']) ?> Office</h3><p class="small muted"><?= e($r['name']) ?></p></div></div>
  <div class="rows">
    <div class="row"><?= icon('User') ?><div><div class="l">Contact</div><div><?= e($r['contact']) ?> <span class="muted">· <?= e($r['role']) ?></span></div></div></div>
    <div class="row"><?= icon('MapPin') ?><div><div class="l">Address</div><div><?= e($r['address']) ?></div></div></div>
    <div class="row"><?= icon('Phone') ?><div><div class="l">Phone</div><a href="tel:<?= e(preg_replace('/\s+/', '', $r['phone'])) ?>" class="text-primary"><?= e($r['phone']) ?></a></div></div>
    <div class="row"><?= icon('Mail') ?><div><div class="l">Email</div><a href="mailto:<?= e($r['email']) ?>" class="text-primary"><?= e($r['email']) ?></a></div></div>
    <div class="row"><?= icon('Clock') ?><div><div class="l">Local time</div><span class="mono mint" data-tz="<?= e($r['tz']) ?>"><?= e(local_time($r['tz'])) ?></span> <span class="muted small">· local time</span></div></div>
  </div>
  <a class="btn btn-primary mt-6" href="<?= url('/contact') ?>" style="width:100%"><?= e(t('global.contact')) ?></a>
</div>
