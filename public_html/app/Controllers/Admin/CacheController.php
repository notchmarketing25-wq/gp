<?php
class CacheController extends Controller
{
    public function __construct() { AdminAuthMiddleware::handle(); requireAdminPermission('settings'); }

    public function clear(): void
    {
        $actions = [];

        // 1. Reset PHP's opcode cache (OPcache) — clears cached compiled PHP bytecode
        if (function_exists('opcache_reset')) {
            opcache_reset();
            $actions[] = 'OPcache';
        }

        // 2. Reset any static in-memory app-level caches
        try { SettingModel::resetCache(); $actions[] = 'إعدادات الموقع'; } catch (\Throwable $e) {}

        // 3. Ask LiteSpeed's cache layer to purge everything for this site
        //    (LiteSpeed officially supports this response header as a purge trigger)
        header('X-LiteSpeed-Purge: *');
        header('X-LiteSpeed-Cache-Control: no-cache');
        $actions[] = 'LiteSpeed Cache';

        $msg = 'تم تنظيف الكاش ✅ (' . implode('، ', $actions) . ')';
        flashSuccess($msg);
        $this->redirect($_SERVER['HTTP_REFERER'] ?? adminUrl());
    }
}
