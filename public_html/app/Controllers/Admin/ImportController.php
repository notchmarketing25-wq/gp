<?php
class ImportController extends Controller
{
    public function __construct() { AdminAuthMiddleware::handle(); requireAdminPermission('settings'); }

    // ── Shopify products CSV import ──────────────────────────────────
    public function shopify(): void
    {
        $this->view('admin.import.shopify', []);
    }

    /** Read the header row and strip a UTF-8 BOM if present (Shopify exports often include one) */
    private function readCsvHeader($fh): ?array
    {
        $header = fgetcsv($fh);
        if (!$header) return null;
        $header = array_map('trim', $header);
        if (isset($header[0])) {
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
        }
        return $header;
    }

    /** Find the first matching column name (case-insensitive) from a list of candidates */
    private function findCol(array $colMap, array $candidates): ?string
    {
        // colMap is [lowercased header => index]; try each candidate case-insensitively
        foreach ($candidates as $cand) {
            $key = strtolower($cand);
            if (isset($colMap[$key])) return $cand;
        }
        return null;
    }

    public function previewShopify(): void
    {
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect(adminUrl('import/shopify')); }
        if (empty($_FILES['csv']['tmp_name'])) { flashError('ارفع ملف CSV'); $this->redirect(adminUrl('import/shopify')); }

        $fh = fopen($_FILES['csv']['tmp_name'], 'r');
        if (!$fh) { flashError('تعذر قراءة الملف'); $this->redirect(adminUrl('import/shopify')); }

        $header = $this->readCsvHeader($fh);
        if (!$header) { flashError('الملف فارغ'); fclose($fh); $this->redirect(adminUrl('import/shopify')); }
        $colLower = array_flip(array_map('strtolower', $header));
        $col = array_flip($header); // column name → index (original case, kept for compatibility)
        $get = function($row, $name) use ($colLower) {
            $idx = $colLower[strtolower($name)] ?? null;
            return $idx !== null ? trim($row[$idx] ?? '') : '';
        };

        $handleCol = $this->findCol($colLower, ['Handle']);
        $titleCol  = $this->findCol($colLower, ['Title']);
        if (!$handleCol || !$titleCol) {
            flashError('لم يتم التعرف على أعمدة Handle و Title — تأكد إنه ملف تصدير منتجات Shopify صحيح. الأعمدة الموجودة: ' . implode('، ', array_slice($header, 0, 8)) . '...');
            fclose($fh);
            $this->redirect(adminUrl('import/shopify'));
        }

        $products = []; // keyed by handle
        while (($row = fgetcsv($fh)) !== false) {
            if (empty($row) || !array_filter($row)) continue;
            $handle = $get($row, 'Handle');
            if (!$handle) continue;

            if (!isset($products[$handle])) {
                $products[$handle] = [
                    'handle'         => $handle,
                    'name'           => $get($row, 'Title'),
                    'description'    => $get($row, 'Body (HTML)'),
                    'vendor'         => $get($row, 'Vendor'),
                    // Shopify's "Type" column is usually short and clean (e.g. "Wallets"), but when
                    // it's empty, "Product Category" is a full Google-taxonomy path (e.g. "Apparel
                    // & Accessories > Handbags, Wallets & Cases > Wallets & Money Clips > Card
                    // Cases") — using that whole string as a collection name produced an ugly,
                    // unreadable breadcrumb, so only the last (most specific) segment is kept.
                    'type'           => $get($row, 'Type') ?: (function($cat) {
                        if (!$cat) return '';
                        $parts = array_map('trim', explode('>', $cat));
                        return end($parts);
                    })($get($row, 'Product Category')),
                    'sku'            => $get($row, 'Variant SKU'),
                    'price'          => (float)($get($row, 'Variant Price') ?: 0),
                    'compare_price'  => (float)($get($row, 'Variant Compare At Price') ?: 0),
                    'stock'          => (int)($get($row, 'Variant Inventory Qty') ?: 0),
                    'published'      => strtoupper($get($row, 'Published')) !== 'FALSE',
                    'images'         => [],
                    'variants'       => [],
                ];
            }
            $img = $get($row, 'Image Src');
            if ($img) $products[$handle]['images'][] = $img;

            // Fill in SKU/price/stock from later rows if the first row lacked them (some exports lead with an image-only row)
            if (!$products[$handle]['sku'] && $get($row,'Variant SKU')) $products[$handle]['sku'] = $get($row,'Variant SKU');
            if (!$products[$handle]['price'] && $get($row,'Variant Price')) $products[$handle]['price'] = (float)$get($row,'Variant Price');

            // Each row can represent a distinct variant (Shopify's CSV has one row per variant,
            // sharing the same Handle). Collect them so confirmShopify() can create real
            // product_variants records instead of collapsing everything into one flat product.
            $o1n = $get($row, 'Option1 Name'); $o1v = $get($row, 'Option1 Value');
            $o2n = $get($row, 'Option2 Name'); $o2v = $get($row, 'Option2 Value');
            $o3n = $get($row, 'Option3 Name'); $o3v = $get($row, 'Option3 Value');
            $variantParts = [];
            foreach ([[$o1n,$o1v],[$o2n,$o2v],[$o3n,$o3v]] as [$n,$v]) {
                if ($v && strtolower($v) !== 'default title') $variantParts[] = $v;
            }
            if (!empty($variantParts)) {
                $products[$handle]['variants'][] = [
                    'title'         => implode(' - ', $variantParts),
                    'sku'           => $get($row, 'Variant SKU'),
                    'price'         => (float)($get($row, 'Variant Price') ?: 0),
                    'compare_price' => (float)($get($row, 'Variant Compare At Price') ?: 0),
                    'stock'         => (int)($get($row, 'Variant Inventory Qty') ?: 0),
                    'image'         => $img,
                ];
            }
        }
        fclose($fh);

        if (empty($products)) {
            flashError('لم يتم العثور على منتجات صالحة في الملف');
            $this->redirect(adminUrl('import/shopify'));
        }

        // Check for existing matches (by slug=handle or sku) to flag create vs update
        foreach ($products as $h => &$p) {
            $existing = Database::fetch("SELECT id FROM products WHERE slug=? OR (sku IS NOT NULL AND sku=?)", [$h, $p['sku']]);
            $p['exists'] = (bool)$existing;
            $p['existing_id'] = $existing['id'] ?? null;
        }
        unset($p);

        $products = array_values($products);
        $batchId = 'shp_' . uniqid();
        Session::set($batchId, $products);

        $this->view('admin.import.shopify-preview', ['products' => $products, 'batchId' => $batchId]);
    }

    public function confirmShopify(): void
    {
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect(adminUrl('import/shopify')); }
        $batchId  = $this->post('batch_id', '');
        $products = Session::get($batchId);
        if (!$products) { flashError('انتهت صلاحية المعاينة، ارفع الملف مرة أخرى'); $this->redirect(adminUrl('import/shopify')); }

        $productModel   = new ProductModel();
        $brandModel     = new BrandModel();
        $collectionModel = new CollectionModel();
        $created = 0; $updated = 0; $totalVariants = 0;

        foreach ($products as $p) {
            // Find or create brand from Vendor
            $brandId = null;
            if ($p['vendor']) {
                $b = Database::fetch("SELECT id FROM brands WHERE name=?", [$p['vendor']]);
                $brandId = $b['id'] ?? $brandModel->create(['name'=>$p['vendor'],'slug'=>slug($p['vendor']).'-'.uniqid(),'is_active'=>1]);
            }
            // Find or create collection from Type
            $collectionId = null;
            if ($p['type']) {
                $c = Database::fetch("SELECT id FROM collections WHERE name=?", [$p['type']]);
                $collectionId = $c['id'] ?? $collectionModel->create(['name'=>$p['type'],'slug'=>slug($p['type']).'-'.uniqid(),'is_active'=>1]);
            }

            $data = [
                'name'          => $p['name'],
                'slug'          => $productModel->uniqueSlug(slug($p['handle']) ?: slug($p['name']), $p['existing_id']),
                'description'   => $p['description'],
                'sku'           => $p['sku'] ?: null,
                'brand_id'      => $brandId,
                'collection_id' => $collectionId,
                'price'         => $p['price'],
                'compare_price' => $p['compare_price'] ?: null,
                'stock'         => $p['stock'],
                'track_stock'   => 1,
                'status'        => $p['published'] ? 'active' : 'draft',
            ];

            if ($p['exists']) {
                $productModel->update($p['existing_id'], $data);
                $id = $p['existing_id'];
                $updated++;
            } else {
                $id = $productModel->create($data);
                $created++;
            }

            // Download & attach images (first = thumbnail, rest = gallery)
            foreach (array_slice($p['images'], 0, 6) as $i => $imgUrl) {
                $saved = $this->downloadRemoteImage($imgUrl);
                if (!$saved) continue;
                if ($i === 0) {
                    $productModel->update($id, ['thumbnail' => $saved]);
                } else {
                    $productModel->addImage($id, $saved);
                }
            }

            // Import variants (color/size/type etc.) as real product_variants rows so they show
            // up as selectable options on the storefront, same as manually-added variants.
            if (!empty($p['variants'])) {
                // Re-importing an updated product: clear old variants first so we don't duplicate.
                if ($p['exists']) {
                    Database::execute("DELETE FROM product_variants WHERE product_id=?", [$id]);
                }
                foreach ($p['variants'] as $vi => $v) {
                    if (!$v['title']) continue;
                    $variantImage = null;
                    if ($v['image']) {
                        $variantImage = $this->downloadRemoteImage($v['image']);
                    }
                    Database::insert(
                        "INSERT INTO product_variants(product_id,title,sku,price,compare_price,stock,image,sort_order,created_at) VALUES(?,?,?,?,?,?,?,?,NOW())",
                        [
                            $id, $v['title'], $v['sku'] ?: null,
                            $v['price'] ?: $p['price'], $v['compare_price'] ?: null,
                            $v['stock'], $variantImage, $vi,
                        ]
                    );
                    $totalVariants++;
                }
            }
        }

        Session::remove($batchId);
        flashSuccess("تم الاستيراد من Shopify ✅ — جديد: $created، تحديث: $updated، متغيرات: $totalVariants");
        $this->redirect(adminUrl('products'));
    }

    private function downloadRemoteImage(string $url): ?string
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) return null;
        try {
            $ctx = stream_context_create(['http' => ['timeout' => 8]]);
            $data = @file_get_contents($url, false, $ctx);
            if (!$data) return null;
            $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION)) ?: 'jpg';
            if (!in_array($ext, ['jpg','jpeg','png','gif','webp'])) $ext = 'jpg';
            $name = 'shopify_' . uniqid() . '.' . $ext;
            $dir = ROOT_PATH . '/uploads/products/';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            file_put_contents($dir . $name, $data);
            return 'products/' . $name;
        } catch (\Throwable $e) {
            return null;
        }
    }

    // ── Shopify CUSTOMERS import ─────────────────────────────────────
    public function shopifyCustomers(): void
    {
        $this->view('admin.import.shopify-customers', []);
    }

    public function previewShopifyCustomers(): void
    {
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect(adminUrl('import/shopify-customers')); }
        if (empty($_FILES['csv']['tmp_name'])) { flashError('ارفع ملف CSV'); $this->redirect(adminUrl('import/shopify-customers')); }

        $fh = fopen($_FILES['csv']['tmp_name'], 'r');
        $header = $this->readCsvHeader($fh);
        if (!$header) { flashError('الملف فارغ'); fclose($fh); $this->redirect(adminUrl('import/shopify-customers')); }
        $colLower = array_flip(array_map('strtolower', $header));
        $get = function($row, $name) use ($colLower) {
            $idx = $colLower[strtolower($name)] ?? null;
            return $idx !== null ? trim($row[$idx] ?? '') : '';
        };
        $emailCol = $this->findCol($colLower, ['Email']);
        if (!$emailCol) {
            flashError('لم يتم العثور على عمود Email في الملف. الأعمدة الموجودة: ' . implode('، ', array_slice($header, 0, 8)) . '...');
            fclose($fh);
            $this->redirect(adminUrl('import/shopify-customers'));
        }

        $customers = [];
        while (($row = fgetcsv($fh)) !== false) {
            if (empty($row) || !array_filter($row)) continue;
            $email = strtolower($get($row, 'Email'));
            if (!$email) continue;
            $name = trim($get($row,'First Name').' '.$get($row,'Last Name'));
            if (!$name) $name = $get($row,'Name');
            if (!$name) $name = $email;
            $existing = Database::fetch("SELECT id FROM customers WHERE email=?", [$email]);
            $customers[$email] = [
                'name' => $name, 'email' => $email,
                'phone' => $get($row,'Phone'), 'company' => $get($row,'Company'),
                'address' => trim($get($row,'Address1').' '.$get($row,'City')),
                'exists' => (bool)$existing, 'existing_id' => $existing['id'] ?? null,
            ];
        }
        fclose($fh);

        if (empty($customers)) { flashError('لم يتم العثور على عملاء صالحين (لازم عمود Email)'); $this->redirect(adminUrl('import/shopify-customers')); }

        $customers = array_values($customers);
        $batchId = 'shpc_' . uniqid();
        Session::set($batchId, $customers);
        $this->view('admin.import.shopify-customers-preview', ['customers' => $customers, 'batchId' => $batchId]);
    }

    public function confirmShopifyCustomers(): void
    {
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect(adminUrl('import/shopify-customers')); }
        $batchId   = $this->post('batch_id', '');
        $customers = Session::get($batchId);
        if (!$customers) { flashError('انتهت صلاحية المعاينة'); $this->redirect(adminUrl('import/shopify-customers')); }

        $model = new CustomerModel();
        $created = 0; $updated = 0;
        foreach ($customers as $c) {
            $data = ['name'=>$c['name'], 'phone'=>$c['phone']];
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
        flashSuccess("تم استيراد العملاء من Shopify ✅ — جديد: $created، تحديث: $updated");
        $this->redirect(adminUrl('customers'));
    }

    // ── Shopify ORDERS import ────────────────────────────────────────
    public function shopifyOrders(): void
    {
        $this->view('admin.import.shopify-orders', []);
    }

    public function previewShopifyOrders(): void
    {
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect(adminUrl('import/shopify-orders')); }
        if (empty($_FILES['csv']['tmp_name'])) { flashError('ارفع ملف CSV'); $this->redirect(adminUrl('import/shopify-orders')); }

        $fh = fopen($_FILES['csv']['tmp_name'], 'r');
        $header = $this->readCsvHeader($fh);
        if (!$header) { flashError('الملف فارغ'); fclose($fh); $this->redirect(adminUrl('import/shopify-orders')); }
        $colLower = array_flip(array_map('strtolower', $header));
        $get = function($row, $name) use ($colLower) {
            $idx = $colLower[strtolower($name)] ?? null;
            return $idx !== null ? trim($row[$idx] ?? '') : '';
        };

        // Shopify's order-number column is usually "Name" (e.g. #1001), but some exports use other headers
        $orderNumCol = $this->findCol($colLower, ['Name','Order Name','Order Number','Order','Order ID','Id']);
        if (!$orderNumCol) {
            flashError('لم يتم العثور على عمود رقم الطلب في الملف (المتوقع: Name أو Order Number). الأعمدة الموجودة: ' . implode('، ', array_slice($header, 0, 10)) . '...');
            fclose($fh);
            $this->redirect(adminUrl('import/shopify-orders'));
        }

        $orders = [];
        $autoNum = 0;
        while (($row = fgetcsv($fh)) !== false) {
            if (empty($row) || !array_filter($row)) continue;
            $num = $get($row, $orderNumCol);
            if (!$num) { $autoNum++; $num = 'AUTO-' . $autoNum; } // fallback: still import even without an order number

            if (!isset($orders[$num])) {
                $email = strtolower($get($row,'Email'));
                $existing = $email ? Database::fetch("SELECT id FROM customers WHERE email=?", [$email]) : null;
                $customerName = $get($row,'Billing Name') ?: $get($row,'Shipping Name');
                if (!$customerName) $customerName = trim($get($row,'First Name').' '.$get($row,'Last Name'));
                if (!$customerName) $customerName = $email ?: 'عميل';
                $orders[$num] = [
                    'order_number'   => $num,
                    'email'          => $email,
                    'customer_name'  => $customerName,
                    'phone'          => $get($row,'Phone'),
                    'address'        => $get($row,'Shipping Address1'),
                    'city'           => $get($row,'Shipping City'),
                    'gov'            => $get($row,'Shipping Province'),
                    'financial'      => $get($row,'Financial Status'),
                    'fulfillment'    => $get($row,'Fulfillment Status'),
                    'total'          => (float)($get($row,'Total') ?: 0),
                    'subtotal'       => (float)($get($row,'Subtotal') ?: 0),
                    'created_at'     => $get($row,'Created at'),
                    'customer_id'    => $existing['id'] ?? null,
                    'items'          => [],
                ];
            }
            $liName = $get($row,'Lineitem name');
            if ($liName) {
                $orders[$num]['items'][] = [
                    'name'  => $liName,
                    'sku'   => $get($row,'Lineitem sku'),
                    'qty'   => (int)($get($row,'Lineitem quantity') ?: 1),
                    'price' => (float)($get($row,'Lineitem price') ?: 0),
                ];
            }
        }
        fclose($fh);

        if (empty($orders)) { flashError('لم يتم العثور على أي صفوف بيانات في الملف'); $this->redirect(adminUrl('import/shopify-orders')); }

        foreach ($orders as $n => &$o) {
            $exists = Database::fetch("SELECT id FROM orders WHERE order_number=?", ['SHP-'.ltrim($n,'#')]);
            $o['already_imported'] = (bool)$exists;
        }
        unset($o);

        $orders = array_values($orders);
        $batchId = 'shpo_' . uniqid();
        Session::set($batchId, $orders);
        $this->view('admin.import.shopify-orders-preview', ['orders' => $orders, 'batchId' => $batchId]);
    }

    public function confirmShopifyOrders(): void
    {
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect(adminUrl('import/shopify-orders')); }
        $batchId = $this->post('batch_id', '');
        $orders  = Session::get($batchId);
        if (!$orders) { flashError('انتهت صلاحية المعاينة'); $this->redirect(adminUrl('import/shopify-orders')); }

        $orderModel    = new OrderModel();
        $customerModel = new CustomerModel();
        $created = 0; $skipped = 0; $customersCreated = 0;

        $statusMap = ['fulfilled'=>'delivered','partial'=>'processing','unfulfilled'=>'pending','restocked'=>'cancelled'];
        $payMap    = ['paid'=>'paid','partially_paid'=>'paid','pending'=>'unpaid','refunded'=>'refunded'];

        foreach ($orders as $o) {
            if ($o['already_imported']) { $skipped++; continue; }
            $orderNumber = 'SHP-' . ltrim($o['order_number'], '#');

            // Ensure a customer record exists and is linked to this order
            $customerId = $o['customer_id'];
            if (!$customerId && $o['email']) {
                $wasNew = !Database::fetch("SELECT id FROM customers WHERE email=?", [$o['email']]);
                $cust = $customerModel->findOrCreateByEmail($o['email'], $o['customer_name'], $o['phone']);
                if ($cust) { $customerId = $cust['id']; if ($wasNew) $customersCreated++; }
            }

            $orderData = [
                'order_number'     => $orderNumber,
                'customer_id'      => $customerId,
                'order_type'       => 'retail',
                'customer_name'    => $o['customer_name'],
                'customer_email'   => $o['email'],
                'customer_phone'   => $o['phone'],
                'shipping_address' => $o['address'],
                'shipping_city'    => $o['city'],
                'shipping_gov'     => $o['gov'],
                'shipping_price'   => max(0, $o['total'] - $o['subtotal']),
                'subtotal'         => $o['subtotal'] ?: $o['total'],
                'discount_code'    => null,
                'discount_amount'  => 0,
                'total'            => $o['total'],
                'payment_method'   => 'imported',
                'payment_status'   => $payMap[strtolower($o['financial'])] ?? 'unpaid',
                'status'           => $statusMap[strtolower($o['fulfillment'])] ?? 'pending',
                'notes'            => 'طلب تم استيراده من Shopify',
            ];
            $items = array_map(function($i) {
                $matched = $i['sku'] ? Database::fetch("SELECT id,thumbnail FROM products WHERE sku=?", [$i['sku']]) : null;
                return [
                    'product_id'=>$matched['id'] ?? null,'variant_id'=>null,'name'=>$i['name'],'variant'=>null,
                    'sku'=>$i['sku'],'price'=>$i['price'],'qty'=>$i['qty'],'image'=>$matched['thumbnail'] ?? null,
                ];
            }, $o['items']);

            if (empty($items)) { $skipped++; continue; }
            $orderModel->createWithItems($orderData, $items);
            $created++;
        }

        Session::remove($batchId);
        $msg = "تم استيراد $created طلب من Shopify ✅";
        if ($customersCreated) $msg .= " — تم إنشاء $customersCreated عميل جديد تلقائياً";
        if ($skipped) $msg .= " (تخطينا $skipped مستورد مسبقاً أو بدون منتجات)";
        flashSuccess($msg);
        $this->redirect(adminUrl('orders'));
    }
}
