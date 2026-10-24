<h1>Sign in</h1>
<p class="muted small mt-2">Enter your administrator credentials.</p>
<?php if ($m = flash('error')): ?><div class="alert alert-error mt-4"><?= e($m) ?></div><?php endif; ?>
<?php if ($m = flash('success')): ?><div class="alert alert-success mt-4"><?= e($m) ?></div><?php endif; ?>
<form method="post" action="<?= url('/admin/login') ?>" class="mt-6" style="display:grid;gap:1rem"><?= csrf_field() ?>
  <label class="field"><span class="label">Email</span><input class="input" type="email" name="email" value="<?= old('email') ?>" required autofocus></label>
  <label class="field"><span class="label">Password</span><input class="input" type="password" name="password" required></label>
  <input type="hidden" name="return_to" value="<?= e($_GET['return_to'] ?? '') ?>">
  <button class="btn btn-primary">Unlock console</button>
  <a class="small muted center" href="<?= url('/admin/forgot-password') ?>">Forgot password?</a>
</form>
