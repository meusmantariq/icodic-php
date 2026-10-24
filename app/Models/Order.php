<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Database as DB;

final class Order
{
    public const STATUSES = ['new', 'confirmed', 'rejected', 'processing', 'dispatched', 'delivered', 'completed', 'cancelled'];

    public static function generateId(): string
    {
        do {
            $id = 'IC-' . strtoupper(substr(base_convert((string)(int)(microtime(true) * 1000), 10, 36), -4)) . strtoupper(bin2hex(random_bytes(1)));
            $id = substr($id, 0, 9);
        } while (DB::scalar('SELECT COUNT(*) FROM orders WHERE order_id = ?', [$id]) > 0);
        return $id;
    }
    public static function track(string $orderId): ?array
    {
        $o = DB::fetch('SELECT order_id, product_name, quantity, billing_period, status, comment, created_at FROM orders WHERE order_id = ?', [strtoupper(trim($orderId))]);
        return $o ?: null;
    }
}
