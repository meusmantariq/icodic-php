<div class="topbar"><h1>My account</h1></div>
<div class="card" style="max-width:36rem;padding:1.5rem">
<form method="post" action="<?= url('/admin/account') ?>" style="display:grid;gap:1rem"><?= csrf_field() ?>
  <label class="field"><span class="label">Name</span><input class="input" name="name" value="<?= e($user['name']) ?>" required></label>
  <label class="field"><span class="label">Email</span><input class="input" type="email" name="email" value="<?= e($user['email']) ?>" required></label>
  <hr style="border:0;border-top:1px solid var(--border)">
  <label class="field"><span class="label">Current password (required to change password)</span><input class="input" type="password" name="current_password"></label>
  <label class="field"><span class="label">New password (min 10 chars, leave blank to keep)</span><input class="input" type="password" name="password" minlength="10"></label>
  <label class="field"><span class="label">Confirm new password</span><input class="input" type="password" name="password_confirmation"></label>
  <div><button class="btn btn-primary">Save changes</button></div>
</form>
</div>
