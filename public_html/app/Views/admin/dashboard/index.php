<?php
$pageTitle  = 'الرئيسية';
$breadcrumb = [];
$a = ADMIN_PREFIX;

// Revenue chart bars (last N days)
$maxR = max(1, max(array_column($salesChart??[], 'revenue') ?: [1]));
$today = date('Y-m-d');

ob_start();
?>

<div class="ph">
  <div class="ph-left"><h1>لوحة التحكم</h1><p><?= date('l, d F Y') ?></p></div>
  <div class="ph-right">
    <a href="<?= adminUrl('products/create') ?>" class="btn btn-primary">
      <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
      منتج جديد
    </a>
  </div>
</div>

<!-- STATS ROW -->
<?php if (!$canOrders && !$canProducts && !$canCustomers && !$canAnalytics): ?>
  <div class="card" style="text-align:center;padding:50px 20px">
    <div style="margin-bottom:12px;display:flex;justify-content:center;color:var(--t3)"><?= svgIcon('home', 40, 1.3) ?></div>
    <div style="font-weight:700;font-size:15px;margin-bottom:6px">أهلاً بيك 👋</div>
    <p style="color:var(--t3);font-size:13px">استخدم القائمة الجانبية للوصول للأقسام المتاحة لحسابك</p>
  </div>
<?php endif; ?>
<div class="stats">
  <?php if ($canAnalytics): ?>
  <div class="stat">
    <div class="stat-top">
      <div class="stat-icon" style="background:#E8F0FF;color:var(--blue)"><?= svgIcon('store', 20) ?></div>
      <div class="stat-change up">
        <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><polyline points="18,15 12,9 6,15"/></svg>
        هذا الشهر
      </div>
    </div>
    <div class="stat-value" style="font-size:22px"><?= money($stats['month_revenue']??0) ?></div>
    <div class="stat-label">الإيرادات</div>
    <div class="stat-sub">إجمالي: <?= money($stats['total_revenue']??0) ?></div>
    <div class="stat-bar"><div class="stat-bar-fill" style="width:72%"></div></div>
  </div>
  <?php endif; ?>
  <?php if ($canOrders): ?>
  <div class="stat">
    <div class="stat-top">
      <div class="stat-icon" style="background:#E8FBF4;color:var(--green)"><?= svgIcon('cart', 20) ?></div>
      <div class="stat-change up">
        <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><polyline points="18,15 12,9 6,15"/></svg>
        اليوم: <?= $stats['today_orders']??0 ?>
      </div>
    </div>
    <div class="stat-value"><?= number_format($stats['total_orders']??0) ?></div>
    <div class="stat-label">إجمالي الطلبات</div>
    <div class="stat-sub">معلق: <?= $stats['pending_orders']??0 ?></div>
    <div class="stat-bar"><div class="stat-bar-fill" style="width:60%;background:var(--green)"></div></div>
  </div>
  <div class="stat">
    <div class="stat-top">
      <div class="stat-icon" style="background:#FFF8E8;color:var(--yellow)"><?= svgIcon('truck', 20) ?></div>
      <div class="stat-change neutral">نشط</div>
    </div>
    <div class="stat-value"><?= count($shipments??[]) ?></div>
    <div class="stat-label">قيد الشحن</div>
    <div class="stat-sub">طلبات في الطريق</div>
    <div class="stat-bar"><div class="stat-bar-fill" style="width:55%;background:var(--yellow)"></div></div>
  </div>
  <?php endif; ?>
  <?php if ($canCustomers || $canProducts): ?>
  <div class="stat">
    <div class="stat-top">
      <div class="stat-icon" style="background:#F0E8FF;color:var(--purple)"><?= svgIcon('user', 20) ?></div>
      <div class="stat-change up">إجمالي</div>
    </div>
    <?php if ($canCustomers): ?>
      <div class="stat-value"><?= number_format($stats['total_customers']??0) ?></div>
      <div class="stat-label">العملاء</div>
      <?php if ($canProducts): ?><div class="stat-sub">منتجات: <?= number_format($stats['total_products']??0) ?></div><?php endif; ?>
    <?php else: ?>
      <div class="stat-value"><?= number_format($stats['total_products']??0) ?></div>
      <div class="stat-label">المنتجات</div>
    <?php endif; ?>
    <div class="stat-bar"><div class="stat-bar-fill" style="width:80%;background:var(--purple)"></div></div>
  </div>
  <?php endif; ?>
</div>

<!-- ORDERS TABLE + SHIPMENTS -->
<?php if ($canOrders): ?>
<div style="display:grid;grid-template-columns:1fr 340px;gap:18px;margin-bottom:18px;align-items:start">

  <div class="card">
    <div class="card-header">
      <div>
        <div class="card-title">أحدث الطلبات</div>
        <div style="font-size:11px;color:var(--t3);margin-top:2px"><?= count($recentOrders??[]) ?> طلب حديث</div>
      </div>
      <a href="<?= adminUrl('orders') ?>" class="btn btn-secondary btn-sm">عرض الكل ←</a>
    </div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>رقم الطلب</th><th>العميل</th><?php if ($canAnalytics): ?><th>المبلغ</th><?php endif; ?><th>الدفع</th><th>الحالة</th><th></th></tr></thead>
        <tbody>
          <?php if (empty($recentOrders)): ?>
            <tr><td colspan="6"><div class="empty"><div class="empty-icon"><?= svgIcon('cart', 32) ?></div><div class="empty-title">لا توجد طلبات بعد</div></div></td></tr>
          <?php else: foreach ($recentOrders as $o): ?>
            <tr>
              <td><a href="<?= adminUrl('orders/'.$o['id']) ?>" style="color:var(--ac);font-weight:700;font-family:monospace"><?= e($o['order_number']) ?></a></td>
              <td>
                <div style="display:flex;align-items:center;gap:8px">
                  <div style="width:26px;height:26px;border-radius:50%;background:var(--acb2);display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:800;color:var(--ac);flex-shrink:0"><?= mb_substr($o['customer_name'],0,1) ?></div>
                  <span style="font-weight:600;font-size:13px"><?= e($o['customer_name']) ?></span>
                </div>
              </td>
              <?php if ($canAnalytics): ?><td style="font-family:var(--font-num);font-weight:800;font-size:14px"><?= money($o['total']) ?></td><?php endif; ?>
              <td><span class="badge <?= ($o['payment_status']==='paid')?'badge-ok':(($o['payment_status']==='failed')?'badge-err':'badge-warn') ?>"><?= paymentStatusLabel($o['payment_status']??'unpaid') ?></span></td>
              <td><span class="badge badge-<?= orderStatusColor($o['status']??'pending') ?>"><?= orderStatusLabel($o['status']??'pending') ?></span></td>
              <td><a href="<?= adminUrl('orders/'.$o['id']) ?>" class="td-dim">‹</a></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- SHIPMENTS -->
  <div class="card">
    <div class="card-header">
      <div><div class="card-title">شحنات قيد التوصيل</div><div style="font-size:11px;color:var(--t3);margin-top:2px"><?= count($shipments??[]) ?> نشطة</div></div>
      <a href="<?= adminUrl('orders?status=shipped') ?>" class="btn btn-secondary btn-sm">تتبع الكل ←</a>
    </div>
    <div style="padding:4px 20px 16px">
      <?php if (empty($shipments)): ?>
        <div style="text-align:center;padding:30px 0;color:var(--t3);font-size:12px">لا توجد شحنات نشطة حالياً</div>
      <?php else: foreach ($shipments as $s): ?>
        <div class="ship-item">
          <div class="ship-icon" style="background:#E8F0FF;color:var(--blue)"><?= svgIcon('truck', 15) ?></div>
          <div style="flex:1;min-width:0">
            <div style="font-size:12px;font-weight:700"><?= e($s['order_number']) ?></div>
            <div style="font-size:11px;color:var(--t3);white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= e($s['shipping_city']??'').'، '.e($s['shipping_gov']??'') ?></div>
          </div>
          <div style="text-align:left;flex-shrink:0">
            <div style="font-size:11px;font-weight:700;color:var(--blue)">في الطريق</div>
            <div style="font-size:10px;color:var(--t3)"><?= formatDate($s['updated_at']??$s['created_at']) ?></div>
          </div>
        </div>
      <?php endforeach; endif; ?>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- BOTTOM ROW: CHART + INVENTORY + QUICK ACTIONS -->
<div style="display:grid;grid-template-columns:<?= $canAnalytics?'1fr ':'' ?><?= $canProducts?'1fr ':'' ?>300px;gap:18px">

  <!-- REVENUE CHART -->
  <?php if ($canAnalytics): ?>
  <div class="card">
    <div class="card-header">
      <div><div class="card-title">الإيرادات — آخر 19 يوم</div><div style="font-size:11px;color:var(--t3);margin-top:2px">تفصيل يومي</div></div>
    </div>
    <div class="card-body" style="padding-top:8px">
      <?php if (!empty($salesChart)): ?>
        <div class="mini-chart">
          <?php foreach ($salesChart as $i => $d):
            $h = max(6, round(($d['revenue']/$maxR)*100));
            $isToday = ($d['date']??'') === $today;
          ?>
            <div class="mc-bar" style="height:<?=$h?>%;<?= $isToday ? 'background:var(--ac)' : '' ?>" title="<?= e($d['date']) ?>: <?= money($d['revenue']) ?>"></div>
          <?php endforeach; ?>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:10px;color:var(--t3);margin-top:8px">
          <span><?= formatDate($salesChart[0]['date']) ?></span><span>اليوم</span>
        </div>
      <?php else: ?>
        <div style="text-align:center;padding:30px;color:var(--t3);font-size:13px">لا توجد بيانات مبيعات بعد</div>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- INVENTORY ALERTS -->
  <?php if ($canProducts): ?>
  <div class="card">
    <div class="card-header">
      <div><div class="card-title">تنبيهات المخزون</div><div style="font-size:11px;color:var(--t3);margin-top:2px"><?= count($stats['low_stock']??[]) ?> منتج منخفض</div></div>
      <a href="<?= adminUrl('warehouse') ?>" class="btn btn-secondary btn-sm">إدارة ←</a>
    </div>
    <div style="padding:4px 20px 16px">
      <?php if (empty($stats['low_stock'])): ?>
        <div style="text-align:center;padding:30px 0;color:var(--t3);font-size:12px"><?= svgIcon('check', 16) ?> كل المنتجات بمخزون كافٍ</div>
      <?php else: foreach ($stats['low_stock'] as $p):
        $stock=(int)$p['stock']; $color = $stock===0?'var(--red)':'var(--yellow)';
      ?>
        <div class="ship-item">
          <div style="width:8px;height:8px;border-radius:50%;background:<?=$color?>;flex-shrink:0"></div>
          <div style="flex:1;min-width:0">
            <div style="font-size:13px;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= e($p['name']) ?></div>
            <?php if($p['sku']??null): ?><div style="font-size:10px;color:var(--t3);font-family:monospace;text-transform:uppercase"><?= e($p['sku']) ?></div><?php endif; ?>
          </div>
          <div style="text-align:left;flex-shrink:0">
            <div style="font-family:var(--font-num);font-size:17px;font-weight:900;color:<?=$color?>"><?= $stock ?></div>
            <div style="font-size:10px;color:var(--t3)">وحدة متبقية</div>
          </div>
        </div>
      <?php endforeach; endif; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- QUICK ACTIONS + SALES MIX -->
  <div style="display:flex;flex-direction:column;gap:18px">
    <?php if ($canAnalytics): ?>
    <div class="card">
      <div class="card-header"><div class="card-title">توزيع المبيعات</div></div>
      <div class="card-body">
        <?php
        $tp = array_sum(array_column($paymentMix??[], 'cnt'));
        $colors = ['#0057FF','#00C48C','#FFB800','#7B2FBE'];
        $offset = 0; $circumference = 2 * M_PI * 35;
        ?>
        <div class="donut-wrap">
          <svg width="80" height="80" viewBox="0 0 90 90" style="flex-shrink:0">
            <circle cx="45" cy="45" r="35" fill="none" stroke="var(--bg3)" stroke-width="12"/>
            <?php foreach (($paymentMix??[]) as $i => $pm):
              $pct = $tp>0 ? $pm['cnt']/$tp : 0;
              $dash = $pct * $circumference;
              $gap = $circumference - $dash;
            ?>
              <circle cx="45" cy="45" r="35" fill="none" stroke="<?= $colors[$i%count($colors)] ?>" stroke-width="12"
                stroke-dasharray="<?= round($dash,1) ?> <?= round($gap,1) ?>" stroke-dashoffset="<?= round(-$offset,1) ?>"
                transform="rotate(-90 45 45)"/>
              <?php $offset += $dash; ?>
            <?php endforeach; ?>
          </svg>
          <div class="donut-legend">
            <?php if (empty($paymentMix)): ?>
              <div style="font-size:12px;color:var(--t3)">لا توجد بيانات</div>
            <?php else: foreach ($paymentMix as $i => $pm):
              $pct = $tp>0 ? round($pm['cnt']/$tp*100) : 0;
              $lbl = $pm['payment_method']==='cod' ? 'الدفع عند الاستلام' : 'فواتيرك';
            ?>
              <div class="donut-leg-item"><div class="donut-leg-dot" style="background:<?= $colors[$i%count($colors)] ?>"></div><span class="donut-leg-label"><?= $lbl ?></span><span class="donut-leg-val"><?= $pct ?>%</span></div>
            <?php endforeach; endif; ?>
          </div>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <div class="card">
      <div class="card-header"><div class="card-title">إجراءات سريعة</div></div>
      <div class="card-body">
        <div class="qa-grid">
          <?php if ($canProducts): ?><a href="<?= adminUrl('products/create') ?>" class="qa-btn"><?= svgIcon('plus', 15) ?> إضافة منتج</a><?php endif; ?>
          <?php if ($canOrders): ?><a href="<?= adminUrl('bulk/orders') ?>" class="qa-btn"><?= svgIcon('upload', 15) ?> تصدير طلبات</a><?php endif; ?>
          <?php if ($canProducts): ?><a href="<?= adminUrl('warehouse') ?>" class="qa-btn"><?= svgIcon('package', 15) ?> تعديل مخزون</a><?php endif; ?>
          <?php if (adminCan('marketing')): ?><a href="<?= adminUrl('discounts') ?>" class="qa-btn"><?= svgIcon('tag', 15) ?> كود خصم</a><?php endif; ?>
        </div>
      </div>
    </div>
  </div>

</div>

<?php $content = ob_get_clean(); require APP_PATH . '/Views/admin/layouts/app.php';
