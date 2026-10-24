<?php $steps = [['new', 'Order Placed', 'Package'], ['confirmed', 'Confirmed', 'CheckCircle2'], ['processing', 'Processing', 'Clock'], ['dispatched', 'Dispatched', 'Truck'], ['delivered', 'Delivered', 'PackageCheck'], ['completed', 'Completed', 'CheckCircle2']]; ?>
<?= App\Core\View::render('partials.page_hero', ['eyebrow' => 'Track Order', 'heading' => 'Where is my order?', 'sub' => 'Enter the Order ID you received when booking (format IC-XXXXXX) to see its live status and any notes from our team.']) ?>
<section class="section"><div class="container" style="max-width:48rem">
  <form id="track-form" class="card reveal flex gap-2" style="padding:1.5rem" method="post" action="<?= url('/track-order') ?>"><input class="input" name="order_id" placeholder="IC-XXXXXX" required style="text-transform:uppercase;font-family:var(--font-mono)"><button class="btn btn-primary" type="submit"><?= icon('Search') ?> Track</button></form>
  <div id="track-error" class="alert alert-error mt-4" hidden></div>
  <div id="track-result" class="card mt-6" style="padding:1.5rem" hidden>
    <div class="flex wrap justify-between gap-2"><div><div class="label" style="margin:0">Order</div><div class="price t-id"></div></div><span class="t-status status"></span></div>
    <div class="steps"><?php foreach ($steps as [$k, $l, $ic]): ?><div class="step" data-step="<?= $k ?>"><?= icon($ic) ?><?= e($l) ?></div><?php endforeach; ?></div>
    <div class="kv"><div><div class="l">Product</div><div class="v t-product"></div></div><div><div class="l">Quantity</div><div class="v t-qty"></div></div><div><div class="l">Placed</div><div class="v t-date"></div></div></div>
    <div class="t-comment alert alert-success mt-6" hidden><b>Note from our team:</b> <span></span></div>
    <p class="xs muted mt-6">Questions? WhatsApp us at +<?= e(App\Models\Repo::settings()['whatsapp_number'] ?? config('site.whatsapp_number')) ?> with your Order ID.</p>
  </div>
</div></section>
