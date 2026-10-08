<?php
$pageTitle  = 'معاينة استيراد Shopify';
$breadcrumb = [['label'=>'المنتجات','url'=>adminUrl('products')],['label'=>'معاينة الاستيراد']];
$a = ADMIN_PREFIX;

$newCount    = count(array_filter($products, fn($p)=>!$p['exists']));
$updateCount = count(array_filter($products, fn($p)=>$p['exists']));
$totalImages = array_sum(array_map(fn($p)=>count($p['images']), $products));
$totalVariants = array_sum(array_map(fn($p)=>count($p['variants'] ?? []), $products));

ob_start();
?>
<div class="ph">
  <div class="ph-left"><h1><span style="display:inline-flex;align-items:center;gap:9px"><?= svgIcon('search', 22) ?> معاينة الاستيراد</span></h1><p>راجع المنتجات قبل الحفظ النهائي</p></div>
  <div class="ph-right"><a href="<?= adminUrl('import/shopify') ?>" class="btn btn-secondary">← رفع ملف تاني</a></div>
</div>

<div class="stats" style="grid-template-columns:repeat(4,1fr);margin-bottom:20px">
  <div class="stat"><div class="stat-icon" style="background:#E8F0FF;color:var(--blue)"><?= svgIcon('package', 20) ?></div><div class="stat-label">إجمالي المنتجات</div><div class="stat-value"><?= count($products) ?></div></div>
  <div class="stat"><div class="stat-icon" style="background:#E8FBF4;color:var(--green)"><?= svgIcon('star', 20) ?></div><div class="stat-label">منتجات جديدة</div><div class="stat-value"><?= $newCount ?></div></div>
  <div class="stat"><div class="stat-icon" style="background:#FFF8E8;color:var(--yellow)"><?= svgIcon('refresh', 20) ?></div><div class="stat-label">هيتم تحديثها</div><div class="stat-value"><?= $updateCount ?></div></div>
  <div class="stat"><div class="stat-icon" style="background:#F0E8FF;color:var(--purple)"><?= svgIcon('image', 20) ?></div><div class="stat-label">صور هتتحمّل</div><div class="stat-value"><?= $totalImages ?></div></div>
  <div class="stat"><div class="stat-icon" style="background:#FFF0F0;color:var(--red)"><?= svgIcon('tag', 20) ?></div><div class="stat-label">متغيرات هتتحمّل</div><div class="stat-value"><?= $totalVariants ?></div></div>
</div>

<div class="card" style="margin-bottom:20px">
  <div class="table-wrap">
    <table>
      <thead><tr><th></th><th>المنتج</th><th>SKU</th><th>السعر</th><th>المخزون</th><th>الماركة</th><th>الحالة</th></tr></thead>
      <tbody>
        <?php foreach ($products as $p): ?>
          <tr>
            <td>
              <?php if (!empty($p['images'][0])): ?>
                <img src="<?= e($p['images'][0]) ?>" style="width:32px;height:32px;object-fit:cover;border-radius:6px;border:1px solid var(--bd)">
              <?php else: ?>
                <div class="prod-img-ph">📦</div>
              <?php endif; ?>
            </td>
            <td style="font-weight:600;font-size:13px;max-width:220px"><?= e($p['name']) ?></td>
            <td class="td-mono td-dim"><?= e($p['sku'] ?: '—') ?></td>
            <td style="font-weight:700"><?= money($p['price']) ?></td>
            <td><?= number_format($p['stock']) ?></td>
            <td class="td-dim"><?= e($p['vendor'] ?: '—') ?></td>
            <td><span class="badge <?= $p['exists']?'badge-warn':'badge-ok' ?>"><?= $p['exists']?'🔄 تحديث':'🆕 جديد' ?></span> <?php if(!empty($p['variants'])): ?><span class="badge badge-dim"><?= count($p['variants']) ?> متغير</span><?php endif; ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<form method="POST" action="<?= adminUrl('import/shopify/confirm') ?>" style="position:sticky;bottom:16px">
  <?= csrf_field() ?>
  <input type="hidden" name="batch_id" value="<?= e($batchId) ?>">
  <div class="card" style="padding:16px 20px;display:flex;align-items:center;justify-content:space-between;box-shadow:var(--shadow-lg);flex-wrap:wrap;gap:10px">
    <div style="font-size:13px;color:var(--t2)">جاهز لاستيراد <strong style="color:var(--t1)"><?= count($products) ?></strong> منتج (هياخد وقت أطول لو فيه صور كتير)</div>
    <button type="submit" class="btn btn-primary btn-lg" onclick="return confirm('تأكيد استيراد <?= count($products) ?> منتج؟')"><?= svgIcon('check', 15) ?> تأكيد وبدء الاستيراد</button>
  </div>
</form>

<?php $content = ob_get_clean(); require APP_PATH . '/Views/admin/layouts/app.php';
