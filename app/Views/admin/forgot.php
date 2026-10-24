<h1>Reset password</h1>
<p class="muted small mt-2">We will email you a one-time reset link (valid 60 minutes).</p>
<?php if ($m = flash('error')): ?><div class="alert alert-error mt-4"><?= e($m) ?></div><?php endif; ?>
<?php if ($m = flash('success')): ?><div class="alert alert-success mt-4"><?= e($m) ?></div><?php endif; ?>
<form method="post" action="<?= url('/admin/forgot-password') ?>" class="mt-6" style="display:grid;gap:1rem"><?= csrf_field() ?>
  <label class="field"><span class="label">Email</span><input class="input" type="email" name="email" required autofocus></label>
  <button class="btn btn-primary">Send reset link</button>
  <a class="small muted center" href="<?= url('/admin/login') ?>">Back to sign in</a>
</form>
