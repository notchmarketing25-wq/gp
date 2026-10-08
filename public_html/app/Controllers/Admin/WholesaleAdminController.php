<?php
class WholesaleAdminController extends Controller
{
    public function __construct() { AdminAuthMiddleware::handle(); requireAdminPermission('business'); }

    public function index(): void
    {
        $status = $this->get('status', 'pending');
        $where  = "account_type='wholesale'";
        $params = [];
        if ($status !== 'all') { $where .= " AND wholesale_status=?"; $params[] = $status; }

        $accounts = Database::fetchAll(
            "SELECT c.*, COUNT(o.id) orders_count, COALESCE(SUM(o.total),0) orders_total
             FROM customers c LEFT JOIN orders o ON o.customer_id=c.id
             WHERE $where GROUP BY c.id ORDER BY c.wholesale_applied_at DESC, c.created_at DESC",
            $params
        );

        $counts = [
            'pending'  => (int)(Database::fetch("SELECT COUNT(*) c FROM customers WHERE account_type='wholesale' AND wholesale_status='pending'")['c'] ?? 0),
            'approved' => (int)(Database::fetch("SELECT COUNT(*) c FROM customers WHERE account_type='wholesale' AND wholesale_status='approved'")['c'] ?? 0),
            'rejected' => (int)(Database::fetch("SELECT COUNT(*) c FROM customers WHERE account_type='wholesale' AND wholesale_status='rejected'")['c'] ?? 0),
        ];

        $this->view('admin.wholesale.index', compact('accounts', 'status', 'counts'));
    }

    // ── Bulk ORDERS upload: CSV → preview → confirm (multi-customer) ──
    public function bulkUpload(): void
    {
        $this->view('admin.wholesale.bulk-upload', []);
    }

    /** Step 1: parse CSV, validate, stage in session, show preview (nothing saved to DB yet) */
    public function previewBulkUpload(): void
    {
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect(adminUrl('wholesale/bulk-upload')); }
        if (empty($_FILES['csv']['tmp_name'])) { flashError('ارفع ملف CSV'); $this->redirect(adminUrl('wholesale/bulk-upload')); }

        $productModel = new ProductModel();
        $rows = [];
        if (($fh = fopen($_FILES['csv']['tmp_name'], 'r')) !== false) {
            $header = fgetcsv($fh); // skip header row
            while (($r = fgetcsv($fh)) !== false) {
                if (count($r) < 5 || !array_filter($r)) continue;
                $rows[] = array_map('trim', $r);
            }
            fclose($fh);
        }

        if (empty($rows)) {
            flashError('الملف فارغ أو الصيغة غير صحيحة. الأعمدة المطلوبة: customer_email,customer_name,customer_phone,company_name,sku,qty');
            $this->redirect(adminUrl('wholesale/bulk-upload'));
        }

        // Group rows by customer email → build order previews
        $orders = [];
        foreach ($rows as $r) {
            [$email, $name, $phone, $company, $sku, $qty] = array_pad($r, 6, '');
            $email = strtolower($email);
            if (!$email || !$sku) continue;
            $qty = max(1, (int)$qty);

            if (!isset($orders[$email])) {
                $existing = Database::fetch("SELECT * FROM customers WHERE email=?", [$email]);
                $orders[$email] = [
                    'email'          => $email,
                    'name'           => $name ?: ($existing['name'] ?? $email),
                    'phone'          => $phone ?: ($existing['phone'] ?? ''),
                    'company'        => $company ?: ($existing['company_name'] ?? ''),
                    'customer_id'    => $existing['id'] ?? null,
                    'is_wholesale'   => $existing && $existing['account_type']==='wholesale' && $existing['wholesale_status']==='approved',
                    'customer_found' => (bool)$existing,
                    'items'          => [],
                    'errors'         => [],
                    'subtotal'       => 0,
                ];
            }

            $product = Database::fetch("SELECT * FROM products WHERE sku=?", [$sku]);
            if (!$product) {
                $orders[$email]['errors'][] = "SKU '$sku' غير موجود";
                continue;
            }
            $price = $product['price'];
            $wasWholesale = false;
            if (!empty($product['wholesale_enabled']) && $qty >= (int)$product['wholesale_min_qty']) {
                $price = $productModel->wholesalePriceFor($product['id'], $qty, $price);
                $wasWholesale = true;
            }
            $orders[$email]['items'][] = [
                'product_id' => $product['id'], 'name' => $product['name'], 'sku' => $product['sku'],
                'price' => $price, 'qty' => $qty, 'image' => $product['thumbnail'], 'wholesale' => $wasWholesale,
            ];
            $orders[$email]['subtotal'] += $price * $qty;
        }

        $orders = array_values($orders);
        if (empty($orders)) {
            flashError('لم يتم العثور على بيانات صالحة في الملف');
            $this->redirect(adminUrl('wholesale/bulk-upload'));
        }

        $batchId = 'wsb_' . uniqid();
        Session::set($batchId, $orders);
        Session::set('wholesale_batch_id', $batchId);

        $this->view('admin.wholesale.bulk-preview', ['orders' => $orders, 'batchId' => $batchId]);
    }

    /** Step 2: create the actual orders from the staged preview data */
    public function confirmBulkUpload(): void
    {
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect(adminUrl('wholesale/bulk-upload')); }
        $batchId = $this->post('batch_id', '');
        $orders  = Session::get($batchId);
        if (!$orders) { flashError('انتهت صلاحية المعاينة، ارفع الملف مرة أخرى'); $this->redirect(adminUrl('wholesale/bulk-upload')); }

        $orderModel    = new OrderModel();
        $customerModel = new CustomerModel();
        $created = 0; $skipped = 0; $customersCreated = 0;

        foreach ($orders as $o) {
            if (empty($o['items'])) { $skipped++; continue; }

            $customerId = $o['customer_id'];
            if (!$customerId && $o['email']) {
                $wasNew = !Database::fetch("SELECT id FROM customers WHERE email=?", [$o['email']]);
                $cust = $customerModel->findOrCreateByEmail($o['email'], $o['company'] ?: $o['name'], $o['phone']);
                if ($cust) { $customerId = $cust['id']; if ($wasNew) $customersCreated++; }
            }

            $orderData = [
                'order_number'     => $orderModel->generateOrderNumber(),
                'customer_id'      => $customerId,
                'order_type'       => 'wholesale',
                'customer_name'    => $o['company'] ?: $o['name'],
                'customer_email'   => $o['email'],
                'customer_phone'   => $o['phone'],
                'shipping_address' => '',
                'shipping_city'    => '',
                'shipping_gov'     => '',
                'shipping_price'   => 0,
                'subtotal'         => $o['subtotal'],
                'discount_code'    => null,
                'discount_amount'  => 0,
                'total'            => $o['subtotal'],
                'payment_method'   => 'cod',
                'payment_status'   => 'unpaid',
                'status'           => 'pending',
                'notes'            => 'طلب جملة تم رفعه عبر CSV بواسطة الإدارة',
            ];
            $orderModel->createWithItems($orderData, array_map(fn($i) => [
                'product_id'=>$i['product_id'],'variant_id'=>null,'name'=>$i['name'],'variant'=>null,
                'sku'=>$i['sku'],'price'=>$i['price'],'qty'=>$i['qty'],'image'=>$i['image'],
            ], $o['items']));
            $created++;
        }

        Session::remove($batchId);
        Session::remove('wholesale_batch_id');
        $msg = "تم إنشاء $created طلب جملة بنجاح ✅";
        if ($customersCreated) $msg .= " — تم إنشاء $customersCreated عميل جديد تلقائياً";
        if ($skipped) $msg .= " (تخطينا $skipped بدون منتجات صالحة)";
        flashSuccess($msg);
        $this->redirect(adminUrl('bulk/orders'));
    }

    // ── Bulk CUSTOMERS upload: CSV → preview → confirm ──────────────
    public function bulkUploadCustomers(): void
    {
        $this->view('admin.wholesale.bulk-customers', []);
    }

    public function previewBulkCustomers(): void
    {
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect(adminUrl('wholesale/customers/bulk-upload')); }
        if (empty($_FILES['csv']['tmp_name'])) { flashError('ارفع ملف CSV'); $this->redirect(adminUrl('wholesale/customers/bulk-upload')); }

        $defaultStatus = in_array($this->post('default_status'), ['pending','approved']) ? $this->post('default_status') : 'pending';
        $rows = [];
        if (($fh = fopen($_FILES['csv']['tmp_name'], 'r')) !== false) {
            fgetcsv($fh); // header
            while (($r = fgetcsv($fh)) !== false) {
                if (count($r) < 2 || !array_filter($r)) continue;
                $rows[] = array_map('trim', $r);
            }
            fclose($fh);
        }
        if (empty($rows)) {
            flashError('الملف فارغ. الأعمدة: name,email,phone,company_name,company_address,tax_number');
            $this->redirect(adminUrl('wholesale/customers/bulk-upload'));
        }

        $customers = [];
        foreach ($rows as $r) {
            [$name, $email, $phone, $company, $address, $tax] = array_pad($r, 6, '');
            $email = strtolower(trim($email));
            if (!$name || !$email) continue;
            $existing = Database::fetch("SELECT id FROM customers WHERE email=?", [$email]);
            $customers[] = [
                'name' => $name, 'email' => $email, 'phone' => $phone,
                'company_name' => $company, 'company_address' => $address, 'tax_number' => $tax,
                'exists' => (bool)$existing, 'existing_id' => $existing['id'] ?? null,
            ];
        }

        if (empty($customers)) {
            flashError('لم يتم العثور على بيانات صالحة');
            $this->redirect(adminUrl('wholesale/customers/bulk-upload'));
        }

        $batchId = 'wcb_' . uniqid();
        Session::set($batchId, ['customers' => $customers, 'status' => $defaultStatus]);
        $this->view('admin.wholesale.bulk-customers-preview', ['customers' => $customers, 'batchId' => $batchId, 'status' => $defaultStatus]);
    }

    public function confirmBulkCustomers(): void
    {
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect(adminUrl('wholesale/customers/bulk-upload')); }
        $batchId = $this->post('batch_id', '');
        $batch   = Session::get($batchId);
        if (!$batch) { flashError('انتهت صلاحية المعاينة، ارفع الملف مرة أخرى'); $this->redirect(adminUrl('wholesale/customers/bulk-upload')); }

        $model = new CustomerModel();
        $created = 0; $updated = 0;
        foreach ($batch['customers'] as $c) {
            $data = [
                'name' => $c['name'], 'phone' => $c['phone'],
                'account_type' => 'wholesale', 'wholesale_status' => $batch['status'],
                'company_name' => $c['company_name'], 'company_address' => $c['company_address'],
                'tax_number' => $c['tax_number'], 'wholesale_applied_at' => date('Y-m-d H:i:s'),
            ];
            if ($c['exists']) {
                $model->update($c['existing_id'], $data);
                $updated++;
            } else {
                $data['email'] = $c['email'];
                $data['password'] = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
                $data['is_active'] = 1;
                $model->create($data);
                $created++;
            }
        }

        Session::remove($batchId);
        flashSuccess("تم استيراد العملاء ✅ — جديد: $created، تحديث: $updated");
        $this->redirect(adminUrl('wholesale?status=' . ($batch['status']==='approved'?'approved':'pending')));
    }
}
