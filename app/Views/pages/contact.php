<?php use App\Models\PageBlock; use App\Core\Region;
$hero = PageBlock::get('contact', 'hero', ['title' => "Let's build your infrastructure", 'subtitle' => "Reach the regional office nearest you, or send a message and we'll route it to the right specialist within one business day."]);
$r = Region::current(); $code = Region::code(); $sent = isset($_GET['sent']); $errors = flash('errors', []);
$extra = '<a class="btn btn-mint mt-8" href="' . e(config('site.review_url')) . '" target="_blank" rel="noopener noreferrer">' . icon('Star') . ' Leave a Google Review</a>';
?>
<?= App\Core\View::render('partials.page_hero', ['eyebrow' => 'Contact iCodic', 'heading' => $hero['title'], 'sub' => $hero['subtitle'], 'extra' => $extra]) ?>
<section class="section" id="form"><div class="container grid lg-2" style="gap:3rem;align-items:start">
  <div class="card reveal" style="padding:2rem">
    <h2 class="h2" style="font-size:1.6rem;margin:0">Send a message</h2><p class="small muted mt-2">Routing to <span class="mint"><?= e($r['city']) ?> (<?= e($code) ?>)</span> office.</p>
    <?php if ($sent): ?><div class="alert alert-success mt-6 flex items-center gap-3"><?= icon('Check') ?><div><b>Message received.</b><div class="small">We'll respond within one business day.</div></div></div>
    <?php else: ?>
    <form method="post" action="<?= url('/contact') ?>" class="mt-6" style="display:grid;gap:1.25rem"><?= csrf_field() ?><input class="hp" type="text" name="website" tabindex="-1" autocomplete="off">
      <?php if ($errors): ?><div class="alert alert-error"><?php foreach ($errors as $er) echo e($er) . '<br>'; ?></div><?php endif; ?>
      <div class="grid sm-2" style="gap:1rem"><label class="field"><span class="label">Full Name</span><input class="input" name="name" required value="<?= old('name') ?>"></label><label class="field"><span class="label">Email</span><input class="input" type="email" name="email" required value="<?= old('email') ?>"></label></div>
      <label class="field"><span class="label">Company</span><input class="input" name="company" value="<?= old('company') ?>"></label>
      <label class="field"><span class="label">Message</span><textarea class="textarea" name="message" rows="5" required><?= old('message') ?></textarea></label>
      <div><div class="label">Route to office</div><div class="flex wrap gap-2"><?php foreach (Region::all() as $c => $o): ?><a class="pill<?= $c === $code ? ' active' : '' ?>" style="padding:0 .8rem" href="<?= url("/prefs?region=$c&back=" . urlencode('/contact#form')) ?>"><?= e($c) ?> · <?= e($o['city']) ?></a><?php endforeach; ?></div></div>
      <button class="btn btn-primary" type="submit">Send Message <?= icon('Send') ?></button>
    </form><?php endif; ?>
  </div>
  <div>
    <?= App\Core\View::render('sections.office_card', ['r' => $r]) ?>
    <div class="mt-6 reveal"><div class="label">All offices</div><div class="region-tabs"><?php foreach (Region::all() as $c => $o): ?><a class="region-tab<?= $c === $code ? ' active' : '' ?>" href="<?= url("/prefs?region=$c&back=" . urlencode('/contact#form')) ?>"><b><?= e($c) ?></b><span><?= e($o['city']) ?></span><small data-tz="<?= e($o['tz']) ?>"><?= e(local_time($o['tz'])) ?></small></a><?php endforeach; ?></div></div>
  </div>
</div></section>
