<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Database as DB;

/**
 * Gapless-ish per-type / per-year document numbering.
 *
 * The counter row is locked inside the caller's transaction (`SELECT … FOR UPDATE`), so two
 * concurrent requests can never receive the same number. If the surrounding transaction
 * rolls back, the increment rolls back with it and the number is reused — which is what you
 * want for a sequence that must not leave gaps on failed saves.
 */
final class DocumentNumber
{
    /** sequence key => [doc_type for the counter table, printed prefix] */
    private const SERIES = [
        'quotation'   => ['quotation',   'QN-QUO'],
        'sales_order' => ['sales_order', 'QN-SO'],
        'invoice'     => ['invoice',     'QN-INV'],
        'payment'     => ['payment',     'QN-PAY'],
    ];
    public const PREFIX = ['quotation' => 'QN-QUO', 'sales_order' => 'QN-SO', 'invoice' => 'QN-INV', 'payment' => 'QN-PAY'];

    /** Human labels for the three document types (used by controllers/views). */
    public const LABELS = ['quotation' => 'Quotation', 'sales_order' => 'Sales Order', 'invoice' => 'Sales Invoice'];

    /** Must be called inside a transaction. Returns e.g. QN-INV-2026-0007. */
    public static function next(string $series, ?string $year = null): string
    {
        if (!isset(self::SERIES[$series])) throw new \InvalidArgumentException("Unknown document series: $series");
        [$key, $prefix] = self::SERIES[$series];
        $period = $year ?? date('Y');
        DB::query('INSERT INTO document_sequences (doc_type, period, next_no) VALUES (?, ?, 0) ON DUPLICATE KEY UPDATE next_no = next_no', [$key, $period]);
        $row = DB::fetch('SELECT next_no FROM document_sequences WHERE doc_type = ? AND period = ? FOR UPDATE', [$key, $period]);
        $no = (int)($row['next_no'] ?? 0) + 1;
        DB::query('UPDATE document_sequences SET next_no = ? WHERE doc_type = ? AND period = ?', [$no, $key, $period]);
        return sprintf('%s-%s-%04d', $prefix, $period, $no);
    }

    public static function nextDocument(string $docType): string { return self::next($docType); }
    public static function nextPayment(): string { return self::next('payment'); }
}
