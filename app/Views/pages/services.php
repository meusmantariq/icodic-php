<?php $pastel = ['c-orange', 'c-blue', 'c-green', 'c-purple', 'c-pink', 'c-teal']; ?>
<?= App\Core\View::render('partials.page_hero', ['eyebrow' => 'Services', 'heading' => 'Beyond the license — full-lifecycle IT services', 'sub' => 'Wrapping every product we sell — from procurement to long-term managed support — across the UK, the UAE, India and Pakistan.']) ?>
<section class="section"><div class="container">
  <div class="reveal sec-head"><div class="eyebrow">// What we do</div><h2 class="h2">Our IT Services</h2></div>
  <?php if (!$services): ?><div class="empty mt-8">No services yet. Add them from the admin panel.</div><?php else: ?>
  <div class="grid sm-2 lg-3 mt-8"><?php foreach ($services as $i => $s): ?><div class="svc reveal card-hover"><?php if ($s['image_url']): ?><div class="svc-img"><img src="<?= e(img($s['image_url'])) ?>" alt="<?= e($s['title']) ?>" loading="lazy"></div><?php endif; ?><div class="icon-circle <?= $pastel[$i % 6] ?>"><?= icon($s['icon']) ?></div><h3><?= e($s['title']) ?></h3><p><?= e($s['description']) ?></p></div><?php endforeach; ?></div><?php endif; ?>
  <div class="cta-band cta-dark reveal"><div><h3>Need a managed IT partner?</h3><p>Talk to a regional specialist about support, infrastructure and licensing.</p></div><a class="btn btn-mint-solid" href="<?= url('/contact') ?>">Contact us <?= icon('ArrowUpRight') ?></a></div>
</div></section>
