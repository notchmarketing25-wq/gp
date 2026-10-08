<?php
class ShippingController extends Controller
{
    public function __construct() { AdminAuthMiddleware::handle(); requireAdminPermission('orders'); }

    // ── Shipments list ────────────────────────────────────────────
    public function index(): void
    {
        $status  = $this->get('status', '');
        $carrier = $this->get('carrier', '');
        $cod     = $this->get('cod', '');
        $search  = trim($this->get('search', ''));
        $page    = max(1, (int)$this->get('page', 1));

        $where = ['1=1']; $params = [];
        if ($status)  { $where[] = 's.status=?';      $params[] = $status; }
        if ($carrier) { $where[] = 's.carrier_id=?';  $params[] = (int)$carrier; }
        if ($cod !== '') { $where[] = 's.cod_collected=?'; $params[] = (int)$cod; }
        if ($search)  { $where[] = '(s.tracking_number LIKE ? OR s.customer_name LIKE ? OR s.customer_phone LIKE ?)';
                         $params = array_merge($params, ["%$search%","%$search%","%$search%"]); }
        $w = implode(' AND ', $where);

        $total = (int)(Database::fetch("SELECT COUNT(*) c FROM shipments s WHERE $w", $params)['c'] ?? 0);
        $lastPage = max(1, (int)ceil($total / ITEMS_PER_PAGE));
        $page = min($page, $lastPage);
        $offset = ($page - 1) * ITEMS_PER_PAGE;

        $shipments = Database::fetchAll(
            "SELECT s.*, c.name carrier_name, o.order_number
             FROM shipments s
             LEFT JOIN shipping_carriers c ON c.id=s.carrier_id
             LEFT JOIN orders o ON o.id=s.order_id
             WHERE $w ORDER BY s.created_at DESC LIMIT " . ITEMS_PER_PAGE . " OFFSET $offset", $params
        );
        $carriers = Database::fetchAll("SELECT * FROM shipping_carriers ORDER BY name");

        $stats = [
            'total'          => (int)(Database::fetch("SELECT COUNT(*) c FROM shipments")['c'] ?? 0),
            'in_transit'     => (int)(Database::fetch("SELECT COUNT(*) c FROM shipments WHERE status IN ('picked_up','in_transit','out_for_delivery')")['c'] ?? 0),
            'delivered'      => (int)(Database::fetch("SELECT COUNT(*) c FROM shipments WHERE status='delivered'")['c'] ?? 0),
            'cod_pending'    => (float)(Database::fetch("SELECT COALESCE(SUM(cod_amount),0) v FROM shipments WHERE cod_collected=0 AND cod_amount>0")['v'] ?? 0),
            'cod_collected'  => (float)(Database::fetch("SELECT COALESCE(SUM(cod_amount),0) v FROM shipments WHERE cod_collected=1")['v'] ?? 0),
        ];

        $this->view('admin.shipping.index', compact('shipments','carriers','stats','status','carrier','cod','search','page','lastPage','total'));
    }

    public function store(): void
    {
        requireAdminPermission('orders', 'create');
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect(adminUrl('shipping')); }
        $orderId = $this->post('order_id') ?: null;
        $order   = $orderId ? Database::fetch("SELECT * FROM orders WHERE id=?", [$orderId]) : null;

        Database::insert(
            "INSERT INTO shipments(order_id,carrier_id,customer_id,tracking_number,status,cod_amount,customer_name,customer_phone,address,city,governorate,notes,created_at)
             VALUES(?,?,?,?,?,?,?,?,?,?,?,?,NOW())",
            [
                $orderId ?: null,
                $this->post('carrier_id') ?: null,
                $order['customer_id'] ?? null,
                trim($this->post('tracking_number','')),
                $this->post('status','pending'),
                (float)$this->post('cod_amount', $order['total'] ?? 0),
                trim($this->post('customer_name', $order['customer_name'] ?? '')),
                trim($this->post('customer_phone', $order['customer_phone'] ?? '')),
                trim($this->post('address', $order['shipping_address'] ?? '')),
                trim($this->post('city', $order['shipping_city'] ?? '')),
                trim($this->post('governorate', $order['shipping_gov'] ?? '')),
                trim($this->post('notes','')),
            ]
        );
        flashSuccess('تم إنشاء الشحنة بنجاح ✅');
        $this->redirect(adminUrl('shipping'));
    }

    public function update(string $id): void
    {
        requireAdminPermission('orders', 'edit');
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect(adminUrl('shipping')); }
        $codCollected = isset($_POST['cod_collected']) ? 1 : 0;
        $shipment = Database::fetch("SELECT cod_collected FROM shipments WHERE id=?", [(int)$id]);

        Database::execute(
            "UPDATE shipments SET carrier_id=?, tracking_number=?, status=?, cod_amount=?, cod_collected=?,
             cod_collected_at=?, notes=?, updated_at=NOW() WHERE id=?",
            [
                $this->post('carrier_id') ?: null,
                trim($this->post('tracking_number','')),
                $this->post('status','pending'),
                (float)$this->post('cod_amount',0),
                $codCollected,
                ($codCollected && !($shipment['cod_collected'] ?? 0)) ? date('Y-m-d H:i:s') : null,
                trim($this->post('notes','')),
                (int)$id,
            ]
        );

        // Reflect delivered status back on the linked order for consistency
        if ($this->post('status') === 'delivered') {
            $s = Database::fetch("SELECT order_id FROM shipments WHERE id=?", [(int)$id]);
            if ($s['order_id'] ?? null) {
                Database::execute("UPDATE orders SET status='delivered', delivered_at=NOW() WHERE id=? AND status!='delivered'", [$s['order_id']]);
            }
        }

        flashSuccess('تم تحديث الشحنة ✅');
        $this->redirect(adminUrl('shipping'));
    }

    public function delete(string $id): void
    {
        requireAdminPermission('orders', 'delete');
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect(adminUrl('shipping')); }
        Database::execute("DELETE FROM shipments WHERE id=?", [(int)$id]);
        flashSuccess('تم حذف الشحنة');
        $this->redirect(adminUrl('shipping'));
    }

    // ── Carriers (شركات الشحن) ───────────────────────────────────
    public function carriers(): void
    {
        $carriers = Database::fetchAll("SELECT * FROM shipping_carriers ORDER BY name");
        $this->view('admin.shipping.carriers', compact('carriers'));
    }

    public function storeCarrier(): void
    {
        requireAdminPermission('orders', 'create');
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect(adminUrl('shipping/carriers')); }
        Database::insert(
            "INSERT INTO shipping_carriers(name,api_url,api_key,api_secret,is_active,notes,created_at) VALUES(?,?,?,?,?,?,NOW())",
            [trim($this->post('name','')), trim($this->post('api_url','')), trim($this->post('api_key','')),
             trim($this->post('api_secret','')), isset($_POST['is_active'])?1:0, trim($this->post('notes',''))]
        );
        flashSuccess('تم إضافة شركة الشحن ✅');
        $this->redirect(adminUrl('shipping/carriers'));
    }

    public function updateCarrier(string $id): void
    {
        requireAdminPermission('orders', 'edit');
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect(adminUrl('shipping/carriers')); }
        Database::execute(
            "UPDATE shipping_carriers SET name=?, api_url=?, api_key=?, api_secret=?, is_active=?, notes=? WHERE id=?",
            [trim($this->post('name','')), trim($this->post('api_url','')), trim($this->post('api_key','')),
             trim($this->post('api_secret','')), isset($_POST['is_active'])?1:0, trim($this->post('notes','')), (int)$id]
        );
        flashSuccess('تم تحديث شركة الشحن ✅');
        $this->redirect(adminUrl('shipping/carriers'));
    }

    public function deleteCarrier(string $id): void
    {
        requireAdminPermission('orders', 'delete');
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect(adminUrl('shipping/carriers')); }
        Database::execute("DELETE FROM shipping_carriers WHERE id=?", [(int)$id]);
        flashSuccess('تم حذف شركة الشحن');
        $this->redirect(adminUrl('shipping/carriers'));
    }

    // ── Import shipments-only from a Shopify orders CSV export ──────
    public function importForm(): void
    {
        $carriers = Database::fetchAll("SELECT * FROM shipping_carriers WHERE is_active=1 ORDER BY name");
        $this->view('admin.shipping.import', compact('carriers'));
    }

    public function previewImport(): void
    {
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect(adminUrl('shipping/import')); }
        if (empty($_FILES['csv']['tmp_name'])) { flashError('ارفع ملف CSV'); $this->redirect(adminUrl('shipping/import')); }

        $fh = fopen($_FILES['csv']['tmp_name'], 'r');
        $header = fgetcsv($fh);
        if (!$header) { flashError('الملف فارغ'); fclose($fh); $this->redirect(adminUrl('shipping/import')); }
        if (isset($header[0])) $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
        $colLower = array_flip(array_map('strtolower', array_map('trim', $header)));
        $get = function($row, $name) use ($colLower) {
            $idx = $colLower[strtolower($name)] ?? null;
            return $idx !== null ? trim($row[$idx] ?? '') : '';
        };

        $orderNumCol = isset($colLower['name']) ? 'Name' : null;
        if (!$orderNumCol) {
            flashError('لم يتم العثور على عمود رقم الطلب (Name) في الملف');
            fclose($fh);
            $this->redirect(adminUrl('shipping/import'));
        }

        $rows = [];
        while (($row = fgetcsv($fh)) !== false) {
            if (empty($row) || !array_filter($row)) continue;
            $num = $get($row, 'Name');
            if (!$num || isset($rows[$num])) continue; // one shipment row per order — skip repeated line-item rows

            $email = strtolower($get($row,'Email'));
            $existingCust = $email ? Database::fetch("SELECT id FROM customers WHERE email=?", [$email]) : null;
            $existingOrder = Database::fetch("SELECT id FROM orders WHERE order_number=?", ['SHP-'.ltrim($num,'#')]);
            $alreadyShipment = Database::fetch("SELECT id FROM shipments WHERE tracking_number=? AND tracking_number!=''", [$get($row,'Tracking Number')]);

            $fulfillStatus = strtolower($get($row,'Fulfillment Status'));
            $statusMap = ['fulfilled'=>'delivered','partial'=>'in_transit','unfulfilled'=>'pending'];

            $rows[$num] = [
                'order_number'    => $num,
                'email'           => $email,
                'customer_name'   => $get($row,'Shipping Name') ?: $get($row,'Billing Name') ?: $email,
                'phone'           => $get($row,'Phone') ?: $get($row,'Shipping Phone'),
                'address'         => $get($row,'Shipping Address1'),
                'city'            => $get($row,'Shipping City'),
                'governorate'     => $get($row,'Shipping Province'),
                'tracking_number' => $get($row,'Tracking Number') ?: $get($row,'Fulfillment Tracking Number'),
                'status'          => $statusMap[$fulfillStatus] ?? 'pending',
                'cod_amount'      => (float)($get($row,'Total') ?: 0),
                'financial'       => strtolower($get($row,'Financial Status')),
                'customer_id'     => $existingCust['id'] ?? null,
                'order_id'        => $existingOrder['id'] ?? null,
                'already_exists'  => (bool)$alreadyShipment,
            ];
        }
        fclose($fh);

        if (empty($rows)) { flashError('لم يتم العثور على بيانات شحن صالحة'); $this->redirect(adminUrl('shipping/import')); }

        $rows = array_values($rows);
        $batchId = 'shpm_' . uniqid();
        Session::set($batchId, $rows);
        $carrierId = (int)$this->post('carrier_id', 1);
        $this->view('admin.shipping.import-preview', ['rows' => $rows, 'batchId' => $batchId, 'carrierId' => $carrierId]);
    }

    public function confirmImport(): void
    {
        requireAdminPermission('orders', 'create');
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect(adminUrl('shipping/import')); }
        $batchId   = $this->post('batch_id', '');
        $rows      = Session::get($batchId);
        $carrierId = (int)$this->post('carrier_id', 1);
        if (!$rows) { flashError('انتهت صلاحية المعاينة'); $this->redirect(adminUrl('shipping/import')); }

        $customerModel = new CustomerModel();
        $created = 0; $skipped = 0;

        foreach ($rows as $r) {
            if ($r['already_exists']) { $skipped++; continue; }

            $customerId = $r['customer_id'];
            if (!$customerId && $r['email']) {
                $cust = $customerModel->findOrCreateByEmail($r['email'], $r['customer_name'], $r['phone']);
                if ($cust) $customerId = $cust['id'];
            }

            Database::insert(
                "INSERT INTO shipments(order_id,carrier_id,customer_id,tracking_number,status,cod_amount,cod_collected,customer_name,customer_phone,address,city,governorate,notes,created_at)
                 VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())",
                [
                    $r['order_id'], $carrierId, $customerId,
                    $r['tracking_number'], $r['status'], $r['cod_amount'],
                    in_array($r['financial'], ['paid','partially_paid']) ? 1 : 0,
                    $r['customer_name'], $r['phone'], $r['address'], $r['city'], $r['governorate'],
                    'شحنة مستوردة من Shopify',
                ]
            );
            $created++;
        }

        Session::remove($batchId);
        flashSuccess("تم استيراد $created شحنة ✅" . ($skipped ? " (تخطينا $skipped مستوردة مسبقاً)" : ''));
        $this->redirect(adminUrl('shipping'));
    }
}
