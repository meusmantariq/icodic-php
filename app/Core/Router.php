<?php
declare(strict_types=1);
namespace App\Core;

final class Router
{
    private array $routes = ['GET' => [], 'POST' => []];
    public function get(string $p, callable|array $h): void { $this->add('GET', $p, $h); }
    public function post(string $p, callable|array $h): void { $this->add('POST', $p, $h); }
    public function any(string $p, callable|array $h): void { $this->add('GET', $p, $h); $this->add('POST', $p, $h); }
    private function add(string $method, string $pattern, callable|array $handler): void
    {
        $params = [];
        $regex = preg_replace_callback('/\{(\w+)(?::([^}]+))?\}/', function ($m) use (&$params) { $params[] = $m[1]; return '(' . ($m[2] ?? '[^/]+') . ')'; }, $pattern);
        $this->routes[$method][] = ['pattern' => '#^' . $regex . '$#u', 'handler' => $handler, 'params' => $params];
    }
    public function dispatch(Request $req): void
    {
        $method = $req->method === 'HEAD' ? 'GET' : $req->method;
        foreach ($this->routes[$method] ?? [] as $route) {
            if (preg_match($route['pattern'], $req->path, $m)) {
                array_shift($m);
                $handler = $route['handler'];
                if (is_array($handler)) { [$class, $action] = $handler; $handler = [new $class(), $action]; }
                echo call_user_func($handler, $req, ...$m) ?? '';
                return;
            }
        }
        Response::notFound();
    }
}
