<h1>Set new password</h1>
<?php if ($m = flash('error')): ?><div class="alert alert-error mt-4"><?= e($m) ?></div><?php endif; ?>
<form method="post" action="<?= url('/admin/reset-password') ?>" class="mt-6" style="display:grid;gap:1rem"><?= csrf_field() ?>
  <input type="hidden" name="token" value="<?= e($token) ?>">
  <label class="field"><span class="label">New password (min 10 chars)</span><input class="input" type="password" name="password" minlength="10" required autofocus></label>
  <label class="field"><span class="label">Confirm</span><input class="input" type="password" name="password_confirmation" minlength="10" required></label>
  <button class="btn btn-primary">Save password</button>
</form>
