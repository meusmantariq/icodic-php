<?php
declare(strict_types=1);
namespace App\Controllers\Admin;

use App\Core\{Auth, Database as DB, Money, Request, Response, View};
use App\Models\{Payment, SalesDocument};

/**
 * Accountant dashboard — receivables only.
 *
 * Scope note (deliberate): this reports what the sales module actually knows — invoiced,
 * collected and outstanding amounts per currency, overdue totals and a receivables ageing
 * bucket. It is NOT a general ledger, it does not post journals, it does not reconcile bank
 * statements and it does not compute VAT/tax liability or file anything. Tax rates on a
 * document are stored as a single reference percentage so a printed document shows what was
 * quoted; no tax return, tax engine or compliance statement is derived from them.
 *
 * Amounts in different currencies are never summed together: every figure is shown per
 * currency because the app stores no exchange rates and inventing one would be a lie.
 */
final class FinanceController
{
    private function registry(): array { return require APP_PATH . '/config/entities.php'; }

    public function dashboard(Request $req): string
    {
        Auth::require($req);
        $byCurrency = SalesDocument::dashboardByCurrency();
        $pipeline = SalesDocument::pipelineByCurrency();

        $counts = [
            'customers' => (int)DB::scalar('SELECT COUNT(*) FROM customers'),
            'customers_active' => (int)DB::scalar("SELECT COUNT(*) FROM customers WHERE status = 'active'"),
            'quotations' => (int)DB::scalar("SELECT COUNT(*) FROM sales_documents WHERE doc_type = 'quotation'"),
            'quotations_draft' => (int)DB::scalar("SELECT COUNT(*) FROM sales_documents WHERE doc_type = 'quotation' AND status = 'draft'"),
            'quotations_approved' => (int)DB::scalar("SELECT COUNT(*) FROM sales_documents WHERE doc_type = 'quotation' AND status = 'approved' AND converted_at IS NULL"),
            'orders' => (int)DB::scalar("SELECT COUNT(*) FROM sales_documents WHERE doc_type = 'sales_order'"),
            'orders_open' => (int)DB::scalar("SELECT COUNT(*) FROM sales_documents WHERE doc_type = 'sales_order' AND status = 'open'"),
            'invoices' => (int)DB::scalar("SELECT COUNT(*) FROM sales_documents WHERE doc_type = 'invoice' AND status <> 'cancelled'"),
            'invoices_open' => (int)DB::scalar("SELECT COUNT(*) FROM sales_documents WHERE doc_type = 'invoice' AND status IN ('unpaid','partial')"),
        ];

        // Ageing buckets per currency (0-30 / 31-60 / 61-90 / 90+ days past due).
        $ageingRows = DB::fetchAll("SELECT d.currency,
                SUM(CASE WHEN DATEDIFF(CURDATE(), d.due_date) BETWEEN 0 AND 30 THEN d.total_minor - COALESCE(p.paid,0) ELSE 0 END) AS b30,
                SUM(CASE WHEN DATEDIFF(CURDATE(), d.due_date) BETWEEN 31 AND 60 THEN d.total_minor - COALESCE(p.paid,0) ELSE 0 END) AS b60,
                SUM(CASE WHEN DATEDIFF(CURDATE(), d.due_date) BETWEEN 61 AND 90 THEN d.total_minor - COALESCE(p.paid,0) ELSE 0 END) AS b90,
                SUM(CASE WHEN DATEDIFF(CURDATE(), d.due_date) > 90 THEN d.total_minor - COALESCE(p.paid,0) ELSE 0 END) AS b90p,
                SUM(CASE WHEN d.due_date IS NULL OR DATEDIFF(CURDATE(), d.due_date) < 0 THEN d.total_minor - COALESCE(p.paid,0) ELSE 0 END) AS current_bucket
            FROM sales_documents d
            LEFT JOIN (SELECT invoice_id, SUM(amount_minor) AS paid FROM payments GROUP BY invoice_id) p ON p.invoice_id = d.id
            WHERE d.doc_type = 'invoice' AND d.status IN ('unpaid','partial')
            GROUP BY d.currency ORDER BY d.currency");
        $ageing = [];
        foreach ($ageingRows as $r) {
            $ageing[$r['currency']] = [
                'current' => (int)$r['current_bucket'], 'd30' => (int)$r['b30'],
                'd60' => (int)$r['b60'], 'd90' => (int)$r['b90'], 'd90p' => (int)$r['b90p'],
            ];
        }

        $outstanding = Payment::outstandingInvoices(12);
        foreach ($outstanding as &$o) {
            $o['balance_minor'] = max(0, (int)$o['total_minor'] - (int)$o['paid_minor']);
            $o['overdue_days'] = ($o['due_date'] !== null && $o['due_date'] < date('Y-m-d')) ? (int)floor((strtotime(date('Y-m-d')) - strtotime((string)$o['due_date'])) / 86400) : 0;
        }
        unset($o);

        return View::page('admin.finance.dashboard', [
            'title' => 'Accounting dashboard',
            'registry' => $this->registry(),
            'byCurrency' => $byCurrency, 'pipeline' => $pipeline, 'counts' => $counts,
            'ageing' => $ageing, 'outstanding' => $outstanding,
            'recentPayments' => Payment::recent(10),
            'recentDocs' => SalesDocument::recent(8),
            'methods' => Payment::byMethod(),
            'currencies' => Money::CURRENCIES,
            'today' => date('Y-m-d'),
        ], 'layouts.admin');
    }
}
