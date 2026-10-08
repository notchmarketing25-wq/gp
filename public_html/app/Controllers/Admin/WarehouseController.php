<?php
class WarehouseController extends Controller
{
    public function __construct() { AdminAuthMiddleware::handle(); requireAdminPermission('products'); }

    // ── Main warehouse page (stock overview) ─────────────────────
    // Once a product has variants (sizes/colors), its own `stock` column is vestigial — the real,
    // sellable quantity lives per-variant. So for any product that has variants, every figure here
    // (the row's displayed stock, the out-of-stock/low-stock counters, the stock value) is computed
    // from SUM(variants.stock) instead of the parent row, and the admin can expand the product to
    // see/edit each variant's own stock individually.
    public function index(): void
    {
        $products = Database::fetchAll("
            SELECT p.id, p.name, p.sku, p.thumbnail, p.stock, p.track_stock,
                   b.name brand_name, c.name collection_name,
                   p.price, p.cost_price
            FROM products p
            LEFT JOIN brands b ON b.id = p.brand_id
            LEFT JOIN collections c ON c.id = p.collection_id
            WHERE p.status != 'archived'
            ORDER BY p.name ASC
        ");

        $variantsByProduct = [];
        if ($products) {
            $ids = array_column($products, 'id');
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $allVariants = Database::fetchAll(
                "SELECT id, product_id, title, sku, price, stock FROM product_variants WHERE product_id IN ($placeholders) ORDER BY sort_order ASC, id ASC",
                $ids
            );
            foreach ($allVariants as $v) {
                $variantsByProduct[$v['product_id']][] = $v;
            }
        }

        // Attach the effective stock figure to each product row and roll up stats from it,
        // rather than trusting products.stock for anything that actually has variants.
        $outOfStock = 0; $lowStock = 0; $totalValue = 0.0; $totalCost = 0.0; $activeCount = 0;
        foreach ($products as &$p) {
            $variants = $variantsByProduct[$p['id']] ?? [];
            $p['has_variants'] = !empty($variants);
            $p['variants'] = $variants;
            $p['effective_stock'] = $p['has_variants']
                ? array_sum(array_column($variants, 'stock'))
                : (int)$p['stock'];
        }
        unset($p);

        // Stats are scoped to active products only, same as before.
        $activeProducts = Database::fetchAll("SELECT id FROM products WHERE status='active'");
        $activeIds = array_flip(array_column($activeProducts, 'id'));
        foreach ($products as $p) {
            if (!isset($activeIds[$p['id']])) continue;
            $activeCount++;
            if (!$p['track_stock']) continue;
            $stock = $p['effective_stock'];
            if ($stock === 0) $outOfStock++;
            elseif ($stock <= 5) $lowStock++;
            $totalValue += $stock * (float)($p['price'] ?? 0);
            $totalCost  += $stock * (float)($p['cost_price'] ?? 0);
        }
        $stats = [
            'total_products' => $activeCount,
            'out_of_stock'   => $outOfStock,
            'low_stock'      => $lowStock,
            'total_value'    => $totalValue,
            'total_cost'     => $totalCost,
        ];

        $movements = Database::fetchAll("
            SELECT m.*, p.name product_name, p.thumbnail
            FROM stock_movements m
            JOIN products p ON p.id = m.product_id
            ORDER BY m.created_at DESC LIMIT 20
        ");
        $warehouses = Database::fetchAll("SELECT * FROM warehouses WHERE is_active=1 ORDER BY name");
        $this->view('admin.warehouse.index', compact('products','stats','movements','warehouses'));
    }

    // ── Adjust stock ─────────────────────────────────────────────
    // variant_id is optional: when present, the adjustment targets that variant's own stock
    // (product_variants.stock) instead of the parent product's — variant products are tracked
    // entirely at the variant level, so the parent row is never touched here for them.
    public function adjust(): void
    {
        requireAdminPermission('products', 'edit');
        if (!verifyCsrf()) { $this->json(['ok'=>false,'msg'=>'خطأ']); }
        $pid    = (int)$this->post('product_id');
        $vid    = (int)$this->post('variant_id', 0);
        $qty    = (int)$this->post('qty');
        $type   = $this->post('type','adjustment'); // in|out|adjustment
        $reason = trim($this->post('reason',''));
        $notes  = trim($this->post('notes',''));

        if (!$pid) { $this->json(['ok'=>false,'msg'=>'منتج غير صحيح']); }

        if ($vid) {
            $v = Database::fetch("SELECT id,title,stock FROM product_variants WHERE id=? AND product_id=?", [$vid, $pid]);
            if (!$v) { $this->json(['ok'=>false,'msg'=>'الاختيار غير موجود']); }

            $before = (int)$v['stock'];
            $after  = match($type) {
                'in'         => $before + abs($qty),
                'out'        => max(0, $before - abs($qty)),
                'adjustment' => max(0, $qty),
                default      => $before,
            };

            Database::beginTransaction();
            try {
                Database::execute("UPDATE product_variants SET stock=? WHERE id=?", [$after, $vid]);
                Database::insert("INSERT INTO stock_movements(product_id,type,qty,qty_before,qty_after,reason,notes,admin_id,created_at) VALUES(?,?,?,?,?,?,?,?,NOW())",
                    [$pid, $type, $qty, $before, $after, trim(($reason ?: '') . ' — الاختيار: ' . $v['title']), $notes, adminUser()['id']??null]);
                Database::commit();
                $this->json(['ok'=>true,'msg'=>'تم تحديث المخزون','before'=>$before,'after'=>$after]);
            } catch (\Throwable $e) {
                Database::rollback();
                $this->json(['ok'=>false,'msg'=>'خطأ: '.$e->getMessage()]);
            }
            return;
        }

        $p = Database::fetch("SELECT id,name,stock,track_stock FROM products WHERE id=?", [$pid]);
        if (!$p) { $this->json(['ok'=>false,'msg'=>'المنتج غير موجود']); }

        $before = (int)$p['stock'];
        $after  = match($type) {
            'in'         => $before + abs($qty),
            'out'        => max(0, $before - abs($qty)),
            'adjustment' => max(0, $qty),
            default      => $before,
        };

        Database::beginTransaction();
        try {
            Database::execute("UPDATE products SET stock=?, updated_at=NOW() WHERE id=?", [$after, $pid]);
            Database::insert("INSERT INTO stock_movements(product_id,type,qty,qty_before,qty_after,reason,notes,admin_id,created_at) VALUES(?,?,?,?,?,?,?,?,NOW())",
                [$pid, $type, $qty, $before, $after, $reason, $notes, adminUser()['id']??null]);
            Database::commit();
            $this->json(['ok'=>true,'msg'=>'تم تحديث المخزون','before'=>$before,'after'=>$after]);
        } catch (\Throwable $e) {
            Database::rollback();
            $this->json(['ok'=>false,'msg'=>'خطأ: '.$e->getMessage()]);
        }
    }

    // ── Bulk save (spreadsheet-style quick edit — multiple products/variants at once) ──
    public function bulkSave(): void
    {
        requireAdminPermission('products', 'edit');
        if (!verifyCsrf()) { $this->json(['ok'=>false,'msg'=>'خطأ']); }
        $stocks        = $_POST['stock'] ?? [];         // [product_id => new_qty] — simple products only
        $variantStocks = $_POST['variant_stock'] ?? [];  // [variant_id => new_qty]
        if (empty($stocks) && empty($variantStocks)) { $this->json(['ok'=>false,'msg'=>'لا توجد تغييرات']); }

        $admin = adminUser();
        $updated = 0;

        foreach ($stocks as $pid => $newQty) {
            $pid = (int)$pid; $newQty = max(0, (int)$newQty);
            $p = Database::fetch("SELECT stock FROM products WHERE id=?", [$pid]);
            if (!$p) continue;
            $before = (int)$p['stock'];
            if ($before === $newQty) continue; // skip unchanged

            Database::execute("UPDATE products SET stock=?, updated_at=NOW() WHERE id=?", [$newQty, $pid]);
            Database::insert("INSERT INTO stock_movements(product_id,type,qty,qty_before,qty_after,reason,admin_id,created_at) VALUES(?,?,?,?,?,?,?,NOW())",
                [$pid, 'adjustment', $newQty, $before, $newQty, 'تعديل سريع (جدول)', $admin['id']??null]);
            $updated++;
        }

        foreach ($variantStocks as $vid => $newQty) {
            $vid = (int)$vid; $newQty = max(0, (int)$newQty);
            $v = Database::fetch("SELECT product_id, title, stock FROM product_variants WHERE id=?", [$vid]);
            if (!$v) continue;
            $before = (int)$v['stock'];
            if ($before === $newQty) continue; // skip unchanged

            Database::execute("UPDATE product_variants SET stock=? WHERE id=?", [$newQty, $vid]);
            Database::insert("INSERT INTO stock_movements(product_id,type,qty,qty_before,qty_after,reason,admin_id,created_at) VALUES(?,?,?,?,?,?,?,NOW())",
                [$v['product_id'], 'adjustment', $newQty, $before, $newQty, 'تعديل سريع (جدول) — الاختيار: ' . $v['title'], $admin['id']??null]);
            $updated++;
        }

        $this->json(['ok'=>true,'msg'=>"تم تحديث $updated عنصر",'updated'=>$updated]);
    }

    // ── Bulk adjust (CSV upload) ──────────────────────────────────
    public function bulkAdjust(): void
    {
        requireAdminPermission('products', 'edit');
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect(adminUrl('warehouse')); }
        if (empty($_FILES['csv']['tmp_name'])) { flashError('يرجى رفع ملف CSV'); $this->redirect(adminUrl('warehouse')); }

        $lines = array_slice(file($_FILES['csv']['tmp_name']), 1); // skip header
        $done = 0; $errors = 0;
        foreach ($lines as $line) {
            $line = trim($line);
            if (!$line) continue;
            [$sku, $qty] = array_map('trim', explode(',', $line));
            $p = Database::fetch("SELECT id,stock FROM products WHERE sku=?", [$sku]);
            if (!$p) { $errors++; continue; }
            $before = (int)$p['stock'];
            $after  = max(0, (int)$qty);
            Database::execute("UPDATE products SET stock=? WHERE id=?", [$after, $p['id']]);
            Database::insert("INSERT INTO stock_movements(product_id,type,qty,qty_before,qty_after,reason,created_at) VALUES(?,?,?,?,?,?,NOW())",
                [$p['id'],'adjustment',$after,$before,$after,'استيراد CSV']);
            $done++;
        }
        flashSuccess("تم تحديث $done منتج" . ($errors ? " — $errors أخطاء" : ''));
        $this->redirect(adminUrl('warehouse'));
    }

    // ── Movements log ─────────────────────────────────────────────
    public function movements(): void
    {
        $page = max(1,(int)($this->get('page',1)));
        $pid  = (int)$this->get('product_id',0);
        $type = $this->get('type','');
        $off  = ($page-1)*30;
        $where=['1=1']; $params=[];
        if ($pid)  { $where[]='m.product_id=?'; $params[]=$pid; }
        if ($type) { $where[]='m.type=?';       $params[]=$type; }
        $w = implode(' AND ',$where);
        $total = (int)(Database::fetch("SELECT COUNT(*) c FROM stock_movements m WHERE $w",$params)['c']??0);
        $rows  = Database::fetchAll("SELECT m.*,p.name product_name,p.sku FROM stock_movements m JOIN products p ON p.id=m.product_id WHERE $w ORDER BY m.created_at DESC LIMIT 30 OFFSET $off",$params);
        $lastPage = (int)ceil($total/30);
        $this->view('admin.warehouse.movements', compact('rows','total','page','lastPage','type','pid'));
    }
}
