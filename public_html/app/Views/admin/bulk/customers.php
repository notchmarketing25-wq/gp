<?php
$pageTitle  = 'تعديل العملاء بالجملة';
$breadcrumb = [['label'=>'العملاء','url'=>adminUrl('customers')],['label'=>'بالجملة']];
$a = ADMIN_PREFIX;
ob_start();
?>
<div class="ph">
  <div class="ph-left"><h1>تعديل العملاء بالجملة</h1><p><?= count($customers) ?> عميل — عدّل وطبّق إجراءات بدون فتح كل عميل لوحده</p></div>
  <div class="ph-right"><a href="<?= adminUrl('customers') ?>" class="btn btn-secondary">← العملاء</a></div>
</div>

<!-- Filters -->
<div class="card" style="margin-bottom:14px">
  <div class="card-body">
    <form method="GET" style="display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:10px;align-items:end">
      <div class="form-group"><label class="form-label">البحث</label><input type="text" name="search" class="form-input" value="<?= e($search??'') ?>" placeholder="الاسم، البريد، الهاتف..."></div>
      <div class="form-group"><label class="form-label">الحالة</label>
        <select name="status" class="form-select">
          <option value="">الكل</option>
          <option value="1" <?= ($status??'')==='1'?'selected':'' ?>>✅ نشط</option>
          <option value="0" <?= ($status??'')==='0'?'selected':'' ?>>🚫 موقوف</option>
        </select>
      </div>
      <div class="form-group"><label class="form-label">عضو منذ</label><input type="date" name="from" class="form-input" value="<?= e($from??'') ?>"></div>
      <button type="submit" class="btn btn-primary">بحث</button>
    </form>
  </div>
</div>

<form method="POST" action="<?= adminUrl('bulk/customers/action') ?>" id="bulkForm">
  <?= csrf_field() ?>
  <div class="card">
    <div class="card-header" style="flex-wrap:wrap;gap:10px">
      <span class="card-title">النتائج</span>
      <div style="display:flex;gap:8px;align-items:center;margin-right:auto;flex-wrap:wrap">
        <select name="action" id="bulkAction" class="form-select" onchange="toggleCreditFields(this.value)">
          <option value="activate">✅ تفعيل المحددين</option>
          <option value="deactivate">🚫 إيقاف المحددين</option>
          <option value="add_credit">💳 إضافة رصيد N&Credit</option>
          <option value="deduct_credit">➖ خصم رصيد N&Credit</option>
          <option value="export_csv">📥 تصدير CSV</option>
        </select>
        <input type="number" name="credit_amount" id="creditAmountInput" class="form-input" placeholder="المبلغ" step="0.01" min="0" style="display:none;width:110px">
        <input type="text" name="credit_reason" id="creditReasonInput" class="form-input" placeholder="السبب (اختياري)" style="display:none;width:170px">
        <button type="submit" class="btn btn-primary" onclick="return checkSelected()"><?= svgIcon('zap', 15) ?> تطبيق على المحدد</button>
      </div>
    </div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th style="width:36px"><input type="checkbox" id="selectAll" onclick="toggleAll(this)" style="cursor:pointer;accent-color:var(--ac)"></th>
            <th>العميل</th><th>الهاتف</th><th>الطلبات</th><th>الإجمالي</th><th>رصيد N&</th><th>الحالة</th><th>تاريخ التسجيل</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($customers)): ?>
            <tr><td colspan="8"><div class="empty"><div class="empty-icon"><?= svgIcon('user', 32) ?></div><div class="empty-title">لا يوجد عملاء</div></div></td></tr>
          <?php else: foreach ($customers as $c): ?>
            <tr>
              <td><input type="checkbox" name="ids[]" value="<?=$c['id']?>" style="cursor:pointer;accent-color:var(--ac)"></td>
              <td>
                <a href="<?= adminUrl('customers/'.$c['id']) ?>" style="display:flex;align-items:center;gap:9px;text-decoration:none">
                  <div style="width:30px;height:30px;border-radius:50%;background:var(--acb);border:1px solid var(--ac);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:var(--ac);flex-shrink:0"><?= mb_substr($c['name'],0,1) ?></div>
                  <div><div style="font-weight:500;font-size:13px;color:var(--t1)"><?= e($c['name']) ?></div><div style="font-size:11px;color:var(--t3)"><?= e($c['email']) ?></div></div>
                </a>
              </td>
              <td class="td-dim"><?= e($c['phone']??'—') ?></td>
              <td style="font-weight:600"><?= number_format($c['orders_count']??0) ?></td>
              <td style="font-weight:600;color:var(--green)"><?= money($c['orders_total']??0) ?></td>
              <td style="font-weight:600;<?= ($c['store_credit']??0)>0?'color:var(--green)':'' ?>"><?= ($c['store_credit']??0)>0 ? money($c['store_credit']) : '—' ?></td>
              <td>
                <label class="toggle" title="تفعيل/إيقاف فوري">
                  <input type="checkbox" class="quick-toggle" data-id="<?= $c['id'] ?>" <?= $c['is_active']?'checked':'' ?>>
                  <span class="toggle-slider"></span>
                </label>
              </td>
              <td class="td-dim"><?= formatDate($c['created_at']) ?></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
    <?php if (!empty($customers)): ?>
      <div style="padding:12px 16px;border-top:1px solid var(--bd);font-size:12px;color:var(--t3)">
        المحدد: <span id="selectedCount">0</span> من <?= count($customers) ?> عميل
      </div>
    <?php endif; ?>
  </div>
</form>

<?php
$csrf = Session::csrf();
$extraScript = "
function toggleAll(cb) {
  document.querySelectorAll('input[name=\"ids[]\"]').forEach(c => c.checked = cb.checked);
  updateCount();
}
document.querySelectorAll('input[name=\"ids[]\"]').forEach(c => c.addEventListener('change', updateCount));
function updateCount() {
  document.getElementById('selectedCount').textContent = document.querySelectorAll('input[name=\"ids[]\"]:checked').length;
}
function checkSelected() {
  const n = document.querySelectorAll('input[name=\"ids[]\"]:checked').length;
  if (!n) { alert('اختر عميلاً واحداً على الأقل'); return false; }
  const action = document.getElementById('bulkAction').value;
  if ((action==='add_credit'||action==='deduct_credit')) {
    const amt = parseFloat(document.getElementById('creditAmountInput').value);
    if (!amt || amt<=0) { alert('أدخل مبلغ صحيح'); return false; }
  }
  return true;
}
function toggleCreditFields(action) {
  const show = (action==='add_credit'||action==='deduct_credit');
  document.getElementById('creditAmountInput').style.display = show?'inline-block':'none';
  document.getElementById('creditReasonInput').style.display = show?'inline-block':'none';
}
// Quick inline activate/deactivate toggle — instant, no page reload
document.querySelectorAll('.quick-toggle').forEach(cb=>{
  cb.addEventListener('change', async function(){
    const id = this.dataset.id;
    const fd = new FormData();
    fd.append('_csrf','".addslashes($csrf)."');
    await fetch('/$a/customers/'+id+'/toggle', {method:'POST', body:fd});
  });
});
";
$content = ob_get_clean();
require APP_PATH . '/Views/admin/layouts/app.php';
