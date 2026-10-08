<?php
$pageTitle = 'تسجيل تاجر جديد';
$labels = ['pending'=>['⏳','قيد المراجعة','badge-warn'],'approved'=>['✅','معتمد','badge-ok'],'rejected'=>['❌','مرفوض','badge-err']];
ob_start();
?>
<div style="display:grid;grid-template-columns:1fr 340px;gap:16px;align-items:start">
  <div class="card">
    <div class="card-header"><span class="card-title">التجار المسجّلين بواسطتك (<?= count($merchants) ?>)</span></div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>الاسم</th><th>المحل</th><th>المنطقة</th><th>الحالة</th></tr></thead>
        <tbody>
          <?php if (empty($merchants)): ?>
            <tr><td colspan="4" style="text-align:center;color:var(--t3);padding:30px">لسه مسجّلتش أي تاجر</td></tr>
          <?php else: foreach ($merchants as $m): [$icon,$lbl,$cls]=$labels[$m['business_status']]; ?>
            <tr>
              <td style="font-weight:600"><?= e($m['name']) ?></td>
              <td><?= e($m['shop_name']) ?></td>
              <td style="color:var(--t2)"><?= e($m['area']) ?>، <?= e($m['governorate']) ?></td>
              <td><span class="badge <?=$cls?>"><?=$icon?> <?=$lbl?></span></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="card">
    <div class="card-header"><span class="card-title">+ تسجيل تاجر جديد</span></div>
    <div class="card-body">
      <form method="POST" action="<?= url('business/add-merchant') ?>">
        <?= csrf_field() ?>
        <div class="form-group"><label class="form-label">اسم التاجر <span>*</span></label><input type="text" name="name" class="form-input" required></div>
        <div class="form-group"><label class="form-label">البريد الإلكتروني <span>*</span></label><input type="email" name="email" class="form-input" required></div>
        <div class="form-group"><label class="form-label">الهاتف <span>*</span></label><input type="tel" name="phone" class="form-input" required></div>
        <div class="form-group"><label class="form-label">اسم المحل <span>*</span></label><input type="text" name="shop_name" class="form-input" required></div>
        <div class="form-group"><label class="form-label">المحافظة <span>*</span></label><input type="text" name="governorate" class="form-input" required></div>
        <div class="form-group"><label class="form-label">المنطقة <span>*</span></label><input type="text" name="area" class="form-input" required></div>
        <button type="submit" class="btn btn-primary btn-full">تسجيل التاجر</button>
      </form>
    </div>
  </div>
</div>
<?php $content = ob_get_clean(); require APP_PATH . '/Views/store/layouts/business.php';
