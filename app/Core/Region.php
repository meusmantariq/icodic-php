<?php
declare(strict_types=1);
namespace App\Core;

final class Region
{
    public const CODES = ['UK', 'UAE', 'IN', 'PK'];
    public const DEFAULTS = [
        'UK'  => ['code' => 'UK',  'name' => 'United Kingdom',       'city' => 'Westgate-on-Sea', 'tz' => 'Europe/London', 'phone' => '+44 7441 428452',  'email' => 'info@quantumnexis.si', 'contact' => 'Usman Tariq', 'role' => 'Founder & Lead Solutions Architect', 'address' => '109 Sea Rd, Westgate-on-Sea CT8 8QE, United Kingdom'],
        'UAE' => ['code' => 'UAE', 'name' => 'United Arab Emirates', 'city' => 'Dubai',           'tz' => 'Asia/Dubai',    'phone' => '+971 54 351 7933', 'email' => 'uae@quantumnexis.si',  'contact' => 'Usman Tariq', 'role' => 'Founder & Lead Solutions Architect, MENA', 'address' => 'Sheikh Zayed Road, One Central Tower, Level 21, Dubai, UAE'],
        'IN'  => ['code' => 'IN',  'name' => 'India',                'city' => 'New Delhi',       'tz' => 'Asia/Kolkata',  'phone' => '+91 11 4555 0199', 'email' => 'in@quantumnexis.si',   'contact' => 'Arjun Mehta', 'role' => 'Country Manager, India', 'address' => 'Sector 32, Cyber City, Tower C, 6th Floor, Gurugram 122002, India'],
        'PK'  => ['code' => 'PK',  'name' => 'Pakistan',             'city' => 'Karachi',         'tz' => 'Asia/Karachi',  'phone' => '+92 21 5555 0177', 'email' => 'pk@quantumnexis.si',   'contact' => 'Bilal Ahmed', 'role' => 'Country Manager, Pakistan', 'address' => 'Shahrah-e-Faisal, Business Centre, 9th Floor, Karachi 75400, Pakistan'],
    ];
    public const CURRENCIES = [
        'UK' => ['code' => 'GBP', 'symbol' => '£',   'rate' => 0.79],
        'UAE'=> ['code' => 'AED', 'symbol' => 'AED', 'rate' => 3.67],
        'IN' => ['code' => 'INR', 'symbol' => '₹',   'rate' => 83],
        'PK' => ['code' => 'PKR', 'symbol' => '₨',   'rate' => 278],
    ];
    private const COUNTRY_TO_REGION = ['GB' => 'UK', 'US' => 'UK', 'CA' => 'UK', 'AE' => 'UAE', 'IN' => 'IN', 'PK' => 'PK'];
    private static ?string $code = null;
    private static ?array $offices = null;

    public static function code(): string
    {
        if (self::$code) return self::$code;
        $c = $_COOKIE['qnexis_region'] ?? null;
        if (!$c || !in_array($c, self::CODES, true)) {
            $cf = strtoupper($_SERVER['HTTP_CF_IPCOUNTRY'] ?? '');
            $c = self::COUNTRY_TO_REGION[$cf] ?? Config::get('site.default_region', 'UAE');
        }
        return self::$code = $c;
    }
    public static function set(string $code): void
    {
        if (!in_array($code, self::CODES, true)) return;
        self::$code = $code;
        setcookie('qnexis_region', $code, ['expires' => time() + 86400 * 365, 'path' => '/', 'samesite' => 'Lax', 'httponly' => false]);
    }
    /** Offices merged: DB rows override hard-coded defaults (same as the original app). */
    public static function all(): array
    {
        if (self::$offices !== null) return self::$offices;
        $rows = [];
        try { foreach (Database::fetchAll('SELECT * FROM offices ORDER BY sort_order ASC') as $o) $rows[$o['code']] = $o; } catch (\Throwable) {}
        $out = [];
        foreach (self::DEFAULTS as $code => $d) {
            $o = $rows[$code] ?? [];
            $out[$code] = [
                'code' => $code, 'city' => $o['city'] ?? $d['city'], 'name' => $o['name'] ?? $d['name'], 'tz' => $o['tz'] ?? $d['tz'],
                'phone' => $o['phone'] ?? $d['phone'], 'email' => $o['email'] ?? $d['email'], 'contact' => $o['contact_name'] ?? $d['contact'],
                'role' => $o['role'] ?? $d['role'], 'address' => $o['address'] ?? $d['address'], 'country' => $o['country'] ?? $d['name'],
            ];
        }
        return self::$offices = $out;
    }
    public static function current(): array { return self::all()[self::code()] ?? self::all()['UK']; }
    public static function formatPrice(float $usd, ?string $code = null): string
    {
        $c = self::CURRENCIES[$code ?? self::code()] ?? ['code' => 'USD', 'symbol' => '$', 'rate' => 1];
        return $c['symbol'] . ' ' . number_format(round($usd * $c['rate']));
    }
    public static function period(): string { $p = $_COOKIE['qnexis_period'] ?? 'monthly'; return $p === 'yearly' ? 'yearly' : 'monthly'; }
    public static function setPeriod(string $p): void { if (in_array($p, ['monthly', 'yearly'], true)) setcookie('qnexis_period', $p, ['expires' => time() + 86400 * 365, 'path' => '/', 'samesite' => 'Lax']); }
}
