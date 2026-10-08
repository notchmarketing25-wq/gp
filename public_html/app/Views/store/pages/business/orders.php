<?php
$pageTitle = 'طلباتي';
$statusLabels = ['pending'=>['⏳','قيد الانتظار','badge-warn'],'processing'=>['🔄','قيد المعالجة','badge-info'],'shipped'=>['🚚','تم الشحن','badge-info'],'delivered'=>['✅','تم التسليم','badge-ok'],'cancelled'=>['❌','ملغي','badge-err']];
$countMap = [];
foreach ($counts as $c) { $countMap[$c['status']] = $c['cnt']; }
ob_start();
?>
<?php if ($customer['business_type'] === 'rep' && (float)($customer['commission_percent'] ?? 0) > 0): ?>
<div style="background:#0A0A0A;border-radius:14px;padding:18px 22px;margin-bottom:16px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px">
  <div style="color:#fff">
    <div style="font-size:11px;color:rgba(255,255,255,.6);font-weight:600">إجمالي عمولتك المكتسبة</div>
    <div style="font-size:24px;font-weight:900;font-family:var(--font-num,inherit)"><?= money($totalCommission) ?></div>
  </div>
  <div style="color:rgba(255,255,255,.7);font-size:12px">نسبتك الحالية: <span style="color:#fff;font-weight:700"><?= e($customer['commission_percent']) ?>%</span> من قيمة كل فاتورة</div>
</div>
<?php endif; ?>
<div style="display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap">
  <a href="?status="><button class="btn <?= !$status?'btn-primary':'btn-secondary' ?> btn-sm">📋 الكل</button></a>
  <?php foreach ($statusLabels as $k=>[$icon,$lbl,$cls]): ?>
    <a href="?status=<?=$k?>"><button class="btn <?= $status===$k?'btn-primary':'btn-secondary' ?> btn-sm"><?=$icon?> <?=$lbl?> <?= isset($countMap[$k]) ? '('.$countMap[$k].')' : '' ?></button></a>
  <?php endforeach; ?>
</div>

<div class="card">
  <div class="table-wrap">
    <table>
      <thead><tr><th>رقم الطلب</th><th>العميل</th><th>المبلغ</th><?php if ($customer['business_type']==='rep'): ?><th>عمولتك</th><?php endif; ?><th>طريقة الدفع</th><th>الحالة</th><th>التاريخ</th></tr></thead>
      <tbody>
        <?php if (empty($orders)): ?>
          <tr><td colspan="<?= $customer['business_type']==='rep'?7:6 ?>" style="text-align:center;color:var(--t3);padding:40px">لا توجد طلبات</td></tr>
        <?php else: foreach ($orders as $o): [$icon,$lbl,$cls] = $statusLabels[$o['status']] ?? ['📦',$o['status'],'badge-dim']; ?>
          <tr>
            <td style="font-family:monospace;font-weight:700;color:var(--ac)"><?= e($o['order_number']) ?></td>
            <td>
              <?= e($o['customer_name']) ?>
              <?php if ($o['customer_id'] != $customer['id']): ?>
                <div style="font-size:10.5px;color:#7B2FBE">👤 نيابة عن عميل</div>
              <?php endif; ?>
            </td>
            <td style="font-weight:700"><?= money($o['total']) ?></td>
            <?php if ($customer['business_type']==='rep'): ?>
              <td style="font-weight:700;color:var(--green,#16a34a)"><?= $o['rep_commission_amount'] !== null ? money($o['rep_commission_amount']) : '—' ?></td>
            <?php endif; ?>
            <td>
              <?php if ($o['payment_method']==='credit'): ?><span class="badge badge-purple">💳 N&Credit</span>
              <?php else: ?><span class="badge badge-dim">💵 عند الاستلام</span><?php endif; ?>
            </td>
            <td><span class="badge <?=$cls?>"><?=$icon?> <?=$lbl?></span></td>
            <td style="color:var(--t3);font-size:12px"><?= formatDate($o['created_at']) ?></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php $content = ob_get_clean(); require APP_PATH . '/Views/store/layouts/business.php';
