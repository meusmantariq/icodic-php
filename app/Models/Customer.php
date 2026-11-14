<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Database as DB;
use App\Core\Money;

final class Customer
{
    public const STATUSES = ['active', 'inactive'];

    /** Whitelisted writable columns — never bind user-controlled keys directly. */
    private const COLUMNS = ['code', 'company_name', 'contact_name', 'email', 'phone', 'trn', 'website',
        'address_line1', 'address_line2', 'city', 'country', 'default_currency', 'status', 'notes'];

    public static function find(int $id): ?array
    {
        return DB::fetch('SELECT * FROM customers WHERE id = ?', [$id]);
    }

    public static function findByCode(string $code): ?array
    {
        return DB::fetch('SELECT * FROM customers WHERE code = ?', [strtoupper(trim($code))]);
    }

    /** @return array<int,array> */
    public static function all(string $q = '', string $status = '', int $limit = 500): array
    {
        $where = []; $params = [];
        if ($q !== '') {
            $like = '%' . $q . '%';
            $where[] = '(company_name LIKE ? OR code LIKE ? OR contact_name LIKE ? OR email LIKE ? OR phone LIKE ?)';
            array_push($params, $like, $like, $like, $like, $like);
        }
        if ($status !== '' && in_array($status, self::STATUSES, true)) { $where[] = 'status = ?'; $params[] = $status; }
        $sql = 'SELECT * FROM customers' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY company_name ASC LIMIT ' . (int)$limit;
        return DB::fetchAll($sql, $params);
    }

    /** Paged listing for the admin table: [rows, total]. */
    public static function paginate(string $q, string $status, int $page, int $per = 25): array
    {
        $where = []; $params = [];
        if ($q !== '') {
            $like = '%' . $q . '%';
            $where[] = '(company_name LIKE ? OR code LIKE ? OR contact_name LIKE ? OR email LIKE ? OR phone LIKE ?)';
            array_push($params, $like, $like, $like, $like, $like);
        }
        if ($status !== '' && in_array($status, self::STATUSES, true)) { $where[] = 'status = ?'; $params[] = $status; }
        $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
        $total = (int)DB::scalar('SELECT COUNT(*) FROM customers' . $whereSql, $params);
        $page = max(1, $page);
        $rows = DB::fetchAll('SELECT * FROM customers' . $whereSql . ' ORDER BY company_name ASC LIMIT ' . $per . ' OFFSET ' . (($page - 1) * $per), $params);
        return [$rows, $total];
    }

    /** Active customers for pickers. */
    public static function options(bool $includeInactive = false): array
    {
        return DB::fetchAll('SELECT id, code, company_name, contact_name, default_currency, status FROM customers'
            . ($includeInactive ? '' : " WHERE status = 'active'") . ' ORDER BY company_name ASC');
    }

    public static function insert(array $data): int
    {
        $row = self::filter($data);
        $cols = array_keys($row);
        $sql = 'INSERT INTO customers (`' . implode('`, `', $cols) . '`) VALUES (' . implode(', ', array_fill(0, count($cols), '?')) . ')';
        DB::query($sql, array_values($row));
        return DB::lastId();
    }

    public static function update(int $id, array $data): void
    {
        $row = self::filter($data);
        if (!$row) return;
        $set = implode(', ', array_map(fn($c) => "`$c` = ?", array_keys($row)));
        DB::query("UPDATE customers SET $set WHERE id = ?", [...array_values($row), $id]);
    }

    /** Refuse to delete a customer that still owns documents (FK would reject anyway). */
    public static function canDelete(int $id): bool
    {
        return (int)DB::scalar('SELECT COUNT(*) FROM sales_documents WHERE customer_id = ?', [$id]) === 0;
    }

    public static function delete(int $id): void
    {
        if (!self::canDelete($id)) throw new \RuntimeException('This customer has sales documents and cannot be deleted. Set it to inactive instead.');
        DB::query('DELETE FROM customers WHERE id = ?', [$id]);
    }

    /** Suggest the next unused CUS-#### code. */
    public static function suggestCode(): string
    {
        $max = (int)DB::scalar("SELECT COALESCE(MAX(CAST(SUBSTRING(code, 5) AS UNSIGNED)), 0) FROM customers WHERE code REGEXP '^CUS-[0-9]+$'");
        return 'CUS-' . str_pad((string)($max + 1), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Validate + normalise input. Returns [data, errors].
     *
     * @param array    $in     raw request input
     * @param int|null $ignoreId customer id being updated (unique checks skip it)
     */
    public static function validate(array $in, ?int $ignoreId = null): array
    {
        $data = []; $errors = [];
        $str = fn(string $k, int $max) => mb_substr(trim((string)($in[$k] ?? '')), 0, $max);

        $code = strtoupper($str('code', 20));
        if ($code === '') $code = self::suggestCode();
        if (!preg_match('/^[A-Z0-9][A-Z0-9._-]{1,19}$/', $code)) $errors[] = 'Customer code must be 2–20 characters: A–Z, 0–9, dot, dash or underscore.';
        $data['code'] = $code;

        $company = $str('company_name', 190);
        if ($company === '') $errors[] = 'Company name is required.';
        $data['company_name'] = $company;

        $data['contact_name'] = $str('contact_name', 190) ?: null;
        $data['phone'] = $str('phone', 40) ?: null;
        $data['trn'] = $str('trn', 60) ?: null;
        $data['city'] = $str('city', 120) ?: null;
        $data['country'] = $str('country', 120) ?: null;
        $data['address_line1'] = $str('address_line1', 190) ?: null;
        $data['address_line2'] = $str('address_line2', 190) ?: null;
        $data['notes'] = mb_substr(trim((string)($in['notes'] ?? '')), 0, 2000) ?: null;

        $email = strtolower($str('email', 190));
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email address is not valid.';
        $data['email'] = $email ?: null;

        $website = $str('website', 190);
        if ($website !== '' && !preg_match('~^https?://[^\s<>"]+$~i', $website)) $errors[] = 'Website must start with http:// or https://';
        $data['website'] = $website ?: null;

        $currency = Money::currency($in['default_currency'] ?? '');
        if ($currency === null) $errors[] = 'Choose a supported default currency.';
        $data['default_currency'] = $currency ?? Money::DEFAULT;

        $status = strtolower($str('status', 20)) ?: 'active';
        if (!in_array($status, self::STATUSES, true)) $errors[] = 'Status must be active or inactive.';
        $data['status'] = in_array($status, self::STATUSES, true) ? $status : 'active';

        if ($code !== '') {
            $dupe = DB::fetch('SELECT id FROM customers WHERE code = ?' . ($ignoreId ? ' AND id <> ?' : ''), $ignoreId ? [$code, $ignoreId] : [$code]);
            if ($dupe) $errors[] = "Customer code {$code} is already used.";
        }
        return [$data, $errors];
    }

    private static function filter(array $data): array
    {
        return array_intersect_key($data, array_flip(self::COLUMNS));
    }
}
