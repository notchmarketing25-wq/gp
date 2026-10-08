<?php
$pageTitle = 'المنتجات — ' . SettingModel::get('store_name', APP_NAME);

$productModel = new ProductModel();
$brandModel   = new BrandModel();
$colModel     = new CollectionModel();

$page       = max(1, (int)($_GET['page'] ?? 1));
$search     = trim($_GET['q'] ?? $_GET['search'] ?? '');
$brandSlug  = $_GET['brand'] ?? '';
$colSlug    = $_GET['collection'] ?? '';
$sort       = $_GET['sort'] ?? 'newest';
$minPrice   = (float)($_GET['min_price'] ?? 0);
$maxPrice   = (float)($_GET['max_price'] ?? 0);

$where  = ["p.status = 'active'", "p.channel != 'wholesale_only'"];
$params = [];

if ($search) {
    $where[]  = "(p.name LIKE ? OR p.description LIKE ? OR p.sku LIKE ?)";
    $params   = array_merge($params, ["%{$search}%", "%{$search}%", "%{$search}%"]);
}
if ($colSlug) {
    $col = $colModel->getBySlug($colSlug);
    if ($col) { $where[] = "p.collection_id = ?"; $params[] = $col['id']; }
}
if ($brandSlug) {
    $brand = $brandModel->getBySlug($brandSlug);
    if ($brand) { $where[] = "p.brand_id = ?"; $params[] = $brand['id']; }
}
if ($minPrice > 0) { $where[] = "p.price >= ?"; $params[] = $minPrice; }
if ($maxPrice > 0) { $where[] = "p.price <= ?"; $params[] = $maxPrice; }

$orderBy = match($sort) {
    'price_asc'  => 'p.price ASC',
    'price_desc' => 'p.price DESC',
    'popular'    => 'p.views DESC',
    default      => 'p.created_at DESC',
};

$whereStr = 'WHERE ' . implode(' AND ', $where);
$perPage  = 16;
$offset   = ($page - 1) * $perPage;
$total    = (int)(Database::fetch("SELECT COUNT(*) as c FROM products p {$whereStr}", $params)['c'] ?? 0);
$products = Database::fetchAll(
    "SELECT p.*, b.name as brand_name FROM products p LEFT JOIN brands b ON b.id=p.brand_id {$whereStr} ORDER BY {$orderBy} LIMIT {$perPage} OFFSET {$offset}",
    $params
);
$lastPage = (int)ceil($total / $perPage);

$allBrands     = $brandModel->getActive();
$allCollections = $colModel->getActive();

// Product counts per collection/brand (helps spot near-duplicate entries with 0/low counts)
$colCounts = [];
foreach (Database::fetchAll("SELECT collection_id, COUNT(*) c FROM products WHERE status='active' AND channel!='wholesale_only' GROUP BY collection_id") as $r) {
    $colCounts[$r['collection_id']] = $r['c'];
}
$brandCounts = [];
foreach (Database::fetchAll("SELECT brand_id, COUNT(*) c FROM products WHERE status='active' AND channel!='wholesale_only' GROUP BY brand_id") as $r) {
    $brandCounts[$r['brand_id']] = $r['c'];
}

$queryStr = http_build_query(array_filter(['q'=>$search,'brand'=>$brandSlug,'collection'=>$colSlug,'sort'=>$sort,'min_price'=>$minPrice?:null,'max_price'=>$maxPrice?:null]));

ob_start(); ?>

<div class="container" style="padding-top:24px;padding-bottom:80px">
  <!-- Breadcrumb -->
  <div class="breadcrumb">
    <a href="<?= url() ?>">الرئيسية</a>
    <span class="breadcrumb-sep">/</span>
    <span class="breadcrumb-current">المنتجات</span>
    <?php if ($search): ?>
      <span class="breadcrumb-sep">/</span>
      <span class="breadcrumb-current">بحث: <?= e($search) ?></span>
    <?php endif; ?>
  </div>

  <!-- Collection banner (uses the collection's own image, if set) -->
  <?php if (!empty($col) && !empty($col['image'])): ?>
    <div class="collection-banner">
      <img src="<?= uploadUrl($col['image']) ?>" alt="<?= e($col['name']) ?>" class="collection-banner-bg">
      <div class="collection-banner-shade"></div>
      <div class="collection-banner-text">
        <div class="collection-banner-title"><?= e($col['name']) ?></div>
        <?php if (!empty($col['description'])): ?><div class="collection-banner-desc"><?= e(mb_substr(strip_tags($col['description']),0,140)) ?></div><?php endif; ?>
      </div>
    </div>
  <?php endif; ?>

  <!-- Header row -->
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px">
    <div>
      <h1 style="font-size:22px;font-weight:800"><?= !empty($col) ? e($col['name']) : ($search ? 'نتائج: "' . e($search) . '"' : 'كل المنتجات') ?></h1>
      <p style="font-size:13px;color:var(--text3);margin-top:3px"><?= number_format($total) ?> منتج</p>
    </div>
    <!-- Sort -->
    <form method="GET" style="display:flex;gap:8px;align-items:center">
      <?php foreach (['q'=>$search,'brand'=>$brandSlug,'collection'=>$colSlug,'min_price'=>$minPrice?:null,'max_price'=>$maxPrice?:null] as $k=>$v): ?>
        <?php if ($v): ?><input type="hidden" name="<?= $k ?>" value="<?= e($v) ?>"><?php endif; ?>
      <?php endforeach; ?>
      <select name="sort" class="form-select" style="width:auto;background:var(--surface);font-size:13px;padding:8px 12px" onchange="this.form.submit()">
        <option value="newest" <?= $sort==='newest'?'selected':'' ?>>الأحدث</option>
        <option value="popular" <?= $sort==='popular'?'selected':'' ?>>الأكثر مشاهدة</option>
        <option value="price_asc" <?= $sort==='price_asc'?'selected':'' ?>>السعر: الأقل</option>
        <option value="price_desc" <?= $sort==='price_desc'?'selected':'' ?>>السعر: الأعلى</option>
      </select>
    </form>
  </div>

  <!-- Mobile quick chips (collections) — Ugreen style -->
  <div class="filter-chips">
    <button type="button" class="filter-toggle-btn" onclick="openFilterSheet()" style="border-color:var(--accent);color:var(--accent)">⚙️ فلترة</button>
    <?php
    $chipBase = array_filter(['q'=>$search,'sort'=>$sort!=='newest'?$sort:null,'brand'=>$brandSlug]);
    ?>
    <a href="<?= url('products') . ($chipBase ? '?'.http_build_query($chipBase) : '') ?>" class="filter-chip <?= !$colSlug?'active':'' ?>">الكل</a>
    <?php foreach ($allCollections as $c): if (($colCounts[$c['id']] ?? 0) === 0) continue; ?>
      <a href="<?= url('products') . '?' . http_build_query(array_merge($chipBase, ['collection'=>$c['slug']])) ?>" class="filter-chip <?= $colSlug===$c['slug']?'active':'' ?>"><?= e($c['name']) ?></a>
    <?php endforeach; ?>
  </div>

  <div style="display:flex;gap:24px;align-items:flex-start">
    <!-- Sidebar filters -->
    <div class="filter-sheet-overlay" id="filterSheetOverlay" onclick="closeFilterSheet()"></div>
    <aside class="filter-sidebar" id="filterSidebar" style="position:sticky;top:80px">
      <div class="filter-sheet-head">
        <span style="font-weight:800;font-size:15px">⚙️ الفلاتر</span>
        <button type="button" onclick="closeFilterSheet()" style="background:var(--bg3);border-radius:100px;width:30px;height:30px;font-size:16px">×</button>
      </div>
      <form method="GET" id="filterForm">
        <?php if ($search): ?><input type="hidden" name="q" value="<?= e($search) ?>"><?php endif; ?>
        <?php if ($sort): ?><input type="hidden" name="sort" value="<?= e($sort) ?>"><?php endif; ?>

        <!-- Collections -->
        <?php if (!empty($allCollections)): ?>
        <div class="filter-group">
          <div class="filter-title">التصنيف</div>
          <label class="filter-item <?= !$colSlug?'active':'' ?>">
            <input type="radio" name="collection" value="" <?= !$colSlug?'checked':'' ?> onchange="this.form.submit()">
            <span class="filter-item-label">الكل</span>
          </label>
          <?php foreach ($allCollections as $c): if (($colCounts[$c['id']] ?? 0) === 0) continue; ?>
            <label class="filter-item <?= $colSlug===$c['slug']?'active':'' ?>">
              <input type="radio" name="collection" value="<?= e($c['slug']) ?>" <?= $colSlug===$c['slug']?'checked':'' ?> onchange="this.form.submit()">
              <span class="filter-item-label"><?= e($c['name']) ?></span>
              <span class="filter-item-count"><?= $colCounts[$c['id']] ?? 0 ?></span>
            </label>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Brands -->
        <?php if (!empty($allBrands)): ?>
        <div class="filter-group">
          <div class="filter-title">الماركة</div>
          <label class="filter-item <?= !$brandSlug?'active':'' ?>">
            <input type="radio" name="brand" value="" <?= !$brandSlug?'checked':'' ?> onchange="this.form.submit()">
            <span class="filter-item-label">الكل</span>
          </label>
          <?php foreach ($allBrands as $b): if (($brandCounts[$b['id']] ?? 0) === 0) continue; ?>
            <label class="filter-item <?= $brandSlug===$b['slug']?'active':'' ?>">
              <input type="radio" name="brand" value="<?= e($b['slug']) ?>" <?= $brandSlug===$b['slug']?'checked':'' ?> onchange="this.form.submit()">
              <span class="filter-item-label"><?= e($b['name']) ?></span>
              <span class="filter-item-count"><?= $brandCounts[$b['id']] ?? 0 ?></span>
            </label>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Price -->
        <div class="filter-group">
          <div class="filter-title">السعر</div>
          <div style="display:flex;gap:8px;align-items:center;margin-bottom:10px">
            <input type="number" name="min_price" class="form-input" placeholder="من" value="<?= $minPrice?:'' ?>" style="padding:8px 10px;font-size:12.5px">
            <span style="color:var(--text3)">—</span>
            <input type="number" name="max_price" class="form-input" placeholder="إلى" value="<?= $maxPrice?:'' ?>" style="padding:8px 10px;font-size:12.5px">
          </div>
          <button type="submit" class="btn btn-primary btn-sm btn-full">تطبيق</button>
        </div>

        <?php if ($search || $colSlug || $brandSlug || $minPrice || $maxPrice): ?>
          <a href="<?= url('products') ?>" class="btn btn-ghost btn-sm btn-full" style="margin-top:8px;color:var(--red)">× مسح كل الفلاتر</a>
        <?php endif; ?>
      </form>
    </aside>

    <!-- Products -->
    <div style="flex:1;min-width:0">
      <?php if (empty($products)): ?>
        <div style="text-align:center;padding:80px 20px;color:var(--text3)">
          <div style="font-size:48px;margin-bottom:16px">😕</div>
          <div style="font-size:18px;font-weight:600;color:var(--text2);margin-bottom:8px">لا توجد منتجات</div>
          <p>جرب تغيير الفلاتر أو البحث بكلمة مختلفة</p>
          <a href="<?= url('products') ?>" class="btn btn-outline" style="margin-top:16px">عرض الكل</a>
        </div>
      <?php else: ?>
        <div class="products-grid">
          <?php foreach ($products as $p): ?>
            <?php include APP_PATH . '/Views/store/partials/product-card.php'; ?>
          <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($lastPage > 1): ?>
          <div style="display:flex;justify-content:center;gap:6px;margin-top:40px">
            <?php if ($page > 1): ?>
              <a href="?<?= $queryStr ?>&page=<?= $page-1 ?>" class="btn btn-outline btn-sm">← السابق</a>
            <?php endif; ?>
            <?php
            $start = max(1, $page - 2); $end = min($lastPage, $page + 2);
            for ($i = $start; $i <= $end; $i++):
            ?>
              <a href="?<?= $queryStr ?>&page=<?= $i ?>" class="btn <?= $i===$page?'btn-primary':'btn-outline' ?> btn-sm"><?= $i ?></a>
            <?php endfor; ?>
            <?php if ($page < $lastPage): ?>
              <a href="?<?= $queryStr ?>&page=<?= $page+1 ?>" class="btn btn-outline btn-sm">التالي →</a>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
function openFilterSheet(){
  document.getElementById('filterSidebar').classList.add('open');
  document.getElementById('filterSheetOverlay').classList.add('open');
  document.body.style.overflow='hidden';
}
function closeFilterSheet(){
  document.getElementById('filterSidebar').classList.remove('open');
  document.getElementById('filterSheetOverlay').classList.remove('open');
  document.body.style.overflow='';
}
</script>

<?php
$content = ob_get_clean();
require APP_PATH . '/Views/store/layouts/app.php';
