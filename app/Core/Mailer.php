<?php
declare(strict_types=1);
namespace App\Core;

/** Minimal mailer: PHP mail(), SMTP (STARTTLS/SSL) or log. No Composer dependency. */
final class Mailer
{
    public static function send(string $to, string $subject, string $text, ?string $html = null): bool
    {
        $cfg = Config::get('mail', []);
        $driver = $cfg['driver'] ?? 'mail';
        $from = $cfg['from_email'] ?? 'noreply@localhost';
        $fromName = $cfg['from_name'] ?? 'iCodic';
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) return false;
        $html ??= nl2br(e($text));
        $boundary = 'b' . bin2hex(random_bytes(12));
        $headers = [
            'From: ' . self::encodeName($fromName) . " <$from>",
            "Reply-To: $from",
            'MIME-Version: 1.0',
            "Content-Type: multipart/alternative; boundary=\"$boundary\"",
            'X-Mailer: iCodic-PHP',
        ];
        $body = "--$boundary\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\n\r\n$text\r\n"
              . "--$boundary\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\n\r\n" . self::wrapHtml($html, $subject) . "\r\n--$boundary--\r\n";
        try {
            if ($driver === 'log') { self::log($to, $subject, $text); return true; }
            if ($driver === 'smtp') return self::smtp($cfg['smtp'] ?? [], $from, $to, self::encodeSubject($subject), implode("\r\n", $headers), $body);
            return @mail($to, self::encodeSubject($subject), $body, implode("\r\n", $headers));
        } catch (\Throwable $e) { error_log('Mail failed: ' . $e->getMessage()); return false; }
    }
    public static function notifyAdmin(string $subject, string $text): bool
    {
        $to = Config::get('mail.admin_email') ?: (\App\Models\Repo::settings()['admin_email'] ?? '');
        return $to ? self::send($to, $subject, $text) : false;
    }
    private static function encodeSubject(string $s): string { return '=?UTF-8?B?' . base64_encode($s) . '?='; }
    private static function encodeName(string $s): string { return preg_match('/[^\x20-\x7e]/', $s) ? self::encodeSubject($s) : '"' . addslashes($s) . '"'; }
    private static function wrapHtml(string $inner, string $title): string
    {
        return '<!doctype html><html><body style="font-family:Inter,Arial,sans-serif;background:#f7f8fa;padding:24px;color:#1f2937">'
            . '<div style="max-width:600px;margin:auto;background:#fff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden">'
            . '<div style="height:4px;background:linear-gradient(90deg,#5b4ee6,#3b63d6,#239a6c)"></div>'
            . '<div style="padding:24px"><h2 style="margin:0 0 12px;font-size:18px">' . e($title) . '</h2><div style="font-size:14px;line-height:1.6">' . $inner . '</div>'
            . '<p style="margin-top:24px;font-size:11px;color:#6b7280">iCodic · UK · UAE · India · Pakistan</p></div></div></body></html>';
    }
    private static function log(string $to, string $subject, string $text): void
    {
        @mkdir(BASE_PATH . '/storage/logs', 0755, true);
        file_put_contents(BASE_PATH . '/storage/logs/mail.log', date('c') . " TO: $to | $subject\n$text\n---\n", FILE_APPEND);
    }
    private static function smtp(array $s, string $from, string $to, string $subject, string $headers, string $body): bool
    {
        $host = $s['host'] ?? 'localhost'; $port = (int)($s['port'] ?? 587); $enc = $s['encryption'] ?? 'tls';
        $remote = ($enc === 'ssl' ? 'ssl://' : '') . $host . ':' . $port;
        $fp = @stream_socket_client($remote, $errno, $errstr, 20);
        if (!$fp) throw new \RuntimeException("SMTP connect failed: $errstr");
        $read = function () use ($fp): string { $r = ''; while ($line = fgets($fp, 515)) { $r .= $line; if (isset($line[3]) && $line[3] === ' ') break; } return $r; };
        $cmd = function (string $c, array $ok) use ($fp, $read): string { fwrite($fp, $c . "\r\n"); $r = $read(); if (!in_array((int)substr($r, 0, 3), $ok, true)) throw new \RuntimeException("SMTP error after '$c': $r"); return $r; };
        $read();
        $cmd('EHLO ' . ($_SERVER['SERVER_NAME'] ?? 'localhost'), [250]);
        if ($enc === 'tls') {
            $cmd('STARTTLS', [220]);
            if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) throw new \RuntimeException('TLS negotiation failed');
            $cmd('EHLO ' . ($_SERVER['SERVER_NAME'] ?? 'localhost'), [250]);
        }
        if (!empty($s['username'])) {
            $cmd('AUTH LOGIN', [334]); $cmd(base64_encode($s['username']), [334]); $cmd(base64_encode($s['password'] ?? ''), [235]);
        }
        $cmd("MAIL FROM:<$from>", [250]); $cmd("RCPT TO:<$to>", [250, 251]); $cmd('DATA', [354]);
        $data = "To: <$to>\r\nSubject: $subject\r\nDate: " . date('r') . "\r\n$headers\r\n\r\n" . preg_replace('/^\./m', '..', $body);
        $cmd($data . "\r\n.", [250]); $cmd('QUIT', [221]); fclose($fp);
        return true;
    }
}
