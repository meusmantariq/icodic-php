<?php $chips = ['c-red', 'c-blue', 'c-emerald', 'c-amber', 'c-violet', 'c-cyan']; $sent = isset($_GET['sent']); $errors = flash('errors', []);
$opts = ['Endpoint Protection', 'Network Security', 'SOC & SIEM', 'Penetration Testing', 'Cloud Security', 'Compliance & GRC', 'General Assessment']; ?>
<?= App\Core\View::render('partials.page_hero', ['eyebrow' => 'Cyber Security', 'heading' => 'Defend the full stack — endpoint, network, cloud and human layer', 'sub' => 'A managed security practice built for regulated enterprises — across the UK, the UAE, India and Pakistan.']) ?>
<section class="section"><div class="container">
  <div class="reveal sec-head"><div class="eyebrow">// Our Practice</div><h2 class="h2">Cyber Security Services</h2></div>
  <?php if (!$services): ?><div class="empty mt-8">No cyber security services yet. Add them from the admin panel.</div><?php else: ?>
  <div class="grid sm-2 lg-3 mt-8"><?php foreach ($services as $i => $s): ?><div class="svc reveal card-hover"><?php if ($s['image_url']): ?><div class="svc-img"><img src="<?= e(img($s['image_url'])) ?>" alt="<?= e($s['title']) ?>" loading="lazy"></div><?php endif; ?><div class="icon-circle <?= $chips[$i % 6] ?>"><?= icon($s['icon']) ?></div><h3><?= e($s['title']) ?></h3><p><?= e($s['description']) ?></p></div><?php endforeach; ?></div><?php endif; ?>
</div></section>
<section class="section section-sec" id="assessment"><div class="container" style="max-width:56rem">
  <div class="card reveal" style="padding:2rem">
    <div class="flex items-center gap-3"><span class="icon-circle c-red"><?= icon('Shield') ?></span><div><h2 class="h2" style="font-size:1.6rem;margin:0">Need a security assessment?</h2><p class="small muted">Tell us your needs and our cyber team will get back to you.</p></div></div>
    <?php if ($sent): ?><div class="center mt-8" style="padding:2rem 0"><div class="success-ring"><?= icon('CheckCircle2') ?></div><h3 class="mt-4" style="font-size:1.25rem;font-weight:700">Request received</h3><p class="small muted mt-2">Our cyber security team will review your needs and contact you shortly.</p><a class="btn btn-outline mt-6" href="<?= url('/cyber-security#assessment') ?>">Submit another</a></div>
    <?php else: ?>
    <form method="post" action="<?= url('/cyber-security/assessment') ?>" class="form-grid mt-6"><?= csrf_field() ?><input class="hp" type="text" name="website" tabindex="-1" autocomplete="off">
      <?php if ($errors): ?><div class="alert alert-error span2"><?php foreach ($errors as $er) echo e($er) . '<br>'; ?></div><?php endif; ?>
      <label class="field"><span class="label">Full Name *</span><input class="input" name="name" required value="<?= old('name') ?>"></label>
      <label class="field"><span class="label">Work Email *</span><input class="input" type="email" name="email" required value="<?= old('email') ?>"></label>
      <label class="field"><span class="label">Company</span><input class="input" name="company" value="<?= old('company') ?>"></label>
      <label class="field"><span class="label">Region</span><select class="select" name="region"><?php foreach (['Canada', 'UAE', 'India', 'Pakistan', 'Global'] as $o): ?><option <?= old('region', 'UAE') === $o ? 'selected' : '' ?>><?= e($o) ?></option><?php endforeach; ?></select></label>
      <label class="field"><span class="label">Service Needed *</span><select class="select" name="service_needed" required><?php foreach ($opts as $o): ?><option <?= old('service_needed', 'General Assessment') === $o ? 'selected' : '' ?>><?= e($o) ?></option><?php endforeach; ?></select></label>
      <label class="field"><span class="label">Urgency</span><select class="select" name="urgency"><?php foreach (['Low', 'Medium', 'High', 'Critical'] as $o): ?><option <?= old('urgency', 'Medium') === $o ? 'selected' : '' ?>><?= e($o) ?></option><?php endforeach; ?></select></label>
      <label class="field span2"><span class="label">Describe your needs *</span><textarea class="textarea" name="message" rows="4" required><?= old('message') ?></textarea></label>
      <div class="span2"><button class="btn btn-primary" type="submit">Submit Request <?= icon('Send') ?></button></div>
    </form><?php endif; ?>
  </div>
</div></section>
