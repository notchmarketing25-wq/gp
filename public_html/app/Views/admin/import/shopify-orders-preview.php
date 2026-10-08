<?php
$pageTitle  = 'معاينة استيراد الطلبات';
$breadcrumb = [['label'=>'الطلبات','url'=>adminUrl('orders')],['label'=>'معاينة']];
$a = ADMIN_PREFIX;
$newCount   = count(array_filter($orders, fn($o)=>!$o['already_imported']));
$skipCount  = count(array_filter($orders, fn($o)=>$o['already_imported']));
$totalValue = array_sum(array_column(array_filter($orders, fn($o)=>!$o['already_imported']), 'total'));
ob_start();
?>
<div class="ph"><div class="ph-left"><h1><span style="display:inline-flex;align-items:center;gap:9px"><?= svgIcon('search', 22) ?> معاينة الطلبات</span></h1><p>راجع قبل التأكيد</p></div><div class="ph-right"><a href="<?= adminUrl('import/shopify-orders') ?>" class="btn btn-secondary">← رفع ملف تاني</a></div></div>
<div class="stats" style="grid-template-columns:repeat(4,1fr);margin-bottom:20px">
  <div class="stat"><div class="stat-icon" style="background:#E8F0FF;color:var(--blue)"><?= svgIcon('cart', 20) ?></div><div class="stat-label">الإجمالي</div><div class="stat-value"><?= count($orders) ?></div></div>
  <div class="stat"><div class="stat-icon" style="background:#E8FBF4;color:var(--green)"><?= svgIcon('star', 20) ?></div><div class="stat-label">هتتنشئ</div><div class="stat-value"><?= $newCount ?></div></div>
  <div class="stat"><div class="stat-icon" style="background:#FFF8E8;color:var(--yellow)"><?= svgIcon('refresh', 20) ?></div><div class="stat-label">مستوردة سابقاً</div><div class="stat-value"><?= $skipCount ?></div></div>
  <div class="stat"><div class="stat-icon" style="background:#F0E8FF;color:var(--purple)"><?= svgIcon('store', 20) ?></div><div class="stat-label">القيمة الإجمالية</div><div class="stat-value" style="font-size:18px"><?= money($totalValue) ?></div></div>
</div>
<div class="card" style="margin-bottom:20px">
  <div class="table-wrap"><table>
    <thead><tr><th>رقم الطلب</th><th>العميل</th><th>المنتجات</th><th>الإجمالي</th><th>الحالة</th></tr></thead>
    <tbody>
      <?php foreach ($orders as $o): ?>
        <tr>
          <td class="td-mono" style="font-weight:600"><?= e($o['order_number']) ?></td>
          <td><?= e($o['customer_name']) ?><div style="font-size:11px;color:var(--t3)"><?= e($o['email']) ?></div></td>
          <td class="td-dim"><?= count($o['items']) ?> منتج</td>
          <td style="font-weight:700"><?= money($o['total']) ?></td>
          <td><span class="badge <?= $o['already_imported']?'badge-dim':'badge-ok' ?>"><?= $o['already_imported']?'⏭️ مستورد':'🆕 جديد' ?></span></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table></div>
</div>
<form method="POST" action="<?= adminUrl('import/shopify-orders/confirm') ?>" style="position:sticky;bottom:16px">
  <?= csrf_field() ?><input type="hidden" name="batch_id" value="<?= e($batchId) ?>">
  <div class="card" style="padding:16px 20px;display:flex;align-items:center;justify-content:space-between;box-shadow:var(--shadow-lg)">
    <div style="font-size:13px;color:var(--t2)">جاهز لاستيراد <strong style="color:var(--t1)"><?= $newCount ?></strong> طلب</div>
    <button type="submit" class="btn btn-primary btn-lg" onclick="return confirm('تأكيد؟')"><?= svgIcon('check', 15) ?> تأكيد الاستيراد</button>
  </div>
</form>
<?php $content = ob_get_clean(); require APP_PATH . '/Views/admin/layouts/app.php';
