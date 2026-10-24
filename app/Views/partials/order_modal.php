<?php use App\Core\Region; $cur = Region::CURRENCIES[$regionCode] ?? ['symbol' => '$', 'rate' => 1]; $period = Region::period(); $wa = $site['whatsapp_number'] ?? config('site.whatsapp_number'); ?>
<div class="modal" id="order-modal" data-rate="<?= e($cur['rate']) ?>" data-symbol="<?= e($cur['symbol']) ?>" data-period="<?= e($period) ?>" data-wa="<?= e($wa) ?>" role="dialog" aria-modal="true">
  <div class="modal-box">
    <div class="modal-head">
      <div class="flex items-center gap-2"><span class="icon-circle c-violet" style="width:2.25rem;height:2.25rem;font-size:1rem"><?= icon('ShoppingBag') ?></span><div><h3><?= e(t('order.title')) ?></h3><p class="mono xs muted m-product"></p></div></div>
      <button type="button" class="sq" style="border:0" data-close aria-label="Close"><?= icon('X') ?></button>
    </div>
    <form class="mt-4" method="post" action="<?= url('/order') ?>" novalidate>
      <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="product_id"><input type="hidden" name="billing_period"><input class="hp" type="text" name="website" tabindex="-1" autocomplete="off">
      <div class="grid sm-2" style="gap:1rem">
        <label class="field"><span class="label">Full Name *</span><input class="input" name="customer_name" required></label>
        <label class="field"><span class="label">Email *</span><input class="input" type="email" name="email" required></label>
        <label class="field"><span class="label">Phone</span><input class="input" name="phone"></label>
        <label class="field"><span class="label">Company</span><input class="input" name="company"></label>
      </div>
      <label class="field mt-4"><span class="label">Quantity</span><input class="input" type="number" min="1" name="quantity" value="1"></label>
      <label class="field mt-4"><span class="label">Notes</span><textarea class="textarea" name="notes" rows="3" style="min-height:4.5rem" placeholder="Any specific requirements, license tiers, etc."></textarea></label>
      <div class="total mt-4"><div><span class="label" style="margin:0"><?= e($period) ?> billing</span><div class="price" style="font-size:1.15rem"><span class="m-total"></span></div></div><span class="mono xs mint m-seats"></span></div>
      <div class="alert alert-error order-err mt-4" hidden></div>
      <div class="grid sm-2 mt-4" style="gap:.75rem">
        <a class="btn btn-mint wa-link" href="#" target="_blank" rel="noopener noreferrer"><?= icon('MessageCircle') ?> <?= e(t('order.whatsapp')) ?></a>
        <button class="btn btn-primary" type="submit"><?= icon('ShoppingBag') ?> <?= e(t('order.book')) ?></button>
      </div>
      <p class="center xs muted mt-3"><b class="mint"><?= e(t('order.book')) ?></b> creates your order with a unique Order ID. <b class="mint"><?= e(t('order.whatsapp')) ?></b> chats with our team instantly.</p>
    </form>
    <div class="order-done center" style="padding:2rem 0" hidden>
      <div class="success-ring"><?= icon('Check') ?></div>
      <h4 class="mt-4" style="font-size:1.25rem;font-weight:700">Order booked!</h4>
      <p class="mt-2 small muted">Your order has been submitted to our team.</p>
      <div class="order-id"><span class="label" style="margin:0">Your Order ID</span><b class="done-id"></b></div>
      <p class="mt-4 xs muted">Save this ID to track your order. We'll also email you at <b class="done-email" style="color:var(--fg)"></b>.</p>
      <div class="flex wrap gap-2 mt-6" style="justify-content:center"><a class="btn btn-outline" href="<?= url('/track-order') ?>"><?= icon('Search') ?> Track Order</a><a class="btn btn-mint-solid wa-link" href="#" target="_blank" rel="noopener noreferrer"><?= icon('MessageCircle') ?> WhatsApp Us</a></div>
    </div>
  </div>
</div>
