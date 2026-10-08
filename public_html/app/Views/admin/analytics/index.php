<?php
$pageTitle  = 'التحليلات';
$breadcrumb = [['label'=>'التحليلات']];
$a = ADMIN_PREFIX;

// Revenue chart bars
$maxR = max(1, max(array_column($salesChart??[], 'revenue') ?: [1]));
$pts  = []; $cnt = count($salesChart??[]);
foreach (($salesChart??[]) as $i=>$row) {
    $x=$cnt>1?($i/($cnt-1))*540+30:300; $y=100-($row['revenue']/$maxR*85);
    $pts[]="{$x},{$y}";
}
// AOV chart bars
$maxAov = max(1, max(array_map(fn($r)=>$r['cnt']>0?$r['revenue']/$r['cnt']:0, $aovChart??[]) ?: [1]));

ob_start();
?>
<div class="ph">
  <div class="ph-left"><h1><span style="display:inline-flex;align-items:center;gap:9px"><?= svgIcon('chart', 22) ?> التحليلات</span></h1><p>نظرة شاملة على أداء المتجر</p></div>
  <div class="ph-right">
    <select class="filter-select" onchange="location.href='?range='+this.value">
      <?php foreach(['7'=>'آخر 7 أيام','30'=>'آخر 30 يوم','90'=>'آخر 90 يوم','365'=>'آخر سنة'] as $v=>$l): ?>
        <option value="<?=$v?>" <?= $range==$v?'selected':'' ?>><?=$l?></option>
      <?php endforeach; ?>
    </select>
    <a href="<?= adminUrl('analytics/export') ?>" class="btn btn-secondary">📥 تصدير CSV</a>
  </div>
</div>

<!-- Top KPIs -->
<div class="stats">
  <div class="stat">
    <div class="stat-top"><div class="stat-icon" style="background:#E8FBF4;color:var(--green)"><?= svgIcon('store', 20) ?></div></div>
    <div class="stat-value" style="font-size:20px"><?= money($grossSales) ?></div>
    <div class="stat-label">إجمالي المبيعات (Gross)</div>
  </div>
  <div class="stat">
    <div class="stat-top"><div class="stat-icon" style="background:#F0E8FF;color:var(--purple)"><?= svgIcon('refresh', 20) ?></div></div>
    <div class="stat-value"><?= $returningRate ?>%</div>
    <div class="stat-label">نسبة العملاء العائدين</div>
  </div>
  <div class="stat">
    <div class="stat-top"><div class="stat-icon" style="background:#E8F0FF;color:var(--blue)"><?= svgIcon('check', 20) ?></div></div>
    <div class="stat-value"><?= number_format($ordersFulfilled) ?></div>
    <div class="stat-label">طلبات تم تسليمها</div>
  </div>
  <div class="stat">
    <div class="stat-top"><div class="stat-icon" style="background:#FFF8E8;color:var(--yellow)"><?= svgIcon('cart', 20) ?></div></div>
    <div class="stat-value"><?= number_format($ordersCount) ?></div>
    <div class="stat-label">إجمالي الطلبات</div>
  </div>
</div>

<!-- Sales over time + breakdown -->
<div style="display:grid;grid-template-columns:2fr 1fr;gap:16px;margin-bottom:16px">
  <div class="card">
    <div class="card-header">
      <div><div class="card-title">إجمالي المبيعات عبر الوقت</div><div style="font-size:20px;font-weight:900;font-family:var(--font-num);margin-top:4px"><?= money($totalSales) ?></div></div>
    </div>
    <div class="card-body">
      <?php if (!empty($pts)): ?>
        <svg viewBox="0 0 600 115" style="width:100%;height:110px">
          <defs><linearGradient id="cg" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="var(--ac)" stop-opacity=".2"/><stop offset="100%" stop-color="var(--ac)" stop-opacity="0"/></linearGradient></defs>
          <polygon points="30,110 <?= implode(' ',$pts) ?> <?= end($pts) ?> 570,110" fill="url(#cg)"/>
          <polyline points="<?= implode(' ',$pts) ?>" fill="none" stroke="var(--ac)" stroke-width="2.5" stroke-linejoin="round"/>
          <?php foreach($pts as $i=>$pt){[$px,$py]=explode(',',$pt);echo"<circle cx='$px' cy='$py' r='3' fill='var(--ac)'><title>".e($salesChart[$i]['date']??'').": ".money($salesChart[$i]['revenue']??0)."</title></circle>";}?>
        </svg>
      <?php else: ?><div style="text-align:center;color:var(--t3);padding:32px">لا توجد بيانات مبيعات في هذه الفترة</div><?php endif; ?>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><span class="card-title">تفصيل إجمالي المبيعات</span></div>
    <div style="padding:4px 0">
      <?php foreach([
        ['إجمالي المبيعات (Gross)', $grossSales, false],
        ['الخصومات', -$discounts, false],
        ['صافي المبيعات (Net)', $netSales, true],
        ['رسوم الشحن', $shipping, false],
        ['إجمالي المبيعات (Total)', $totalSales, true],
      ] as [$label,$val,$bold]): ?>
        <div style="display:flex;justify-content:space-between;padding:10px 20px;<?= $bold?'border-top:1px solid var(--bd);':'' ?>font-size:13px">
          <span style="<?= $bold?'font-weight:700':'color:var(--t2)' ?>"><?= $label ?></span>
          <span style="<?= $bold?'font-weight:800':'font-weight:500' ?>"><?= ($val<0?'-':'').money(abs($val)) ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- Sales by channel + AOV + Top products -->
<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px">

  <div class="card">
    <div class="card-header"><span class="card-title">المبيعات حسب النوع</span></div>
    <div class="card-body">
      <?php if (empty($salesByChannel)): ?>
        <div style="text-align:center;color:var(--t3);padding:20px;font-size:12px">لا توجد بيانات</div>
      <?php else:
        $totalChannelRev = array_sum(array_column($salesByChannel,'revenue'));
        foreach ($salesByChannel as $ch):
          $pct = $totalChannelRev>0 ? round($ch['revenue']/$totalChannelRev*100) : 0;
          $lbl = $ch['order_type']==='wholesale' ? '🏬 الجملة' : '🛍️ التجزئة';
      ?>
        <div style="margin-bottom:14px">
          <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:5px"><span><?=$lbl?></span><span style="font-weight:700"><?= money($ch['revenue']) ?></span></div>
          <div style="height:6px;background:var(--bg3);border-radius:3px"><div style="height:100%;width:<?=$pct?>%;background:var(--ac);border-radius:3px"></div></div>
          <div style="font-size:11px;color:var(--t3);margin-top:3px"><?= $ch['cnt'] ?> طلب — <?=$pct?>%</div>
        </div>
      <?php endforeach; endif; ?>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><span class="card-title">متوسط قيمة الطلب</span></div>
    <div class="card-body">
      <div style="font-size:20px;font-weight:900;margin-bottom:10px"><?= money($aov) ?></div>
      <?php if (!empty($aovChart)):
        $aovPts = [];
        foreach ($aovChart as $i=>$row) {
          $avg = $row['cnt']>0 ? $row['revenue']/$row['cnt'] : 0;
          $x=count($aovChart)>1?($i/(count($aovChart)-1))*260+10:135; $y=70-($avg/$maxAov*55);
          $aovPts[]="{$x},{$y}";
        }
      ?>
        <svg viewBox="0 0 280 75" style="width:100%;height:65px">
          <polyline points="<?= implode(' ',$aovPts) ?>" fill="none" stroke="var(--purple)" stroke-width="2" stroke-linejoin="round"/>
        </svg>
      <?php else: ?><div style="text-align:center;color:var(--t3);padding:20px;font-size:12px">لا توجد بيانات</div><?php endif; ?>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><span class="card-title"><?= svgIcon('grid', 15) ?> الزيارات والتصفح</span></div>
    <div class="card-body">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:16px">
        <div style="background:var(--bg3);border-radius:10px;padding:14px;text-align:center">
          <div style="font-size:22px;font-weight:900;font-family:var(--font-num)"><?= number_format($totalVisits) ?></div>
          <div style="font-size:11px;color:var(--t3);font-weight:600">إجمالي الزيارات</div>
        </div>
        <div style="background:var(--bg3);border-radius:10px;padding:14px;text-align:center">
          <div style="font-size:22px;font-weight:900;font-family:var(--font-num)"><?= number_format($uniqueVisitors) ?></div>
          <div style="font-size:11px;color:var(--t3);font-weight:600">زوار مميزون</div>
        </div>
      </div>
      <div style="font-size:12px;font-weight:700;color:var(--t3);margin-bottom:8px">الأكثر مشاهدة</div>
      <?php if (empty($mostViewedProducts)): ?>
        <div style="text-align:center;color:var(--t3);padding:16px;font-size:12px">لا توجد بيانات مشاهدة بعد</div>
      <?php else: foreach ($mostViewedProducts as $mv): ?>
        <div style="display:flex;align-items:center;gap:10px;padding:6px 0">
          <?php if($mv['thumbnail']): ?><img src="<?= uploadUrl($mv['thumbnail']) ?>" style="width:28px;height:28px;object-fit:cover;border-radius:6px"><?php endif; ?>
          <div style="flex:1;font-size:12.5px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= e($mv['name']) ?></div>
          <div style="font-size:11.5px;color:var(--ac);font-weight:700"><?= number_format($mv['views']) ?> مشاهدة</div>
        </div>
      <?php endforeach; endif; ?>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><span class="card-title">🏆 الأكثر مبيعاً</span></div>
    <div class="table-wrap" style="max-height:220px;overflow-y:auto">
      <table>
        <tbody>
          <?php if (empty($topProducts)): ?>
            <tr><td style="text-align:center;color:var(--t3);padding:20px;font-size:12px">لا توجد مبيعات</td></tr>
          <?php else: foreach ($topProducts as $i=>$p): ?>
            <tr>
              <td style="font-weight:700;color:var(--t3);width:20px"><?= $i+1 ?></td>
              <td style="font-size:12px;font-weight:500"><?= e($p['name']) ?></td>
              <td style="font-size:12px;font-weight:700;color:var(--green);text-align:left"><?= money($p['revenue']) ?></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<!-- Payment mix + geo -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:16px">
  <div class="card">
    <div class="card-header"><span class="card-title">طرق الدفع</span></div>
    <div class="card-body">
      <?php
      $tp = array_sum(array_column($paymentMix??[], 'cnt'));
      if (empty($paymentMix)): ?>
        <div style="text-align:center;color:var(--t3);padding:20px;font-size:12px">لا توجد بيانات</div>
      <?php else: foreach ($paymentMix as $pm):
        $pct = $tp>0 ? round($pm['cnt']/$tp*100) : 0;
        $lbl = $pm['payment_method']==='cod' ? '💵 الدفع عند الاستلام' : ($pm['payment_method']==='credit' ? '💳 رصيد N&Credit' : '💳 فواتيرك');
      ?>
        <div style="margin-bottom:14px">
          <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:5px"><span><?=$lbl?></span><span style="font-weight:700"><?=$pct?>%</span></div>
          <div style="height:6px;background:var(--bg3);border-radius:3px"><div style="height:100%;width:<?=$pct?>%;background:var(--ac);border-radius:3px"></div></div>
        </div>
      <?php endforeach; endif; ?>
    </div>
  </div>
  <div class="card">
    <div class="card-header"><span class="card-title"><?= svgIcon('map-pin', 15) ?> المبيعات حسب المحافظة</span></div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>المحافظة</th><th>الطلبات</th><th>الإيراد</th></tr></thead>
        <tbody>
          <?php if (empty($topGovs)): ?>
            <tr><td colspan="3" style="text-align:center;color:var(--t3);padding:20px;font-size:12px">لا توجد بيانات</td></tr>
          <?php else: foreach ($topGovs as $g): ?>
            <tr>
              <td style="font-weight:500;font-size:12px"><?= e($g['shipping_gov']) ?></td>
              <td style="font-size:12px"><?= number_format($g['orders']) ?></td>
              <td style="color:var(--green);font-weight:700;font-size:12px"><?= money($g['revenue']??0) ?></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php $content = ob_get_clean(); require APP_PATH . '/Views/admin/layouts/app.php';
