<section class="section"><div class="container" style="max-width:60rem">
  <a class="btn-link" href="<?= url('/#erp') ?>"><?= icon('ChevronLeft') ?> Back to ERP</a>
  <div class="grid lg-2 mt-8" style="gap:3rem;align-items:start">
    <?php if ($sol['image_url']): ?><div class="card" style="overflow:hidden;aspect-ratio:4/3"><img src="<?= e(img($sol['image_url'])) ?>" alt="<?= e($sol['name']) ?>" style="width:100%;height:100%;object-fit:cover"></div><?php endif; ?>
    <div><div class="flex wrap gap-2"><span class="tag tag-primary"><?= e($sol['type']) ?></span><?php if ($sol['consultation']): ?><span class="tag tag-mint">Consultation required</span><?php endif; ?></div>
      <h1 class="h1" style="font-size:2.5rem"><?= e($sol['name']) ?></h1><?php if ($sol['tagline']): ?><p class="mint mono mt-2"><?= e($sol['tagline']) ?></p><?php endif; ?><p class="lead"><?= e($sol['description']) ?></p>
      <?php $feats = json_list($sol['features']); if ($feats): ?><h2 class="eyebrow mt-8">Key features</h2><ul class="features"><?php foreach ($feats as $f): ?><li><?= icon('Check') ?> <?= e($f) ?></li><?php endforeach; ?></ul><?php endif; ?>
      <a class="btn btn-primary mt-8" href="<?= url('/contact') ?>"><?= e(t('cta.consult')) ?> <?= icon('ArrowUpRight') ?></a></div>
  </div>
</div></section>
