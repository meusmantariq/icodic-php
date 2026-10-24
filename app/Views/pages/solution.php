<?= App\Core\View::render('partials.page_hero', ['eyebrow' => $c['type'], 'heading' => $c['name'], 'sub' => $c['tagline'], 'extra' => '<div class="flex wrap gap-3 mt-8"><a class="btn btn-primary" href="' . url('/contact') . '">Book Consultation ' . icon('ArrowUpRight') . '</a><a class="btn btn-ghost" href="' . url('/#erp') . '">All Solutions</a></div>']) ?>
<section class="section"><div class="container grid lg-2" style="gap:3rem;align-items:start">
  <div class="reveal"><div class="eyebrow">// Overview</div><h2 class="h2">What it is</h2><p class="lead"><?= e($c['overview']) ?></p><?php if ($db && $db['description'] && $db['description'] !== $c['overview']): ?><p class="mt-4 muted"><?= e($db['description']) ?></p><?php endif; ?></div>
  <div class="reveal card" style="padding:1.5rem"><div class="eyebrow">// Tech Stack</div><div class="flex wrap gap-2 mt-4"><?php foreach ($c['stack'] as $s): ?><span class="tag tag-primary"><?= e($s) ?></span><?php endforeach; ?></div>
    <?php if ($db && $db['image_url']): ?><img class="mt-6" src="<?= e(img($db['image_url'])) ?>" alt="<?= e($c['name']) ?>" style="border-radius:.5rem;aspect-ratio:16/9;object-fit:cover;width:100%"><?php endif; ?></div>
</div></section>
<section class="section section-sec"><div class="container">
  <div class="reveal sec-head"><div class="eyebrow">// Capabilities</div><h2 class="h2">Key features</h2></div>
  <div class="grid sm-2 lg-4 mt-8"><?php foreach ($c['features'] as $f): ?><div class="card reveal flex gap-3" style="padding:1.25rem;align-items:flex-start"><?= icon('Check', 'ico mint') ?><span class="small"><?= e($f) ?></span></div><?php endforeach; ?></div>
</div></section>
<section class="section"><div class="container center reveal"><h2 class="h2">Ready to implement <?= e($c['name']) ?>?</h2><p class="lead" style="margin:1rem auto 0">Every engagement begins with a consultation. Talk to a regional specialist about your requirements.</p><a class="btn btn-primary mt-8" href="<?= url('/contact') ?>">Start a conversation <?= icon('ArrowRight') ?></a></div></section>
