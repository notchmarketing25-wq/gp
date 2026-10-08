<?php
$pageTitle  = 'الطلبات';
$breadcrumb = [['label' => 'الطلبات']];
$a = ADMIN_PREFIX;
$data     = $paginator['data']         ?? [];
$total    = $paginator['total']        ?? 0;
$curPage  = $paginator['current_page'] ?? 1;
$lastPage = $paginator['last_page']    ?? 1;
ob_start();
?>
<div class="ph">
  <div class="ph-left"><h1>الطلبات</h1><p><?= number_format($total) ?> طلب</p></div>
  <div class="ph-right">
    <form method="POST" action="<?= adminUrl('orders/relink-products') ?>" onsubmit="return confirm('هيتم البحث عن منتجات مطابقة (بالـ SKU) لكل عناصر الطلبات الغير مربوطة، وربطها. تكمل؟')">
      <?= csrf_field() ?>
      <button type="submit" class="btn btn-secondary"><?= svgIcon('package', 15) ?> ربط بالـ SKU</button>
    </form>
    <a href="<?= adminUrl('orders/orphans') ?>" class="btn btn-secondary">🔗 منتجات غير مرتبطة (بالاسم)</a>
    <a href="<?= adminUrl('import/shopify-orders') ?>" class="btn btn-secondary">🛍️ استيراد من Shopify</a>
  </div>
</div>

<div class="card" style="margin-bottom:0;border-radius:var(--radius) var(--radius) 0 0">
  <!-- Order type tabs — same neutral pattern used across the admin (products, inventory) -->
  <div class="chan-tabs">
    <?php foreach (['' => ['كل الطلبات','all'], 'retail' => ['طلبات عادية','retail'], 'wholesale' => ['طلبات جملة (Business)','wholesale']] as $val => [$lbl,$countKey]): ?>
      <a href="?order_type=<?= $val ?>&status=<?= e($status??'') ?>&search=<?= urlencode($search??'') ?>" class="chan-tab <?= ($orderType??'')===$val ? 'active' : '' ?>">
        <?= $lbl ?> <span class="chan-tab-count"><?= number_format($typeCounts[$countKey] ?? 0) ?></span>
      </a>
    <?php endforeach; ?>
  </div>
  <!-- Status tabs -->
  <div class="chan-tabs" style="border-top:1px solid var(--bd)">
    <?php foreach ([
      ''          => ['الكل','all','grid'],
      'pending'   => ['معلق','pending','clock'],
      'processing'=> ['قيد المعالجة','processing','refresh'],
      'shipped'   => ['تم الشحن','shipped','truck'],
      'delivered' => ['تم التسليم','delivered','check'],
      'cancelled' => ['ملغي','cancelled','close'],
    ] as $val => [$lbl,$countKey,$icon]): ?>
      <a href="?status=<?= $val ?>&order_type=<?= e($orderType??'') ?>&search=<?= urlencode($search??'') ?>" class="chan-tab <?= ($status??'')===$val ? 'active' : '' ?>">
        <?= svgIcon($icon, 13) ?> <?= $lbl ?> <span class="chan-tab-count"><?= number_format($statusCounts[$countKey] ?? 0) ?></span>
      </a>
    <?php endforeach; ?>
  </div>
</div>

<div class="card" style="border-top:none;border-radius:0 0 var(--radius) var(--radius)">
  <div style="padding:12px 14px;border-bottom:1px solid var(--bd)">
    <form method="GET" class="filter-bar">
      <input type="hidden" name="status" value="<?= e($status??'') ?>">
      <input type="hidden" name="order_type" value="<?= e($orderType??'') ?>">
      <div class="search-box">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
        <input type="text" name="search" class="search-input" placeholder="رقم الطلب، الاسم، الهاتف..." value="<?= e($search??'') ?>">
      </div>
      <button type="submit" class="btn btn-secondary">بحث</button>
    </form>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>رقم الطلب</th><th>النوع</th><th>العميل</th><th>المبلغ</th><th>الدفع</th><th>الحالة</th><th>التاريخ</th><th></th></tr></thead>
      <tbody>
        <?php if (empty($data)): ?>
          <tr><td colspan="8"><div class="empty"><div class="empty-icon"><?= svgIcon('cart', 32) ?></div><div class="empty-title">لا توجد طلبات</div></div></td></tr>
        <?php else: foreach ($data as $o): $ot = $o['order_type']??'retail'; ?>
          <tr style="<?= $ot!=='retail' ? 'background:rgba(123,47,190,.03)' : '' ?>">
            <td><a href="/<?= $a ?>/orders/<?= $o['id'] ?>" style="color:var(--ac);font-weight:700;font-family:monospace"><?= e($o['order_number']) ?></a></td>
            <td>
              <?php if ($ot==='wholesale'): ?>
                <span class="badge badge-purple">🏬 جملة/Business</span>
              <?php else: ?>
                <span class="badge badge-dim">🛍️ عادي</span>
              <?php endif; ?>
            </td>
            <td>
              <div style="font-weight:500"><?= e($o['customer_name']) ?></div>
              <div style="font-size:11px;color:var(--t3)"><?= e($o['customer_phone']??'') ?></div>
              <?php if ($o['placed_by_name']??null): ?>
                <div style="font-size:10.5px;color:#7B2FBE;margin-top:2px">
                  عبر <?= ['merchant'=>'🏪 تاجر','distributor'=>'🚛 موزع','rep'=>'🧑‍💼 مندوب'][$o['placed_by_type']] ?? '' ?>: <?= e($o['placed_by_name']) ?>
                </div>
              <?php endif; ?>
            </td>
            <td style="font-weight:700"><?= money($o['total']) ?></td>
            <td><span class="badge <?= ($o['payment_status']??'')==='paid'?'badge-ok':(($o['payment_status']??'')==='failed'?'badge-err':'badge-warn') ?>"><?= paymentStatusLabel($o['payment_status']??'unpaid') ?></span></td>
            <td><span class="badge badge-<?= orderStatusColor($o['status']??'pending') ?>"><?= orderStatusLabel($o['status']??'pending') ?></span></td>
            <td class="td-dim"><?= formatDate($o['created_at']) ?></td>
            <td><a href="/<?= $a ?>/orders/<?= $o['id'] ?>" class="btn btn-secondary btn-sm">عرض</a></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
  <?php if ($lastPage > 1): ?>
    <div class="pagination">
      <?php for ($i=1;$i<=$lastPage;$i++): ?>
        <a href="?page=<?=$i?>&status=<?=urlencode($status??'')?>&order_type=<?=urlencode($orderType??'')?>&search=<?=urlencode($search??'')?>" class="page-btn <?=$i===$curPage?'active':''?>"><?=$i?></a>
      <?php endfor; ?>
    </div>
  <?php endif; ?>
</div>
<?php $content = ob_get_clean(); require APP_PATH . '/Views/admin/layouts/app.php';
