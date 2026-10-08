<?php
$pageTitle  = 'معاينة استيراد العملاء';
$breadcrumb = [['label'=>'العملاء','url'=>adminUrl('customers')],['label'=>'معاينة']];
$a = ADMIN_PREFIX;
$newCount = count(array_filter($customers, fn($c)=>!$c['exists']));
$updCount = count(array_filter($customers, fn($c)=>$c['exists']));
ob_start();
?>
<div class="ph"><div class="ph-left"><h1><span style="display:inline-flex;align-items:center;gap:9px"><?= svgIcon('search', 22) ?> معاينة العملاء</span></h1><p>راجع قبل التأكيد</p></div><div class="ph-right"><a href="<?= adminUrl('import/shopify-customers') ?>" class="btn btn-secondary">← رفع ملف تاني</a></div></div>
<div class="stats" style="grid-template-columns:repeat(3,1fr);margin-bottom:20px">
  <div class="stat"><div class="stat-icon" style="background:#E8F0FF;color:var(--blue)"><?= svgIcon('user', 20) ?></div><div class="stat-label">الإجمالي</div><div class="stat-value"><?= count($customers) ?></div></div>
  <div class="stat"><div class="stat-icon" style="background:#E8FBF4;color:var(--green)"><?= svgIcon('star', 20) ?></div><div class="stat-label">جديد</div><div class="stat-value"><?= $newCount ?></div></div>
  <div class="stat"><div class="stat-icon" style="background:#FFF8E8;color:var(--yellow)"><?= svgIcon('refresh', 20) ?></div><div class="stat-label">تحديث</div><div class="stat-value"><?= $updCount ?></div></div>
</div>
<div class="card" style="margin-bottom:20px">
  <div class="table-wrap"><table>
    <thead><tr><th>الاسم</th><th>البريد</th><th>الهاتف</th><th>الحالة</th></tr></thead>
    <tbody>
      <?php foreach ($customers as $c): ?>
        <tr><td style="font-weight:600"><?= e($c['name']) ?></td><td class="td-dim"><?= e($c['email']) ?></td><td class="td-dim"><?= e($c['phone']?:'—') ?></td>
        <td><span class="badge <?= $c['exists']?'badge-warn':'badge-ok' ?>"><?= $c['exists']?'🔄 تحديث':'🆕 جديد' ?></span></td></tr>
      <?php endforeach; ?>
    </tbody>
  </table></div>
</div>
<form method="POST" action="<?= adminUrl('import/shopify-customers/confirm') ?>" style="position:sticky;bottom:16px">
  <?= csrf_field() ?><input type="hidden" name="batch_id" value="<?= e($batchId) ?>">
  <div class="card" style="padding:16px 20px;display:flex;align-items:center;justify-content:space-between;box-shadow:var(--shadow-lg)">
    <div style="font-size:13px;color:var(--t2)">جاهز لاستيراد <strong style="color:var(--t1)"><?= count($customers) ?></strong> عميل</div>
    <button type="submit" class="btn btn-primary btn-lg" onclick="return confirm('تأكيد؟')"><?= svgIcon('check', 15) ?> تأكيد الاستيراد</button>
  </div>
</form>
<?php $content = ob_get_clean(); require APP_PATH . '/Views/admin/layouts/app.php';
