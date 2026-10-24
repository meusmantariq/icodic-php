<?php
declare(strict_types=1);
namespace App\Core;

final class Request
{
    public string $method; public string $path; public array $query; public array $post; public array $files; private ?array $json = null;

    public function __construct()
    {
        $this->method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $base = base_path_prefix();
        if ($base && str_starts_with($path, $base)) $path = substr($path, strlen($base));
        $path = '/' . trim($path, '/');
        $this->path = $path === '' ? '/' : $path;
        $this->query = $_GET; $this->post = $_POST; $this->files = $_FILES;
        if (str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')) {
            $this->json = json_decode(file_get_contents('php://input') ?: '[]', true) ?: [];
        }
    }
    public function input(string $key, mixed $default = null): mixed
    {
        if ($this->json !== null && array_key_exists($key, $this->json)) return $this->json[$key];
        if (array_key_exists($key, $this->post)) return $this->post[$key];
        return $this->query[$key] ?? $default;
    }
    public function str(string $key, string $default = ''): string { $v = $this->input($key, $default); return is_scalar($v) ? trim((string)$v) : $default; }
    public function int(string $key, int $default = 0): int { $v = $this->input($key); return is_numeric($v) ? (int)$v : $default; }
    public function all(): array { return array_merge($this->query, $this->post, $this->json ?? []); }
    public function header(string $name): ?string { return $_SERVER['HTTP_' . strtoupper(str_replace('-', '_', $name))] ?? null; }
    public function isPost(): bool { return $this->method === 'POST'; }
    public function wantsJson(): bool { return $this->json !== null || str_contains($this->header('Accept') ?? '', 'application/json') || $this->header('X-Requested-With') === 'XMLHttpRequest'; }
    public function ip(): string { return substr((string)($_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 45); }
}
