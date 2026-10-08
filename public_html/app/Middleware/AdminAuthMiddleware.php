<?php
class AdminAuthMiddleware
{
    public static function handle(): void
    {
        // Admin pages are personalized/dynamic and must never be cached by LiteSpeed/CDN —
        // without this, a cache layer that ignores query strings (e.g. ?tab=appearance)
        // can serve the same cached tab content regardless of which tab was actually requested.
        header('Cache-Control: no-store, no-cache, must-revalidate, private');
        header('X-LiteSpeed-Cache-Control: no-cache');
        header('Pragma: no-cache');

        if (!isAdminLoggedIn()) {
            flash('error', 'يجب تسجيل الدخول أولاً');
            header('Location: ' . adminUrl('login'));
            exit;
        }

        // The session stores a snapshot of the admin_users row taken at login. Without refreshing
        // it here, a role/permission change (or deactivation) made by a superadmin while this
        // admin is already logged in would silently NOT take effect until they log out and back
        // in — which looks exactly like "permissions aren't working". Re-check against the DB
        // on every request instead, and end the session immediately if the account no longer
        // exists or was deactivated.
        $sessionUser = adminUser();
        $fresh = Database::fetch("SELECT id,name,email,role,permissions,is_active FROM admin_users WHERE id=?", [(int)($sessionUser['id'] ?? 0)]);
        if (!$fresh || !$fresh['is_active']) {
            Session::remove('admin_user');
            flash('error', 'تم إيقاف هذا الحساب أو حذفه — تواصل مع المدير العام');
            header('Location: ' . adminUrl('login'));
            exit;
        }
        Session::set('admin_user', $fresh);
    }
}
