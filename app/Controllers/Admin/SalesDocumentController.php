<?php
declare(strict_types=1);
namespace App\Controllers\Admin;

use App\Core\{Auth, Csrf, Database as DB, Money, Request, Response, Session, View};
use App\Models\{Customer, Payment, SalesDocument};

/**
 * One controller for all three sales document types; the type is a raw route argument that is
 * validated against a whitelist before use, so `/admin/{type}` can never reach an arbitrary table.
 */
final class SalesDocumentController
{
    /** route slug => doc_type */
    private const SLUGS = [
        'quotations' => 'quotation',
        'sales-orders' => 'sales_order',
        'invoices' => 'invoice',
    ];

    private array $registry = [];

    public function __construct() { $this->registry = require APP_PATH . '/config/entities.php'; }

    private function type(string $slug): string
    {
        if (!isset(self::SLUGS[$slug])) Response::notFound('Unknown sales section.');
        return self::SLUGS[$slug];
    }

    private function viewData(array $extra = []): array
    {
        return $extra + ['registry' => $this->registry];
    }

    /** Type-specific view folder: quotation|sales_order|invoice → quotation|sales_order|invoice */
    private function folder(string $type): string { return $type; }

    // ------------------------------------------------------------------ list

    public function index(Request $req, string $slug): string
    {
        Auth::require($req);
        $type = $this->type($slug);
        $q = $req->str('q');
        $status = $req->str('status');
        $currencyRaw = $req->str('currency');
        $currency = $currencyRaw === '' ? null : Money::currency($currencyRaw);
        $page = max(1, $req->int('page', 1));
        [$rows, $total] = SalesDocument::paginate($type, $q, $status, $currency, $page);
        return View::page('admin.sales.index', $this->viewData([
            'title' => SalesDocument::TYPE_PLURAL[$type],
            'type' => $type, 'slug' => $slug, 'rows' => $rows, 'total' => $total,
            'q' => $q, 'status' => $status, 'currency' => $currencyRaw,
            'page' => $page, 'pages' => (int)ceil($total / 25),
            'statuses' => SalesDocument::STATUSES[$type],
            'summary' => SalesDocument::statusSummary($type),
            'currencies' => Money::CURRENCIES,
        ]), 'layouts.admin');
    }

    // ------------------------------------------------------------------ create / edit

    public function create(Request $req, string $slug): string
    {
        Auth::require($req);
        $type = $this->type($slug);
        $customers = Customer::options(true);
        if (!$customers) { Session::flash('error', 'Create a customer first — documents need a customer.'); Response::redirect('/admin/customers/new'); }

        $old = Session::get('_doc_old', []);
        $oldLines = Session::get('_doc_old_lines', []);
        Session::forget('_doc_old'); Session::forget('_doc_old_lines');

        $prefCustomer = $req->int('customer_id', 0);
        $defaultCurrency = Money::DEFAULT;
        foreach ($customers as $c) if ((int)$c['id'] === $prefCustomer) $defaultCurrency = $c['default_currency'];

        $doc = $old ?: [
            'customer_id' => $prefCustomer, 'currency' => $defaultCurrency,
            'issue_date' => date('Y-m-d'),
            'valid_until' => $type === 'quotation' ? date('Y-m-d', strtotime('+30 days')) : '',
            'due_date' => $type === 'invoice' ? date('Y-m-d', strtotime('+30 days')) : '',
            'customer_reference' => '', 'notes' => '', 'discount' => '0.00', 'tax_rate' => '0.00',
        ];
        $lines = $oldLines ?: [['sku' => '', 'description' => '', 'qty' => '1', 'unit_price' => '0.00']];
        return View::page('admin.sales.form', $this->viewData([
            'title' => 'New ' . SalesDocument::TYPE_LABEL[$type],
            'type' => $type, 'slug' => $slug, 'doc' => $doc, 'lines' => $lines,
            'customers' => $customers, 'isNew' => true,
            'errors' => flash('errors', []),
        ]), 'layouts.admin');
    }

    public function edit(Request $req, string $slug, string $id): string
    {
        Auth::require($req);
        $type = $this->type($slug);
        $doc = SalesDocument::load((int)$id);
        if (!$doc || $doc['doc_type'] !== $type) Response::notFound('Document not found.');
        try { SalesDocument::assertEditable($doc); }
        catch (\RuntimeException $e) { Session::flash('error', $e->getMessage()); Response::redirect(SalesDocument::path($type, (int)$id)); }

        $old = Session::get('_doc_old', []); $oldLines = Session::get('_doc_old_lines', []);
        Session::forget('_doc_old'); Session::forget('_doc_old_lines');
        $form = $old ?: [
            'customer_id' => (int)$doc['customer_id'], 'currency' => $doc['currency'], 'issue_date' => $doc['issue_date'],
            'valid_until' => (string)($doc['valid_until'] ?? ''), 'due_date' => (string)($doc['due_date'] ?? ''),
            'customer_reference' => (string)($doc['customer_reference'] ?? ''), 'notes' => (string)($doc['notes'] ?? ''),
            'discount' => Money::decimal((int)$doc['discount_minor'], $doc['currency']),
            'tax_rate' => rtrim(rtrim(number_format((int)$doc['tax_rate_bp'] / 100, 2, '.', ''), '0'), '.') ?: '0',
        ];
        $lines = $oldLines ?: array_map(fn($i) => [
            'sku' => (string)($i['sku'] ?? ''), 'description' => $i['description'],
            'qty' => Money::qtyString((int)$i['qty_thousandths']),
            'unit_price' => Money::decimal((int)$i['unit_price_minor'], $doc['currency']),
        ], $doc['items']);
        return View::page('admin.sales.form', $this->viewData([
            'title' => 'Edit ' . $doc['doc_no'], 'type' => $type, 'slug' => $slug,
            'doc' => $form + ['id' => (int)$doc['id'], 'doc_no' => $doc['doc_no'], 'status' => $doc['status']],
            'lines' => $lines, 'customers' => Customer::options(true), 'isNew' => false,
            'errors' => flash('errors', []),
        ]), 'layouts.admin');
    }

    // ------------------------------------------------------------------ save

    public function store(Request $req, string $slug): never
    {
        Auth::require($req); Csrf::check($req);
        $type = $this->type($slug);
        [$header, $errors, $lines] = SalesDocument::validate($req->all(), $type);
        if ($errors) {
            Session::flash('errors', $errors);
            Session::set('_doc_old', $this->rawHeader($req));
            Session::set('_doc_old_lines', $this->rawLines($req));
            Response::redirect(SalesDocument::BASE_PATH[$type] . '/new');
        }
        try {
            $id = SalesDocument::create($type, $header, $lines, Auth::user()['id'] ?? null);
        } catch (\Throwable $e) {
            error_log('sales doc create failed: ' . $e->getMessage());
            Session::flash('errors', ['Could not save the document: ' . $e->getMessage()]);
            Session::set('_doc_old', $this->rawHeader($req));
            Session::set('_doc_old_lines', $this->rawLines($req));
            Response::redirect(SalesDocument::BASE_PATH[$type] . '/new');
        }
        Session::forget('_doc_old'); Session::forget('_doc_old_lines');
        $this->log($type, 'create', $id, (string)$header['issue_date']);
        Session::flash('success', SalesDocument::TYPE_LABEL[$type] . ' created.');
        Response::redirect(SalesDocument::path($type, $id));
    }

    public function update(Request $req, string $slug, string $id): never
    {
        Auth::require($req); Csrf::check($req);
        $type = $this->type($slug);
        $docId = (int)$id;
        $doc = SalesDocument::findOfType($docId, $type);
        if (!$doc) Response::notFound('Document not found.');
        [$header, $errors, $lines] = SalesDocument::validate($req->all(), $type, $docId);
        if ($errors) {
            Session::flash('errors', $errors);
            Session::set('_doc_old', $this->rawHeader($req));
            Session::set('_doc_old_lines', $this->rawLines($req));
            Response::redirect(SalesDocument::path($type, $docId, '/edit'));
        }
        try {
            SalesDocument::update($docId, $header, $lines, Auth::user()['id'] ?? null);
        } catch (\Throwable $e) {
            Session::flash('errors', [$e->getMessage()]);
            Session::set('_doc_old', $this->rawHeader($req));
            Session::set('_doc_old_lines', $this->rawLines($req));
            Response::redirect(SalesDocument::path($type, $docId, '/edit'));
        }
        Session::forget('_doc_old'); Session::forget('_doc_old_lines');
        $this->log($type, 'update', $docId, $doc['doc_no']);
        Session::flash('success', 'Document updated.');
        Response::redirect(SalesDocument::path($type, $docId));
    }

    public function destroy(Request $req, string $slug, string $id): never
    {
        Auth::require($req); Csrf::check($req);
        $type = $this->type($slug);
        $docId = (int)$id;
        $doc = SalesDocument::findOfType($docId, $type);
        if (!$doc) Response::notFound('Document not found.');
        try {
            SalesDocument::delete($docId);
            $this->log($type, 'delete', $docId, $doc['doc_no']);
            Session::flash('success', 'Document deleted.');
        } catch (\RuntimeException $e) {
            Session::flash('error', $e->getMessage());
        }
        Response::redirect(SalesDocument::BASE_PATH[$type]);
    }

    // ------------------------------------------------------------------ detail / print

    public function show(Request $req, string $slug, string $id): string
    {
        Auth::require($req);
        $type = $this->type($slug);
        $doc = SalesDocument::load((int)$id);
        if (!$doc || $doc['doc_type'] !== $type) Response::notFound('Document not found.');
        return View::page('admin.sales.show', $this->viewData([
            'title' => $doc['doc_no'], 'type' => $type, 'slug' => $slug, 'doc' => $doc,
        ]), 'layouts.admin');
    }

    public function print(Request $req, string $slug, string $id): string
    {
        Auth::require($req);
        $type = $this->type($slug);
        $doc = SalesDocument::load((int)$id);
        if (!$doc || $doc['doc_type'] !== $type) Response::notFound('Document not found.');
        $settings = DB::fetch('SELECT * FROM site_settings ORDER BY id ASC LIMIT 1') ?: [];
        $office = DB::fetch("SELECT * FROM offices WHERE code = 'UAE' LIMIT 1") ?: [];
        return View::page('admin.sales.print', [
            'doc' => $doc, 'settings' => $settings, 'office' => $office,
        ], 'layouts.print');
    }

    // ------------------------------------------------------------------ lifecycle actions

    public function approve(Request $req, string $slug, string $id): never
    {
        Auth::require($req); Csrf::check($req);
        $type = $this->type($slug);
        if ($type !== 'quotation') Response::notFound('Only quotations are approved.');
        $docId = (int)$id;
        $doc = SalesDocument::findOfType($docId, 'quotation');
        if (!$doc) Response::notFound('Quotation not found.');
        $ok = SalesDocument::approve($docId, Auth::user()['id'] ?? null);
        if ($ok) { $this->log('quotation', 'approve', $docId, $doc['doc_no']); Session::flash('success', 'Quotation ' . $doc['doc_no'] . ' approved.'); }
        else { Session::flash('error', 'Only a draft quotation can be approved (this one is ' . (SalesDocument::findOfType($docId, 'quotation')['status'] ?? 'unknown') . ').'); }
        Response::redirect(SalesDocument::path('quotation', $docId));
    }

    public function reject(Request $req, string $slug, string $id): never
    {
        Auth::require($req); Csrf::check($req);
        $type = $this->type($slug);
        if ($type !== 'quotation') Response::notFound('Only quotations are rejected.');
        $docId = (int)$id;
        $doc = SalesDocument::findOfType($docId, 'quotation');
        if (!$doc) Response::notFound('Quotation not found.');
        $ok = SalesDocument::reject($docId, Auth::user()['id'] ?? null);
        Session::flash($ok ? 'success' : 'error', $ok ? 'Quotation rejected.' : 'Only a draft quotation can be rejected.');
        if ($ok) $this->log('quotation', 'reject', $docId, $doc['doc_no']);
        Response::redirect(SalesDocument::path('quotation', $docId));
    }

    public function cancel(Request $req, string $slug, string $id): never
    {
        Auth::require($req); Csrf::check($req);
        $type = $this->type($slug);
        $docId = (int)$id;
        $doc = SalesDocument::findOfType($docId, $type);
        if (!$doc) Response::notFound('Document not found.');
        try {
            SalesDocument::cancel($docId);
            $this->log($type, 'cancel', $docId, $doc['doc_no']);
            Session::flash('success', 'Document cancelled.');
        } catch (\RuntimeException $e) {
            Session::flash('error', $e->getMessage());
        }
        Response::redirect(SalesDocument::path($type, $docId));
    }

    /**
     * quotation → sales order, sales order → invoice.
     * The redirect target differs per outcome, so the user always lands on the document that
     * now exists (or back on the parent with an explanation).
     */
    public function convert(Request $req, string $slug, string $id): never
    {
        Auth::require($req); Csrf::check($req);
        $type = $this->type($slug);
        if ($type === 'invoice') Response::notFound('Invoices cannot be converted.');
        $docId = (int)$id;
        $doc = SalesDocument::findOfType($docId, $type);
        if (!$doc) Response::notFound('Document not found.');
        try {
            $child = SalesDocument::convert($docId, Auth::user()['id'] ?? null);
            $this->log($type, 'convert', $docId, $doc['doc_no'] . ' → ' . $child['doc_no']);
            Session::flash('success', SalesDocument::TYPE_LABEL[$child['doc_type']] . ' ' . $child['doc_no'] . ' created from ' . $doc['doc_no'] . '.');
            Response::redirect(SalesDocument::path($child['doc_type'], $child['id']));
        } catch (\RuntimeException $e) {
            Session::flash('error', $e->getMessage());
            Response::redirect(SalesDocument::path($type, $docId));
        }
    }

    // ------------------------------------------------------------------ payments

    public function addPayment(Request $req, string $slug, string $id): never
    {
        Auth::require($req); Csrf::check($req);
        $type = $this->type($slug);
        if ($type !== 'invoice') Response::notFound('Payments are recorded against invoices only.');
        $invoiceId = (int)$id;
        $invoice = SalesDocument::findOfType($invoiceId, 'invoice');
        if (!$invoice) Response::notFound('Invoice not found.');
        [$data, $errors] = Payment::validate($req->all(), $invoice);
        if ($errors) {
            Session::flash('error', implode(' ', $errors));
            Response::redirect(SalesDocument::path('invoice', $invoiceId));
        }
        try {
            Payment::record($invoiceId, $data, Auth::user()['id'] ?? null);
            $this->log('invoice', 'payment', $invoiceId, $invoice['doc_no'] . ' +' . Money::format((int)$data['amount_minor'], $invoice['currency']));
            Session::flash('success', 'Payment of ' . Money::format((int)$data['amount_minor'], $invoice['currency']) . ' recorded.');
        } catch (\RuntimeException $e) {
            Session::flash('error', $e->getMessage());
        }
        Response::redirect(SalesDocument::path('invoice', $invoiceId));
    }

    public function deletePayment(Request $req, string $slug, string $id): never
    {
        Auth::require($req); Csrf::check($req);
        $type = $this->type($slug);
        if ($type !== 'invoice') Response::notFound('Payments belong to invoices.');
        $invoiceId = (int)$id;
        $paymentId = $req->int('payment_id');
        $payment = Payment::find($paymentId);
        if (!$payment || (int)$payment['invoice_id'] !== $invoiceId) { Session::flash('error', 'Payment not found on this invoice.'); Response::redirect(SalesDocument::path('invoice', $invoiceId)); }
        Payment::delete($paymentId);
        $this->log('invoice', 'payment_delete', $invoiceId, (string)$payment['payment_no']);
        Session::flash('success', 'Payment ' . $payment['payment_no'] . ' removed.');
        Response::redirect(SalesDocument::path('invoice', $invoiceId));
    }

    // ------------------------------------------------------------------ helpers

    /** Re-render the submitted header on validation failure (raw strings, not parsed). */
    private function rawHeader(Request $req): array
    {
        return [
            'customer_id' => $req->int('customer_id'),
            'currency' => strtoupper($req->str('currency')),
            'issue_date' => $req->str('issue_date'),
            'valid_until' => $req->str('valid_until'),
            'due_date' => $req->str('due_date'),
            'customer_reference' => $req->str('customer_reference'),
            'notes' => $req->str('notes'),
            'discount' => $req->str('discount'),
            'tax_rate' => $req->str('tax_rate'),
        ];
    }

    private function rawLines(Request $req): array
    {
        $all = $req->all();
        $desc = $all['line_description'] ?? [];
        $qty = $all['line_qty'] ?? [];
        $price = $all['line_unit_price'] ?? [];
        $sku = $all['line_sku'] ?? [];
        if (!is_array($desc)) $desc = [$desc];
        $out = [];
        $n = max(count($desc), is_array($qty) ? count($qty) : 0, is_array($price) ? count($price) : 0);
        for ($i = 0; $i < $n; $i++) {
            $out[] = [
                'sku' => (string)(is_array($sku) ? ($sku[$i] ?? '') : ''),
                'description' => (string)($desc[$i] ?? ''),
                'qty' => (string)(is_array($qty) ? ($qty[$i] ?? '') : ''),
                'unit_price' => (string)(is_array($price) ? ($price[$i] ?? '') : ''),
            ];
        }
        return $out ?: [['sku' => '', 'description' => '', 'qty' => '1', 'unit_price' => '0.00']];
    }

    private function log(string $type, string $action, int $id, string $summary): void
    {
        try {
            DB::query('INSERT INTO activity_log (user_id, action, entity, record_id, summary) VALUES (?, ?, ?, ?, ?)',
                [Auth::user()['id'] ?? null, $action, 'sales_' . $type, $id, mb_substr($summary, 0, 490)]);
        } catch (\Throwable) {}
    }
}
