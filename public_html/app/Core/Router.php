<?php
class Router
{
    private array $routes = [];

    public function get(string $path, $handler): void  { $this->add('GET',  $path, $handler); }
    public function post(string $path, $handler): void { $this->add('POST', $path, $handler); }
    public function any(string $path, $handler): void  { $this->add('ANY',  $path, $handler); }

    private function add(string $m, string $p, $h): void {
        $this->routes[] = ['method'=>$m, 'path'=>trim($p,'/'), 'handler'=>$h];
    }

    public function dispatch(): void {
        // LiteSpeed fix: read REQUEST_URI directly, not $_GET['url']
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        if (($pos = strpos($uri, '?')) !== false) $uri = substr($uri, 0, $pos);
        $url    = trim(rawurldecode($uri), '/');
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        foreach ($this->routes as $r) {
            $params = $this->match($r['path'], $url);
            if ($params === false) continue;
            if ($r['method'] !== 'ANY' && $r['method'] !== $method) continue;
            $this->call($r['handler'], $params);
            return;
        }
        $this->abort404();
    }

    private function match(string $route, string $url): array|false {
        if ($route === $url) return [];
        $pat = '#^' . preg_replace('/\{[a-zA-Z_]+\}/', '([^/]+)', $route) . '$#u';
        if (preg_match($pat, $url, $m)) { array_shift($m); return $m; }
        return false;
    }

    private function call($handler, array $params): void {
        if (is_callable($handler)) { call_user_func_array($handler, $params); return; }
        [$cls, $method] = explode('@', $handler);
        if (!class_exists($cls)) { $this->abort404(); return; }
        $obj = new $cls();
        if (!method_exists($obj, $method)) { $this->abort404(); return; }
        call_user_func_array([$obj, $method], $params);
    }

    private function abort404(): void {
        http_response_code(404);
        $f = defined('APP_PATH') ? APP_PATH.'/Views/store/pages/404.php' : null;
        if ($f && file_exists($f)) { require $f; }
        else echo '<h1 style="font-family:sans-serif;text-align:center;margin-top:20vh;color:#6d5acd">404 — الصفحة غير موجودة</h1>';
    }
}
