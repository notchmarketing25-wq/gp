<?php
$pageTitle  = 'معاينة عملاء الجملة';
$breadcrumb = [['label'=>'الجملة','url'=>adminUrl('wholesale')],['label'=>'معاينة العملاء']];
$a = ADMIN_PREFIX;

$newCount     = count(array_filter($customers, fn($c)=>!$c['exists']));
$updateCount  = count(array_filter($customers, fn($c)=>$c['exists']));

ob_start();
?>
<div class="ph">
  <div class="ph-left"><h1><span style="display:inline-flex;align-items:center;gap:9px"><?= svgIcon('search', 22) ?> معاينة العملاء قبل الحفظ</span></h1><p>الحالة اللي هتتحدد: <strong><?= $status==='approved'?'✅ معتمد مباشرة':'⏳ قيد المراجعة' ?></strong></p></div>
  <div class="ph-right"><a href="<?= adminUrl('wholesale/customers/bulk-upload') ?>" class="btn btn-secondary">← رفع ملف تاني</a></div>
</div>

<div class="stats" style="grid-template-columns:repeat(3,1fr);margin-bottom:20px">
  <div class="stat"><div class="stat-icon" style="background:#E8F0FF;color:var(--blue)"><?= svgIcon('user', 20) ?></div><div class="stat-label">إجمالي السطور</div><div class="stat-value"><?= count($customers) ?></div></div>
  <div class="stat"><div class="stat-icon" style="background:#E8FBF4;color:var(--green)"><?= svgIcon('star', 20) ?></div><div class="stat-label">حسابات جديدة</div><div class="stat-value"><?= $newCount ?></div></div>
  <div class="stat"><div class="stat-icon" style="background:#FFF8E8;color:var(--yellow)"><?= svgIcon('refresh', 20) ?></div><div class="stat-label">حسابات هتتحدث</div><div class="stat-value"><?= $updateCount ?></div></div>
</div>

<div class="card" style="margin-bottom:20px">
  <div class="table-wrap">
    <table>
      <thead><tr><th>الاسم</th><th>البريد</th><th>الهاتف</th><th>الشركة</th><th>الحالة</th></tr></thead>
      <tbody>
        <?php foreach ($customers as $c): ?>
          <tr>
            <td style="font-weight:600"><?= e($c['name']) ?></td>
            <td class="td-dim"><?= e($c['email']) ?></td>
            <td class="td-dim"><?= e($c['phone']?:'—') ?></td>
            <td><?= e($c['company_name']?:'—') ?></td>
            <td><span class="badge <?= $c['exists']?'badge-warn':'badge-ok' ?>"><?= $c['exists']?'🔄 تحديث حساب موجود':'🆕 حساب جديد' ?></span></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<form method="POST" action="<?= adminUrl('wholesale/customers/bulk-upload/confirm') ?>" style="position:sticky;bottom:16px">
  <?= csrf_field() ?>
  <input type="hidden" name="batch_id" value="<?= e($batchId) ?>">
  <div class="card" style="padding:16px 20px;display:flex;align-items:center;justify-content:space-between;box-shadow:var(--shadow-lg)">
    <div style="font-size:13px;color:var(--t2)">جاهز لاستيراد <strong style="color:var(--t1)"><?= count($customers) ?></strong> عميل</div>
    <button type="submit" class="btn btn-primary btn-lg" onclick="return confirm('تأكيد استيراد <?= count($customers) ?> عميل؟')"><?= svgIcon('check', 15) ?> تأكيد الاستيراد</button>
  </div>
</form>

<?php $content = ob_get_clean(); require APP_PATH . '/Views/admin/layouts/app.php';
