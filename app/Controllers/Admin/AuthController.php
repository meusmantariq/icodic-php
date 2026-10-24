<?php
declare(strict_types=1);
namespace App\Controllers\Admin;

use App\Core\{Request, Response, View, Csrf, Auth, Session, Mailer, Database as DB};

final class AuthController
{
    public function loginForm(Request $req): string
    {
        if (Auth::check()) Response::redirect('/admin');
        return View::page('admin.login', ['title' => 'Admin Console'], 'layouts.auth');
    }
    public function login(Request $req): never
    {
        Csrf::check($req);
        usleep(200000);
        if (Auth::throttled($req->ip())) { Session::flash('error', 'Too many attempts. Try again in 15 minutes.'); Response::redirect('/admin/login'); }
        if (Auth::attempt($req->str('email'), $req->str('password'), $req->ip())) {
            $to = Session::get('_intended', '/admin'); Session::forget('_intended');
            Response::redirect(str_starts_with((string)$to, '/admin') ? $to : '/admin');
        }
        Session::flash('error', 'Incorrect email or password.');
        Session::set('_old', ['email' => $req->str('email')]);
        Response::redirect('/admin/login');
    }
    public function logout(Request $req): never { Csrf::check($req); Auth::logout(); Response::redirect('/admin/login'); }

    public function forgotForm(Request $req): string { return View::page('admin.forgot', ['title' => 'Reset password'], 'layouts.auth'); }
    public function forgot(Request $req): never
    {
        Csrf::check($req);
        $email = strtolower($req->str('email'));
        $u = DB::fetch('SELECT id, name FROM admin_users WHERE email = ? AND is_active = 1', [$email]);
        if ($u) {
            $token = bin2hex(random_bytes(32));
            DB::query('INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?, ?, (NOW() + INTERVAL 1 HOUR))', [$u['id'], hash('sha256', $token)]);
            $link = rtrim(config('app.url', ''), '/') . url('/admin/reset-password?token=' . $token);
            Mailer::send($email, 'Reset your iCodic admin password', "Hi {$u['name']},\n\nUse this link within 1 hour to set a new password:\n$link\n\nIf you did not request this, ignore this email.");
        }
        Session::flash('success', 'If that email belongs to an admin account, a reset link has been sent.');
        Response::redirect('/admin/forgot-password');
    }
    public function resetForm(Request $req): string
    {
        $row = $this->validToken($req->str('token'));
        if (!$row) { Session::flash('error', 'This reset link is invalid or has expired.'); Response::redirect('/admin/forgot-password'); }
        return View::page('admin.reset', ['title' => 'Set new password', 'token' => $req->str('token')], 'layouts.auth');
    }
    public function reset(Request $req): never
    {
        Csrf::check($req);
        $row = $this->validToken($req->str('token'));
        if (!$row) { Session::flash('error', 'This reset link is invalid or has expired.'); Response::redirect('/admin/forgot-password'); }
        $p = $req->str('password');
        if (strlen($p) < 10 || $p !== $req->str('password_confirmation')) { Session::flash('error', 'Passwords must match and be at least 10 characters.'); Response::redirect('/admin/reset-password?token=' . $req->str('token')); }
        DB::query('UPDATE admin_users SET password_hash = ? WHERE id = ?', [password_hash($p, PASSWORD_DEFAULT), $row['user_id']]);
        DB::query('UPDATE password_resets SET used_at = NOW() WHERE id = ?', [$row['id']]);
        Session::flash('success', 'Password updated. You can sign in now.');
        Response::redirect('/admin/login');
    }
    private function validToken(string $token): ?array
    {
        if (strlen($token) !== 64) return null;
        return DB::fetch('SELECT id, user_id FROM password_resets WHERE token_hash = ? AND used_at IS NULL AND expires_at > NOW()', [hash('sha256', $token)]);
    }

    public function account(Request $req): string { Auth::require($req); return View::page('admin.account', ['title' => 'My account', 'user' => Auth::user()], 'layouts.admin'); }
    public function updateAccount(Request $req): never
    {
        Auth::require($req); Csrf::check($req);
        $u = DB::fetch('SELECT * FROM admin_users WHERE id = ?', [Auth::user()['id']]);
        if (!password_verify($req->str('current_password'), $u['password_hash'])) { Session::flash('error', 'Current password is incorrect.'); Response::redirect('/admin/account'); }
        $data = ['name' => $req->str('name') ?: $u['name']];
        $email = strtolower($req->str('email'));
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) $data['email'] = $email;
        $p = $req->str('password');
        if ($p !== '') {
            if (strlen($p) < 10 || $p !== $req->str('password_confirmation')) { Session::flash('error', 'New passwords must match and be at least 10 characters.'); Response::redirect('/admin/account'); }
            $data['password_hash'] = password_hash($p, PASSWORD_DEFAULT);
        }
        try { \App\Models\Repo::update('admin_users', (int)$u['id'], $data); } catch (\PDOException) { Session::flash('error', 'That email is already in use.'); Response::redirect('/admin/account'); }
        Session::flash('success', 'Account updated.');
        Response::redirect('/admin/account');
    }
}
