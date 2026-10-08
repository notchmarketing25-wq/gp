<?php
/**
 * Formal stock-in/stock-out documents (إذن استلام/شراء + إذن صرف/بيع). A voucher can hold
 * multiple product lines; confirming it applies every line to stock_movements and updates
 * product.stock atomically, giving an auditable paper trail for every quantity change.
 */
class InventoryVoucherController extends Controller
{
    public function __construct() { AdminAuthMiddleware::handle(); requireAdminPermission('products'); }

    public function index(): void
    {
        $type = $this->get('type', '');
        $page = max(1, (int)$this->get('page', 1));
        $where = '1=1'; $params = [];
        if (in_array($type, ['receipt','issue'], true)) { $where = 'type=?'; $params[] = $type; }

        $total = (int)(Database::fetch("SELECT COUNT(*) c FROM inventory_vouchers WHERE $where", $params)['c'] ?? 0);
        $lastPage = max(1, (int)ceil($total / ITEMS_PER_PAGE));
        $page = min($page, $lastPage);
        $offset = ($page - 1) * ITEMS_PER_PAGE;

        $vouchers = Database::fetchAll(
            "SELECT v.*, (SELECT COUNT(*) FROM inventory_voucher_items WHERE voucher_id=v.id) items_count,
                    (SELECT COALESCE(SUM(qty),0) FROM inventory_voucher_items WHERE voucher_id=v.id) total_qty
             FROM inventory_vouchers v WHERE $where ORDER BY v.created_at DESC LIMIT " . ITEMS_PER_PAGE . " OFFSET $offset",
            $params
        );

        $this->view('admin.inventory.index', compact('vouchers', 'type', 'page', 'lastPage', 'total'));
    }

    public function create(): void
    {
        $type = in_array($this->get('type'), ['receipt','issue'], true) ? $this->get('type') : 'receipt';
        $this->view('admin.inventory.form', compact('type'));
    }

    public function store(): void
    {
        requireAdminPermission('products', 'create');
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect(adminUrl('inventory')); }

        $type = in_array($this->post('type'), ['receipt','issue'], true) ? $this->post('type') : 'receipt';
        $productIds = $_POST['product_id'] ?? [];
        $qtys       = $_POST['qty'] ?? [];
        $lineNotes  = $_POST['line_notes'] ?? [];

        $lines = [];
        foreach ($productIds as $i => $pid) {
            $pid = (int)$pid; $qty = (int)($qtys[$i] ?? 0);
            if ($pid > 0 && $qty > 0) {
                $lines[] = ['product_id' => $pid, 'qty' => $qty, 'notes' => trim($lineNotes[$i] ?? '')];
            }
        }
        if (empty($lines)) { flashError('ضيف منتج واحد على الأقل بكمية أكبر من صفر'); $this->redirect(adminUrl('inventory/create?type='.$type)); }

        $prefix = $type === 'receipt' ? 'REC' : 'ISS';
        $voucherNumber = $prefix . '-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));

        $voucherId = Database::insert(
            "INSERT INTO inventory_vouchers(voucher_number,type,reference_name,notes,status,created_by,created_at) VALUES(?,?,?,?,'draft',?,NOW())",
            [$voucherNumber, $type, trim($this->post('reference_name','')), trim($this->post('notes','')), adminUser()['id'] ?? null]
        );
        foreach ($lines as $l) {
            Database::insert("INSERT INTO inventory_voucher_items(voucher_id,product_id,qty,notes) VALUES(?,?,?,?)", [$voucherId, $l['product_id'], $l['qty'], $l['notes'] ?: null]);
        }

        flashSuccess("تم إنشاء الإذن {$voucherNumber} كمسودة ✅ — أكّده عشان يتطبق على المخزون");
        $this->redirect(adminUrl('inventory/'.$voucherId));
    }

    public function show(string $id): void
    {
        $voucher = Database::fetch("SELECT * FROM inventory_vouchers WHERE id=?", [(int)$id]);
        if (!$voucher) { flashError('الإذن غير موجود'); $this->redirect(adminUrl('inventory')); }
        $items = Database::fetchAll(
            "SELECT vi.*, p.name product_name, p.sku, p.thumbnail, p.stock current_stock
             FROM inventory_voucher_items vi JOIN products p ON p.id=vi.product_id WHERE vi.voucher_id=?",
            [(int)$id]
        );
        $this->view('admin.inventory.show', compact('voucher', 'items'));
    }

    /** Confirms a draft voucher — applies every line to stock + stock_movements atomically */
    public function confirm(string $id): void
    {
        requireAdminPermission('products', 'edit');
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect(adminUrl('inventory/'.$id)); }

        $voucher = Database::fetch("SELECT * FROM inventory_vouchers WHERE id=?", [(int)$id]);
        if (!$voucher) { flashError('الإذن غير موجود'); $this->redirect(adminUrl('inventory')); }
        if ($voucher['status'] !== 'draft') { flashError('الإذن ده اتأكد أو اتلغى بالفعل'); $this->redirect(adminUrl('inventory/'.$id)); }

        $items = Database::fetchAll("SELECT * FROM inventory_voucher_items WHERE voucher_id=?", [(int)$id]);
        $direction = $voucher['type'] === 'receipt' ? 1 : -1;

        foreach ($items as $item) {
            $product = Database::fetch("SELECT stock FROM products WHERE id=?", [$item['product_id']]);
            if (!$product) continue;
            $before = (int)$product['stock'];
            $after  = max(0, $before + ($direction * (int)$item['qty']));

            Database::execute("UPDATE products SET stock=? WHERE id=?", [$after, $item['product_id']]);
            Database::insert(
                "INSERT INTO stock_movements(product_id,type,qty,qty_before,qty_after,reason,reference,admin_id,notes,created_at) VALUES(?,?,?,?,?,?,?,?,?,NOW())",
                [
                    $item['product_id'], $voucher['type'] === 'receipt' ? 'in' : 'out',
                    $direction * (int)$item['qty'], $before, $after,
                    $voucher['type'] === 'receipt' ? 'إذن استلام/شراء' : 'إذن صرف/بيع',
                    $voucher['voucher_number'], adminUser()['id'] ?? null, $item['notes'],
                ]
            );
        }

        Database::execute("UPDATE inventory_vouchers SET status='confirmed', confirmed_at=NOW() WHERE id=?", [(int)$id]);
        flashSuccess('تم تأكيد الإذن وتحديث المخزون ✅');
        $this->redirect(adminUrl('inventory/'.$id));
    }

    public function cancel(string $id): void
    {
        requireAdminPermission('products', 'delete');
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect(adminUrl('inventory/'.$id)); }
        $voucher = Database::fetch("SELECT status FROM inventory_vouchers WHERE id=?", [(int)$id]);
        if ($voucher && $voucher['status'] === 'draft') {
            Database::execute("UPDATE inventory_vouchers SET status='cancelled' WHERE id=?", [(int)$id]);
            flashSuccess('تم إلغاء الإذن');
        } else {
            flashError('لا يمكن إلغاء إذن مؤكَّد بالفعل — عمل إذن عكسي بدلاً من ذلك');
        }
        $this->redirect(adminUrl('inventory/'.$id));
    }
}
