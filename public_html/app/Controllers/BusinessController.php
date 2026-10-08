<?php
class BusinessController extends Controller
{
    private CustomerModel $model;
    private ProductModel $productModel;

    public function __construct()
    {
        $this->model = new CustomerModel();
        $this->productModel = new ProductModel();
    }

    private function requireBusinessLogin(): array
    {
        header('Cache-Control: no-store, no-cache, must-revalidate, private');
        header('X-LiteSpeed-Cache-Control: no-cache');
        if (!isStoreLoggedIn()) {
            flashError('يجب تسجيل الدخول أولاً');
            $this->redirect(url('login') . '?next=' . urlencode('/business'));
        }
        $customer = $this->model->find(storeUser()['id']);
        if (!$customer || $customer['business_type'] === 'none') {
            flashError('هذا القسم متاح فقط لحسابات Business');
            $this->redirect(url('business/register'));
        }
        return $customer;
    }

    /** Same as requireBusinessLogin, but also blocks actions that require an approved account (e.g. placing orders) */
    private function requireApprovedBusiness(): array
    {
        $customer = $this->requireBusinessLogin();
        if ($customer['business_status'] !== 'approved') {
            flashError('لازم يتم اعتماد حسابك أولاً قبل ما تقدر تعمل طلبات');
            $this->redirect(url('business'));
        }
        return $customer;
    }

    // ══════════════════════════════════════════
    // REGISTRATION
    // ══════════════════════════════════════════
    public function registerChoice(): void
    {
        $this->view('store.pages.business.register-choice');
    }

    public function registerForm(string $type): void
    {
        if (!in_array($type, ['merchant','distributor','rep'])) { $this->redirect(url('business/register')); }
        $this->view('store.pages.business.register-form', ['type' => $type]);
    }

    public function register(string $type): void
    {
        if (!in_array($type, ['merchant','distributor','rep'])) { $this->redirect(url('business/register')); }
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect(url('business/register/'.$type)); }

        $email = trim($this->post('email',''));
        $existing = $this->model->findByEmail($email);

        $data = [
            'name'          => trim($this->post('name','')),
            'phone'         => trim($this->post('phone','')),
            'business_type' => $type,
            'business_status' => 'pending',
            'governorate'   => trim($this->post('governorate','')),
            'area'          => trim($this->post('area','')),
            'shop_name'     => trim($this->post('shop_name','')),
        ];

        // Merchants/distributors declare an expected monthly sales volume, which decides which
        // price tier they START at — a merchant declaring a large volume shouldn't have to earn
        // their way up from the lowest tier through real purchases first, since the whole point
        // of the tier is to match the discount to how much business they're expected to bring.
        $expectedSales = null;
        if (in_array($type, ['merchant','distributor'], true)) {
            $expectedSales = $this->post('expected_monthly_sales') !== '' ? (float)$this->post('expected_monthly_sales') : null;
            if ($expectedSales !== null) $data['expected_monthly_sales'] = $expectedSales;
        }

        if ($existing) {
            $customerId = $existing['id'];
            $this->model->update($customerId, $data);
        } else {
            $pass = $this->post('password','');
            if (strlen($pass) < 8) { flashError('كلمة المرور يجب أن تكون 8 أحرف على الأقل'); $this->redirect(url('business/register/'.$type)); }
            $data['email']    = $email;
            $data['password'] = password_hash($pass, PASSWORD_BCRYPT);
            $data['is_active'] = 1;
            $data['price_tier_id'] = $this->pickStartingTier($expectedSales);
            $customerId = $this->model->create($data);
        }

        $customer = $this->model->find($customerId);
        unset($customer['password']);
        Session::set('store_user', $customer);

        $typeLabels = ['merchant'=>'تاجر','distributor'=>'موزع','rep'=>'مندوب'];
        try {
            EmailService::send('business_registration_received', $customer['email'], $customer['name'],
                ['name'=>$customer['name'], 'business_type_label'=>$typeLabels[$type] ?? $type], 'business', $customerId);
        } catch (\Throwable $e) {}

        flashSuccess('تم إرسال طلبك بنجاح! سنراجعه ونفعّل حسابك قريباً 🎉');
        $this->redirect(url('business'));
    }

    /**
     * Picks the highest price tier whose min_purchase_auto threshold the declared expected
     * monthly sales volume meets or exceeds — same thresholds already used for real-purchase
     * auto-upgrades (see CustomerModel::maybeAutoUpgradeTier()), just applied at registration
     * time using the self-declared figure instead of actual purchase history. Falls back to the
     * default tier if no volume was declared or none of the thresholds are met.
     */
    private function pickStartingTier(?float $expectedSales): ?int
    {
        if ($expectedSales !== null && $expectedSales > 0) {
            $tier = Database::fetch(
                "SELECT id FROM price_tiers WHERE min_purchase_auto IS NOT NULL AND min_purchase_auto <= ? ORDER BY min_purchase_auto DESC LIMIT 1",
                [$expectedSales]
            );
            if ($tier) return (int)$tier['id'];
        }
        return Database::fetch("SELECT id FROM price_tiers WHERE is_default=1 LIMIT 1")['id'] ?? null;
    }

    // ══════════════════════════════════════════
    // DASHBOARD
    // ══════════════════════════════════════════
    public function dashboard(): void
    {
        $customer = $this->requireBusinessLogin();
        $orders   = Database::fetchAll(
            "SELECT * FROM orders WHERE customer_id=? OR placed_by_business_id=? ORDER BY created_at DESC LIMIT 10",
            [$customer['id'], $customer['id']]
        );
        $tier     = $customer['price_tier_id'] ? Database::fetch("SELECT * FROM price_tiers WHERE id=?", [$customer['price_tier_id']]) : null;

        $expenses = $samples = [];
        if ($customer['business_type'] === 'rep') {
            $expenses = Database::fetchAll("SELECT * FROM rep_expenses WHERE rep_id=? ORDER BY created_at DESC LIMIT 5", [$customer['id']]);
            $samples  = Database::fetchAll("SELECT sr.*, p.name product_name FROM sample_requests sr JOIN products p ON p.id=sr.product_id WHERE sr.rep_id=? ORDER BY sr.created_at DESC LIMIT 5", [$customer['id']]);
        }

        $this->view('store.pages.business.dashboard', compact('customer','orders','tier','expenses','samples'));
    }

    public function uploadIdCard(): void
    {
        $customer = $this->requireBusinessLogin();
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect(url('business')); }

        $idNumber = trim($this->post('id_card_number',''));
        if (!$idNumber) { flashError('أدخل رقم البطاقة'); $this->redirect(url('business')); }
        if (empty($_FILES['id_card_image']['name'])) { flashError('ارفع صورة البطاقة'); $this->redirect(url('business')); }

        $up = uploadFile($_FILES['id_card_image'], 'id_cards');
        if (!$up) { flashError(uploadFileError() ?? 'فشل رفع الصورة'); $this->redirect(url('business')); }

        Database::execute("UPDATE customers SET id_card_number=?, id_card_image=? WHERE id=?", [$idNumber, $up, $customer['id']]);
        flashSuccess('تم رفع بيانات البطاقة بنجاح ✅ — بانتظار مراجعة الإدارة');
        $this->redirect(url('business'));
    }

    // ══════════════════════════════════════════
    // FULL ORDER HISTORY (with status filter)
    // ══════════════════════════════════════════
    public function ordersList(): void
    {
        $customer = $this->requireBusinessLogin();
        $status   = $this->get('status', '');
        $where = "(customer_id=? OR placed_by_business_id=?)"; $params = [$customer['id'], $customer['id']];
        if ($status) { $where .= " AND status=?"; $params[] = $status; }
        $orders = Database::fetchAll("SELECT * FROM orders WHERE $where ORDER BY created_at DESC LIMIT 200", $params);
        $counts = Database::fetchAll(
            "SELECT status, COUNT(*) cnt FROM orders WHERE customer_id=? OR placed_by_business_id=? GROUP BY status",
            [$customer['id'], $customer['id']]
        );
        $totalCommission = 0;
        if ($customer['business_type'] === 'rep') {
            $totalCommission = array_sum(array_column($orders, 'rep_commission_amount'));
        }
        $this->view('store.pages.business.orders', compact('customer','orders','status','counts','totalCommission'));
    }

    // ══════════════════════════════════════════
    // PRODUCTS AT TIER PRICE
    // ══════════════════════════════════════════
    public function products(): void
    {
        $customer = $this->requireBusinessLogin();
        $search   = trim($this->get('search',''));
        $where = "status='active' AND channel!='retail_only'"; $params = [];
        if ($search) { $where .= " AND name LIKE ?"; $params[] = "%$search%"; }
        $products = Database::fetchAll("SELECT * FROM products WHERE $where ORDER BY name LIMIT 200", $params);
        foreach ($products as &$p) {
            $info = $this->productModel->businessPriceInfo($p, $customer);
            $p['tier_price'] = $info['price'];
            $p['min_qty']    = $info['min_qty'];
            $p['has_tiers']  = $info['has_tiers'];
            $p['tiers']      = $info['tiers'];
        }
        unset($p);
        $this->view('store.pages.business.products', compact('customer','products','search'));
    }

    public function productShow(string $id): void
    {
        $customer = $this->requireBusinessLogin();
        $product = Database::fetch("SELECT p.*, c.name collection_name, b.name brand_name FROM products p LEFT JOIN collections c ON c.id=p.collection_id LEFT JOIN brands b ON b.id=p.brand_id WHERE p.id=? AND p.status='active' AND p.channel!='retail_only'", [(int)$id]);
        if (!$product) { flashError('المنتج غير موجود'); $this->redirect(url('business/products')); }

        $images = $this->productModel->getImages((int)$id);
        $info = $this->productModel->businessPriceInfo($product, $customer);
        $product['tier_price'] = $info['price'];
        $product['min_qty']    = $info['min_qty'];
        $product['has_tiers']  = $info['has_tiers'];
        $product['tiers']      = $info['tiers'];

        $this->view('store.pages.business.product-show', compact('customer','product','images'));
    }

    // ══════════════════════════════════════════
    // CATALOG DOWNLOAD (CSV at tier prices)
    // ══════════════════════════════════════════
    public function catalog(): void
    {
        $customer = $this->requireBusinessLogin();
        $products = Database::fetchAll("SELECT p.*, c.name collection_name FROM products p LEFT JOIN collections c ON c.id=p.collection_id WHERE p.status='active' AND p.channel!='retail_only' ORDER BY p.name");
        $tier     = $customer['price_tier_id'] ? Database::fetch("SELECT * FROM price_tiers WHERE id=?", [$customer['price_tier_id']]) : null;

        $storeName = SettingModel::get('store_name', APP_NAME);
        $logo      = SettingModel::get('store_logo');
        $color     = SettingModel::get('primary_color', '#0057FF') ?: '#0057FF';
        $today = date('Y-m-d');

        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename=catalog-' . date('Y-m-d') . '.xls');
        header('Cache-Control: max-age=0');

        echo "\xEF\xBB\xBF"; // BOM for Arabic in Excel
        ?>
<html dir="rtl">
<head><meta charset="UTF-8"><style>
table{border-collapse:collapse;font-family:Arial,sans-serif;width:100%}
.title{font-size:20px;font-weight:bold;background:#0A0A0A;color:#fff;padding:14px}
.meta{background:#F4F4F5;font-size:12px;padding:8px}
.meta b{color:#000}
th{background:<?= $color ?>;color:#fff;font-size:12px;padding:8px;text-align:right;border:1px solid #ddd}
td{padding:7px 8px;font-size:12px;border:1px solid #ddd;text-align:right}
.price{color:#00A86B;font-weight:bold}
.prodname{font-weight:bold;background:#FAFAFA}
</style></head>
<body>
<table>
  <tr><td colspan="4" class="title">
    <?php if ($logo): ?><img src="<?= uploadUrl($logo) ?>" height="34" style="vertical-align:middle;margin-left:10px"><?php endif; ?>
    <?= e($storeName) ?> — كاتالوج أسعار الجملة
  </td></tr>
  <tr><td colspan="4" class="meta">
    <b>اسم التاجر/المحل:</b> <?= e($customer['shop_name'] ?: $customer['name']) ?> &nbsp;&nbsp;
    <?php if ($tier): ?><b>الشريحة السعرية:</b> <?= e($tier['name']) ?> &nbsp;&nbsp;<?php endif; ?>
    <b>تاريخ الإصدار:</b> <?= $today ?>
  </td></tr>
  <tr><td colspan="4"></td></tr>
  <?php foreach ($products as $p):
      $info = $this->productModel->businessPriceInfo($p, $customer);
  ?>
  <tr>
    <td colspan="4" class="prodname"><?= e($p['name']) ?> <?php if($p['sku']): ?>(<?= e($p['sku']) ?>)<?php endif; ?><?php if($p['collection_name']): ?> — <?= e($p['collection_name']) ?><?php endif; ?></td>
  </tr>
  <tr>
    <th>الكمية</th><th colspan="3">سعر القطعة</th>
  </tr>
  <?php if ($info['has_tiers']): foreach ($info['tiers'] as $i => $t):
      $next = $info['tiers'][$i+1]['min_qty'] ?? null;
      $range = $next ? ($t['min_qty'].' - '.($next-1)) : ($t['min_qty'].'+');
  ?>
    <tr><td><?= $range ?> قطعة</td><td colspan="3" class="price"><?= number_format($t['price'],2) ?></td></tr>
  <?php endforeach; else: ?>
    <tr><td>أي كمية</td><td colspan="3" class="price"><?= number_format($info['price'],2) ?></td></tr>
  <?php endif; ?>
  <tr><td colspan="4">&nbsp;</td></tr>
  <?php endforeach; ?>
</table>
</body>
</html>
        <?php
    }

    /** Printable catalog — opens in browser styled for print; person can "Save as PDF" from the print dialog */
    public function catalogPrint(): void
    {
        $customer = $this->requireBusinessLogin();
        $products = Database::fetchAll("SELECT p.*, c.name collection_name FROM products p LEFT JOIN collections c ON c.id=p.collection_id WHERE p.status='active' AND p.channel!='retail_only' ORDER BY p.name");
        $tier     = $customer['price_tier_id'] ? Database::fetch("SELECT * FROM price_tiers WHERE id=?", [$customer['price_tier_id']]) : null;
        foreach ($products as &$p) {
            $info = $this->productModel->businessPriceInfo($p, $customer);
            $p['tier_price'] = $info['price'];
            $p['min_qty']    = $info['min_qty'];
            $p['has_tiers']  = $info['has_tiers'];
            $p['tiers']      = $info['tiers'];
        }
        unset($p);
        $this->view('store.pages.business.catalog-print', compact('customer','products','tier'));
    }

    // ══════════════════════════════════════════
    // CREATE ORDER
    // ══════════════════════════════════════════
    public function orderForm(): void
    {
        $customer = $this->requireApprovedBusiness();
        $creditAvailable = $this->model->creditAvailable($customer);
        $products = Database::fetchAll("SELECT * FROM products WHERE status='active' AND channel!='retail_only' ORDER BY name");
        foreach ($products as &$p) {
            $info = $this->productModel->businessPriceInfo($p, $customer);
            $p['tier_price'] = $info['price'];
            $p['min_qty']    = $info['min_qty'];
            $p['has_tiers']  = $info['has_tiers'];
            $p['tiers']      = $info['tiers'];
        }
        unset($p);
        $this->view('store.pages.business.order-form', compact('customer','products','creditAvailable'));
    }

    public function orderStore(): void
    {
        $customer = $this->requireApprovedBusiness();
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect(url('business/orders/create')); }

        $productIds = $_POST['product_id'] ?? [];
        $qtys       = $_POST['qty'] ?? [];
        if (empty($productIds)) { flashError('اختر منتج واحد على الأقل'); $this->redirect(url('business/orders/create')); }

        $items = []; $subtotal = 0;
        foreach ($productIds as $i => $pid) {
            $qty = max(1, (int)($qtys[$i] ?? 1));
            $p = Database::fetch("SELECT * FROM products WHERE id=?", [(int)$pid]);
            if (!$p) continue;
            $tiers = $this->productModel->getPriceTiers((int)$pid);
            $price = !empty($tiers)
                ? $this->productModel->wholesalePriceFor((int)$pid, $qty, (float)$p['price'])
                : $this->productModel->businessPriceInfo($p, $customer)['price'];
            $items[] = ['product_id'=>$p['id'],'variant_id'=>null,'name'=>$p['name'],'variant'=>null,'sku'=>$p['sku'],'price'=>$price,'qty'=>$qty,'image'=>$p['thumbnail']];
            $subtotal += $price * $qty;
        }
        if (empty($items)) { flashError('لم يتم العثور على منتجات صالحة'); $this->redirect(url('business/orders/create')); }

        // Validate N&Credit if selected as the payment method
        $paymentMethod = $this->post('payment_method', 'cod');
        if ($paymentMethod === 'credit') {
            if ($customer['credit_status'] !== 'approved') {
                flashError('لا يمكن الدفع بـ N&Credit — حسابك لسه ما اتوافقش على طلب الكريدت. راجع صفحة N&Credit.');
                $this->redirect(url('business/orders/create'));
            }
            $available = $this->model->creditAvailable($customer);
            if ($subtotal > $available) {
                flashError('رصيدك المتاح من N&Credit (' . money($available) . ') أقل من قيمة الطلب (' . money($subtotal) . '). اختر طريقة دفع تانية أو قلّل الطلب.');
                $this->redirect(url('business/orders/create'));
            }
        }

        // Distributor / rep: order is FOR an end customer they enter manually
        $isForEndCustomer = in_array($customer['business_type'], ['distributor','rep']) && $this->post('end_customer_name');
        $orderCustomerId  = $customer['id'];
        $custName  = $customer['shop_name'] ?: $customer['name'];
        $custPhone = $customer['phone'];
        $custEmail = $customer['email'];

        if ($isForEndCustomer) {
            $custName  = trim($this->post('end_customer_name',''));
            $custPhone = trim($this->post('end_customer_phone',''));
            $custEmail = trim($this->post('end_customer_email',''));
        }

        $orderModel = new OrderModel();
        // Rep commission: snapshot the rep's current rate + computed amount on the order itself,
        // so a later change to their commission_percent never alters past orders' earnings.
        $repCommissionPercent = null; $repCommissionAmount = null;
        if ($customer['business_type'] === 'rep' && (float)($customer['commission_percent'] ?? 0) > 0) {
            $repCommissionPercent = (float)$customer['commission_percent'];
            $repCommissionAmount  = round($subtotal * $repCommissionPercent / 100, 2);
        }
        $orderData = [
            'order_number'          => $orderModel->generateOrderNumber(),
            'customer_id'           => $isForEndCustomer ? null : $orderCustomerId,
            'placed_by_business_id' => $customer['id'],
            'rep_commission_percent'=> $repCommissionPercent,
            'rep_commission_amount' => $repCommissionAmount,
            'order_type'            => 'wholesale',
            'customer_name'         => $custName,
            'customer_email'        => $custEmail,
            'customer_phone'        => $custPhone,
            'shipping_address'      => trim($this->post('shipping_address', $customer['company_address'] ?? '')),
            'shipping_city'         => trim($this->post('shipping_city', $customer['area'] ?? '')),
            'shipping_gov'          => trim($this->post('shipping_gov', $customer['governorate'] ?? '')),
            'shipping_price'        => 0,
            'subtotal'              => $subtotal,
            'discount_amount'       => 0,
            'total'                 => $subtotal,
            'payment_method'        => $paymentMethod,
            'payment_status'        => $paymentMethod === 'credit' ? 'paid' : 'unpaid',
            'status'                => 'pending',
            'notes'                 => trim($this->post('notes','')) . ($isForEndCustomer ? ' — طلب مُقدَّم بواسطة: '.$customer['name'] : ''),
        ];

        $orderId = $orderModel->createWithItems($orderData, $items);
        $this->model->updateStats($customer['id']);
        $this->model->maybeAutoUpgradeTier($customer['id']);

        if ($paymentMethod === 'credit') {
            Database::execute("UPDATE customers SET credit_used = credit_used + ? WHERE id=?", [$subtotal, $customer['id']]);
            // Paid immediately via N&Credit — same automatic stock-out voucher as retail orders.
            createIssueVoucherForOrder($orderId);
        }

        // Apply any active "per invoice" reward rules for this account type — e.g. a fixed or
        // percentage wallet reward on every wholesale order. Configurable from /admin/settings/rewards.
        applyRewardRules($customer['id'], 'per_order', $customer['business_type'], $subtotal);

        flashSuccess('تم إنشاء الطلب بنجاح ✅');
        $this->redirect(url('business'));
    }

    // ══════════════════════════════════════════
    // N& CREDIT APPLICATION
    // ══════════════════════════════════════════
    public function creditPage(): void
    {
        $customer = $this->requireBusinessLogin();
        $this->view('store.pages.business.credit', compact('customer'));
    }

    public function creditApply(): void
    {
        $customer = $this->requireBusinessLogin();
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect(url('business/credit')); }

        $data = [
            'id_card_number'   => trim($this->post('id_card_number','')),
            'credit_requested' => (float)$this->post('credit_requested', 0),
            'credit_status'    => 'pending',
        ];
        if (!empty($_FILES['id_card_image']['name'])) {
            $path = uploadFile($_FILES['id_card_image'], 'credit-docs');
            if ($path) $data['id_card_image'] = $path;
        }
        if (!empty($_FILES['company_papers_image']['name'])) {
            $path = uploadFile($_FILES['company_papers_image'], 'credit-docs');
            if ($path) $data['company_papers_image'] = $path;
        }

        if (empty($data['id_card_number']) || empty($data['id_card_image']) && empty($customer['id_card_image'])) {
            flashError('رقم البطاقة وصورتها مطلوبان لتقديم طلب الكريدت');
            $this->redirect(url('business/credit'));
        }

        $this->model->update($customer['id'], $data);
        flashSuccess('تم إرسال طلب الكريدت بنجاح، سيتم مراجعته من الإدارة ✅');
        $this->redirect(url('business/credit'));
    }

    // ══════════════════════════════════════════
    // REP: EXPENSES
    // ══════════════════════════════════════════
    public function expenses(): void
    {
        $customer = $this->requireBusinessLogin();
        if ($customer['business_type'] !== 'rep') { $this->redirect(url('business')); }
        $expenses = Database::fetchAll("SELECT * FROM rep_expenses WHERE rep_id=? ORDER BY created_at DESC", [$customer['id']]);
        $this->view('store.pages.business.expenses', compact('customer','expenses'));
    }

    public function expenseStore(): void
    {
        $customer = $this->requireBusinessLogin();
        if ($customer['business_type'] !== 'rep') { $this->redirect(url('business')); }
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect(url('business/expenses')); }

        $receipt = null;
        if (!empty($_FILES['receipt_image']['name'])) { $receipt = uploadFile($_FILES['receipt_image'], 'expenses'); }

        Database::insert(
            "INSERT INTO rep_expenses(rep_id,amount,type,description,receipt_image,status,created_at) VALUES(?,?,?,?,?,?,NOW())",
            [$customer['id'], (float)$this->post('amount',0), trim($this->post('type','مواصلات')), trim($this->post('description','')), $receipt, 'pending']
        );
        flashSuccess('تم إرسال طلب المصروفات، بانتظار الموافقة ✅');
        $this->redirect(url('business/expenses'));
    }

    // ══════════════════════════════════════════
    // REP: SAMPLE / GOODS REQUESTS
    // ══════════════════════════════════════════
    public function samples(): void
    {
        $customer = $this->requireBusinessLogin();
        if ($customer['business_type'] !== 'rep') { $this->redirect(url('business')); }
        $samples  = Database::fetchAll("SELECT sr.*, p.name product_name, p.thumbnail FROM sample_requests sr JOIN products p ON p.id=sr.product_id WHERE sr.rep_id=? ORDER BY sr.created_at DESC", [$customer['id']]);
        $products = Database::fetchAll("SELECT id,name FROM products WHERE status='active' ORDER BY name");
        $this->view('store.pages.business.samples', compact('customer','samples','products'));
    }

    public function sampleStore(): void
    {
        $customer = $this->requireBusinessLogin();
        if ($customer['business_type'] !== 'rep') { $this->redirect(url('business')); }
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect(url('business/samples')); }

        Database::insert(
            "INSERT INTO sample_requests(rep_id,product_id,qty,reason,status,created_at) VALUES(?,?,?,?,?,NOW())",
            [$customer['id'], (int)$this->post('product_id',0), max(1,(int)$this->post('qty',1)), trim($this->post('reason','')), 'pending']
        );
        flashSuccess('تم إرسال طلب العينة، بانتظار الموافقة ✅');
        $this->redirect(url('business/samples'));
    }

    // ══════════════════════════════════════════
    // REP: ADD MERCHANT (register a new تاجر on their behalf)
    // ══════════════════════════════════════════
    public function addMerchantForm(): void
    {
        $customer = $this->requireBusinessLogin();
        if ($customer['business_type'] !== 'rep') { $this->redirect(url('business')); }
        $merchants = Database::fetchAll("SELECT * FROM customers WHERE parent_rep_id=? ORDER BY created_at DESC", [$customer['id']]);
        $this->view('store.pages.business.add-merchant', compact('customer','merchants'));
    }

    public function addMerchantStore(): void
    {
        $customer = $this->requireBusinessLogin();
        if ($customer['business_type'] !== 'rep') { $this->redirect(url('business')); }
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect(url('business/add-merchant')); }

        $email = trim($this->post('email',''));
        if ($this->model->findByEmail($email)) { flashError('البريد الإلكتروني مستخدم مسبقاً'); $this->redirect(url('business/add-merchant')); }

        $defaultTier = Database::fetch("SELECT id FROM price_tiers WHERE is_default=1 LIMIT 1")['id'] ?? null;
        $this->model->create([
            'name'  => trim($this->post('name','')),
            'email' => $email,
            'phone' => trim($this->post('phone','')),
            'password' => password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT),
            'is_active' => 1,
            'business_type'   => 'merchant',
            'business_status' => 'pending',
            'governorate' => trim($this->post('governorate','')),
            'area'        => trim($this->post('area','')),
            'shop_name'   => trim($this->post('shop_name','')),
            'price_tier_id'  => $defaultTier,
            'parent_rep_id'  => $customer['id'],
        ]);
        flashSuccess('تم تسجيل التاجر بنجاح، بانتظار اعتماد الإدارة ✅');
        $this->redirect(url('business/add-merchant'));
    }

    public function myCommission(): void
    {
        $customer = $this->requireBusinessLogin();
        if ($customer['business_type'] !== 'rep') { $this->redirect(url('business')); }

        $orders = [];
        try {
            $orders = Database::fetchAll(
                "SELECT order_number, customer_name, total, rep_commission_percent, rep_commission_amount, status, created_at
                 FROM orders WHERE placed_by_business_id=? AND rep_commission_amount IS NOT NULL ORDER BY created_at DESC LIMIT 100",
                [$customer['id']]
            );
        } catch (\Throwable $e) {
            // The rep-commission migration hasn't been run yet — show the page with zero data
            // instead of a hard failure, so the rep at least sees their rate once it's set.
        }
        $totalCommission = array_sum(array_column($orders, 'rep_commission_amount'));
        $monthCommission = array_sum(array_map(
            fn($o) => date('Y-m', strtotime($o['created_at'])) === date('Y-m') ? (float)$o['rep_commission_amount'] : 0,
            $orders
        ));

        $this->view('store.pages.business.commission', compact('customer','orders','totalCommission','monthCommission'));
    }
}
