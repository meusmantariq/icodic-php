<?php
declare(strict_types=1);
namespace App\Controllers\Admin;

use App\Core\{Request, Response, View, Csrf, Auth, Session, Upload, Database as DB};
use App\Models\Repo;

/** Generic schema-driven CRUD for all 13 entities (replaces adminCrud function + EntityCrud.jsx). */
final class EntityController
{
    private array $registry;
    public function __construct() { $this->registry = require APP_PATH . '/config/entities.php'; }

    private function def(string $key): array
    {
        if (!isset($this->registry[$key])) Response::notFound('Unknown admin section.');
        return $this->registry[$key] + ['key' => $key];
    }

    public function dashboard(Request $req): string
    {
        Auth::require($req);
        $counts = [];
        foreach ($this->registry as $k => $d) $counts[$k] = Repo::count($d['table']);
        $stats = [
            'orders_new' => Repo::count('orders', ['status' => 'new']),
            'messages_new' => Repo::count('contact_messages', ['status' => 'new']),
            'assess_new' => Repo::count('security_assessments', ['status' => 'new']),
            'revenue' => (float)DB::scalar("SELECT COALESCE(SUM(total_usd),0) FROM orders WHERE status IN ('confirmed','processing','dispatched','delivered','completed')"),
        ];
        return View::page('admin.dashboard', ['title' => 'Command Console', 'registry' => $this->registry, 'counts' => $counts, 'stats' => $stats,
            'recentOrders' => Repo::all('orders', 'created_at DESC', 8), 'recentMessages' => Repo::all('contact_messages', 'created_at DESC', 5),
            'log' => DB::fetchAll('SELECT l.*, u.name AS user_name FROM activity_log l LEFT JOIN admin_users u ON u.id = l.user_id ORDER BY l.id DESC LIMIT 10')], 'layouts.admin');
    }

    public function index(Request $req, string $key): string
    {
        Auth::require($req);
        $d = $this->def($key);
        if (!empty($d['singleton'])) {
            $row = Repo::all($d['table'], 'id ASC', 1)[0] ?? null;
            if (!$row) { $id = Repo::insert($d['table'], ['company_name' => 'iCodic']); $row = Repo::find($d['table'], $id); }
            Response::redirect("/admin/$key/{$row['id']}/edit");
        }
        $q = $req->str('q'); $status = $req->str('status');
        $where = []; $params = [];
        if ($q !== '' && !empty($d['search'])) {
            $like = []; foreach ($d['search'] as $c) { $like[] = "`$c` LIKE ?"; $params[] = "%$q%"; }
            $where[] = '(' . implode(' OR ', $like) . ')';
        }
        if ($status !== '' && $this->field($d, 'status')) { $where[] = '`status` = ?'; $params[] = $status; }
        $sql = "SELECT * FROM `{$d['table']}`" . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . " ORDER BY {$d['order']}";
        $page = max(1, $req->int('page', 1)); $per = 25;
        $total = (int)DB::scalar(preg_replace('/^SELECT \*/', 'SELECT COUNT(*)', preg_replace('/ ORDER BY .*$/', '', $sql)), $params);
        $rows = DB::fetchAll($sql . ' LIMIT ' . $per . ' OFFSET ' . (($page - 1) * $per), $params);
        return View::page('admin.index', ['title' => $d['title'], 'd' => $d, 'rows' => $rows, 'q' => $q, 'status' => $status, 'page' => $page, 'pages' => (int)ceil($total / $per), 'total' => $total, 'registry' => $this->registry], 'layouts.admin');
    }

    public function create(Request $req, string $key): string
    {
        Auth::require($req);
        $d = $this->def($key);
        $row = []; foreach ($d['fields'] as $f) $row[$f['name']] = $f['default'] ?? ($f['type'] === 'boolean' ? 0 : '');
        return View::page('admin.form', ['title' => 'New · ' . $d['title'], 'd' => $d, 'row' => $row, 'registry' => $this->registry], 'layouts.admin');
    }
    public function edit(Request $req, string $key, string $id): string
    {
        Auth::require($req);
        $d = $this->def($key);
        $row = Repo::find($d['table'], (int)$id) ?? Response::notFound('Record not found.');
        if (!empty($d['inbox']) && ($row['status'] ?? '') === 'new' && in_array($d['table'], ['contact_messages', 'security_assessments'], true)) { Repo::update($d['table'], (int)$id, ['status' => 'read']); $row['status'] = 'read'; }
        return View::page('admin.form', ['title' => 'Edit · ' . $d['title'], 'd' => $d, 'row' => $row, 'registry' => $this->registry], 'layouts.admin');
    }

    public function store(Request $req, string $key): never { $this->save($req, $key, null); }
    public function update(Request $req, string $key, string $id): never { $this->save($req, $key, (int)$id); }

    private function save(Request $req, string $key, ?int $id): never
    {
        Auth::require($req); Csrf::check($req);
        $d = $this->def($key);
        [$data, $errors] = $this->validate($d, $req);
        if ($errors) { Session::flash('errors', $errors); Session::set('_old', $data); Response::redirect($id ? "/admin/$key/$id/edit" : "/admin/$key/new"); }
        try {
            if ($id) { Repo::update($d['table'], $id, $data); }
            else { $id = Repo::insert($d['table'], $data); }
        } catch (\PDOException $e) {
            Session::flash('errors', [str_contains($e->getMessage(), 'Duplicate') ? 'A record with the same unique value already exists.' : 'Database error: ' . $e->getMessage()]);
            Session::set('_old', $data); Response::redirect($id ? "/admin/$key/$id/edit" : "/admin/$key/new");
        }
        Session::forget('_old');
        $this->log($req->isPost() && $req->str('_was_new') ? 'create' : 'update', $d, $id, $data);
        Session::flash('success', 'Saved.');
        if ($req->str('_action') === 'save_stay' || !empty($d['singleton'])) Response::redirect("/admin/$key/$id/edit");
        Response::redirect("/admin/$key");
    }
    public function destroy(Request $req, string $key, string $id): never
    {
        Auth::require($req); Csrf::check($req);
        $d = $this->def($key);
        if (!empty($d['singleton'])) Response::redirect("/admin/$key");
        $row = Repo::find($d['table'], (int)$id);
        if ($row) { Repo::delete($d['table'], (int)$id); $this->log('delete', $d, (int)$id, $row); }
        Session::flash('success', 'Deleted.');
        Response::redirect("/admin/$key");
    }
    /** Quick status change from list view. */
    public function status(Request $req, string $key, string $id): never
    {
        Auth::require($req); Csrf::check($req);
        $d = $this->def($key);
        $f = $this->field($d, 'status');
        $s = $req->str('status');
        if ($f && in_array($s, $f['options'], true)) { Repo::update($d['table'], (int)$id, ['status' => $s]); $this->log('status', $d, (int)$id, ['status' => $s]); }
        if ($req->wantsJson()) Response::json(['ok' => true]);
        Response::back("/admin/$key");
    }
    public function export(Request $req, string $key): never
    {
        Auth::require($req);
        $d = $this->def($key);
        $rows = Repo::all($d['table'], $d['order'], 10000);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $d['table'] . '_' . date('Ymd') . '.csv"');
        $out = fopen('php://output', 'w'); fwrite($out, "\xEF\xBB\xBF");
        if ($rows) { fputcsv($out, array_keys($rows[0])); foreach ($rows as $r) fputcsv($out, $r); }
        fclose($out); exit;
    }
    public function upload(Request $req): never
    {
        Auth::require($req); Csrf::check($req);
        try { Response::json(['ok' => true, 'url' => Upload::image($req->files['file'] ?? [])]); }
        catch (\Throwable $e) { Response::json(['ok' => false, 'error' => $e->getMessage()], 422); }
    }

    private function field(array $d, string $name): ?array { foreach ($d['fields'] as $f) if ($f['name'] === $name) return $f; return null; }

    private function validate(array $d, Request $req): array
    {
        $data = []; $errors = [];
        foreach ($d['fields'] as $f) {
            $n = $f['name']; $v = $req->input($n);
            switch ($f['type']) {
                case 'boolean': $data[$n] = $v ? 1 : 0; break;
                case 'number': $data[$n] = ($v === '' || $v === null) ? ($f['default'] ?? 0) : (int)$v; break;
                case 'decimal': $data[$n] = ($v === '' || $v === null) ? 0 : round((float)str_replace(',', '', (string)$v), 2); break;
                case 'list': $arr = is_array($v) ? $v : array_values(array_filter(array_map('trim', explode(',', (string)$v)), 'strlen')); $data[$n] = json_encode($arr, JSON_UNESCAPED_UNICODE); break;
                case 'multiselect': $arr = array_values(array_intersect(is_array($v) ? $v : [], $f['options'])); $data[$n] = json_encode($arr); break;
                case 'select': $v = (string)$v; if ($v === '' && !empty($f['default'])) $v = $f['default']; if ($v !== '' && !in_array($v, $f['options'], true)) $errors[] = "Invalid value for {$f['label']}."; $data[$n] = $v === '' ? ($f['options'][0] ?? null) : $v; break;
                default: $v = is_scalar($v) ? trim((string)$v) : ''; $data[$n] = $v === '' ? null : $v;
            }
            if (!empty($f['required']) && ($data[$n] === null || $data[$n] === '')) $errors[] = "{$f['label']} is required.";
            if (in_array($n, ['email'], true) && $data[$n] && !filter_var($data[$n], FILTER_VALIDATE_EMAIL)) $errors[] = 'Email is not valid.';
            if ($f['type'] === 'image' && $data[$n] && !preg_match('~^(https?://|/uploads/)~', $data[$n])) $errors[] = "{$f['label']} must be an uploaded image or an https URL.";
        }
        if ($d['table'] === 'offices' && isset($data['code'])) $data['code'] = strtoupper($data['code']);
        return [$data, $errors];
    }
    private function log(string $action, array $d, ?int $id, array $data): void
    {
        $summary = $data['name'] ?? $data['title'] ?? $data['order_id'] ?? $data['item'] ?? ($data['page'] ?? '') . ' ' . ($data['section'] ?? '');
        try { DB::query('INSERT INTO activity_log (user_id, action, entity, record_id, summary) VALUES (?, ?, ?, ?, ?)', [Auth::user()['id'] ?? null, $action, $d['table'], $id, mb_substr(trim((string)$summary), 0, 490)]); } catch (\Throwable) {}
    }
}
