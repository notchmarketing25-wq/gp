<?php
$pageTitle = 'المنتجات وأسعاري';
ob_start();
?>
<div class="card" style="margin-bottom:16px">
  <div class="card-body">
    <form method="GET" style="display:flex;gap:8px">
      <input type="text" name="search" class="form-input" placeholder="بحث عن منتج..." value="<?= e($search??'') ?>">
      <button class="btn btn-primary">بحث</button>
    </form>
  </div>
</div>
<div class="card">
  <div class="table-wrap">
    <table>
      <thead><tr><th>المنتج</th><th>سعر الجملة</th><th>أقل كمية</th><th>المخزون</th></tr></thead>
      <tbody>
        <?php if (empty($products)): ?>
          <tr><td colspan="4" style="text-align:center;color:var(--t3);padding:30px">لا توجد منتجات</td></tr>
        <?php else: foreach ($products as $p): ?>
          <tr onclick="location.href='<?= url('business/products/'.$p['id']) ?>'" style="cursor:pointer">
            <td>
              <div style="display:flex;align-items:center;gap:10px">
                <?php if($p['thumbnail']): ?><img src="<?= uploadUrl($p['thumbnail']) ?>" style="width:36px;height:36px;border-radius:8px;object-fit:cover"><?php else: ?><div style="width:36px;height:36px;border-radius:8px;background:var(--bg3);display:flex;align-items:center;justify-content:center">📦</div><?php endif; ?>
                <span style="font-weight:600"><?= e($p['name']) ?></span>
              </div>
            </td>
            <td>
              <span style="font-weight:800;color:var(--ac);font-family:var(--font-num);font-size:15px"><?= money($p['tier_price']) ?></span>
              <?php if ($p['has_tiers'] && count($p['tiers']) > 1): ?>
                <div style="font-size:10.5px;color:var(--t3)">يقل السعر مع زيادة الكمية</div>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($p['has_tiers']): ?>
                <span class="badge badge-purple"><?= $p['min_qty'] ?>+ قطعة</span>
              <?php else: ?>
                <span class="td-dim">قطعة واحدة</span>
              <?php endif; ?>
            </td>
            <td><?= $p['track_stock'] ? number_format($p['stock']) : 'غير محدود' ?></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php $content = ob_get_clean(); require APP_PATH . '/Views/store/layouts/business.php';
