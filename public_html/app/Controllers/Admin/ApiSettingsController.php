<?php
class ApiSettingsController extends Controller
{
    public function __construct() { AdminAuthMiddleware::handle(); requireAdminPermission('settings'); }

    public function index(): void
    {
        $keys = Database::fetchAll("SELECT * FROM api_keys ORDER BY created_at DESC");
        $this->view('admin.settings.api', compact('keys'));
    }

    public function store(): void
    {
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect(adminUrl('settings/api')); }
        $label = trim($this->post('label', ''));
        if (!$label) { flashError('اكتب اسم/وصف للمفتاح'); $this->redirect(adminUrl('settings/api')); }

        $key = 'nt_' . bin2hex(random_bytes(24));
        Database::insert(
            "INSERT INTO api_keys(label,api_key,is_active,created_by,created_at) VALUES(?,?,1,?,NOW())",
            [$label, $key, adminUser()['id'] ?? null]
        );
        flashSuccess('تم إنشاء المفتاح ✅ — انسخه دلوقتي، مش هيظهر تاني كامل بعد كده');
        Session::set('_new_api_key', $key);
        $this->redirect(adminUrl('settings/api'));
    }

    public function toggle(string $id): void
    {
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect(adminUrl('settings/api')); }
        Database::execute("UPDATE api_keys SET is_active = NOT is_active WHERE id=?", [(int)$id]);
        $this->redirect(adminUrl('settings/api'));
    }

    public function delete(string $id): void
    {
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect(adminUrl('settings/api')); }
        Database::execute("DELETE FROM api_keys WHERE id=?", [(int)$id]);
        flashSuccess('تم حذف المفتاح');
        $this->redirect(adminUrl('settings/api'));
    }
}
