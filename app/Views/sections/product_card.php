<?php $price = $period === 'monthly' ? $p['price_monthly'] : $p['price_yearly']; ?>
<div class="product" data-cat="<?= e($p['category']) ?>" data-search="<?= e(mb_strtolower($p['name'] . ' ' . $p['brand'] . ' ' . $p['description'])) ?>">
  <?php if ($p['image_url']): ?><div class="product-img"><img src="<?= e(img($p['image_url'])) ?>" alt="<?= e($p['name']) ?>" loading="lazy"></div><?php endif; ?>
  <div class="product-body">
    <div class="flex justify-between gap-2" style="align-items:flex-start"><div style="min-width:0"><span class="mono xs upper mint"><?= e($p['brand']) ?></span><h3 class="truncate"><?= e($p['name']) ?></h3></div><?php if ($p['in_stock']): ?><span class="mono xs mint flex items-center gap-1 stock" style="flex:none"><span class="dot" style="animation:none"></span> <?= e(t('software.instock')) ?></span><?php else: ?><span class="mono xs muted stock"><?= e(t('software.onrequest')) ?></span><?php endif; ?></div>
    <p class="desc"><?= e($p['description']) ?></p>
    <?php if ($p['sku']): ?><div class="mono xs muted mt-3 sku">SKU: <?= e($p['sku']) ?></div><?php endif; ?>
    <div class="product-foot">
      <div class="flex justify-between gap-2" style="align-items:flex-end"><div><span class="price"><?= e(format_price($price, $regionCode)) ?></span><span class="mono xs upper muted">/<?= $period === 'monthly' ? 'mo' : 'yr' ?></span></div>
        <button type="button" class="btn btn-mint btn-sm" data-order data-id="<?= (int)$p['id'] ?>" data-name="<?= e($p['name']) ?>" data-brand="<?= e($p['brand']) ?>" data-pm="<?= e($p['price_monthly']) ?>" data-py="<?= e($p['price_yearly']) ?>" aria-label="Inquire about <?= e($p['name']) ?>"><?= icon('ShoppingBag') ?> <span class="only-desktop"><?= e(t('software.inquire')) ?></span></button></div>
      <?php if ($p['license_tiers']): ?><div class="tiers"><?= icon('Check', 'ico mint') ?> <?= e($p['license_tiers']) ?></div><?php endif; ?>
    </div>
  </div>
</div>
