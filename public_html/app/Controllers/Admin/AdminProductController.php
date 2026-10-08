<?php
class AdminProductController extends Controller
{
    private ProductModel    $product;
    private CollectionModel $collection;
    private BrandModel      $brand;

    public function __construct()
    {
        AdminAuthMiddleware::handle();
        requireAdminPermission('products');
        $this->product    = new ProductModel();
        $this->collection = new CollectionModel();
        $this->brand      = new BrandModel();
    }

    public function index(): void
    {
        $page    = (int)$this->get('page', 1);
        $search  = trim($this->get('search', ''));
        $status  = $this->get('status', '');
        $channel = $this->get('channel', '');

        $paginator   = $this->product->adminList($page, $search, $status, $channel);
        $channelCounts = $this->product->channelCounts();
        $statusCounts  = $this->product->statusCounts();
        $collections = $this->collection->getActive();
        $brands      = $this->brand->getActive();

        $this->view('admin.products.index', compact('paginator', 'search', 'status', 'channel', 'channelCounts', 'statusCounts', 'collections', 'brands'));
    }

    public function create(): void
    {
        $collections = $this->collection->getActive();
        $brands      = $this->brand->getActive();
        $this->view('admin.products.form', compact('collections', 'brands'));
    }

    public function store(): void
    {
        requireAdminPermission('products', 'create');
        if (!verifyCsrf()) { flashError('خطأ في التحقق'); $this->redirect($this->adminUrl('products')); }

        if (trim($this->post('name','')) === '') {
            flashError('اسم المنتج مطلوب');
            $this->redirect($this->adminUrl('products/create'));
        }

        try {
            $data = $this->buildProductData();

            // Handle thumbnail (file upload OR media library URL)
            if (!empty($_FILES['thumbnail']['name'])) {
                $path = uploadFile($_FILES['thumbnail'], 'products');
                if ($path) $data['thumbnail'] = $path;
            } elseif (!empty($_POST['thumbnail_url'])) {
                $data['thumbnail'] = ltrim(str_replace(APP_URL.'/uploads/', '', $_POST['thumbnail_url']), '/');
            }

            // Generate a guaranteed-unique slug (never crashes on collision)
            $baseSlug = !empty($data['slug']) ? slug($data['slug']) : slug($data['name']);
            if ($baseSlug === '') $baseSlug = 'product-' . uniqid();
            $data['slug'] = $this->product->uniqueSlug($baseSlug);

            $id = $this->product->create($data);

            // Upload extra images (file upload)
            if (!empty($_FILES['images']['name'][0])) {
                foreach ($_FILES['images']['name'] as $i => $name) {
                    if ($_FILES['images']['error'][$i] === 0) {
                        $file = [
                            'name'     => $name,
                            'type'     => $_FILES['images']['type'][$i],
                            'tmp_name' => $_FILES['images']['tmp_name'][$i],
                            'error'    => $_FILES['images']['error'][$i],
                            'size'     => $_FILES['images']['size'][$i],
                        ];
                        $path = uploadFile($file, 'products');
                        if ($path) $this->product->addImage($id, $path);
                    }
                }
            }

            // Extra images picked from media library
            if (!empty($_POST['media_images']) && is_array($_POST['media_images'])) {
                foreach ($_POST['media_images'] as $url) {
                    $rel = ltrim(str_replace(APP_URL.'/uploads/', '', $url), '/');
                    if ($rel) $this->product->addImage($id, $rel);
                }
            }

            $this->product->savePriceTiers($id, $this->buildTiersFromPost());

            flashSuccess('تم إضافة المنتج بنجاح ✅');
            $this->redirect($this->adminUrl('products/' . $id));
        } catch (\Throwable $e) {
            flashError('حدث خطأ أثناء إضافة المنتج: ' . $e->getMessage());
            $this->redirect($this->adminUrl('products/create'));
        }
    }

        public function saveBarcode(string $id): void
    {
        requireAdminPermission('products', 'edit');
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect($this->adminUrl('products/'.$id)); }
        $barcode = trim($this->post('barcode',''));
        if (!$barcode) { flashError('أدخل قيمة الباركود'); $this->redirect($this->adminUrl('products/'.$id)); }
        Database::execute("UPDATE products SET barcode=? WHERE id=?", [$barcode, (int)$id]);
        flashSuccess('تم حفظ الباركود ✅');
        $this->redirect($this->adminUrl('products/'.$id));
    }

        public function show(string $id): void
    {
        $product = Database::fetch("
            SELECT p.*, c.name collection_name, b.name brand_name
            FROM products p
            LEFT JOIN collections c ON c.id=p.collection_id
            LEFT JOIN brands b ON b.id=p.brand_id
            WHERE p.id=?", [(int)$id]);
        if (!$product) { flashError('المنتج غير موجود'); $this->redirect($this->adminUrl('products')); }

        $images = Database::fetchAll("SELECT * FROM product_images WHERE product_id=? ORDER BY sort_order", [(int)$id]);
        $variants = Database::fetchAll("SELECT * FROM product_variants WHERE product_id=?", [(int)$id]);

        // Stock movements
        $movements = [];
        try {
            $movements = Database::fetchAll("SELECT * FROM stock_movements WHERE product_id=? ORDER BY created_at DESC LIMIT 10", [(int)$id]);
        } catch (\Throwable $e) {}

        // Add-ons ("خد الشاحن مع الكابل") — complementary products suggested on this product's page
        $addons = [];
        try {
            $addons = Database::fetchAll(
                "SELECT pa.*, p.name addon_name, p.price addon_price, p.thumbnail addon_thumbnail, p.sku addon_sku
                 FROM product_addons pa
                 JOIN products p ON p.id = pa.addon_product_id
                 WHERE pa.product_id = ? ORDER BY pa.sort_order ASC, pa.id ASC",
                [(int)$id]
            );
        } catch (\Throwable $e) {}

        $this->view('admin.products.show', compact('product','images','variants','movements','addons'));
    }

    public function edit(string $id): void
    {
        $product = $this->product->find((int)$id);
        if (!$product) { flashError('المنتج غير موجود'); $this->redirect($this->adminUrl('products')); }

        $product['images']      = $this->product->getImages((int)$id);
        $product['variants']    = $this->product->getVariants((int)$id);
        $product['price_tiers'] = array_map(fn($t) => ['min_qty'=>$t['min_qty'],'price'=>$t['price']], $this->product->getPriceTiers((int)$id));
        $collections         = $this->collection->getActive();
        $brands              = $this->brand->getActive();

        $this->view('admin.products.form', compact('product', 'collections', 'brands'));
    }

    public function update(string $id): void
    {
        requireAdminPermission('products', 'edit');
        if (!verifyCsrf()) { flashError('خطأ في التحقق'); $this->redirect($this->adminUrl('products')); }

        $product = $this->product->find((int)$id);
        if (!$product) { flashError('المنتج غير موجود'); $this->redirect($this->adminUrl('products')); }

        if (trim($this->post('name','')) === '') {
            flashError('اسم المنتج مطلوب');
            $this->redirect($this->adminUrl('products/' . $id . '/edit'));
        }

        try {
            $data = $this->buildProductData();

            if (!empty($_FILES['thumbnail']['name'])) {
                $path = uploadFile($_FILES['thumbnail'], 'products');
                if ($path) {
                    if ($product['thumbnail']) deleteFile($product['thumbnail']);
                    $data['thumbnail'] = $path;
                }
            } elseif (!empty($_POST['thumbnail_url'])) {
                $data['thumbnail'] = ltrim(str_replace(APP_URL.'/uploads/', '', $_POST['thumbnail_url']), '/');
            }

            // Keep existing slug unless the name/slug actually changed
            if (empty($data['slug'])) {
                $data['slug'] = $product['slug'];
            } else {
                $newBase = slug($data['slug']);
                if ($newBase !== $product['slug']) {
                    $data['slug'] = $this->product->uniqueSlug($newBase, (int)$id);
                } else {
                    $data['slug'] = $product['slug'];
                }
            }

            $this->product->update((int)$id, $data);

            if (!empty($_FILES['images']['name'][0])) {
                foreach ($_FILES['images']['name'] as $i => $name) {
                    if ($_FILES['images']['error'][$i] === 0) {
                        $file = [
                            'name'     => $name,
                            'type'     => $_FILES['images']['type'][$i],
                            'tmp_name' => $_FILES['images']['tmp_name'][$i],
                            'error'    => $_FILES['images']['error'][$i],
                            'size'     => $_FILES['images']['size'][$i],
                        ];
                        $path = uploadFile($file, 'products');
                        if ($path) $this->product->addImage((int)$id, $path);
                    }
                }
            }

            if (!empty($_POST['media_images']) && is_array($_POST['media_images'])) {
                foreach ($_POST['media_images'] as $url) {
                    $rel = ltrim(str_replace(APP_URL.'/uploads/', '', $url), '/');
                    if ($rel) $this->product->addImage((int)$id, $rel);
                }
            }

            $this->product->savePriceTiers((int)$id, $this->buildTiersFromPost());

            flashSuccess('تم تحديث المنتج بنجاح ✅');
            $this->redirect($this->adminUrl('products/' . $id . '/edit'));
        } catch (\Throwable $e) {
            flashError('حدث خطأ أثناء تحديث المنتج: ' . $e->getMessage());
            $this->redirect($this->adminUrl('products/' . $id . '/edit'));
        }
    }

    /** Quick status change from the products list (no full edit form needed) */
    public function quickStatus(string $id): void
    {
        requireAdminPermission('products', 'edit');
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect($this->adminUrl('products')); }
        $status = $this->post('status', '');
        if (!in_array($status, ['active','draft','archived'], true)) { $this->redirect($this->adminUrl('products')); }
        Database::execute("UPDATE products SET status=? WHERE id=?", [$status, (int)$id]);
        flashSuccess('تم تحديث حالة المنتج ✅');
        $this->redirect($this->adminUrl('products'));
    }

    /** Inline price edit from the products list — no need to open the full edit form for a quick price change */
    public function quickPrice(string $id): void
    {
        requireAdminPermission('products', 'edit');
        if (!verifyCsrf()) { $this->json(['ok'=>false,'msg'=>'خطأ']); }
        $price = (float)$this->post('price', -1);
        if ($price < 0) { $this->json(['ok'=>false,'msg'=>'سعر غير صالح']); }
        Database::execute("UPDATE products SET price=? WHERE id=?", [$price, (int)$id]);
        $this->json(['ok'=>true,'msg'=>'تم تحديث السعر ✅','price'=>money($price)]);
    }

    /** Inline channel (retail/wholesale/both) edit from the products list */
    public function quickChannel(string $id): void
    {
        requireAdminPermission('products', 'edit');
        if (!verifyCsrf()) { $this->json(['ok'=>false,'msg'=>'خطأ']); }
        $channel = $this->post('channel', '');
        if (!in_array($channel, ['both','retail_only','wholesale_only'], true)) { $this->json(['ok'=>false,'msg'=>'اختيار غير صالح']); }
        Database::execute("UPDATE products SET channel=? WHERE id=?", [$channel, (int)$id]);
        $this->json(['ok'=>true,'msg'=>'تم تحديث مكان البيع ✅']);
    }

    /** Bulk actions on selected products: status change, or channel (retail/wholesale/both) change */
    public function bulkUpdate(): void
    {
        requireAdminPermission('products', 'edit');
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect($this->adminUrl('products')); }

        $ids = array_filter(array_map('intval', $_POST['ids'] ?? []));
        if (empty($ids)) { flashError('اختر منتج واحد على الأقل'); $this->redirect($this->adminUrl('products')); }

        $action = $this->post('bulk_action', '');
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        if ($action === 'status') {
            $status = $this->post('status', '');
            if (!in_array($status, ['active','draft','archived'], true)) { flashError('حالة غير صالحة'); $this->redirect($this->adminUrl('products')); }
            Database::execute("UPDATE products SET status=? WHERE id IN ($placeholders)", array_merge([$status], $ids));
            flashSuccess(count($ids) . ' منتج اتحدثت حالته ✅');
        } elseif ($action === 'channel') {
            $channel = $this->post('channel', '');
            if (!in_array($channel, ['both','retail_only','wholesale_only'], true)) { flashError('اختيار غير صالح'); $this->redirect($this->adminUrl('products')); }
            Database::execute("UPDATE products SET channel=? WHERE id IN ($placeholders)", array_merge([$channel], $ids));
            flashSuccess(count($ids) . ' منتج اتحدث مكان بيعه ✅');
        } elseif ($action === 'delete') {
            requireAdminPermission('products', 'delete');
            Database::execute("DELETE FROM products WHERE id IN ($placeholders)", $ids);
            flashSuccess(count($ids) . ' منتج اتحذف');
        } else {
            flashError('إجراء غير معروف');
        }
        $this->redirect($this->adminUrl('products'));
    }

    /** Lightweight JSON product search used by the inventory voucher line-item picker */
    public function searchJson(): void
    {
        $q = trim($this->get('q', ''));
        if (mb_strlen($q) < 2) { header('Content-Type: application/json'); echo '[]'; exit; }

        $results = Database::fetchAll(
            "SELECT id, name, sku, stock, thumbnail FROM products WHERE (name LIKE ? OR sku LIKE ?) AND status='active' ORDER BY name LIMIT 15",
            ["%$q%", "%$q%"]
        );
        foreach ($results as &$r) { $r['thumbnail_url'] = $r['thumbnail'] ? uploadUrl($r['thumbnail']) : null; unset($r['thumbnail']); }
        header('Content-Type: application/json');
        echo json_encode($results, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Creates a fully independent copy of a product — its own row, own stock, own images, own
     * variants — so a merchant can split a single "both" product into two separate ones (one
     * retail_only, one wholesale_only) with prices and stock that no longer affect each other.
     * The new copy also becomes the target channel directly, and starts as 'draft' so the admin
     * can review it (pricing, description) before it goes live.
     */
    public function duplicate(string $id): void
    {
        requireAdminPermission('products', 'create');
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect($this->adminUrl('products')); }

        $source = Database::fetch("SELECT * FROM products WHERE id=?", [(int)$id]);
        if (!$source) { flashError('المنتج غير موجود'); $this->redirect($this->adminUrl('products')); }

        $targetChannel = $this->post('channel', '');
        if (!in_array($targetChannel, ['both','retail_only','wholesale_only'], true)) $targetChannel = $source['channel'] ?? 'both';

        $newName = $source['name'] . ' (نسخة)';
        $baseSlug = $source['slug'] . '-copy-' . substr(uniqid(), -5);

        $newId = Database::insert(
            "INSERT INTO products(
                name,slug,description,short_desc,sku,collection_id,brand_id,
                price,compare_price,cost_price,stock,track_stock,allow_backorder,
                weight,status,is_featured,has_variants,thumbnail,
                meta_title,meta_desc,sort_order,wholesale_enabled,wholesale_min_qty,channel,created_at
            ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())",
            [
                $newName, $baseSlug, $source['description'], $source['short_desc'],
                $source['sku'] ? $source['sku'].'-COPY' : null, $source['collection_id'], $source['brand_id'],
                $source['price'], $source['compare_price'], $source['cost_price'],
                $source['stock'], $source['track_stock'], $source['allow_backorder'],
                $source['weight'], 'draft', 0, $source['has_variants'], $source['thumbnail'],
                $source['meta_title'], $source['meta_desc'], $source['sort_order'],
                $source['wholesale_enabled'], $source['wholesale_min_qty'], $targetChannel,
            ]
        );

        // Copy gallery images
        $images = Database::fetchAll("SELECT * FROM product_images WHERE product_id=?", [(int)$id]);
        foreach ($images as $img) {
            Database::insert("INSERT INTO product_images(product_id,image,alt,sort_order) VALUES(?,?,?,?)", [$newId, $img['image'], $img['alt'], $img['sort_order']]);
        }

        // Copy variants (each keeps its own stock, same as the source did)
        $variants = Database::fetchAll("SELECT * FROM product_variants WHERE product_id=?", [(int)$id]);
        foreach ($variants as $v) {
            Database::insert(
                "INSERT INTO product_variants(product_id,title,sku,price,compare_price,stock,image,sort_order,created_at) VALUES(?,?,?,?,?,?,?,?,NOW())",
                [$newId, $v['title'], $v['sku'] ? $v['sku'].'-COPY' : null, $v['price'], $v['compare_price'], $v['stock'], $v['image'], $v['sort_order']]
            );
        }

        // Copy quantity price tiers too, if any
        try {
            $tiers = Database::fetchAll("SELECT * FROM product_price_tiers WHERE product_id=?", [(int)$id]);
            foreach ($tiers as $t) {
                Database::insert("INSERT INTO product_price_tiers(product_id,min_qty,price) VALUES(?,?,?)", [$newId, $t['min_qty'], $t['price']]);
            }
        } catch (\Throwable $e) {}

        flashSuccess("تم إنشاء نسخة مستقلة من المنتج ✅ — بمخزون وسعر منفصلين تماماً. راجعها وعدّلها ثم فعّلها.");
        $this->redirect($this->adminUrl('products/'.$newId.'/edit'));
    }

    public function delete(string $id): void
    {
        requireAdminPermission('products', 'delete');
        if (!verifyCsrf()) { flashError('خطأ في التحقق'); $this->redirect($this->adminUrl('products')); }

        $product = $this->product->find((int)$id);
        if ($product) {
            if ($product['thumbnail']) deleteFile($product['thumbnail']);
            foreach ($this->product->getImages((int)$id) as $img) deleteFile($img['image']);
            $this->product->delete((int)$id);
            flashSuccess('تم حذف المنتج');
        }
        $this->redirect($this->adminUrl('products'));
    }

    /**
     * Merges a duplicate product into another: every order_items/reviews/product_addons/wishlists
     * row that points at the source product gets repointed at the target instead, so historical
     * reports read correctly off a single product. The source is then archived (never hard
     * deleted) — its variants/images stay intact in case the merge needs to be undone by hand, it
     * just stops being visible/sellable and nothing new attaches to it going forward.
     */
    public function merge(string $id): void
    {
        requireAdminPermission('products', 'edit');
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect($this->adminUrl('products/'.$id)); }

        $sourceId = (int)$id;
        $targetId = (int)$this->post('target_id', 0);

        if (!$targetId || $targetId === $sourceId) {
            flashError('اختر منتج آخر صحيح للدمج فيه');
            $this->redirect($this->adminUrl('products/'.$sourceId));
        }

        $source = Database::fetch("SELECT id, name FROM products WHERE id=?", [$sourceId]);
        $target = Database::fetch("SELECT id, name FROM products WHERE id=?", [$targetId]);
        if (!$source || !$target) {
            flashError('أحد المنتجين غير موجود');
            $this->redirect($this->adminUrl('products/'.$sourceId));
        }

        Database::beginTransaction();
        try {
            // Order history — the whole point of the merge: every past order now reports under $target.
            Database::execute("UPDATE order_items SET product_id=? WHERE product_id=?", [$targetId, $sourceId]);

            // Reviews move over as-is.
            Database::execute("UPDATE reviews SET product_id=? WHERE product_id=?", [$targetId, $sourceId]);

            // product_addons: UNIQUE(product_id, addon_product_id) means a straight UPDATE can hit a
            // duplicate if $target already has the same pairing — drop the now-redundant row first,
            // then move whatever's left.
            Database::execute("DELETE FROM product_addons WHERE product_id=? AND addon_product_id IN (SELECT addon_product_id FROM product_addons WHERE product_id=?)", [$targetId, $sourceId]);
            Database::execute("UPDATE product_addons SET product_id=? WHERE product_id=?", [$targetId, $sourceId]);
            Database::execute("DELETE FROM product_addons WHERE addon_product_id=? AND product_id IN (SELECT product_id FROM product_addons WHERE addon_product_id=?)", [$targetId, $sourceId]);
            Database::execute("UPDATE product_addons SET addon_product_id=? WHERE addon_product_id=?", [$targetId, $sourceId]);

            // wishlists: UNIQUE(customer_id, product_id) — same duplicate-guard approach.
            Database::execute("DELETE FROM wishlists WHERE product_id=? AND customer_id IN (SELECT customer_id FROM wishlists WHERE product_id=?)", [$targetId, $sourceId]);
            Database::execute("UPDATE wishlists SET product_id=? WHERE product_id=?", [$targetId, $sourceId]);

            // Source is archived, not deleted — its own stock no longer means anything once nothing
            // sells it, and the name gets a marker so it can't be confused with a live product.
            Database::execute(
                "UPDATE products SET status='archived', stock=0, name=CONCAT(name, ' (مدموج مع #', ?, ')') WHERE id=?",
                [$targetId, $sourceId]
            );

            Database::commit();
            flashSuccess('تم دمج "' . $source['name'] . '" في "' . $target['name'] . '" بنجاح — كل الطلبات والتقييمات انتقلت للمنتج الجديد');
        } catch (\Throwable $e) {
            Database::rollback();
            flashError('خطأ أثناء الدمج: ' . $e->getMessage());
            $this->redirect($this->adminUrl('products/'.$sourceId));
        }

        $this->redirect($this->adminUrl('products/'.$targetId));
    }

    public function deleteImage(string $id): void
    {
        requireAdminPermission('products', 'edit');
        if (!verifyCsrf()) { jsonResponse(false, 'خطأ'); }

        $imageId = (int)$this->post('image_id', 0);
        $img     = $this->product->deleteImage($imageId);
        if ($img) deleteFile($img['image']);

        jsonResponse(true, 'تم الحذف');
    }

    private function buildProductData(): array
    {
        return [
            'name'            => trim($this->post('name', '')),
            'slug'            => trim($this->post('slug', '')),
            'description'     => $this->post('description', ''),
            'short_desc'      => trim($this->post('short_desc', '')),
            'sku'             => trim($this->post('sku', '')) ?: null,
            'collection_id'   => $this->post('collection_id') ?: null,
            'brand_id'        => $this->post('brand_id') ?: null,
            'price'           => (float)$this->post('price', 0),
            'compare_price'   => $this->post('compare_price') ? (float)$this->post('compare_price') : null,
            'cost_price'      => $this->post('cost_price') ? (float)$this->post('cost_price') : null,
            'stock'           => (int)$this->post('stock', 0),
            'track_stock'     => $this->post('track_stock') ? 1 : 0,
            'allow_backorder' => $this->post('allow_backorder') ? 1 : 0,
            'weight'          => $this->post('weight') ? (float)$this->post('weight') : null,
            'status'          => $this->post('status', 'draft'),
            'is_featured'     => $this->post('is_featured') ? 1 : 0,
            'meta_title'      => trim($this->post('meta_title', '')),
            'meta_desc'       => trim($this->post('meta_desc', '')),
            'sort_order'      => (int)$this->post('sort_order', 0),
            'wholesale_enabled'  => $this->post('wholesale_enabled') ? 1 : 0,
            'wholesale_min_qty'  => (int)$this->post('wholesale_min_qty', 10) ?: 10,
            'channel'            => in_array($this->post('channel','both'), ['both','retail_only','wholesale_only']) ? $this->post('channel','both') : 'both',
        ];
    }

    /** Extract tier_qty[] + tier_price[] arrays from POST into [['qty'=>,'price'=>],...] */
    private function buildTiersFromPost(): array
    {
        $qtys   = $_POST['tier_qty'] ?? [];
        $prices = $_POST['tier_price'] ?? [];
        $tiers  = [];
        foreach ($qtys as $i => $q) {
            if ((int)$q > 0 && !empty($prices[$i]) && (float)$prices[$i] > 0) {
                $tiers[] = ['qty' => (int)$q, 'price' => (float)$prices[$i]];
            }
        }
        return $tiers;
    }

    // ══════════════════════════════════════════
    // PRODUCT VARIANTS (e.g. "أحمر - USB-C") — separate price/stock/image per option
    // ══════════════════════════════════════════
    public function variantStore(string $productId): void
    {
        requireAdminPermission('products', 'edit');
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect($this->adminUrl('products/'.$productId)); }

        $title = trim($this->post('title', ''));
        if (!$title) { flashError('اكتب اسم المتغير'); $this->redirect($this->adminUrl('products/'.$productId)); }

        $image = null;
        if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $up = uploadFile($_FILES['image'], 'variants');
            if ($up) $image = $up;
        }

        Database::insert(
            "INSERT INTO product_variants(product_id,title,sku,price,compare_price,stock,image,sort_order,created_at) VALUES(?,?,?,?,?,?,?,?,NOW())",
            [
                (int)$productId, $title, trim($this->post('sku','')) ?: null,
                (float)$this->post('price', 0), $this->post('compare_price') ? (float)$this->post('compare_price') : null,
                (int)$this->post('stock', 0), $image, (int)$this->post('sort_order', 0),
            ]
        );
        flashSuccess('تم إضافة المتغير ✅');
        $this->redirect($this->adminUrl('products/'.$productId));
    }

    public function variantUpdate(string $productId, string $variantId): void
    {
        requireAdminPermission('products', 'edit');
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect($this->adminUrl('products/'.$productId)); }

        $variant = Database::fetch("SELECT * FROM product_variants WHERE id=? AND product_id=?", [(int)$variantId, (int)$productId]);
        if (!$variant) { flashError('المتغير غير موجود'); $this->redirect($this->adminUrl('products/'.$productId)); }

        $image = $variant['image'];
        if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $up = uploadFile($_FILES['image'], 'variants');
            if ($up) $image = $up;
        }

        Database::execute(
            "UPDATE product_variants SET title=?,sku=?,price=?,compare_price=?,stock=?,image=?,sort_order=? WHERE id=?",
            [
                trim($this->post('title','')), trim($this->post('sku','')) ?: null,
                (float)$this->post('price', 0), $this->post('compare_price') ? (float)$this->post('compare_price') : null,
                (int)$this->post('stock', 0), $image, (int)$this->post('sort_order', 0), (int)$variantId,
            ]
        );
        flashSuccess('تم تحديث المتغير ✅');
        $this->redirect($this->adminUrl('products/'.$productId));
    }

    public function variantDelete(string $productId, string $variantId): void
    {
        requireAdminPermission('products', 'delete');
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect($this->adminUrl('products/'.$productId)); }
        Database::execute("DELETE FROM product_variants WHERE id=? AND product_id=?", [(int)$variantId, (int)$productId]);
        flashSuccess('تم حذف المتغير');
        $this->redirect($this->adminUrl('products/'.$productId));
    }

    // ───────────────────────────────────────────────────────────────────────
    // PRODUCT ADD-ONS ("خد الشاحن مع الكابل بسعر خاص") — complementary products
    // suggested on this product's storefront page, with an optional bundle
    // discount. Reuses searchJson() above for the product picker.
    // ───────────────────────────────────────────────────────────────────────

    public function addonStore(string $productId): void
    {
        requireAdminPermission('products', 'edit');
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect($this->adminUrl('products/'.$productId)); }

        $addonId = (int)$this->post('addon_product_id');
        if (!$addonId || $addonId === (int)$productId) {
            flashError('اختار منتج مختلف عن المنتج الحالي');
            $this->redirect($this->adminUrl('products/'.$productId));
        }

        $exists = Database::fetch("SELECT id FROM products WHERE id=?", [$addonId]);
        if (!$exists) { flashError('المنتج غير موجود'); $this->redirect($this->adminUrl('products/'.$productId)); }

        $discountType = in_array($this->post('discount_type', 'none'), ['none','percent','fixed'], true)
            ? $this->post('discount_type', 'none') : 'none';

        try {
            Database::insert(
                "INSERT INTO product_addons(product_id,addon_product_id,discount_type,discount_value,is_checked_default,sort_order,created_at)
                 VALUES(?,?,?,?,?,?,NOW())",
                [
                    (int)$productId, $addonId, $discountType,
                    (float)$this->post('discount_value', 0),
                    $this->post('is_checked_default') ? 1 : 0,
                    (int)$this->post('sort_order', 0),
                ]
            );
            flashSuccess('تم إضافة الإضافة ✅');
        } catch (\Throwable $e) {
            // Most likely the UNIQUE(product_id, addon_product_id) constraint — already added.
            flashError('المنتج ده مضاف كإضافة بالفعل، أو لازم تشغّل ملف database/product_addons_migration.sql الأول');
        }
        $this->redirect($this->adminUrl('products/'.$productId));
    }

    public function addonDelete(string $productId, string $addonId): void
    {
        requireAdminPermission('products', 'delete');
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect($this->adminUrl('products/'.$productId)); }
        Database::execute("DELETE FROM product_addons WHERE id=? AND product_id=?", [(int)$addonId, (int)$productId]);
        flashSuccess('تم حذف الإضافة');
        $this->redirect($this->adminUrl('products/'.$productId));
    }
}
