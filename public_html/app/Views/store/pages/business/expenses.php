<?php
$pageTitle = 'مصروفات المواصلات';
$labels = ['pending'=>['⏳','قيد المراجعة','badge-warn'],'approved'=>['✅','معتمد','badge-ok'],'rejected'=>['❌','مرفوض','badge-err']];
ob_start();
?>
<div style="display:grid;grid-template-columns:1fr 320px;gap:16px;align-items:start">
  <div class="card">
    <div class="card-header"><span class="card-title">سجل المصروفات</span></div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>النوع</th><th>المبلغ</th><th>الوصف</th><th>الحالة</th><th>التاريخ</th></tr></thead>
        <tbody>
          <?php if (empty($expenses)): ?>
            <tr><td colspan="5" style="text-align:center;color:var(--t3);padding:30px">لا توجد مصروفات مسجلة</td></tr>
          <?php else: foreach ($expenses as $ex): [$icon,$lbl,$cls]=$labels[$ex['status']]; ?>
            <tr>
              <td><?= e($ex['type']) ?></td>
              <td style="font-weight:700"><?= money($ex['amount']) ?></td>
              <td style="color:var(--t2);font-size:12px"><?= e($ex['description']?:'—') ?></td>
              <td><span class="badge <?=$cls?>"><?=$icon?> <?=$lbl?></span></td>
              <td style="color:var(--t3);font-size:11px"><?= formatDate($ex['created_at']) ?></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="card">
    <div class="card-header"><span class="card-title">+ إضافة مصروف</span></div>
    <div class="card-body">
      <form method="POST" action="<?= url('business/expenses') ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="form-group"><label class="form-label">النوع</label>
          <select name="type" class="form-select">
            <option value="مواصلات">🚕 مواصلات</option>
            <option value="وقود">⛽ وقود</option>
            <option value="أخرى">📋 أخرى</option>
          </select>
        </div>
        <div class="form-group"><label class="form-label">المبلغ <span>*</span></label><input type="number" name="amount" class="form-input" step="0.01" min="0.01" required></div>
        <div class="form-group"><label class="form-label">الوصف</label><input type="text" name="description" class="form-input" placeholder="تفاصيل المشوار..."></div>
        <div class="form-group"><label class="form-label">صورة الإيصال (اختياري)</label><input type="file" name="receipt_image" class="form-input" accept="image/*" style="padding:8px"></div>
        <button type="submit" class="btn btn-primary btn-full">إرسال</button>
      </form>
    </div>
  </div>
</div>
<?php $content = ob_get_clean(); require APP_PATH . '/Views/store/layouts/business.php';
