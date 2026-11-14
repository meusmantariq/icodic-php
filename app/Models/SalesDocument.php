<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Database as DB;
use App\Core\Money;

/**
 * Quotations, Sales Orders and Sales Invoices.
 *
 * All three kinds live in `sales_documents` (doc_type discriminator) with lines in
 * `sales_document_items`. The lifecycle is deliberately explicit and enforced here, in SQL
 * conditions *and* in PHP, so a stale page or a crafted POST cannot skip a step:
 *
 *   quotation draft ──approve──▶ approved ──convert──▶ sales_order (open)
 *   quotation draft ──reject───▶ rejected                    │
 *   quotation/sales_order ──cancel──▶ cancelled         convert
 *                                                            ▼
 *                                                    invoice (unpaid/partial/paid)
 *
 * Rules implemented:
 *  - A quotation can be converted only when approved and not yet converted.
 *  - A sales order can be converted only by an *approved* quotation.
 *  - An invoice is created only from a sales order, never from a quotation, and a sales order
 *    produces at most one invoice (`uq_documents_source` unique index + status guard).
 *  - Documents with children (converted) are immutable and cannot be deleted.
 *  - Amounts are integer minor units computed by App\Core\Money.
 */
final class SalesDocument
{
    public const TYPES = ['quotation', 'sales_order', 'invoice'];

    public const STATUSES = [
        'quotation'   => ['draft', 'approved', 'rejected', 'cancelled', 'expired'],
        'sales_order' => ['open', 'fulfilled', 'cancelled'],
        'invoice'     => ['unpaid', 'partial', 'paid', 'cancelled'],
    ];

    /** Statuses that may produce a child document. */
    public const CONVERTIBLE = [
        'quotation'   => 'approved',
        'sales_order' => 'open',
    ];

    /** Statuses whose content may still be edited. */
    public const EDITABLE = [
        'quotation'   => ['draft'],
        'sales_order' => ['open'],
        'invoice'     => [],
    ];

    public const TYPE_LABEL = ['quotation' => 'Quotation', 'sales_order' => 'Sales Order', 'invoice' => 'Sales Invoice'];
    public const TYPE_PLURAL = ['quotation' => 'Quotations', 'sales_order' => 'Sales Orders', 'invoice' => 'Invoices'];
    public const BASE_PATH = ['quotation' => '/admin/quotations', 'sales_order' => '/admin/sales-orders', 'invoice' => '/admin/invoices'];

    public static function path(string $type, int $id, string $suffix = ''): string
    {
        return (self::BASE_PATH[$type] ?? '/admin') . '/' . $id . $suffix;
    }

    // ---------------------------------------------------------------- reads

    public static function find(int $id): ?array
    {
        return DB::fetch('SELECT * FROM sales_documents WHERE id = ?', [$id]);
    }

    public static function findOfType(int $id, string $type): ?array
    {
        return DB::fetch('SELECT * FROM sales_documents WHERE id = ? AND doc_type = ?', [$id, $type]);
    }

    /** Document + customer + lines + payments + children, ready for the detail/print views. */
    public static function load(int $id): ?array
    {
        $doc = self::find($id);
        if (!$doc) return null;
        $doc['items'] = self::items($id);
        $doc['customer'] = Customer::find((int)$doc['customer_id']) ?? [];
        $doc['payments'] = $doc['doc_type'] === 'invoice' ? Payment::forInvoice($id) : [];
        $doc['paid_minor'] = $doc['doc_type'] === 'invoice' ? Payment::paidTotal($id) : 0;
        $doc['balance_minor'] = $doc['doc_type'] === 'invoice' ? max(0, (int)$doc['total_minor'] - (int)$doc['paid_minor']) : 0;
        $doc['children'] = DB::fetchAll('SELECT id, doc_type, doc_no, status, total_minor, currency, issue_date FROM sales_documents WHERE source_type = ? AND source_id = ? ORDER BY id ASC', [$doc['doc_type'], $id]);
        $doc['parent'] = $doc['source_id'] ? DB::fetch('SELECT id, doc_type, doc_no, status FROM sales_documents WHERE id = ?', [(int)$doc['source_id']]) : null;
        $doc['created_by_name'] = $doc['created_by'] ? (string)DB::scalar('SELECT name FROM admin_users WHERE id = ?', [(int)$doc['created_by']]) : '';
        $doc['approved_by_name'] = $doc['approved_by'] ? (string)DB::scalar('SELECT name FROM admin_users WHERE id = ?', [(int)$doc['approved_by']]) : '';
        return $doc;
    }

    public static function items(int $id): array
    {
        $rows = DB::fetchAll('SELECT * FROM sales_document_items WHERE document_id = ? ORDER BY line_no ASC', [$id]);
        foreach ($rows as &$r) $r['qty_thousandths'] = self::qtyInt($r['qty_thousandths']);
        return $rows;
    }

    /** DECIMAL(12,3) comes back as string; normalise "2.500" to 2500 without float maths. */
    public static function qtyInt(mixed $value): int
    {
        return Money::parseQty(is_string($value) ? $value : (string)$value) ?? 0;
    }

    /**
     * Paged listing.
     *
     * @return array{0:array<int,array>,1:int}
     */
    public static function paginate(string $type, string $q, string $status, ?string $currency, int $page, int $per = 25): array
    {
        $where = ['d.doc_type = ?']; $params = [$type];
        if ($q !== '') {
            $like = '%' . $q . '%';
            $where[] = '(d.doc_no LIKE ? OR c.company_name LIKE ? OR c.code LIKE ? OR d.customer_reference LIKE ?)';
            array_push($params, $like, $like, $like, $like);
        }
        if ($status !== '' && in_array($status, self::STATUSES[$type] ?? [], true)) { $where[] = 'd.status = ?'; $params[] = $status; }
        if ($currency !== null) { $where[] = 'd.currency = ?'; $params[] = $currency; }
        $whereSql = ' WHERE ' . implode(' AND ', $where);
        $from = ' FROM sales_documents d JOIN customers c ON c.id = d.customer_id';
        $total = (int)DB::scalar('SELECT COUNT(*)' . $from . $whereSql, $params);
        $page = max(1, $page);
        $rows = DB::fetchAll('SELECT d.*, c.company_name, c.code AS customer_code' . $from . $whereSql
            . ' ORDER BY d.issue_date DESC, d.id DESC LIMIT ' . $per . ' OFFSET ' . (($page - 1) * $per), $params);
        foreach ($rows as &$r) {
            if (in_array($r['doc_type'], ['invoice'], true)) {
                $r['paid_minor'] = Payment::paidTotal((int)$r['id']);
                $r['balance_minor'] = max(0, (int)$r['total_minor'] - (int)$r['paid_minor']);
            }
        }
        return [$rows, $total];
    }

    /** Recent documents for the dashboard. */
    public static function recent(int $limit = 8): array
    {
        return DB::fetchAll('SELECT d.*, c.company_name FROM sales_documents d JOIN customers c ON c.id = d.customer_id ORDER BY d.id DESC LIMIT ' . (int)$limit);
    }

    // ---------------------------------------------------------------- validation

    /**
     * Validate a document POST.
     *
     * @param array $in raw input (lines come as parallel arrays: line_description[], line_qty[], …)
     * @return array{0:array,1:string[],2:array<int,array>} [header, errors, lines]
     */
    public static function validate(array $in, string $type, int $documentId = 0): array
    {
        $errors = [];
        $header = [];

        $customerId = (int)($in['customer_id'] ?? 0);
        $customer = $customerId > 0 ? Customer::find($customerId) : null;
        if (!$customer) $errors[] = 'Choose an existing customer.';
        if ($customer && ($customer['status'] ?? '') !== 'active') $errors[] = 'That customer is inactive. Reactivate it before creating documents.';
        $header['customer_id'] = $customerId;

        $currency = Money::currency($in['currency'] ?? '');
        if ($currency === null) $errors[] = 'Choose a supported currency (AED, USD, EUR, GBP, SAR, INR, PKR).';
        $header['currency'] = $currency ?? Money::DEFAULT;

        $issue = self::parseDate($in['issue_date'] ?? '');
        if ($issue === null) $errors[] = 'Issue date must be a valid date (YYYY-MM-DD).';
        $header['issue_date'] = $issue ?? date('Y-m-d');

        if ($type === 'quotation') {
            $valid = self::parseDate($in['valid_until'] ?? '');
            if (($in['valid_until'] ?? '') !== '' && $valid === null) $errors[] = 'Valid-until must be a valid date.';
            if ($valid !== null && $valid < $header['issue_date']) $errors[] = 'Valid-until cannot be before the issue date.';
            $header['valid_until'] = $valid;
            $header['due_date'] = null;
        } elseif ($type === 'invoice') {
            $due = self::parseDate($in['due_date'] ?? '');
            if ($due === null) $errors[] = 'Due date must be a valid date.';
            $header['due_date'] = $due;
            $header['valid_until'] = null;
        } else {
            $due = self::parseDate($in['due_date'] ?? '');
            if (($in['due_date'] ?? '') !== '' && $due === null) $errors[] = 'Delivery/due date must be a valid date.';
            $header['due_date'] = $due;
            $header['valid_until'] = null;
        }

        $header['customer_reference'] = mb_substr(trim((string)($in['customer_reference'] ?? '')), 0, 120) ?: null;
        $header['notes'] = mb_substr(trim((string)($in['notes'] ?? '')), 0, 2000) ?: null;

        // Lines: parallel arrays posted by the repeatable row editor.
        $desc = $in['line_description'] ?? [];
        $qty  = $in['line_qty'] ?? [];
        $price = $in['line_unit_price'] ?? [];
        $sku  = $in['line_sku'] ?? [];
        if (!is_array($desc)) $desc = [$desc];
        $lines = [];
        $count = max(count($desc), is_array($qty) ? count($qty) : 1, is_array($price) ? count($price) : 1);
        for ($i = 0; $i < $count; $i++) {
            $d = trim((string)($desc[$i] ?? ''));
            $q = trim((string)(is_array($qty) ? ($qty[$i] ?? '') : ''));
            $p = trim((string)(is_array($price) ? ($price[$i] ?? '') : ''));
            $s = trim((string)(is_array($sku) ? ($sku[$i] ?? '') : ''));
            $blank = ($d === '' && $q === '' && $p === '');
            if ($blank) continue; // skip empty template rows
            $rowErrors = [];
            if ($d === '') $rowErrors[] = 'description is required';
            if (mb_strlen($d) > 500) $rowErrors[] = 'description is too long (max 500)';
            $qtyT = Money::parseQty($q);
            if ($qtyT === null || $qtyT <= 0) $rowErrors[] = 'quantity must be a positive number (max 3 decimals)';
            $unit = Money::parse($p, $header['currency']);
            if ($unit === null) $rowErrors[] = 'unit price must be a non-negative amount with at most ' . Money::scale($header['currency']) . ' decimals';
            if ($rowErrors) $errors[] = 'Line ' . ($i + 1) . ': ' . implode(', ', $rowErrors) . '.';
            $lines[] = ['sku' => mb_substr($s, 0, 64) ?: null, 'description' => mb_substr($d, 0, 500), 'qty_thousandths' => (int)$qtyT, 'unit_price_minor' => (int)$unit];
        }
        if (!$lines) $errors[] = 'Add at least one line item.';
        if (count($lines) > 200) $errors[] = 'A document can hold at most 200 lines.';

        $discount = Money::parse($in['discount'] ?? '0', $header['currency']) ?? 0;
        if (isset($in['discount']) && trim((string)$in['discount']) !== '' && Money::parse((string)$in['discount'], $header['currency']) === null) {
            $errors[] = 'Document discount must be a non-negative amount that fits the currency.';
        }
        $header['discount_minor'] = $discount;

        $rateRaw = trim((string)($in['tax_rate'] ?? '0'));
        $rate = 0;
        if ($rateRaw !== '') {
            if (!preg_match('/^\d{1,3}(?:\.\d{1,2})?$/', $rateRaw)) $errors[] = 'Tax rate must be a percentage such as 5 or 5.00.';
            else $rate = (int)round(((float)$rateRaw) * 100); // 5.00 % -> 500 bp (display-level conversion only)
        }
        if ($rate > 5000) $errors[] = 'Tax rate cannot exceed 50%.';
        $header['tax_rate_bp'] = $rate;

        // Totals (throws if the discount exceeds the subtotal — surfaced as a field error).
        $sums = null;
        if (!$errors) {
            try {
                $sums = self::computeTotals($lines, $discount, $rate);
            } catch (\InvalidArgumentException $e) {
                $errors[] = $e->getMessage();
            }
        }
        if ($sums) $header += $sums;

        return [$header, $errors, $lines];
    }

    /**
     * Exact totals for a set of validated lines.
     *
     * @param array<int,array{qty_thousandths:int,unit_price_minor:int}> $lines
     */
    public static function computeTotals(array $lines, int $discountMinor, int $taxRateBp): array
    {
        $totals = [];
        foreach ($lines as $l) $totals[] = Money::lineTotal((int)$l['unit_price_minor'], (int)$l['qty_thousandths']);
        return Money::totals($totals, $discountMinor, $taxRateBp);
    }

    public static function parseDate(mixed $value): ?string
    {
        $s = trim((string)$value);
        if ($s === '') return null;
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $s, $m)) return null;
        if (!checkdate((int)$m[2], (int)$m[3], (int)$m[1])) return null;
        return $s;
    }

    // ---------------------------------------------------------------- writes

    /**
     * Create a document. Wrapped in a transaction; the lines and the header are written
     * together or not at all.
     *
     * @param array{source_type?:?string,source_id?:?int,status?:string} $extra
     */
    public static function create(string $type, array $header, array $lines, ?int $userId, array $extra = []): int
    {
        if (!in_array($type, self::TYPES, true)) throw new \InvalidArgumentException('Unknown document type.');
        $pdo = DB::pdo();
        $pdo->beginTransaction();
        try {
            $docNo = DocumentNumber::next($type);
            $status = $extra['status'] ?? self::STATUSES[$type][0];
            if (!in_array($status, self::STATUSES[$type], true)) throw new \RuntimeException('Invalid initial status for ' . $type . '.');
            $totals = self::computeTotals($lines, (int)$header['discount_minor'], (int)$header['tax_rate_bp']);
            DB::query('INSERT INTO sales_documents
                (doc_type, doc_no, customer_id, currency, status, issue_date, valid_until, due_date, customer_reference, notes,
                 subtotal_minor, discount_minor, tax_rate_bp, tax_minor, total_minor,
                 source_type, source_id, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [
                $type, $docNo, (int)$header['customer_id'], $header['currency'], $status,
                $header['issue_date'], $header['valid_until'] ?? null, $header['due_date'] ?? null,
                $header['customer_reference'] ?? null, $header['notes'] ?? null,
                $totals['subtotal_minor'], $totals['discount_minor'], $totals['tax_rate_bp'], $totals['tax_minor'], $totals['total_minor'],
                $extra['source_type'] ?? null, $extra['source_id'] ?? null, $userId,
            ]);
            $id = DB::lastId();
            self::writeItems($id, $lines);
            $pdo->commit();
            return $id;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }

    /** Replace lines + header of an editable document. */
    public static function update(int $id, array $header, array $lines, ?int $userId): void
    {
        $doc = self::find($id);
        if (!$doc) throw new \RuntimeException('Document not found.');
        self::assertEditable($doc);
        $pdo = DB::pdo();
        $pdo->beginTransaction();
        try {
            $totals = self::computeTotals($lines, (int)$header['discount_minor'], (int)$header['tax_rate_bp']);
            DB::query('UPDATE sales_documents SET customer_id = ?, currency = ?, issue_date = ?, valid_until = ?, due_date = ?,
                customer_reference = ?, notes = ?, subtotal_minor = ?, discount_minor = ?, tax_rate_bp = ?, tax_minor = ?, total_minor = ?
                WHERE id = ?', [
                (int)$header['customer_id'], $header['currency'], $header['issue_date'],
                $header['valid_until'] ?? null, $header['due_date'] ?? null,
                $header['customer_reference'] ?? null, $header['notes'] ?? null,
                $totals['subtotal_minor'], $totals['discount_minor'], $totals['tax_rate_bp'], $totals['tax_minor'], $totals['total_minor'],
                $id,
            ]);
            DB::query('DELETE FROM sales_document_items WHERE document_id = ?', [$id]);
            self::writeItems($id, $lines);
            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }

    private static function writeItems(int $documentId, array $lines): void
    {
        $lineNo = 0;
        foreach ($lines as $l) {
            $lineNo++;
            $total = Money::lineTotal((int)$l['unit_price_minor'], (int)$l['qty_thousandths']);
            DB::query('INSERT INTO sales_document_items (document_id, line_no, sku, description, qty_thousandths, unit_price_minor, line_total_minor)
                VALUES (?, ?, ?, ?, ?, ?, ?)', [
                $documentId, $lineNo, $l['sku'] ?? null, $l['description'],
                Money::qtyString((int)$l['qty_thousandths']), (int)$l['unit_price_minor'], $total,
            ]);
        }
    }

    public static function assertEditable(array $doc): void
    {
        $type = (string)$doc['doc_type'];
        if (!in_array($doc['status'], self::EDITABLE[$type] ?? [], true)) {
            throw new \RuntimeException(self::TYPE_LABEL[$type] . ' ' . $doc['doc_no'] . ' is ' . $doc['status'] . ' and can no longer be edited.');
        }
        if (!empty($doc['converted_at'])) throw new \RuntimeException('This document has already been converted and is locked.');
        if ($type === 'invoice' && Payment::paidTotal((int)$doc['id']) > 0) throw new \RuntimeException('Invoices with payments recorded cannot be edited.');
    }

    /** Delete a document. Only allowed when it produced no child and has no payments. */
    public static function delete(int $id): void
    {
        $doc = self::find($id);
        if (!$doc) return;
        if (!empty($doc['converted_at']) || (int)DB::scalar('SELECT COUNT(*) FROM sales_documents WHERE source_id = ? AND source_type = ?', [$id, $doc['doc_type']]) > 0) {
            throw new \RuntimeException('Documents that have been converted cannot be deleted.');
        }
        if ($doc['doc_type'] === 'invoice' && Payment::paidTotal($id) > 0) throw new \RuntimeException('Invoices with payments cannot be deleted.');
        DB::query('DELETE FROM sales_documents WHERE id = ?', [$id]); // items cascade
    }

    // ---------------------------------------------------------------- lifecycle

    /**
     * Approve a quotation. Idempotent: approving twice reports success without changing data
     * (the second attempt finds status <> 'draft' and returns false).
     */
    public static function approve(int $id, ?int $userId): bool
    {
        $doc = self::findOfType($id, 'quotation');
        if (!$doc) throw new \RuntimeException('Quotation not found.');
        $pdo = self::begin();
        try {
            // Conditional UPDATE = compare-and-swap: only one concurrent request wins.
            $affected = DB::query("UPDATE sales_documents SET status = 'approved', approved_at = NOW(), approved_by = ?
                WHERE id = ? AND doc_type = 'quotation' AND status = 'draft' AND converted_at IS NULL", [$userId, $id])->rowCount();
            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
        return $affected === 1;
    }

    /** Reject a draft quotation. */
    public static function reject(int $id, ?int $userId): bool
    {
        return DB::query("UPDATE sales_documents SET status = 'rejected', closed_at = NOW()
            WHERE id = ? AND doc_type = 'quotation' AND status = 'draft'", [$id])->rowCount() === 1;
    }

    /** Cancel a document that has no children / payments. */
    public static function cancel(int $id): bool
    {
        $doc = self::find($id);
        if (!$doc) throw new \RuntimeException('Document not found.');
        if (!empty($doc['converted_at'])) throw new \RuntimeException('Converted documents cannot be cancelled.');
        if ($doc['doc_type'] === 'invoice' && Payment::paidTotal($id) > 0) throw new \RuntimeException('Invoices with payments cannot be cancelled.');
        $allowed = match ($doc['doc_type']) {
            'quotation'   => ['draft', 'approved', 'expired'],
            'sales_order' => ['open'],
            'invoice'     => ['unpaid'],
            default       => [],
        };
        if (!in_array($doc['status'], $allowed, true)) throw new \RuntimeException('A ' . $doc['status'] . ' ' . $doc['doc_type'] . ' cannot be cancelled.');
        $newStatus = 'cancelled';
        return DB::query('UPDATE sales_documents SET status = ?, closed_at = NOW() WHERE id = ? AND status = ?', [$newStatus, $id, $doc['status']])->rowCount() === 1;
    }

    /**
     * Convert a quotation → sales order, or a sales order → invoice.
     *
     * Everything happens in one transaction:
     *   1. re-read the parent FOR UPDATE (serialises concurrent conversions),
     *   2. verify the parent is approved/open and not yet converted,
     *   3. copy header + lines with new numbers,
     *   4. stamp the parent as converted,
     *   5. commit.
     * The `uq_documents_source` unique index is the last line of defence against duplicates.
     *
     * @return array{id:int,doc_no:string,doc_type:string}
     */
    public static function convert(int $id, ?int $userId): array
    {
        $pdo = self::begin();
        try {
            $parent = DB::fetch('SELECT * FROM sales_documents WHERE id = ? FOR UPDATE', [$id]);
            if (!$parent) throw new \RuntimeException('Source document not found.');
            $type = (string)$parent['doc_type'];

            if ($type === 'quotation' && $parent['status'] !== 'approved') {
                throw new \RuntimeException('Only an approved quotation can be converted to a sales order. This quotation is ' . $parent['status'] . '.');
            }
            if ($type === 'sales_order' && !in_array($parent['status'], ['open', 'fulfilled'], true)) {
                throw new \RuntimeException('Only an open sales order can be invoiced. This order is ' . $parent['status'] . '.');
            }
            if ($type === 'invoice') throw new \RuntimeException('An invoice cannot be converted.');
            if (!empty($parent['converted_at'])) {
                throw new \RuntimeException('This document was already converted.');
            }
            $childType = $type === 'quotation' ? 'sales_order' : 'invoice';
            $existing = DB::fetch('SELECT id, doc_no FROM sales_documents WHERE source_type = ? AND source_id = ?', [$type, $id]);
            if ($existing) {
                throw new \RuntimeException('Already converted to ' . $existing['doc_no'] . '.');
            }

            $items = self::items($id);
            $lines = [];
            foreach ($items as $it) {
                $lines[] = ['sku' => $it['sku'], 'description' => $it['description'],
                    'qty_thousandths' => (int)$it['qty_thousandths'], 'unit_price_minor' => (int)$it['unit_price_minor']];
            }
            $docNo = DocumentNumber::next($childType);
            $issue = date('Y-m-d');
            $due = $childType === 'invoice' ? date('Y-m-d', strtotime('+30 days')) : null;
            DB::query('INSERT INTO sales_documents
                (doc_type, doc_no, customer_id, currency, status, issue_date, valid_until, due_date, customer_reference, notes,
                 subtotal_minor, discount_minor, tax_rate_bp, tax_minor, total_minor, source_type, source_id, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [
                $childType, $docNo, (int)$parent['customer_id'], $parent['currency'], self::STATUSES[$childType][0],
                $issue, null, $due, $parent['customer_reference'], $parent['notes'],
                (int)$parent['subtotal_minor'], (int)$parent['discount_minor'], (int)$parent['tax_rate_bp'], (int)$parent['tax_minor'], (int)$parent['total_minor'],
                $type, $id, $userId,
            ]);
            $childId = DB::lastId();
            self::writeItems($childId, $lines);

            $stamped = DB::query('UPDATE sales_documents SET converted_at = NOW() WHERE id = ? AND converted_at IS NULL', [$id])->rowCount();
            if ($stamped !== 1) throw new \RuntimeException('This document was already converted.');

            $pdo->commit();
            return ['id' => $childId, 'doc_no' => $docNo, 'doc_type' => $childType];
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            if ($e instanceof \PDOException && str_contains($e->getMessage(), 'uq_documents_source')) {
                throw new \RuntimeException('This document was already converted (concurrent request). Reload the page.');
            }
            throw $e;
        }
    }

    /**
     * Mark a sales order fulfilled once its invoice is fully paid. Cheap, explicit hook —
     * the order status is derived from money, never guessed.
     */
    public static function syncOrderFulfilment(int $invoiceId): void
    {
        $inv = self::findOfType($invoiceId, 'invoice');
        if (!$inv || $inv['source_type'] !== 'sales_order' || !$inv['source_id']) return;
        $paid = Payment::paidTotal($invoiceId);
        if ((int)$inv['total_minor'] > 0 && $paid >= (int)$inv['total_minor']) {
            DB::query("UPDATE sales_documents SET status = 'fulfilled' WHERE id = ? AND doc_type = 'sales_order' AND status = 'open'", [(int)$inv['source_id']]);
        }
    }

    /**
     * Recompute an invoice's paid/partial/unpaid status from its payments.
     * Called after every payment insert/delete.
     */
    public static function refreshInvoiceStatus(int $invoiceId): void
    {
        $inv = self::findOfType($invoiceId, 'invoice');
        if (!$inv) return;
        if ($inv['status'] === 'cancelled') return;
        $paid = Payment::paidTotal($invoiceId);
        $total = (int)$inv['total_minor'];
        $status = $paid <= 0 ? 'unpaid' : ($paid >= $total ? 'paid' : 'partial');
        if ($status !== $inv['status']) DB::query('UPDATE sales_documents SET status = ? WHERE id = ? AND doc_type = \'invoice\'', [$status, $invoiceId]);
    }

    private static function begin(): \PDO
    {
        $pdo = DB::pdo();
        if (!$pdo->inTransaction()) $pdo->beginTransaction();
        return $pdo;
    }

    // ---------------------------------------------------------------- reporting

    /**
     * Per-currency totals for the accountant dashboard.
     *
     * @return array<int,array{currency:string,invoiced_minor:int,paid_minor:int,outstanding_minor:int,overdue_minor:int,invoice_count:int,open_count:int}>
     */
    public static function dashboardByCurrency(): array
    {
        $rows = DB::fetchAll("SELECT currency,
                COALESCE(SUM(total_minor),0) AS invoiced_minor,
                COUNT(*) AS invoice_count,
                COALESCE(SUM(CASE WHEN status <> 'paid' THEN total_minor ELSE 0 END),0) AS open_total_minor,
                COALESCE(SUM(CASE WHEN status <> 'paid' THEN 1 ELSE 0 END),0) AS open_count,
                COALESCE(SUM(CASE WHEN status <> 'paid' AND due_date IS NOT NULL AND due_date < CURDATE() THEN total_minor ELSE 0 END),0) AS overdue_minor,
                COALESCE(SUM(CASE WHEN status <> 'paid' AND due_date IS NOT NULL AND due_date < CURDATE() THEN 1 ELSE 0 END),0) AS overdue_count
            FROM sales_documents
            WHERE doc_type = 'invoice' AND status <> 'cancelled'
            GROUP BY currency ORDER BY currency ASC");
        $paid = DB::fetchAll("SELECT d.currency, COALESCE(SUM(p.amount_minor),0) AS paid_minor
            FROM payments p JOIN sales_documents d ON d.id = p.invoice_id
            WHERE d.status <> 'cancelled' GROUP BY d.currency");
        $paidMap = [];
        foreach ($paid as $p) $paidMap[$p['currency']] = (int)$p['paid_minor'];
        $out = [];
        foreach ($rows as $r) {
            $invoiced = (int)$r['invoiced_minor'];
            $paidMinor = $paidMap[$r['currency']] ?? 0;
            $out[] = [
                'currency' => $r['currency'],
                'invoiced_minor' => $invoiced,
                'paid_minor' => $paidMinor,
                'outstanding_minor' => max(0, $invoiced - $paidMinor),
                'overdue_minor' => (int)$r['overdue_minor'],
                'invoice_count' => (int)$r['invoice_count'],
                'open_count' => (int)$r['open_count'],
                'overdue_count' => (int)$r['overdue_count'],
            ];
        }
        return $out;
    }

    /** Counts + value by status for one doc type (used by list page tiles). */
    public static function statusSummary(string $type): array
    {
        $rows = DB::fetchAll('SELECT status, COUNT(*) AS n, COALESCE(SUM(total_minor),0) AS t FROM sales_documents WHERE doc_type = ? GROUP BY status', [$type]);
        $out = [];
        foreach (self::STATUSES[$type] as $s) $out[$s] = ['count' => 0, 'total' => 0];
        foreach ($rows as $r) $out[$r['status']] = ['count' => (int)$r['n'], 'total' => (int)$r['t']];
        return $out;
    }

    /** Quotation pipeline: approved-but-unconverted value per currency. */
    public static function pipelineByCurrency(): array
    {
        return DB::fetchAll("SELECT currency, COUNT(*) AS n, COALESCE(SUM(total_minor),0) AS total_minor
            FROM sales_documents WHERE doc_type = 'quotation' AND status = 'approved' AND converted_at IS NULL
            GROUP BY currency ORDER BY currency");
    }
}
