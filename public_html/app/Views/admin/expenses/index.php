<?php
$pageTitle  = 'المصروفات';
$breadcrumb = [['label'=>'التحليلات','url'=>adminUrl('analytics')], ['label'=>'المصروفات']];
$a = ADMIN_PREFIX;
$monthLabel = date('F Y', strtotime($month.'-01'));
ob_start();
?>
<?php if (!empty($migrationMissing)): ?>
<div style="background:rgba(220,38,38,.08);border:1px solid rgba(220,38,38,.25);border-radius:10px;padding:16px 20px;margin-bottom:20px">
  <div style="font-weight:800;font-size:14px;color:var(--red);margin-bottom:6px">⚠️ نظام المصروفات لسه مش مفعّل</div>
  <div style="font-size:12.5px;color:var(--t2);line-height:1.8">
    لازم تشغّل ملف <code style="background:var(--bg3);padding:2px 6px;border-radius:5px;direction:ltr;display:inline-block">database/accounting_migration.sql</code> على قاعدة البيانات الأول.
    لو شغّلته وبرضو الصفحة مش شغالة، تأكد إنه اتشغّل بنجاح من غير رسالة خطأ في الآخر.
  </div>
</div>
<?php else: ?>
<div class="ph">
  <div class="ph-left"><h1><span style="display:inline-flex;align-items:center;gap:9px"><?= svgIcon('receipt', 22) ?> المصروفات</span></h1><p>سجّل كل مصاريف الشركة — إعلانات، إيجار، رواتب، وأي حاجة تانية</p></div>
  <div class="ph-right">
    <a href="<?= adminUrl('finance?month='.$month) ?>" class="btn btn-secondary"><?= svgIcon('chart', 15) ?> تقرير صافي الدخل</a>
    <button class="btn btn-primary" onclick="openExpenseModal()">+ مصروف جديد</button>
  </div>
</div>

<div class="card" style="margin-bottom:16px">
  <div class="card-body" style="display:flex;gap:14px;align-items:center;flex-wrap:wrap">
    <form method="GET" style="display:flex;gap:8px;align-items:center">
      <input type="month" name="month" value="<?= e($month) ?>" class="form-input" onchange="this.form.submit()">
      <select name="category" class="form-select" onchange="this.form.submit()">
        <option value="">كل الأنواع</option>
        <?php foreach ($categories as $c): ?><option value="<?= $c['id'] ?>" <?= $categoryId==$c['id']?'selected':'' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
      </select>
    </form>
    <div style="margin-right:auto;font-weight:800;font-size:16px">إجمالي <?= $monthLabel ?>: <span style="color:var(--red)"><?= money($total) ?></span></div>
  </div>
</div>

<div class="card">
  <div class="table-wrap">
    <table>
      <thead><tr><th>التاريخ</th><th>النوع</th><th>الوصف</th><th>الجهة</th><th>المبلغ</th><th></th></tr></thead>
      <tbody>
        <?php if (empty($expenses)): ?>
          <tr><td colspan="6"><div class="empty"><div class="empty-icon"><?= svgIcon('receipt', 32) ?></div><div class="empty-title">لا توجد مصروفات في <?= $monthLabel ?></div></div></td></tr>
        <?php else: foreach ($expenses as $e): ?>
          <tr>
            <td class="td-dim" style="font-size:12px"><?= formatDate($e['expense_date']) ?></td>
            <td><span class="badge badge-dim"><?= svgIcon($e['category_icon']?:'tag', 12) ?> <?= e($e['category_name'] ?: 'غير مصنّف') ?></span></td>
            <td><?= e($e['description'] ?: '—') ?></td>
            <td class="td-dim"><?= e($e['vendor'] ?: '—') ?></td>
            <td style="font-weight:700;color:var(--red)"><?= money($e['amount']) ?></td>
            <td>
              <div style="display:flex;gap:5px;justify-content:flex-end">
                <button class="btn btn-secondary btn-sm" onclick='editExpense(<?= htmlspecialchars(json_encode($e),ENT_QUOTES) ?>)'>تعديل</button>
                <button class="btn btn-danger btn-sm" onclick="confirmDelete('<?= adminUrl('expenses/'.$e['id'].'/delete') ?>','حذف المصروف؟','')">حذف</button>
              </div>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php if (empty($migrationMissing)): ?>
<div class="modal-overlay" id="expenseModal">
  <div class="modal">
    <div class="modal-head"><h3 id="emTitle">+ مصروف جديد</h3><button type="button" class="btn btn-ghost btn-icon" onclick="document.getElementById('expenseModal').classList.remove('open')" style="font-size:18px">×</button></div>
    <form method="POST" id="expenseForm" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:12px">
        <div class="form-group" style="margin:0">
          <label class="form-label">النوع</label>
          <select name="category_id" id="emCategory" class="form-select">
            <?php foreach ($categories as $c): ?><option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="form-group" style="margin:0">
          <label class="form-label">المبلغ <span class="req">*</span></label>
          <input type="number" name="amount" id="emAmount" class="form-input" step="0.01" min="0" required>
        </div>
      </div>
      <div class="form-group" style="margin-bottom:12px">
        <label class="form-label">الوصف</label>
        <input type="text" name="description" id="emDescription" class="form-input" placeholder="مثال: إعلان فيسبوك أسبوعي">
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:12px">
        <div class="form-group" style="margin:0">
          <label class="form-label">الجهة / المورد</label>
          <input type="text" name="vendor" id="emVendor" class="form-input">
        </div>
        <div class="form-group" style="margin:0">
          <label class="form-label">التاريخ <span class="req">*</span></label>
          <input type="date" name="expense_date" id="emDate" class="form-input" required value="<?= date('Y-m-d') ?>">
        </div>
      </div>
      <div class="form-group" style="margin-bottom:16px">
        <label class="form-label">صورة الفاتورة (اختياري)</label>
        <input type="file" name="receipt_image" class="form-input" accept="image/*" style="padding:8px">
      </div>
      <div class="modal-actions">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('expenseModal').classList.remove('open')">إلغاء</button>
        <button type="submit" class="btn btn-primary">حفظ</button>
      </div>
    </form>
  </div>
</div>

<?php
$extraScript = "
function openExpenseModal(){
  document.getElementById('emTitle').textContent='+ مصروف جديد';
  document.getElementById('expenseForm').reset();
  document.getElementById('expenseForm').action='/$a/expenses/create';
  document.getElementById('emDate').value='" . date('Y-m-d') . "';
  document.getElementById('expenseModal').classList.add('open');
}
function editExpense(e){
  document.getElementById('emTitle').textContent='تعديل المصروف';
  document.getElementById('expenseForm').action='/$a/expenses/'+e.id;
  document.getElementById('emCategory').value=e.category_id||'';
  document.getElementById('emAmount').value=e.amount;
  document.getElementById('emDescription').value=e.description||'';
  document.getElementById('emVendor').value=e.vendor||'';
  document.getElementById('emDate').value=e.expense_date;
  document.getElementById('expenseModal').classList.add('open');
}
";
endif;
$content = ob_get_clean();
require APP_PATH . '/Views/admin/layouts/app.php';
