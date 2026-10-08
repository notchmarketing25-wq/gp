<?php
class Controller
{
    protected function view(string $path, array $data = []): void
    {
        extract($data);
        $file = APP_PATH . '/Views/' . str_replace('.', '/', $path) . '.php';
        if (!file_exists($file)) {
            die("View not found: {$path}");
        }
        require_once $file;
    }

    protected function json(array $data, int $status = 200): void
    {
        // Strip any accidental output (PHP warnings/notices, stray echo, BOM, etc.)
        // that would otherwise corrupt the JSON and break client-side parsing.
        while (ob_get_level() > 0) { ob_end_clean(); }
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('X-LiteSpeed-Cache-Control: no-cache');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    protected function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }

    protected function redirectBack(): void
    {
        $this->redirect($_SERVER['HTTP_REFERER'] ?? APP_URL);
    }

    protected function isPost(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    protected function isGet(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'GET';
    }

    protected function input(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    protected function post(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $default;
    }

    protected function get(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    protected function abort(int $code = 404): void
    {
        http_response_code($code);
        exit;
    }

    protected function adminUrl(string $path = ''): string
    {
        return APP_URL . '/' . ADMIN_PREFIX . ($path ? '/' . ltrim($path, '/') : '');
    }

    protected function url(string $path = ''): string
    {
        return APP_URL . ($path ? '/' . ltrim($path, '/') : '');
    }
}
