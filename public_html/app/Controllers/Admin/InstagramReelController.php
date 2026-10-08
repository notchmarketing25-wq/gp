<?php
class InstagramReelController extends Controller
{
    public function __construct() { AdminAuthMiddleware::handle(); requireAdminPermission('marketing'); }

    public function index(): void
    {
        $reels = Database::fetchAll("SELECT * FROM instagram_reels ORDER BY sort_order, id");
        $this->view('admin.instagram.index', compact('reels'));
    }

    public function store(): void
    {
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect(adminUrl('instagram')); }
        $url = trim($this->post('url',''));
        if (!$url || !str_contains($url, 'instagram.com')) {
            flashError('من فضلك أدخل رابط انستجرام صحيح (بوست أو ريل)');
            $this->redirect(adminUrl('instagram'));
        }
        $maxSort = (int)(Database::fetch("SELECT MAX(sort_order) m FROM instagram_reels")['m'] ?? 0);
        Database::insert(
            "INSERT INTO instagram_reels(url,caption,sort_order,is_active,created_at) VALUES(?,?,?,1,NOW())",
            [$url, trim($this->post('caption','')) ?: null, $maxSort + 1]
        );
        flashSuccess('تم إضافة الفيديو ✅ — هيظهر في الصفحة الرئيسية');
        $this->redirect(adminUrl('instagram'));
    }

    public function delete(string $id): void
    {
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect(adminUrl('instagram')); }
        Database::execute("DELETE FROM instagram_reels WHERE id=?", [(int)$id]);
        flashSuccess('تم الحذف');
        $this->redirect(adminUrl('instagram'));
    }

    public function toggle(string $id): void
    {
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect(adminUrl('instagram')); }
        Database::execute("UPDATE instagram_reels SET is_active = NOT is_active WHERE id=?", [(int)$id]);
        $this->redirect(adminUrl('instagram'));
    }

    public function reorder(string $id): void
    {
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect(adminUrl('instagram')); }
        $dir = $this->post('direction', 'up');
        $item = Database::fetch("SELECT * FROM instagram_reels WHERE id=?", [(int)$id]);
        if (!$item) { $this->redirect(adminUrl('instagram')); }
        $siblings = Database::fetchAll("SELECT id, sort_order FROM instagram_reels ORDER BY sort_order, id");
        $idx = array_search($item['id'], array_column($siblings, 'id'));
        $swapIdx = $dir === 'up' ? $idx - 1 : $idx + 1;
        if ($idx !== false && isset($siblings[$swapIdx])) {
            Database::execute("UPDATE instagram_reels SET sort_order=? WHERE id=?", [$siblings[$swapIdx]['sort_order'], $item['id']]);
            Database::execute("UPDATE instagram_reels SET sort_order=? WHERE id=?", [$item['sort_order'], $siblings[$swapIdx]['id']]);
        }
        $this->redirect(adminUrl('instagram'));
    }
}
