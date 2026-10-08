<?php
$pageTitle  = 'الطلبات بالجملة';
$breadcrumb = [['label'=>'الطلبات','url'=>adminUrl('orders')],['label'=>'بالجملة']];
$a = ADMIN_PREFIX;
ob_start();
?>
<div class="ph">
  <div class="ph-left"><h1>إدارة الطلبات بالجملة</h1><p><?= count($orders) ?> طلب</p></div>
  <div class="ph-right">
    <a href="<?= adminUrl('orders') ?>" class="btn btn-secondary">← الطلبات</a>
  </div>
</div>

<!-- Filters -->
<div class="card" style="margin-bottom:14px">
  <div class="card-body">
    <form method="GET" style="display:grid;grid-template-columns:repeat(5,1fr) auto;gap:10px;align-items:end">
      <div class="form-group"><label class="form-label">البحث</label><input type="text" name="search" class="form-input" value="<?= e($search??'') ?>" placeholder="رقم، اسم، هاتف..."></div>
      <div class="form-group"><label class="form-label">حالة الطلب</label>
        <select name="status" class="form-select">
          <option value="">الكل</option>
          <?php foreach(['pending'=>'⏳ معلق','processing'=>'🔄 قيد المعالجة','shipped'=>'🚚 تم الشحن','delivered'=>'✅ تم التسليم','cancelled'=>'❌ ملغي'] as $v=>$l): ?>
            <option value="<?=$v?>" <?= ($status??'')===$v?'selected':'' ?>><?=$l?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group"><label class="form-label">حالة الدفع</label>
        <select name="payment" class="form-select">
          <option value="">الكل</option>
          <option value="unpaid" <?= ($payment??'')==='unpaid'?'selected':'' ?>>❌ غير مدفوع</option>
          <option value="paid"   <?= ($payment??'')==='paid'?'selected':'' ?>>✅ مدفوع</option>
        </select>
      </div>
      <div class="form-group"><label class="form-label">من تاريخ</label><input type="date" name="from" class="form-input" value="<?= e($from??'') ?>"></div>
      <div class="form-group"><label class="form-label">إلى تاريخ</label><input type="date" name="to" class="form-input" value="<?= e($to??'') ?>"></div>
      <button type="submit" class="btn btn-primary">بحث</button>
    </form>
  </div>
</div>

<!-- Bulk action form -->
<form method="POST" action="<?= adminUrl('bulk/orders/action') ?>" id="bulkForm">
  <?= csrf_field() ?>

  <div class="card">
    <div class="card-header" style="gap:12px">
      <span class="card-title">نتائج البحث</span>
      <div style="display:flex;align-items:center;gap:8px;margin-right:auto">
        <select name="action" class="form-select" style="min-width:200px">
          <optgroup label="تغيير الحالة">
            <option value="mark_processing">🔄 تحويل لـ "قيد المعالجة"</option>
            <option value="mark_shipped">🚚 تحويل لـ "تم الشحن"</option>
            <option value="mark_delivered">✅ تحويل لـ "تم التسليم"</option>
            <option value="mark_cancelled">❌ إلغاء الطلبات</option>
            <option value="mark_paid">💰 تحديد كـ "مدفوع"</option>
          </optgroup>
          <optgroup label="تصدير">
            <option value="export_csv">📥 تصدير CSV</option>
          </optgroup>
        </select>
        <button type="submit" class="btn btn-primary" onclick="return confirmBulk()"><?= svgIcon('zap', 15) ?> تطبيق</button>
      </div>
    </div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th style="width:36px"><input type="checkbox" id="selectAll" onclick="toggleAll(this)" style="cursor:pointer;accent-color:var(--ac)"></th>
            <th>رقم الطلب</th><th>العميل</th><th>المبلغ</th><th>الدفع</th><th>الحالة</th><th>المحافظة</th><th>التاريخ</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($orders)): ?>
            <tr><td colspan="8"><div class="empty"><div class="empty-icon"><?= svgIcon('cart', 32) ?></div><div class="empty-title">لا توجد طلبات تطابق البحث</div></div></td></tr>
          <?php else: foreach ($orders as $o): ?>
            <tr>
              <td><input type="checkbox" name="ids[]" value="<?=$o['id']?>" style="cursor:pointer;accent-color:var(--ac)"></td>
              <td><a href="<?= adminUrl('orders/'.$o['id']) ?>" style="color:var(--ac);font-weight:700;font-family:monospace" target="_blank"><?= e($o['order_number']) ?></a></td>
              <td>
                <div style="font-weight:500;font-size:13px"><?= e($o['customer_name']) ?></div>
                <div style="font-size:11px;color:var(--t3)"><?= e($o['customer_phone']??'') ?></div>
              </td>
              <td style="font-weight:700"><?= money($o['total']) ?></td>
              <td><span class="badge <?= ($o['payment_status']==='paid')?'badge-ok':($o['payment_status']==='failed'?'badge-err':'badge-warn') ?>"><?= paymentStatusLabel($o['payment_status']??'unpaid') ?></span></td>
              <td><span class="badge badge-<?= orderStatusColor($o['status']??'pending') ?>"><?= orderStatusLabel($o['status']??'pending') ?></span></td>
              <td class="td-dim"><?= e($o['shipping_gov']??'—') ?></td>
              <td class="td-dim"><?= formatDate($o['created_at']) ?></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
    <?php if (!empty($orders)): ?>
      <div style="padding:12px 16px;border-top:1px solid var(--bd);font-size:12px;color:var(--t3)">
        المحدد: <span id="selectedCount">0</span> من <?= count($orders) ?> طلب
        | إجمالي المعروض: <strong style="color:var(--t1)"><?= money(array_sum(array_column($orders,'total'))) ?></strong>
      </div>
    <?php endif; ?>
  </div>
</form>

<?php
$extraScript = "
function toggleAll(cb) {
  document.querySelectorAll('input[name=\"ids[]\"]').forEach(c => c.checked = cb.checked);
  updateCount();
}
document.querySelectorAll('input[name=\"ids[]\"]').forEach(c => c.addEventListener('change', updateCount));
function updateCount() {
  const n = document.querySelectorAll('input[name=\"ids[]\"]:checked').length;
  document.getElementById('selectedCount').textContent = n;
}
function confirmBulk() {
  const n = document.querySelectorAll('input[name=\"ids[]\"]:checked').length;
  if (!n) { alert('اختر طلباً واحداً على الأقل'); return false; }
  const action = document.querySelector('[name=action]').value;
  if (action === 'mark_cancelled') return confirm('إلغاء ' + n + ' طلب؟');
  return n > 0;
}
";
$content = ob_get_clean();
require APP_PATH . '/Views/admin/layouts/app.php';
