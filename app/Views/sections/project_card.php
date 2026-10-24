<article class="proj reveal card-hover">
  <div class="proj-img"><?php if ($p['image_url']): ?><img src="<?= e(img($p['image_url'])) ?>" alt="<?= e($p['title']) ?>" loading="lazy"><?php endif; ?>
    <?php if (!empty($site['company_logo_url'])): ?><div class="proj-brand"><img src="<?= e(img($site['company_logo_url'])) ?>" alt="iCodic"></div><?php endif; ?>
    <div class="proj-tags"><span class="tag"><?= e($p['region']) ?></span><?php if ($p['year']): ?><span class="tag"><?= e($p['year']) ?></span><?php endif; ?></div></div>
  <div class="proj-body"><div class="mono xs upper mint"><?= e($p['client']) ?></div><h3><?= e($p['title']) ?></h3><p class="desc"><?= e($p['description']) ?></p>
    <?php $ts = json_list($p['tech_stack']); if ($ts): ?><div class="stack"><?php foreach ($ts as $tsi): ?><span class="tag"><?= e($tsi) ?></span><?php endforeach; ?></div><?php endif; ?>
    <?php if ($p['url']): ?><a class="btn-link mt-4" href="<?= e($p['url']) ?>" target="_blank" rel="noreferrer">View Case Study <?= icon('ArrowUpRight') ?></a><?php endif; ?></div>
</article>
