<?php
class MenuController extends Controller
{
    public function __construct() { AdminAuthMiddleware::handle(); requireAdminPermission('marketing'); }

    public function index(): void
    {
        $all = Database::fetchAll("SELECT * FROM nav_menu_items ORDER BY location, parent_id IS NULL DESC, sort_order, id");
        // Build a simple tree: top-level items with their children nested
        $tree = ['main' => [], 'footer' => []];
        $byParent = [];
        foreach ($all as $item) {
            $byParent[$item['parent_id'] ?? 0][] = $item;
        }
        foreach ($all as $item) {
            if (empty($item['parent_id'])) {
                $item['children'] = $byParent[$item['id']] ?? [];
                $tree[$item['location']][] = $item;
            }
        }
        $topLevelItems = array_filter($all, fn($i) => empty($i['parent_id']));
        $this->view('admin.menu.index', compact('tree', 'topLevelItems'));
    }

    public function store(): void
    {
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect(adminUrl('menu')); }
        $maxSort = (int)(Database::fetch("SELECT MAX(sort_order) m FROM nav_menu_items WHERE location=? AND parent_id " . ($this->post('parent_id') ? '=?' : 'IS NULL'),
            $this->post('parent_id') ? [$this->post('location','main'), (int)$this->post('parent_id')] : [$this->post('location','main')]
        )['m'] ?? 0);

        Database::insert(
            "INSERT INTO nav_menu_items(parent_id,label,url,icon,location,sort_order,is_active,created_at) VALUES(?,?,?,?,?,?,?,NOW())",
            [
                $this->post('parent_id') ?: null,
                trim($this->post('label','')),
                trim($this->post('url','')),
                trim($this->post('icon','')) ?: null,
                $this->post('location','main'),
                $maxSort + 1,
                isset($_POST['is_active']) ? 1 : 0,
            ]
        );
        flashSuccess('تم إضافة العنصر ✅');
        $this->redirect(adminUrl('menu'));
    }

    public function update(string $id): void
    {
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect(adminUrl('menu')); }
        Database::execute(
            "UPDATE nav_menu_items SET parent_id=?, label=?, url=?, icon=?, location=?, is_active=? WHERE id=?",
            [
                $this->post('parent_id') ?: null,
                trim($this->post('label','')),
                trim($this->post('url','')),
                trim($this->post('icon','')) ?: null,
                $this->post('location','main'),
                isset($_POST['is_active']) ? 1 : 0,
                (int)$id,
            ]
        );
        flashSuccess('تم التحديث ✅');
        $this->redirect(adminUrl('menu'));
    }

    public function delete(string $id): void
    {
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect(adminUrl('menu')); }
        Database::execute("DELETE FROM nav_menu_items WHERE id=? OR parent_id=?", [(int)$id, (int)$id]);
        flashSuccess('تم الحذف');
        $this->redirect(adminUrl('menu'));
    }

    /** Move an item up or down within its sibling group (same parent + location) */
    public function reorder(string $id): void
    {
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect(adminUrl('menu')); }
        $dir = $this->post('direction', 'up'); // up | down
        $item = Database::fetch("SELECT * FROM nav_menu_items WHERE id=?", [(int)$id]);
        if (!$item) { $this->redirect(adminUrl('menu')); }

        $where = $item['parent_id'] ? "parent_id=?" : "parent_id IS NULL";
        $params = $item['parent_id'] ? [$item['parent_id']] : [];
        $where .= " AND location=?";
        $params[] = $item['location'];

        $siblings = Database::fetchAll("SELECT id, sort_order FROM nav_menu_items WHERE $where ORDER BY sort_order, id", $params);
        $idx = array_search($item['id'], array_column($siblings, 'id'));
        $swapIdx = $dir === 'up' ? $idx - 1 : $idx + 1;

        if ($idx !== false && isset($siblings[$swapIdx])) {
            Database::execute("UPDATE nav_menu_items SET sort_order=? WHERE id=?", [$siblings[$swapIdx]['sort_order'], $item['id']]);
            Database::execute("UPDATE nav_menu_items SET sort_order=? WHERE id=?", [$item['sort_order'], $siblings[$swapIdx]['id']]);
        }
        $this->redirect(adminUrl('menu'));
    }
}
