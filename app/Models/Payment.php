<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Database as DB;
use App\Core\Money;

/**
 * Payments recorded against a sales invoice.
 *
 * Overpayments are impossible by construction:
 *  - the invoice row is locked `FOR UPDATE` for the duration of the check + insert,
 *  - the remaining balance is recomputed from the payments table inside that lock,
 *  - the amount must be > 0 and <= balance,
 *  - and the invoice status is refreshed from the payment sum (never set by hand).
 * This holds for concurrent requests as well as for crafted POSTs.
 */
final class Payment
{
    public const METHODS = ['bank_transfer', 'cash', 'cheque', 'card', 'other'];
    public const METHOD_LABEL = [
        'bank_transfer' => 'Bank transfer', 'cash' => 'Cash', 'cheque' => 'Cheque',
        'card' => 'Card', 'other' => 'Other',
    ];

    public static function forInvoice(int $invoiceId): array
    {
        return DB::fetchAll('SELECT * FROM payments WHERE invoice_id = ? ORDER BY paid_on ASC, id ASC', [$invoiceId]);
    }

    public static function paidTotal(int $invoiceId): int
    {
        return (int)DB::scalar('SELECT COALESCE(SUM(amount_minor),0) FROM payments WHERE invoice_id = ?', [$invoiceId]);
    }

    public static function find(int $id): ?array
    {
        return DB::fetch('SELECT * FROM payments WHERE id = ?', [$id]);
    }

    /** Recent payments across all invoices (dashboard). */
    public static function recent(int $limit = 8): array
    {
        return DB::fetchAll('SELECT p.*, d.doc_no, d.currency, c.company_name
            FROM payments p JOIN sales_documents d ON d.id = p.invoice_id JOIN customers c ON c.id = d.customer_id
            ORDER BY p.paid_on DESC, p.id DESC LIMIT ' . (int)$limit);
    }

    /** Payment totals per method for the dashboard (single currency view avoided: grouped by currency+method). */
    public static function byMethod(): array
    {
        return DB::fetchAll('SELECT d.currency, p.method, COUNT(*) AS n, SUM(p.amount_minor) AS total_minor
            FROM payments p JOIN sales_documents d ON d.id = p.invoice_id
            GROUP BY d.currency, p.method ORDER BY d.currency, p.method');
    }

    /**
     * Validate a payment POST against a specific invoice.
     *
     * @return array{0:array,1:string[]}
     */
    public static function validate(array $in, array $invoice): array
    {
        $errors = [];
        $currency = (string)$invoice['currency'];
        $data = ['invoice_id' => (int)$invoice['id']];

        $amount = Money::parse($in['amount'] ?? '', $currency);
        if ($amount === null) $errors[] = 'Amount must be a positive number with at most ' . Money::scale($currency) . ' decimals.';
        elseif ($amount <= 0) $errors[] = 'Amount must be greater than zero.';
        $data['amount_minor'] = (int)($amount ?? 0);

        $paidOn = SalesDocument::parseDate($in['paid_on'] ?? '');
        if ($paidOn === null) $errors[] = 'Payment date must be a valid date.';
        $data['paid_on'] = $paidOn ?? date('Y-m-d');

        $method = strtolower(trim((string)($in['method'] ?? 'bank_transfer')));
        if (!in_array($method, self::METHODS, true)) $errors[] = 'Choose a valid payment method.';
        $data['method'] = in_array($method, self::METHODS, true) ? $method : 'bank_transfer';

        $data['reference'] = mb_substr(trim((string)($in['reference'] ?? '')), 0, 120) ?: null;
        $data['notes'] = mb_substr(trim((string)($in['notes'] ?? '')), 0, 500) ?: null;
        return [$data, $errors];
    }

    /**
     * Record a payment. Returns the new payment id.
     * Throws RuntimeException when the invoice is not payable or the amount exceeds the balance.
     */
    public static function record(int $invoiceId, array $data, ?int $userId): int
    {
        $pdo = DB::pdo();
        $pdo->beginTransaction();
        try {
            $inv = DB::fetch("SELECT * FROM sales_documents WHERE id = ? AND doc_type = 'invoice' FOR UPDATE", [$invoiceId]);
            if (!$inv) throw new \RuntimeException('Invoice not found.');
            if ($inv['status'] === 'cancelled') throw new \RuntimeException('Payments cannot be recorded against a cancelled invoice.');
            $total = (int)$inv['total_minor'];
            $paid = self::paidTotal($invoiceId);
            $balance = $total - $paid;
            $amount = (int)$data['amount_minor'];
            if ($balance <= 0) throw new \RuntimeException('This invoice is already fully paid.');
            if ($amount <= 0) throw new \RuntimeException('Payment amount must be greater than zero.');
            if ($amount > $balance) {
                throw new \RuntimeException('Payment exceeds the outstanding balance of ' . Money::format($balance, (string)$inv['currency']) . '.');
            }
            $paymentNo = DocumentNumber::next('payment');
            DB::query('INSERT INTO payments (payment_no, invoice_id, paid_on, amount_minor, method, reference, notes, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)', [
                $paymentNo, $invoiceId, $data['paid_on'], $amount, $data['method'],
                $data['reference'] ?? null, $data['notes'] ?? null, $userId,
            ]);
            $id = DB::lastId();
            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
        SalesDocument::refreshInvoiceStatus($invoiceId);
        SalesDocument::syncOrderFulfilment($invoiceId);
        return $id;
    }

    /** Remove a payment (correction) and resync the invoice. */
    public static function delete(int $paymentId): void
    {
        $p = self::find($paymentId);
        if (!$p) return;
        DB::query('DELETE FROM payments WHERE id = ?', [$paymentId]);
        SalesDocument::refreshInvoiceStatus((int)$p['invoice_id']);
    }

    /** Outstanding invoices for the dashboard (oldest first). */
    public static function outstandingInvoices(int $limit = 10): array
    {
        return DB::fetchAll("SELECT d.id, d.doc_no, d.currency, d.due_date, d.total_minor, d.status, c.company_name,
                COALESCE((SELECT SUM(p.amount_minor) FROM payments p WHERE p.invoice_id = d.id),0) AS paid_minor
            FROM sales_documents d JOIN customers c ON c.id = d.customer_id
            WHERE d.doc_type = 'invoice' AND d.status IN ('unpaid','partial')
            ORDER BY (d.due_date IS NULL), d.due_date ASC, d.id ASC LIMIT " . (int)$limit);
    }
}
