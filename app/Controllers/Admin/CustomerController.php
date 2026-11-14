<?php
declare(strict_types=1);
namespace App\Controllers\Admin;

use App\Core\{Auth, Csrf, Database as DB, Money, Request, Response, Session, View};
use App\Models\Customer;

/** Customer master data CRUD (the source of truth for every sales document). */
final class CustomerController
{
    private function registry(): array { return require APP_PATH . '/config/entities.php'; }

    private function guard(Request $req): void
    {
        Auth::require($req);
    }

    public function index(Request $req): string
    {
        $this->guard($req);
        $q = $req->str('q');
        $status = $req->str('status');
        $page = max(1, $req->int('page', 1));
        [$rows, $total] = Customer::paginate($q, $status, $page);
        return View::page('admin.customers.index', [
            'title' => 'Customers',
            'registry' => $this->registry(),
            'rows' => $rows, 'total' => $total, 'q' => $q, 'status' => $status,
            'page' => $page, 'pages' => (int)ceil($total / 25),
        ], 'layouts.admin');
    }

    public function create(Request $req): string
    {
        $this->guard($req);
        $row = ['code' => Customer::suggestCode(), 'default_currency' => Money::DEFAULT, 'status' => 'active'];
        return View::page('admin.customers.form', [
            'title' => 'New customer', 'registry' => $this->registry(),
            'row' => $row, 'isNew' => true,
        ], 'layouts.admin');
    }

    public function edit(Request $req, string $id): string
    {
        $this->guard($req);
        $row = Customer::find((int)$id);
        if (!$row) Response::notFound('Customer not found.');
        return View::page('admin.customers.form', [
            'title' => 'Edit ' . $row['company_name'], 'registry' => $this->registry(),
            'row' => $row, 'isNew' => false,
            'documents' => DB::fetchAll('SELECT id, doc_type, doc_no, status, issue_date, total_minor, currency FROM sales_documents WHERE customer_id = ? ORDER BY id DESC LIMIT 25', [(int)$id]),
        ], 'layouts.admin');
    }

    public function store(Request $req): never
    {
        $this->guard($req); Csrf::check($req);
        [$data, $errors] = Customer::validate($req->all());
        if ($errors) {
            Session::flash('errors', $errors);
            Session::set('_customer_old', $data);
            Response::redirect('/admin/customers/new');
        }
        try {
            $id = Customer::insert($data);
        } catch (\PDOException $e) {
            Session::flash('errors', [str_contains($e->getMessage(), 'Duplicate') ? 'That customer code is already in use.' : 'Database error while saving the customer.']);
            Session::set('_customer_old', $data);
            Response::redirect('/admin/customers/new');
        }
        Session::forget('_customer_old');
        $this->log($req, 'create', $id, $data['code'] . ' ' . $data['company_name']);
        Session::flash('success', 'Customer ' . $data['code'] . ' created.');
        Response::redirect('/admin/customers/' . $id . '/edit');
    }

    public function update(Request $req, string $id): never
    {
        $this->guard($req); Csrf::check($req);
        $customerId = (int)$id;
        $existing = Customer::find($customerId);
        if (!$existing) Response::notFound('Customer not found.');
        [$data, $errors] = Customer::validate($req->all(), $customerId);
        if ($errors) {
            Session::flash('errors', $errors);
            Session::set('_customer_old', $data);
            Response::redirect('/admin/customers/' . $customerId . '/edit');
        }
        try {
            Customer::update($customerId, $data);
        } catch (\PDOException $e) {
            Session::flash('errors', [str_contains($e->getMessage(), 'Duplicate') ? 'That customer code is already in use.' : 'Database error while saving the customer.']);
            Session::set('_customer_old', $data);
            Response::redirect('/admin/customers/' . $customerId . '/edit');
        }
        Session::forget('_customer_old');
        $this->log($req, 'update', $customerId, $data['code'] . ' ' . $data['company_name']);
        Session::flash('success', 'Customer updated.');
        Response::redirect('/admin/customers/' . $customerId . '/edit');
    }

    public function destroy(Request $req, string $id): never
    {
        $this->guard($req); Csrf::check($req);
        $customerId = (int)$id;
        $row = Customer::find($customerId);
        if (!$row) Response::notFound('Customer not found.');
        try {
            Customer::delete($customerId);
            $this->log($req, 'delete', $customerId, $row['code'] . ' ' . $row['company_name']);
            Session::flash('success', 'Customer deleted.');
        } catch (\RuntimeException $e) {
            Session::flash('error', $e->getMessage());
        }
        Response::redirect('/admin/customers');
    }

    private function log(Request $req, string $action, int $id, string $summary): void
    {
        try {
            DB::query('INSERT INTO activity_log (user_id, action, entity, record_id, summary) VALUES (?, ?, ?, ?, ?)',
                [Auth::user()['id'] ?? null, $action, 'customers', $id, mb_substr($summary, 0, 490)]);
        } catch (\Throwable) {}
    }
}
