<?php
class ExpenseController extends Controller
{
    public function __construct() { AdminAuthMiddleware::handle(); requireAdminPermission('analytics'); }

    public function index(): void
    {
        $month = $this->get('month', date('Y-m'));
        $categoryId = $this->get('category', '');

        $where = ["DATE_FORMAT(expense_date,'%Y-%m')=?"]; $params = [$month];
        if ($categoryId) { $where[] = 'category_id=?'; $params[] = (int)$categoryId; }
        $w = implode(' AND ', $where);

        try {
            $expenses = Database::fetchAll(
                "SELECT e.*, c.name category_name, c.icon category_icon FROM expenses e
                 LEFT JOIN expense_categories c ON c.id=e.category_id
                 WHERE $w ORDER BY e.expense_date DESC, e.id DESC", $params
            );
            $categories = Database::fetchAll("SELECT * FROM expense_categories ORDER BY sort_order");
        } catch (\Throwable $e) {
            // The accounting_migration.sql file hasn't been run yet — show a clear, actionable
            // message instead of a blank white page from an uncaught PDOException.
            $this->view('admin.expenses.index', [
                'migrationMissing' => true,
                'expenses' => [], 'total' => 0, 'byCategory' => [], 'categories' => [],
                'month' => $month, 'categoryId' => $categoryId,
            ]);
            return;
        }

        $total = array_sum(array_column($expenses, 'amount'));

        $byCategory = [];
        foreach ($expenses as $e) {
            $key = $e['category_name'] ?: 'غير مصنّف';
            $byCategory[$key] = ($byCategory[$key] ?? 0) + (float)$e['amount'];
        }
        arsort($byCategory);

        $this->view('admin.expenses.index', compact('expenses', 'total', 'byCategory', 'categories', 'month', 'categoryId'));
    }

    public function store(): void
    {
        requireAdminPermission('analytics');
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect(adminUrl('expenses')); }
        $this->save(null);
    }

    public function update(string $id): void
    {
        requireAdminPermission('analytics');
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect(adminUrl('expenses')); }
        $this->save((int)$id);
    }

    private function save(?int $id): void
    {
        $amount = (float)$this->post('amount', 0);
        $date = $this->post('expense_date', date('Y-m-d'));
        if ($amount <= 0) { flashError('المبلغ لازم يكون أكبر من صفر'); $this->redirect(adminUrl('expenses')); }

        $receipt = null;
        if (!empty($_FILES['receipt_image']['name'])) {
            $up = uploadFile($_FILES['receipt_image'], 'expenses');
            if ($up) $receipt = $up;
        }

        $categoryId = $this->post('category_id') ?: null;
        $description = trim($this->post('description', ''));
        $vendor = trim($this->post('vendor', ''));

        if ($id) {
            $sql = "UPDATE expenses SET category_id=?,amount=?,description=?,vendor=?,expense_date=?";
            $params = [$categoryId, $amount, $description, $vendor, $date];
            if ($receipt) { $sql .= ",receipt_image=?"; $params[] = $receipt; }
            $sql .= " WHERE id=?"; $params[] = $id;
            try {
                Database::execute($sql, $params);
                flashSuccess('تم تحديث المصروف ✅');
            } catch (\Throwable $e) {
                flashError('نظام المصروفات لسه مش مفعّل — شغّل ملف accounting_migration.sql الأول');
            }
        } else {
            try {
                Database::insert(
                    "INSERT INTO expenses(category_id,amount,description,vendor,expense_date,receipt_image,created_by,created_at) VALUES(?,?,?,?,?,?,?,NOW())",
                    [$categoryId, $amount, $description, $vendor, $date, $receipt, adminUser()['id'] ?? null]
                );
                flashSuccess('تم إضافة المصروف ✅');
            } catch (\Throwable $e) {
                flashError('نظام المصروفات لسه مش مفعّل — شغّل ملف accounting_migration.sql الأول');
            }
        }
        $this->redirect(adminUrl('expenses?month='.date('Y-m', strtotime($date))));
    }

    public function delete(string $id): void
    {
        requireAdminPermission('analytics');
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect(adminUrl('expenses')); }
        Database::execute("DELETE FROM expenses WHERE id=?", [(int)$id]);
        flashSuccess('تم حذف المصروف');
        $this->redirect(adminUrl('expenses'));
    }
}
