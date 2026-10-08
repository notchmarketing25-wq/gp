<?php
$pageTitle = e($product['name']);
ob_start();
?>
<a href="<?= url('business/products') ?>" class="btn btn-secondary btn-sm" style="margin-bottom:16px;display:inline-flex">
  <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="m12 19-7-7 7-7"/></svg>
  رجوع للمنتجات
</a>

<div class="biz-product-grid">
  <!-- Gallery -->
  <div class="card">
    <div class="card-body">
      <div class="biz-gallery-main">
        <img id="bizMainImg" src="<?= $product['thumbnail'] ? uploadUrl($product['thumbnail']) : '' ?>" alt="<?= e($product['name']) ?>">
      </div>
      <?php if (!empty($images)): ?>
        <div class="biz-gallery-thumbs">
          <?php if ($product['thumbnail']): ?>
            <img src="<?= uploadUrl($product['thumbnail']) ?>" class="biz-thumb active" onclick="bizSwapImg(this)">
          <?php endif; ?>
          <?php foreach ($images as $img): ?>
            <img src="<?= uploadUrl($img['image']) ?>" class="biz-thumb" onclick="bizSwapImg(this)">
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Info -->
  <div>
    <div class="card" style="margin-bottom:16px">
      <div class="card-body">
        <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:10px">
          <?php if ($product['brand_name']): ?><span class="badge badge-dim"><?= e($product['brand_name']) ?></span><?php endif; ?>
          <?php if ($product['collection_name']): ?><span class="badge badge-dim"><?= e($product['collection_name']) ?></span><?php endif; ?>
          <?php if ($product['sku']): ?><span class="badge badge-dim" style="font-family:monospace"><?= e($product['sku']) ?></span><?php endif; ?>
        </div>
        <h1 style="font-size:20px;font-weight:800;margin-bottom:14px"><?= e($product['name']) ?></h1>

        <div style="background:var(--bg3);border-radius:12px;padding:16px;margin-bottom:16px">
          <div style="font-size:11px;color:var(--t3);font-weight:600;margin-bottom:4px">سعر الجملة</div>
          <div style="font-size:26px;font-weight:900;color:var(--ac);font-family:var(--font-num)"><?= money($product['tier_price']) ?></div>
          <?php if ($product['has_tiers']): ?>
            <div style="font-size:12px;color:var(--t3);margin-top:4px">أقل كمية للطلب: <?= $product['min_qty'] ?> قطعة</div>
          <?php endif; ?>
        </div>

        <?php if ($product['has_tiers'] && count($product['tiers']) > 1): ?>
          <div style="margin-bottom:16px">
            <div style="font-size:12.5px;font-weight:700;margin-bottom:8px">شرائح الأسعار حسب الكمية</div>
            <div class="table-wrap">
              <table>
                <thead><tr><th>الكمية</th><th>سعر القطعة</th></tr></thead>
                <tbody>
                  <?php foreach ($product['tiers'] as $i => $t):
                    $next = $product['tiers'][$i+1]['min_qty'] ?? null;
                    $range = $next ? ($t['min_qty'].' - '.($next-1)) : ($t['min_qty'].'+');
                  ?>
                    <tr><td><?= $range ?> قطعة</td><td style="font-weight:700;color:var(--ac)"><?= money($t['price']) ?></td></tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        <?php endif; ?>

        <div style="display:flex;justify-content:space-between;font-size:12.5px;padding:10px 0;border-top:1px solid var(--bd)">
          <span class="td-dim">المخزون</span>
          <span style="font-weight:700"><?= $product['track_stock'] ? number_format($product['stock']).' قطعة' : 'غير محدود' ?></span>
        </div>

        <a href="<?= url('business/orders/create') ?>" class="btn btn-primary btn-full" style="margin-top:16px">🛒 اطلب هذا المنتج</a>
      </div>
    </div>

    <?php if ($product['description']): ?>
    <div class="card">
      <div class="card-header"><span class="card-title">الوصف</span></div>
      <div class="card-body" style="font-size:13px;color:var(--t2);line-height:1.8"><?= nl2br(e(strip_tags($product['description']))) ?></div>
    </div>
    <?php endif; ?>
  </div>
</div>

<style>
.biz-product-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;align-items:start}
.biz-gallery-main{aspect-ratio:1;background:var(--bg3);border-radius:12px;display:flex;align-items:center;justify-content:center;overflow:hidden;margin-bottom:12px}
.biz-gallery-main img{width:100%;height:100%;object-fit:contain;padding:16px}
.biz-gallery-thumbs{display:flex;gap:8px;overflow-x:auto}
.biz-thumb{width:56px;height:56px;object-fit:cover;border-radius:8px;border:2px solid transparent;cursor:pointer;flex-shrink:0;background:var(--bg3)}
.biz-thumb.active{border-color:var(--ac)}
@media(max-width:900px){.biz-product-grid{grid-template-columns:1fr}}
</style>
<script>
function bizSwapImg(el){
  document.getElementById('bizMainImg').src = el.src;
  document.querySelectorAll('.biz-thumb').forEach(t => t.classList.remove('active'));
  el.classList.add('active');
}
</script>
<?php
$content = ob_get_clean();
require APP_PATH . '/Views/store/layouts/business.php';
