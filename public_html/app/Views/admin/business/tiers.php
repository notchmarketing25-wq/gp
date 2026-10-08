<?php
$pageTitle  = 'الشرائح السعرية';
$breadcrumb = [['label'=>'Business','url'=>adminUrl('business')],['label'=>'الشرائح السعرية']];
$a = ADMIN_PREFIX;
ob_start();
?>
<div class="ph"><div class="ph-left"><h1><span style="display:inline-flex;align-items:center;gap:9px"><?= svgIcon('tag', 22) ?> الشرائح السعرية</span></h1><p>كل شريحة بتحدد نسبة خصم عن السعر العادي</p></div><div class="ph-right"><a href="<?= adminUrl('business') ?>" class="btn btn-secondary">← Business</a></div></div>

<div style="display:grid;grid-template-columns:1fr 320px;gap:16px;align-items:start">
  <div class="card">
    <div class="table-wrap">
      <table>
        <thead><tr><th>الشريحة</th><th>الخصم</th><th>ترقية تلقائية عند</th><th>الحسابات</th><th>افتراضية</th><th></th></tr></thead>
        <tbody>
          <?php if (empty($tiers)): ?><tr><td colspan="6"><div class="empty"><div class="empty-icon"><?= svgIcon('tag', 32) ?></div><div class="empty-title">لا توجد شرائح</div></div></td></tr>
          <?php else: foreach ($tiers as $t): ?>
            <tr>
              <td style="font-weight:600"><?= e($t['name']) ?></td>
              <td><span class="badge badge-purple">-<?= $t['discount_percent'] ?>%</span></td>
              <td class="td-dim"><?= $t['min_purchase_auto'] ? money($t['min_purchase_auto']) : 'يدوي فقط' ?></td>
              <td><?= $t['accounts_count'] ?></td>
              <td><?= $t['is_default'] ? '⭐' : '—' ?></td>
              <td>
                <div style="display:flex;gap:5px;justify-content:flex-end">
                  <button class="btn btn-secondary btn-sm" onclick='editTier(<?= htmlspecialchars(json_encode($t),ENT_QUOTES) ?>)'>تعديل</button>
                  <button class="btn btn-danger btn-sm" onclick="confirmDelete('<?= adminUrl('business/tiers/'.$t['id'].'/delete') ?>','حذف الشريحة؟','')">حذف</button>
                </div>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="card">
    <div class="card-header"><span class="card-title">+ إضافة شريحة</span></div>
    <div class="card-body">
      <form method="POST" action="<?= adminUrl('business/tiers/create') ?>">
        <?= csrf_field() ?>
        <div class="form-group" style="margin-bottom:12px"><label class="form-label">الاسم <span class="req">*</span></label><input type="text" name="name" class="form-input" required></div>
        <div class="form-group" style="margin-bottom:12px"><label class="form-label">نسبة الخصم % <span class="req">*</span></label><input type="number" name="discount_percent" class="form-input" step="0.1" min="0" max="100" required></div>
        <div class="form-group" style="margin-bottom:12px"><label class="form-label">ترقية تلقائية عند إجمالي مشتريات</label><input type="number" name="min_purchase_auto" class="form-input" step="0.01" min="0" placeholder="اتركه فارغ = يدوي فقط"></div>
        <div class="form-group" style="margin-bottom:12px"><label class="form-label">الترتيب</label><input type="number" name="sort_order" class="form-input" value="0"></div>
        <div class="toggle-wrap" style="margin-bottom:16px"><label class="toggle"><input type="checkbox" name="is_default" value="1"><span class="toggle-slider"></span></label><span style="font-size:13px">شريحة افتراضية للتسجيل الجديد</span></div>
        <button type="submit" class="btn btn-primary btn-full">+ إضافة</button>
      </form>
    </div>
  </div>
</div>

<div class="modal-overlay" id="editTierModal">
  <div class="modal">
    <div class="modal-head"><h3>✏️ تعديل الشريحة</h3><button class="btn btn-ghost btn-icon" onclick="document.getElementById('editTierModal').classList.remove('open')" style="font-size:18px">×</button></div>
    <form method="POST" id="editTierForm">
      <?= csrf_field() ?>
      <div class="form-group" style="margin-bottom:12px"><label class="form-label">الاسم</label><input type="text" name="name" id="etName" class="form-input" required></div>
      <div class="form-group" style="margin-bottom:12px"><label class="form-label">نسبة الخصم %</label><input type="number" name="discount_percent" id="etDiscount" class="form-input" step="0.1"></div>
      <div class="form-group" style="margin-bottom:12px"><label class="form-label">ترقية تلقائية عند</label><input type="number" name="min_purchase_auto" id="etMinPurchase" class="form-input" step="0.01"></div>
      <div class="form-group" style="margin-bottom:12px"><label class="form-label">الترتيب</label><input type="number" name="sort_order" id="etSort" class="form-input"></div>
      <div class="toggle-wrap" style="margin-bottom:16px"><label class="toggle"><input type="checkbox" name="is_default" id="etDefault" value="1"><span class="toggle-slider"></span></label><span style="font-size:13px">افتراضية</span></div>
      <div class="modal-actions"><button type="button" class="btn btn-secondary" onclick="document.getElementById('editTierModal').classList.remove('open')">إلغاء</button><button type="submit" class="btn btn-primary"><?= svgIcon('check', 15) ?> حفظ</button></div>
    </form>
  </div>
</div>

<?php
$extraScript = "
function editTier(t){
  document.getElementById('etName').value = t.name;
  document.getElementById('etDiscount').value = t.discount_percent;
  document.getElementById('etMinPurchase').value = t.min_purchase_auto || '';
  document.getElementById('etSort').value = t.sort_order;
  document.getElementById('etDefault').checked = t.is_default == 1;
  document.getElementById('editTierForm').action = '/$a/business/tiers/'+t.id;
  document.getElementById('editTierModal').classList.add('open');
}
";
$content = ob_get_clean();
require APP_PATH . '/Views/admin/layouts/app.php';
