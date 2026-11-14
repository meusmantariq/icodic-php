<?php
declare(strict_types=1);
namespace App\Core;

/**
 * Integer minor-unit money helpers.
 *
 * Every amount persisted by the sales module (quotations, sales orders, invoices,
 * payments) is an INTEGER count of the smallest currency unit (fils for AED,
 * cents for USD/EUR/GBP, paisa for INR/PKR...). No float arithmetic is used
 * anywhere: parsing, multiplication, rounding and formatting are done with
 * integer/string maths only, so totals are exact and reproducible in PHP, in
 * SQL (BIGINT SUM) and in the browser (small integer helper in app.js).
 *
 * Rounding policy: half-up away from zero, applied once per line
 * (unit_price_minor * qty_thousandths / 1000) and once for the document-level
 * tax (taxable_minor * rate_bp / 10000).
 */
final class Money
{
    /** code => [label, minor scale, symbol] */
    public const CURRENCIES = [
        'AED' => ['label' => 'UAE Dirham',      'scale' => 2, 'symbol' => 'AED'],
        'USD' => ['label' => 'US Dollar',       'scale' => 2, 'symbol' => '$'],
        'EUR' => ['label' => 'Euro',            'scale' => 2, 'symbol' => '€'],
        'GBP' => ['label' => 'Pound Sterling',  'scale' => 2, 'symbol' => '£'],
        'SAR' => ['label' => 'Saudi Riyal',     'scale' => 2, 'symbol' => 'SAR'],
        'INR' => ['label' => 'Indian Rupee',    'scale' => 2, 'symbol' => '₹'],
        'PKR' => ['label' => 'Pakistani Rupee', 'scale' => 2, 'symbol' => '₨'],
    ];

    public const DEFAULT = 'AED';
    /** Quantities are stored as DECIMAL(12,3) — 3 fractional digits, i.e. thousandths. */
    public const QTY_SCALE = 3;
    /** Hard ceiling for a single monetary value (99,999,999.99) — keeps int maths inside 64-bit range. */
    public const MAX_MINOR = 9_999_999_999;
    public const MAX_QTY_THOUSANDTHS = 1_000_000_000; // 1,000,000.000

    public static function codes(): array { return array_keys(self::CURRENCIES); }
    public static function isSupported(string $code): bool { return isset(self::CURRENCIES[strtoupper($code)]); }
    public static function scale(string $code): int { return self::CURRENCIES[strtoupper($code)]['scale'] ?? 2; }
    public static function symbol(string $code): string { return self::CURRENCIES[strtoupper($code)]['symbol'] ?? strtoupper($code); }
    public static function label(string $code): string
    {
        $c = self::CURRENCIES[strtoupper($code)] ?? null;
        return $c ? $c['label'] . ' (' . strtoupper($code) . ')' : strtoupper($code);
    }

    /** Normalise a user supplied currency code, or null when unsupported. */
    public static function currency(?string $raw): ?string
    {
        $c = strtoupper(trim((string)$raw));
        return self::isSupported($c) ? $c : null;
    }

    /**
     * Parse a human amount ("1,234.50", " 1234 ", "0.05") into minor units.
     * Returns null when the input is not a well formed amount with at most
     * `scale` decimals (never silently rounds user input like "1.234").
     */
    public static function parse(?string $raw, string $currency, bool $allowNegative = false): ?int
    {
        $scale = self::scale($currency);
        $s = trim((string)$raw);
        if ($s === '') return null;
        $s = str_replace([',', ' ', "\u{00A0}"], '', $s);
        $neg = false;
        if ($s[0] === '-') { $neg = true; $s = substr($s, 1); }
        elseif ($s[0] === '+') { $s = substr($s, 1); }
        if ($neg && !$allowNegative) return null;
        $pattern = '/^\d{1,12}(?:\.\d{1,' . $scale . '})?$/';
        if (!preg_match($pattern, $s)) return null;
        [$int, $frac] = array_pad(explode('.', $s, 2), 2, '');
        $frac = str_pad($frac, $scale, '0');
        $digits = ltrim(($int === '' ? '0' : $int) . $frac, '0');
        if ($digits === '') return 0;
        if (strlen($digits) > 18) return null;
        $minor = (int)$digits;
        if ($minor > self::MAX_MINOR) return null;
        return $neg ? -$minor : $minor;
    }

    /** Parse a decimal string into thousandths (quantity). */
    public static function parseQty(?string $raw): ?int
    {
        $s = trim((string)$raw);
        if ($s === '') return null;
        $s = str_replace([',', ' '], '', $s);
        $scale = self::QTY_SCALE;
        if (!preg_match('/^\d{1,10}(?:\.\d{1,' . $scale . '})?$/', $s)) return null;
        [$int, $frac] = array_pad(explode('.', $s, 2), 2, '');
        $digits = ltrim(($int === '' ? '0' : $int) . str_pad($frac, $scale, '0'), '0');
        if ($digits === '') return 0;
        $t = (int)$digits;
        return $t > self::MAX_QTY_THOUSANDTHS ? null : $t;
    }

    /** thousandths -> "12.500" */
    public static function qtyString(int $thousandths): string
    {
        $sign = $thousandths < 0 ? '-' : '';
        $a = abs($thousandths);
        return $sign . intdiv($a, 1000) . '.' . str_pad((string)($a % 1000), 3, '0', STR_PAD_LEFT);
    }

    /** 1499 -> "14.99" (no grouping, no symbol) — safe for HTML input values and SQL. */
    public static function decimal(int $minor, string $currency): string
    {
        $scale = self::scale($currency);
        return self::shiftString($minor, $scale, false);
    }

    /** 123456789 -> "1,234,567.89" */
    public static function formatPlain(int $minor, string $currency): string
    {
        return self::shiftString($minor, self::scale($currency), true);
    }

    /** "AED 1,234.56" / "$1,234.56" */
    public static function format(int $minor, string $currency): string
    {
        $code = strtoupper($currency);
        $sym = self::symbol($code);
        $num = self::formatPlain($minor, $code);
        return strlen($sym) > 1 ? $sym . ' ' . $num : $sym . $num;
    }

    private static function shiftString(int $minor, int $scale, bool $group): string
    {
        $neg = $minor < 0;
        $a = abs($minor);
        $div = 10 ** $scale;
        $int = intdiv($a, $div);
        $frac = $a % $div;
        $intStr = $group ? number_format($int) : (string)$int;
        $out = $scale > 0 ? $intStr . '.' . str_pad((string)$frac, $scale, '0', STR_PAD_LEFT) : $intStr;
        return $neg ? '-' . $out : $out;
    }

    /** Line total = unit (minor) × qty (thousandths), rounded half-up, integer only. */
    public static function lineTotal(int $unitMinor, int $qtyThousandths): int
    {
        // Divide before multiplying so valid large inputs cannot overflow PHP's integer range.
        $whole = intdiv($unitMinor, 1000) * $qtyThousandths;
        $remainder = ($unitMinor % 1000) * $qtyThousandths;
        return $whole + intdiv($remainder, 1000) + (($remainder % 1000) * 2 >= 1000 ? 1 : 0);
    }

    /** Round(value × basis points / 10000) half-up for non-negative values. */
    public static function percent(int $value, int $rateBp): int
    {
        if ($rateBp === 0 || $value === 0) return 0;
        $whole = intdiv($value, 10000) * $rateBp;
        $remainder = ($value % 10000) * $rateBp;
        return $whole + intdiv($remainder, 10000) + (($remainder % 10000) * 2 >= 10000 ? 1 : 0);
    }

    /**
     * Document totals from already-computed line totals.
     *
     * @param int[] $lineTotals minor units
     * @return array{subtotal_minor:int,discount_minor:int,taxable_minor:int,tax_rate_bp:int,tax_minor:int,total_minor:int}
     */
    public static function totals(array $lineTotals, int $discountMinor, int $taxRateBp): array
    {
        $subtotal = 0;
        foreach ($lineTotals as $t) $subtotal += (int)$t;
        if ($discountMinor < 0 || $discountMinor > $subtotal) {
            throw new \InvalidArgumentException('Discount must be between 0 and the subtotal.');
        }
        if ($taxRateBp < 0 || $taxRateBp > 5000) {
            throw new \InvalidArgumentException('Tax rate must be between 0 and 5000 basis points (0–50%).');
        }
        $taxable = $subtotal - $discountMinor;
        $tax = self::percent($taxable, $taxRateBp);
        return [
            'subtotal_minor'  => $subtotal,
            'discount_minor'  => $discountMinor,
            'taxable_minor'   => $taxable,
            'tax_rate_bp'     => $taxRateBp,
            'tax_minor'       => $tax,
            'total_minor'     => $taxable + $tax,
        ];
    }
}
