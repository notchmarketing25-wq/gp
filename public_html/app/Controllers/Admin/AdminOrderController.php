<?php
class AdminOrderController extends Controller
{
    private OrderModel $order;

    public function __construct()
    {
        AdminAuthMiddleware::handle();
        requireAdminPermission('orders');
        $this->order = new OrderModel();
    }

    public function index(): void
    {
        $page      = (int)$this->get('page', 1);
        $search    = trim($this->get('search', ''));
        $status    = $this->get('status', '');
        $payment   = $this->get('payment', '');
        $orderType = $this->get('order_type', '');

        $paginator = $this->order->adminList($page, $search, $status, $payment, $orderType);

        $statusCounts = [];
        foreach (Database::fetchAll("SELECT status, COUNT(*) c FROM orders GROUP BY status") as $r) { $statusCounts[$r['status']] = (int)$r['c']; }
        $typeCounts = [];
        foreach (Database::fetchAll("SELECT order_type, COUNT(*) c FROM orders GROUP BY order_type") as $r) { $typeCounts[$r['order_type'] ?: 'retail'] = (int)$r['c']; }
        $typeCounts['all'] = array_sum($typeCounts);
        $statusCounts['all'] = array_sum($statusCounts);

        $this->view('admin.orders.index', compact('paginator', 'search', 'status', 'payment', 'orderType', 'statusCounts', 'typeCounts'));
    }

    public function show(string $id): void
    {
        $order = $this->order->getWithItems((int)$id);
        if (!$order) { flashError('الطلب غير موجود'); $this->redirect($this->adminUrl('orders')); }

        $this->view('admin.orders.show', compact('order'));
    }

    public function updateStatus(string $id): void
    {
        requireAdminPermission('orders', 'edit');
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect($this->adminUrl('orders')); }

        $status  = $this->post('status', '');
        $payment = $this->post('payment_status', '');

        // Safety net: an order paid via the online gateway (Fawateerk) must never be moved to a
        // fulfillment status (processing/shipped/delivered) while its payment is still unpaid —
        // that would mean shipping something the customer never actually paid for. This can happen
        // if the customer abandoned the payment page, or the gateway callback never arrived.
        if (in_array($status, ['processing','shipped','delivered'], true)) {
            $current = Database::fetch("SELECT payment_method, payment_status FROM orders WHERE id=?", [(int)$id]);
            $willBePaid = $payment === 'paid' || ($current && $current['payment_status'] === 'paid');
            if ($current && $current['payment_method'] === 'fawateerk' && !$willBePaid) {
                flashError('لا يمكن تنفيذ هذا الطلب — الدفع الإلكتروني لسه مأكدش من بوابة الدفع. تأكد من حالة الدفع أولاً أو غيّرها يدوياً لو استلمت المبلغ بطريقة تانية.');
                $this->redirect($this->adminUrl('orders/'.$id));
            }
        }

        $data = [];
        if ($status)  $data['status'] = $status;
        if ($payment) $data['payment_status'] = $payment;
        if ($status === 'shipped')   $data['shipped_at']   = date('Y-m-d H:i:s');
        if ($status === 'delivered') $data['delivered_at'] = date('Y-m-d H:i:s');

        if ($data) {
            $data['updated_at'] = date('Y-m-d H:i:s');
            $sets   = implode(', ', array_map(fn($k) => "`{$k}` = ?", array_keys($data)));
            $params = array_values($data);
            $params[] = (int)$id;
            Database::execute("UPDATE `orders` SET {$sets} WHERE `id` = ?", $params);

            // Same automatic "إذن صرف" stock deduction as the online-payment callback — triggers
            // here too so it applies whether payment was confirmed by the gateway or manually by
            // an admin (e.g. cash/COD orders marked paid by hand). createIssueVoucherForOrder()
            // is itself idempotent, so this is safe even if it was already created earlier.
            if ($payment === 'paid') {
                createIssueVoucherForOrder((int)$id);
            }

            // Order cancelled — the units it reserved at creation are no longer going out the
            // door, so return them to stock (idempotent; safe even if clicked twice).
            if ($status === 'cancelled') {
                restoreStockForOrder((int)$id);
            }

            if (in_array($status, ['shipped','delivered'])) {
                $order = Database::fetch("SELECT * FROM orders WHERE id=?", [(int)$id]);
                if ($order && $order['customer_email']) {
                    try {
                        $tplKey = $status === 'shipped' ? 'order_shipped' : 'order_delivered';
                        $shipment = Database::fetch("SELECT tracking_number FROM shipments WHERE order_id=? ORDER BY id DESC LIMIT 1", [(int)$id]);
                        EmailService::send($tplKey, $order['customer_email'], $order['customer_name'], [
                            'name' => $order['customer_name'], 'order_number' => $order['order_number'],
                            'tracking_number' => $shipment['tracking_number'] ?? '—',
                        ], 'order', (int)$id);
                    } catch (\Throwable $e) {}
                }
            }
        }

        flashSuccess('تم تحديث حالة الطلب');
        $this->redirect($this->adminUrl('orders/' . $id));
    }

    public function updateNotes(string $id): void
    {
        requireAdminPermission('orders', 'edit');
        if (!verifyCsrf()) { jsonResponse(false, 'خطأ'); }
        Database::execute(
            "UPDATE `orders` SET `admin_notes` = ? WHERE `id` = ?",
            [$this->post('admin_notes', ''), (int)$id]
        );
        jsonResponse(true, 'تم الحفظ');
    }

    /**
     * Retroactively link order_items to real products by matching SKU.
     * Useful after a CSV/Shopify import that couldn't match a product at the time.
     */
    public function relinkProducts(): void
    {
        requireAdminPermission('orders', 'edit');
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect($this->adminUrl('orders')); }

        $rows = Database::fetchAll(
            "SELECT oi.id, oi.sku FROM order_items oi
             WHERE oi.product_id IS NULL AND oi.sku IS NOT NULL AND oi.sku != ''"
        );
        $linked = 0;
        foreach ($rows as $r) {
            $p = Database::fetch("SELECT id, thumbnail FROM products WHERE sku=?", [$r['sku']]);
            if ($p) {
                Database::execute("UPDATE order_items SET product_id=?, image=COALESCE(image,?) WHERE id=?", [$p['id'], $p['thumbnail'], $r['id']]);
                $linked++;
            }
        }
        $stillUnlinked = (int)(Database::fetch("SELECT COUNT(*) c FROM order_items WHERE product_id IS NULL")['c'] ?? 0);

        flashSuccess("تم ربط $linked منتج بالطلبات بنجاح ✅" . ($stillUnlinked ? " — لسه $stillUnlinked عنصر من غير SKU مطابق" : ''));
        $this->redirect($this->adminUrl('orders'));
    }

    /**
     * Orders whose product was later deleted keep their item's own `name`/`sku`/`image` snapshot
     * (taken at order time) but lose `product_id` — relinkProducts() above recovers those by SKU,
     * but an admin re-creating the product often doesn't know/reuse the old SKU, only the name.
     * This page groups every still-unlinked order_item by its saved name so the admin can point a
     * whole group at an existing (e.g. freshly re-created) product by name instead.
     */
    public function orphanProducts(): void
    {
        $groups = Database::fetchAll("
            SELECT oi.name, oi.sku, COUNT(*) items, COUNT(DISTINCT oi.order_id) orders_count, SUM(oi.qty) total_qty, MAX(oi.image) image
            FROM order_items oi
            WHERE oi.product_id IS NULL
            GROUP BY oi.name
            ORDER BY orders_count DESC, oi.name ASC
        ");
        // The product picker is a plain native <select> filled with every active product — no
        // AJAX search box. That's a deliberate simplification: a hand-built JS autocomplete kept
        // breaking silently whenever a product name contained a quote or a symbol like ™, since
        // it had to splice the name into a JS string. A native <select> renders every option
        // through plain PHP escaping (e()), so there is no string-building step left to break.
        $products = Database::fetchAll("SELECT id, name, sku FROM products WHERE status != 'archived' ORDER BY name ASC");
        $this->view('admin.orders.orphans', compact('groups', 'products'));
    }

    /**
     * Links every still-unlinked order_item that has this exact saved name to a real product the
     * admin picked (typically one they just re-created). Scoped to `name` only — never touches
     * order_items that already have a product_id — so it can't accidentally relink a group twice
     * or steal items that already resolved via SKU. A plain form POST (not AJAX) — the whole page
     * reloads with a flash message, same pattern as relinkProducts() above.
     */
    public function linkOrphanGroup(): void
    {
        requireAdminPermission('orders', 'edit');
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect($this->adminUrl('orders/orphans')); }

        $name      = trim($this->post('name', ''));
        $productId = (int)$this->post('product_id');
        if ($name === '' || !$productId) {
            flashError('اختر منتج من القائمة أولاً');
            $this->redirect($this->adminUrl('orders/orphans'));
        }

        $product = Database::fetch("SELECT id, name, thumbnail FROM products WHERE id=?", [$productId]);
        if (!$product) {
            flashError('المنتج غير موجود');
            $this->redirect($this->adminUrl('orders/orphans'));
        }

        $affected = Database::execute(
            "UPDATE order_items SET product_id=?, image=COALESCE(image,?) WHERE product_id IS NULL AND name=?",
            [$product['id'], $product['thumbnail'], $name]
        );

        flashSuccess("تم ربط $affected عنصر بـ\"{$product['name']}\" بنجاح ✅");
        $this->redirect($this->adminUrl('orders/orphans'));
    }
}
