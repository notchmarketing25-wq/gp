<?php
// ════════════════════════════════════════════════════
// AdminSettingsController
// ════════════════════════════════════════════════════
class AdminSettingsController extends Controller
{
    public function __construct() { AdminAuthMiddleware::handle(); requireAdminPermission('settings'); }

    public function index(): void
    {
        $settings = SettingModel::getAllSettings();
        $this->view('admin.settings.index', compact('settings'));
    }

    public function update(): void
    {
        if (!verifyCsrf()) { flashError('خطأ في التحقق'); $this->redirect($this->adminUrl('settings')); }

        $tab = $_POST['_tab'] ?? 'general';

        // Each tab has its OWN fields — only save what belongs to this tab
        // Checkboxes: if NOT in POST = unchecked = '0'. Text fields: default ''
        $tabFields = [
            'general' => [
                'text'     => ['store_name','store_email','store_phone','store_phone2',
                               'store_address','store_description','store_currency',
                               'contact_whatsapp','contact_map_url'],
                'checkbox' => [],
                'files'    => ['store_logo','store_logo_white','store_favicon','store_og_image'],
            ],
            'homepage' => [
                'text'     => ['hero_title','hero_subtitle','hero_btn_text','hero_btn_url'],
                'checkbox' => [],
                'files'    => ['hero_image'],
            ],
            'appearance' => [
                'text'     => ['primary_color','store_font','theme_mode'],
                'checkbox' => [],
                'files'    => [],
            ],
            'payment' => [
                'text'     => ['cod_label','cod_fee',
                               'fawateerk_api_key',
                               'instapay_number','instapay_name',
                               'bank_transfer_details',
                               'min_order_amount','free_shipping_above'],
                'checkbox' => ['cod_active','fawateerk_active','instapay_active','bank_transfer_active'],
                'files'    => [],
            ],
            'social' => [
                'text'     => ['social_facebook','social_instagram','social_twitter',
                               'social_youtube','social_tiktok','social_snapchat','social_linkedin'],
                'checkbox' => [],
                'files'    => [],
            ],
            'seo' => [
                'text'     => ['meta_title','meta_description','google_analytics',
                               'facebook_pixel','google_search_console'],
                'checkbox' => [],
                'files'    => [],
            ],
            'system' => [
                'text'     => ['maintenance_message','items_per_page','low_stock_threshold',
                               'order_prefix','invoice_prefix','auto_cancel_hours'],
                'checkbox' => ['maintenance_mode','email_notifications','sms_notifications'],
                'files'    => [],
            ],
        ];

        $fields = $tabFields[$tab] ?? ['text'=>[],'checkbox'=>[],'files'=>[]];
        $data   = [];

        // Text fields: save as-is, default ''
        foreach ($fields['text'] as $f) {
            $data[$f] = trim($_POST[$f] ?? '');
        }
        // Checkboxes: '1' if checked, '0' if not
        foreach ($fields['checkbox'] as $f) {
            $data[$f] = isset($_POST[$f]) ? '1' : '0';
        }
        // File uploads — supports: remove checkbox, media-library URL, or direct file upload
        foreach ($fields['files'] as $field) {
            if (!empty($_POST['remove_' . $field])) {
                $data[$field] = '';
            } elseif (!empty($_FILES[$field]['name']) && $_FILES[$field]['error'] === 0) {
                $path = uploadFile($_FILES[$field], 'settings');
                if ($path) $data[$field] = $path;
            } elseif (!empty($_POST[$field . '_url'])) {
                $data[$field] = ltrim(str_replace(APP_URL.'/uploads/', '', $_POST[$field . '_url']), '/');
            }
        }

        SettingModel::setMany($data);
        flashSuccess('تم حفظ إعدادات ' . $tab . ' بنجاح ✅');
        $this->redirect($this->adminUrl('settings') . '?tab=' . $tab);
    }

    public function setTheme(): void
    {
        $theme = $_POST['theme'] ?? 'dark';
        if (!in_array($theme, ['dark','light'])) $theme = 'dark';
        SettingModel::set('theme_mode', $theme);
        $this->json(['ok'=>true]);
    }

    public function shipping(): void
    {
        $zones = Database::fetchAll("SELECT * FROM `shipping_zones` ORDER BY `id` ASC");
        $this->view('admin.settings.shipping', compact('zones'));
    }

    public function updateShipping(): void
    {
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect($this->adminUrl('settings/shipping')); }

        $name      = trim($this->post('name', ''));
        $price     = (float)$this->post('price', 0);
        $freeAbove = $this->post('free_above') ? (float)$this->post('free_above') : null;
        $isActive  = isset($_POST['is_active']) ? 1 : 0;
        // Governorates come as checkbox array
        $govs      = array_filter(array_map('trim', $_POST['governorates'] ?? []));

        Database::insert(
            "INSERT INTO `shipping_zones` (`name`,`price`,`free_above`,`is_active`,`governorates`) VALUES (?,?,?,?,?)",
            [$name, $price, $freeAbove, $isActive, json_encode(array_values($govs), JSON_UNESCAPED_UNICODE)]
        );

        flashSuccess('تم إضافة منطقة الشحن');
        $this->redirect($this->adminUrl('settings/shipping'));
    }

    public function editShipping(string $id): void
    {
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect($this->adminUrl('settings/shipping')); }

        $name      = trim($this->post('name', ''));
        $price     = (float)$this->post('price', 0);
        $freeAbove = $this->post('free_above') ? (float)$this->post('free_above') : null;
        $isActive  = isset($_POST['is_active']) ? 1 : 0;
        $govs      = array_filter(array_map('trim', $_POST['governorates'] ?? []));

        Database::execute(
            "UPDATE `shipping_zones` SET `name`=?,`price`=?,`free_above`=?,`is_active`=?,`governorates`=? WHERE `id`=?",
            [$name, $price, $freeAbove, $isActive, json_encode(array_values($govs), JSON_UNESCAPED_UNICODE), (int)$id]
        );

        flashSuccess('تم تحديث منطقة الشحن');
        $this->redirect($this->adminUrl('settings/shipping'));
    }

    public function deleteShipping(string $id): void
    {
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect($this->adminUrl('settings/shipping')); }
        Database::execute("DELETE FROM `shipping_zones` WHERE `id` = ?", [(int)$id]);
        flashSuccess('تم حذف منطقة الشحن');
        $this->redirect($this->adminUrl('settings/shipping'));
    }
}

// ════════════════════════════════════════════════════
// AdminCollectionController
// ════════════════════════════════════════════════════
class AdminCollectionController extends Controller
{
    private CollectionModel $model;
    public function __construct() { AdminAuthMiddleware::handle(); requireAdminPermission('products'); $this->model = new CollectionModel(); }

    public function index(): void
    {
        $collections = $this->model->withProductCount();
        $this->view('admin.collections.index', compact('collections'));
    }

    public function create(): void
    {
        $this->view('admin.collections.form');
    }

    public function store(): void
    {
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect($this->adminUrl('collections')); }
        $data = $this->buildData();
        if (!empty($_FILES['image']['name'])) {
            $path = uploadFile($_FILES['image'], 'categories');
            if ($path) $data['image'] = $path;
        }
        if (empty($data['slug'])) $data['slug'] = slug($data['name']) . '-' . time();
        $this->model->create($data);
        flashSuccess('تم إضافة التصنيف');
        $this->redirect($this->adminUrl('collections'));
    }

    public function edit(string $id): void
    {
        $collection = $this->model->find((int)$id);
        if (!$collection) { flashError('التصنيف غير موجود'); $this->redirect($this->adminUrl('collections')); }
        $this->view('admin.collections.form', compact('collection'));
    }

    public function update(string $id): void
    {
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect($this->adminUrl('collections')); }
        $data = $this->buildData();
        $col  = $this->model->find((int)$id);
        if (!empty($_FILES['image']['name'])) {
            $path = uploadFile($_FILES['image'], 'categories');
            if ($path) { if ($col['image']) deleteFile($col['image']); $data['image'] = $path; }
        }
        if (empty($data['slug'])) $data['slug'] = $col['slug'];
        $this->model->update((int)$id, $data);
        flashSuccess('تم تحديث التصنيف');
        $this->redirect($this->adminUrl('collections'));
    }

    public function delete(string $id): void
    {
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect($this->adminUrl('collections')); }
        $col = $this->model->find((int)$id);
        if ($col) { if ($col['image']) deleteFile($col['image']); $this->model->delete((int)$id); flashSuccess('تم الحذف'); }
        $this->redirect($this->adminUrl('collections'));
    }

    /** Merge a duplicate collection into another: reassigns all its products, then deletes it */
    public function merge(): void
    {
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect($this->adminUrl('collections')); }
        $fromId = (int)$this->post('from_id', 0);
        $toId   = (int)$this->post('to_id', 0);
        if (!$fromId || !$toId || $fromId === $toId) { flashError('اختر تصنيفين مختلفين'); $this->redirect($this->adminUrl('collections')); }

        $moved = Database::execute("UPDATE products SET collection_id=? WHERE collection_id=?", [$toId, $fromId]);
        $from = $this->model->find($fromId);
        if ($from && $from['image']) deleteFile($from['image']);
        $this->model->delete($fromId);

        flashSuccess("تم دمج التصنيف ونقل منتجاته ✅");
        $this->redirect($this->adminUrl('collections'));
    }

    private function buildData(): array
    {
        return [
            'name'        => trim($this->post('name', '')),
            'slug'        => trim($this->post('slug', '')),
            'description' => $this->post('description', ''),
            'is_active'   => $this->post('is_active') ? 1 : 0,
            'sort_order'  => (int)$this->post('sort_order', 0),
            'meta_title'  => trim($this->post('meta_title', '')),
            'meta_desc'   => trim($this->post('meta_desc', '')),
        ];
    }
}

// ════════════════════════════════════════════════════
// AdminBrandController
// ════════════════════════════════════════════════════
class AdminBrandController extends Controller
{
    private BrandModel $model;
    public function __construct() { AdminAuthMiddleware::handle(); requireAdminPermission('products'); $this->model = new BrandModel(); }

    public function index(): void
    {
        $brands = $this->model->all('sort_order', 'ASC');
        $this->view('admin.collections.brands', compact('brands'));
    }

    public function store(): void
    {
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect($this->adminUrl('brands')); }
        $data = ['name' => trim($this->post('name','')), 'slug' => slug(trim($this->post('name',''))).'_'.time(), 'is_active' => 1, 'sort_order' => (int)$this->post('sort_order',0)];
        if (!empty($_FILES['logo']['name'])) { $path = uploadFile($_FILES['logo'], 'brands'); if ($path) $data['logo'] = $path; }
        $this->model->create($data);
        flashSuccess('تم إضافة الماركة');
        $this->redirect($this->adminUrl('brands'));
    }

    public function update(string $id): void
    {
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect($this->adminUrl('brands')); }
        $data = ['name' => trim($this->post('name','')), 'is_active' => $this->post('is_active') ? 1 : 0, 'sort_order' => (int)$this->post('sort_order',0)];
        if (!empty($_FILES['logo']['name'])) { $path = uploadFile($_FILES['logo'], 'brands'); if ($path) $data['logo'] = $path; }
        $this->model->update((int)$id, $data);
        flashSuccess('تم التحديث');
        $this->redirect($this->adminUrl('brands'));
    }

    public function delete(string $id): void
    {
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect($this->adminUrl('brands')); }
        $this->model->delete((int)$id);
        flashSuccess('تم الحذف');
        $this->redirect($this->adminUrl('brands'));
    }

    /** Merge a duplicate brand into another: reassigns all its products, then deletes it */
    public function merge(): void
    {
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect($this->adminUrl('brands')); }
        $fromId = (int)$this->post('from_id', 0);
        $toId   = (int)$this->post('to_id', 0);
        if (!$fromId || !$toId || $fromId === $toId) { flashError('اختر ماركتين مختلفتين'); $this->redirect($this->adminUrl('brands')); }

        Database::execute("UPDATE products SET brand_id=? WHERE brand_id=?", [$toId, $fromId]);
        $this->model->delete($fromId);

        flashSuccess("تم دمج الماركة ونقل منتجاتها ✅");
        $this->redirect($this->adminUrl('brands'));
    }
}

// ════════════════════════════════════════════════════
// AdminCustomerController
// ════════════════════════════════════════════════════
class AdminCustomerController extends Controller
{
    private CustomerModel $model;
    public function __construct() { AdminAuthMiddleware::handle(); requireAdminPermission('customers'); $this->model = new CustomerModel(); }

    public function index(): void
    {
        $page      = (int)$this->get('page', 1);
        $search    = trim($this->get('search', ''));
        $paginator = $this->model->adminList($page, $search);
        $this->view('admin.customers.index', compact('paginator', 'search'));
    }

    public function show(string $id): void
    {
        $customer = $this->model->find((int)$id);
        if (!$customer) { flashError('العميل غير موجود'); $this->redirect($this->adminUrl('customers')); }
        $orders = Database::fetchAll("SELECT * FROM `orders` WHERE customer_id = ? ORDER BY created_at DESC", [(int)$id]);
        $address = Database::fetch("SELECT * FROM customer_addresses WHERE customer_id=? ORDER BY is_default DESC, id DESC LIMIT 1", [(int)$id]);
        $creditHistory = $this->model->creditHistory((int)$id, 10);

        $recentViews = [];
        try {
            $recentViews = Database::fetchAll(
                "SELECT p.id, p.name, p.thumbnail, p.slug, MAX(pv.created_at) last_viewed, COUNT(*) view_count
                 FROM page_views pv JOIN products p ON p.id = pv.product_id
                 WHERE pv.customer_id=? AND pv.page_type='product'
                 GROUP BY pv.product_id ORDER BY last_viewed DESC LIMIT 10",
                [(int)$id]
            );
        } catch (\Throwable $e) {}

        $this->view('admin.customers.show', compact('customer', 'orders', 'address', 'creditHistory', 'recentViews'));
    }

    public function toggle(string $id): void
    {
        requireAdminPermission('customers', 'edit');
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect($this->adminUrl('customers')); }
        $c = $this->model->find((int)$id);
        if ($c) { $this->model->update((int)$id, ['is_active' => $c['is_active'] ? 0 : 1]); flashSuccess('تم تغيير حالة العميل'); }
        $this->redirect($this->adminUrl('customers'));
    }

    public function approveWholesale(string $id): void
    {
        requireAdminPermission('customers', 'edit');
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect($this->adminUrl('customers/'.$id)); }
        $this->model->update((int)$id, ['wholesale_status' => 'approved']);
        $c = $this->model->find((int)$id);
        if ($c) { try { EmailService::send('wholesale_approved', $c['email'], $c['name'], ['name'=>$c['name']], 'customer', (int)$id); } catch (\Throwable $e) {} }
        flashSuccess('تم اعتماد الحساب كحساب جملة ✅');
        $this->redirect($this->adminUrl('customers/'.$id));
    }

    public function rejectWholesale(string $id): void
    {
        requireAdminPermission('customers', 'edit');
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect($this->adminUrl('customers/'.$id)); }
        $this->model->update((int)$id, ['wholesale_status' => 'rejected']);
        flashSuccess('تم رفض طلب حساب الجملة');
        $this->redirect($this->adminUrl('customers/'.$id));
    }

    public function adjustCredit(string $id): void
    {
        requireAdminPermission('customers', 'edit');
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect($this->adminUrl('customers/'.$id)); }
        $customer = $this->model->find((int)$id);
        if (!$customer) { flashError('العميل غير موجود'); $this->redirect($this->adminUrl('customers')); }

        $type   = $this->post('type', 'add'); // add | deduct
        $amount = abs((float)$this->post('amount', 0));
        $reason = trim($this->post('reason', ''));
        if ($amount <= 0) { flashError('أدخل مبلغ صحيح'); $this->redirect($this->adminUrl('customers/'.$id)); }

        $delta = $type === 'deduct' ? -$amount : $amount;
        $admin = adminUser();
        $this->model->adjustCredit((int)$id, $delta, $reason ?: ($type==='deduct'?'خصم يدوي':'إضافة يدوية'), null, $admin['id'] ?? null);

        flashSuccess($type==='deduct' ? "تم خصم " . money($amount) . " من رصيد العميل" : "تم إضافة " . money($amount) . " لرصيد العميل");
        $this->redirect($this->adminUrl('customers/'.$id));
    }
}

// ════════════════════════════════════════════════════
// AdminDiscountController
// ════════════════════════════════════════════════════
class AdminDiscountController extends Controller
{
    private DiscountModel $model;
    public function __construct() { AdminAuthMiddleware::handle(); requireAdminPermission('marketing'); $this->model = new DiscountModel(); }

    public function index(): void
    {
        $page      = (int)$this->get('page', 1);
        $paginator = $this->model->adminList($page);
        $this->view('admin.discounts.index', compact('paginator'));
    }

    public function store(): void
    {
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect($this->adminUrl('discounts')); }
        $data = [
            'code'       => strtoupper(trim($this->post('code', ''))),
            'type'       => $this->post('type', 'percentage'),
            'value'      => (float)$this->post('value', 0),
            'min_order'  => $this->post('min_order') ? (float)$this->post('min_order') : null,
            'max_uses'   => $this->post('max_uses') ? (int)$this->post('max_uses') : null,
            'starts_at'  => $this->post('starts_at') ?: null,
            'expires_at' => $this->post('expires_at') ?: null,
            'is_active'  => 1,
        ];
        try { $this->model->create($data); flashSuccess('تم إضافة كود الخصم'); }
        catch (\Exception $e) { flashError('الكود مستخدم مسبقاً'); }
        $this->redirect($this->adminUrl('discounts'));
    }

    public function toggle(string $id): void
    {
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect($this->adminUrl('discounts')); }
        $d = $this->model->find((int)$id);
        if ($d) { $this->model->update((int)$id, ['is_active' => $d['is_active'] ? 0 : 1]); }
        flashSuccess('تم تغيير الحالة');
        $this->redirect($this->adminUrl('discounts'));
    }

    public function delete(string $id): void
    {
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect($this->adminUrl('discounts')); }
        $this->model->delete((int)$id);
        flashSuccess('تم الحذف');
        $this->redirect($this->adminUrl('discounts'));
    }
}

// ════════════════════════════════════════════════════
// AdminAnalyticsController
// ════════════════════════════════════════════════════
class AdminAnalyticsController extends Controller
{
    public function __construct() { AdminAuthMiddleware::handle(); requireAdminPermission('analytics'); }

    public function index(): void
    {
        $range = $this->get('range', '30');
        $days  = max(1, min(365, (int)$range));
        $from  = date('Y-m-d 00:00:00', strtotime("-{$days} days"));

        $orderModel = new OrderModel();
        $stats      = $orderModel->stats();
        $salesChart = $orderModel->salesChart($days);

        // ── Top-level KPIs (Shopify-style) ──
        $grossSales = (float)(Database::fetch("SELECT COALESCE(SUM(subtotal),0) v FROM orders WHERE created_at>=?", [$from])['v'] ?? 0);
        $discounts  = (float)(Database::fetch("SELECT COALESCE(SUM(discount_amount),0) v FROM orders WHERE created_at>=?", [$from])['v'] ?? 0);
        $shipping   = (float)(Database::fetch("SELECT COALESCE(SUM(shipping_price),0) v FROM orders WHERE created_at>=?", [$from])['v'] ?? 0);
        $totalSales = (float)(Database::fetch("SELECT COALESCE(SUM(total),0) v FROM orders WHERE created_at>=?", [$from])['v'] ?? 0);
        $netSales   = $grossSales - $discounts;
        $ordersCount = (int)(Database::fetch("SELECT COUNT(*) c FROM orders WHERE created_at>=?", [$from])['c'] ?? 0);
        $ordersFulfilled = (int)(Database::fetch("SELECT COUNT(*) c FROM orders WHERE created_at>=? AND status='delivered'", [$from])['c'] ?? 0);
        $aov = $ordersCount > 0 ? $totalSales / $ordersCount : 0;

        // Returning customer rate: customers with >1 order overall / customers with >=1 order
        $customersWithOrders  = (int)(Database::fetch("SELECT COUNT(*) c FROM customers WHERE total_orders>=1")['c'] ?? 0);
        $returningCustomers   = (int)(Database::fetch("SELECT COUNT(*) c FROM customers WHERE total_orders>1")['c'] ?? 0);
        $returningRate = $customersWithOrders > 0 ? round($returningCustomers / $customersWithOrders * 100, 1) : 0;

        // Sales by "channel" — we track retail vs wholesale as our two real channels
        $salesByChannel = Database::fetchAll(
            "SELECT order_type, COUNT(*) cnt, COALESCE(SUM(total),0) revenue FROM orders WHERE created_at>=? GROUP BY order_type",
            [$from]
        );

        // AOV over time (daily average order value)
        $aovChart = Database::fetchAll(
            "SELECT DATE(created_at) date, COUNT(*) cnt, COALESCE(SUM(total),0) revenue
             FROM orders WHERE created_at>=? GROUP BY DATE(created_at) ORDER BY date",
            [$from]
        );

        $topProducts = Database::fetchAll(
            "SELECT p.name, p.thumbnail, SUM(oi.qty) as sold, SUM(oi.total) as revenue
             FROM `order_items` oi JOIN `products` p ON p.id = oi.product_id
             JOIN `orders` o ON o.id = oi.order_id
             WHERE o.created_at >= ?
             GROUP BY oi.product_id ORDER BY revenue DESC LIMIT 10",
            [$from]
        );
        $topGovs = Database::fetchAll(
            "SELECT shipping_gov, COUNT(*) as orders, SUM(total) as revenue
             FROM `orders` WHERE payment_status='paid' AND created_at>=?
             GROUP BY shipping_gov ORDER BY orders DESC LIMIT 10",
            [$from]
        );
        $paymentMix = Database::fetchAll(
            "SELECT payment_method, COUNT(*) as cnt FROM `orders` WHERE created_at>=? GROUP BY payment_method",
            [$from]
        );

        // Traffic/view analytics — total visits, unique visitors, most-viewed products
        $totalVisits = 0; $uniqueVisitors = 0; $mostViewedProducts = [];
        try {
            $totalVisits    = (int)(Database::fetch("SELECT COUNT(*) c FROM page_views WHERE created_at>=?", [$from])['c'] ?? 0);
            $uniqueVisitors = (int)(Database::fetch("SELECT COUNT(DISTINCT visitor_key) c FROM page_views WHERE created_at>=?", [$from])['c'] ?? 0);
            $mostViewedProducts = Database::fetchAll(
                "SELECT p.id, p.name, p.thumbnail, COUNT(*) views, COUNT(DISTINCT pv.visitor_key) unique_views
                 FROM page_views pv JOIN products p ON p.id = pv.product_id
                 WHERE pv.page_type='product' AND pv.created_at>=?
                 GROUP BY pv.product_id ORDER BY views DESC LIMIT 10",
                [$from]
            );
        } catch (\Throwable $e) {
            // page_views migration not run yet — analytics section just shows zeros/empty instead of crashing
        }

        $this->view('admin.analytics.index', compact(
            'stats','salesChart','topProducts','topGovs','paymentMix','range','days',
            'grossSales','discounts','shipping','totalSales','netSales','ordersCount',
            'ordersFulfilled','aov','returningRate','salesByChannel','aovChart',
            'totalVisits','uniqueVisitors','mostViewedProducts'
        ));
    }

    public function export(): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=orders-' . date('Y-m-d') . '.csv');
        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM
        fputcsv($out, ['رقم الطلب','العميل','الهاتف','المدينة','المحافظة','الإجمالي','الدفع','الحالة','التاريخ']);
        $orders = Database::fetchAll("SELECT * FROM `orders` ORDER BY created_at DESC");
        foreach ($orders as $o) {
            fputcsv($out, [$o['order_number'],$o['customer_name'],$o['customer_phone'],$o['shipping_city'],$o['shipping_gov'],$o['total'],$o['payment_method'],$o['status'],$o['created_at']]);
        }
        fclose($out);
    }
}

// ════════════════════════════════════════════════════
// AdminReviewController
// ════════════════════════════════════════════════════
class AdminReviewController extends Controller
{
    public function __construct() { AdminAuthMiddleware::handle(); requireAdminPermission('marketing'); }

    public function index(): void
    {
        $reviews = Database::fetchAll("SELECT r.*, p.name as product_name FROM `reviews` r LEFT JOIN `products` p ON p.id = r.product_id ORDER BY r.created_at DESC");
        $this->view('admin.reviews.index', compact('reviews'));
    }

    public function approve(string $id): void
    {
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect($this->adminUrl('reviews')); }
        Database::execute("UPDATE `reviews` SET `is_approved` = 1 WHERE `id` = ?", [(int)$id]);
        flashSuccess('تم الموافقة على التقييم');
        $this->redirect($this->adminUrl('reviews'));
    }

    public function delete(string $id): void
    {
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect($this->adminUrl('reviews')); }
        Database::execute("DELETE FROM `reviews` WHERE `id` = ?", [(int)$id]);
        flashSuccess('تم الحذف');
        $this->redirect($this->adminUrl('reviews'));
    }
}
