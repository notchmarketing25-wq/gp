<?php
$pageTitle  = 'معاينة استيراد الشحنات';
$breadcrumb = [['label'=>'الشحنات','url'=>adminUrl('shipping')],['label'=>'معاينة']];
$a = ADMIN_PREFIX;
$newCount = count(array_filter($rows, fn($r)=>!$r['already_exists']));
$skipCount = count(array_filter($rows, fn($r)=>$r['already_exists']));
ob_start();
?>
<div class="ph"><div class="ph-left"><h1><span style="display:inline-flex;align-items:center;gap:9px"><?= svgIcon('search', 22) ?> معاينة الشحنات</span></h1><p>راجع قبل التأكيد</p></div><div class="ph-right"><a href="<?= adminUrl('shipping/import') ?>" class="btn btn-secondary">← رفع ملف تاني</a></div></div>
<div class="stats" style="grid-template-columns:repeat(3,1fr);margin-bottom:20px">
  <div class="stat"><div class="stat-icon" style="background:#E8F0FF;color:var(--blue)"><?= svgIcon('truck', 20) ?></div><div class="stat-label">الإجمالي</div><div class="stat-value"><?= count($rows) ?></div></div>
  <div class="stat"><div class="stat-icon" style="background:#E8FBF4;color:var(--green)"><?= svgIcon('star', 20) ?></div><div class="stat-label">هتتنشئ</div><div class="stat-value"><?= $newCount ?></div></div>
  <div class="stat"><div class="stat-icon" style="background:#FFF8E8;color:var(--yellow)"><?= svgIcon('refresh', 20) ?></div><div class="stat-label">مستوردة سابقاً</div><div class="stat-value"><?= $skipCount ?></div></div>
</div>
<div class="card" style="margin-bottom:20px">
  <div class="table-wrap"><table>
    <thead><tr><th>رقم الطلب</th><th>العميل</th><th>رقم التتبع</th><th>الحالة</th><th>مبلغ COD</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td class="td-mono" style="font-weight:600"><?= e($r['order_number']) ?></td>
          <td><?= e($r['customer_name']) ?><div style="font-size:11px;color:var(--t3)"><?= e($r['city']) ?>، <?= e($r['governorate']) ?></div></td>
          <td class="td-mono td-dim"><?= e($r['tracking_number'] ?: '—') ?></td>
          <td class="td-dim"><?= e($r['status']) ?></td>
          <td style="font-weight:700"><?= money($r['cod_amount']) ?></td>
          <td><span class="badge <?= $r['already_exists']?'badge-dim':'badge-ok' ?>"><?= $r['already_exists']?'⏭️ موجودة':'🆕 جديدة' ?></span></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table></div>
</div>
<form method="POST" action="<?= adminUrl('shipping/import/confirm') ?>" style="position:sticky;bottom:16px">
  <?= csrf_field() ?>
  <input type="hidden" name="batch_id" value="<?= e($batchId) ?>">
  <input type="hidden" name="carrier_id" value="<?= (int)$carrierId ?>">
  <div class="card" style="padding:16px 20px;display:flex;align-items:center;justify-content:space-between;box-shadow:var(--shadow-lg)">
    <div style="font-size:13px;color:var(--t2)">جاهز لاستيراد <strong style="color:var(--t1)"><?= $newCount ?></strong> شحنة</div>
    <button type="submit" class="btn btn-primary btn-lg" onclick="return confirm('تأكيد؟')"><?= svgIcon('check', 15) ?> تأكيد الاستيراد</button>
  </div>
</form>
<?php $content = ob_get_clean(); require APP_PATH . '/Views/admin/layouts/app.php';
