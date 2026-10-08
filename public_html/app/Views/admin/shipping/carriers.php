<?php
$pageTitle  = 'شركات الشحن';
$breadcrumb = [['label'=>'الشحنات','url'=>adminUrl('shipping')],['label'=>'شركات الشحن']];
$a = ADMIN_PREFIX;
ob_start();
?>
<div class="ph">
  <div class="ph-left"><h1><span style="display:inline-flex;align-items:center;gap:9px"><?= svgIcon('building', 22) ?> شركات الشحن</span></h1><p>أضف شركات الشحن وبيانات الربط بـ API الخاصة بيهم</p></div>
  <div class="ph-right"><a href="<?= adminUrl('shipping') ?>" class="btn btn-secondary">← الشحنات</a></div>
</div>

<div style="display:grid;grid-template-columns:1fr 340px;gap:16px;align-items:start">
  <div class="card">
    <div class="table-wrap">
      <table>
        <thead><tr><th>الشركة</th><th>API</th><th>الحالة</th><th></th></tr></thead>
        <tbody>
          <?php if (empty($carriers)): ?>
            <tr><td colspan="4"><div class="empty"><div class="empty-icon"><?= svgIcon('building', 32) ?></div><div class="empty-title">لا توجد شركات شحن</div></div></td></tr>
          <?php else: foreach ($carriers as $c): ?>
            <tr>
              <td style="font-weight:600"><?= e($c['name']) ?></td>
              <td><span class="badge <?= $c['api_url']?'badge-ok':'badge-dim' ?>"><?= $c['api_url']?'✅ مربوط':'يدوي' ?></span></td>
              <td><span class="badge <?= $c['is_active']?'badge-ok':'badge-dim' ?>"><?= $c['is_active']?'نشط':'موقوف' ?></span></td>
              <td>
                <div style="display:flex;gap:5px;justify-content:flex-end">
                  <button class="btn btn-secondary btn-sm" onclick='editCarrier(<?= htmlspecialchars(json_encode($c),ENT_QUOTES) ?>)'>تعديل</button>
                  <button class="btn btn-danger btn-sm" onclick="confirmDelete('<?= adminUrl('shipping/carriers/'.$c['id'].'/delete') ?>','حذف شركة الشحن؟','')">حذف</button>
                </div>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><span class="card-title">+ إضافة شركة شحن</span></div>
    <div class="card-body">
      <form method="POST" action="<?= adminUrl('shipping/carriers/create') ?>">
        <?= csrf_field() ?>
        <div class="form-group" style="margin-bottom:12px"><label class="form-label">اسم الشركة <span class="req">*</span></label><input type="text" name="name" class="form-input" required placeholder="مثال: أرامكس، بوسطة، سبيد أف..."></div>
        <div class="form-group" style="margin-bottom:12px"><label class="form-label">رابط API (اختياري)</label><input type="text" name="api_url" class="form-input" placeholder="https://api.carrier.com/v1"></div>
        <div class="form-group" style="margin-bottom:12px"><label class="form-label">API Key</label><input type="text" name="api_key" class="form-input"></div>
        <div class="form-group" style="margin-bottom:12px"><label class="form-label">API Secret</label><input type="password" name="api_secret" class="form-input"></div>
        <div class="form-group" style="margin-bottom:12px"><label class="form-label">ملاحظات</label><textarea name="notes" class="form-textarea" rows="2"></textarea></div>
        <div class="toggle-wrap" style="margin-bottom:16px"><label class="toggle"><input type="checkbox" name="is_active" value="1" checked><span class="toggle-slider"></span></label><span style="font-size:13px">نشطة</span></div>
        <button type="submit" class="btn btn-primary btn-full">+ إضافة</button>
      </form>
    </div>
  </div>
</div>

<div class="card" style="margin-top:16px">
  <div class="card-body" style="font-size:12.5px;color:var(--t2);line-height:1.9">
    ℹ️ بيانات الـ API المدخلة هنا بتتحفظ عشان الجاهزية لربط شركة الشحن مستقبلاً — كل شركة شحن ليها API مختلف تماماً (نقاط نهاية وصيغة بيانات مختلفة)، فلازم تفعيل تكامل مخصص لكل شركة تختارها فعلياً. حالياً تقدر تدير الشحنات يدوياً (إضافة/تعديل/تتبع الحالة والتحصيل) بشكل كامل بدون أي API.
  </div>
</div>

<!-- Edit carrier modal -->
<div class="modal-overlay" id="editCarrierModal">
  <div class="modal">
    <div class="modal-head"><h3>✏️ تعديل شركة الشحن</h3><button class="btn btn-ghost btn-icon" onclick="document.getElementById('editCarrierModal').classList.remove('open')" style="font-size:18px">×</button></div>
    <form method="POST" id="editCarrierForm">
      <?= csrf_field() ?>
      <div class="form-group" style="margin-bottom:12px"><label class="form-label">اسم الشركة</label><input type="text" name="name" id="ecName" class="form-input" required></div>
      <div class="form-group" style="margin-bottom:12px"><label class="form-label">رابط API</label><input type="text" name="api_url" id="ecUrl" class="form-input"></div>
      <div class="form-group" style="margin-bottom:12px"><label class="form-label">API Key</label><input type="text" name="api_key" id="ecKey" class="form-input"></div>
      <div class="form-group" style="margin-bottom:12px"><label class="form-label">API Secret</label><input type="password" name="api_secret" id="ecSecret" class="form-input"></div>
      <div class="form-group" style="margin-bottom:12px"><label class="form-label">ملاحظات</label><textarea name="notes" id="ecNotes" class="form-textarea" rows="2"></textarea></div>
      <div class="toggle-wrap" style="margin-bottom:16px"><label class="toggle"><input type="checkbox" name="is_active" id="ecActive" value="1"><span class="toggle-slider"></span></label><span style="font-size:13px">نشطة</span></div>
      <div class="modal-actions">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('editCarrierModal').classList.remove('open')">إلغاء</button>
        <button type="submit" class="btn btn-primary"><?= svgIcon('check', 15) ?> حفظ</button>
      </div>
    </form>
  </div>
</div>

<?php
$extraScript = "
function editCarrier(c){
  document.getElementById('ecName').value = c.name;
  document.getElementById('ecUrl').value = c.api_url || '';
  document.getElementById('ecKey').value = c.api_key || '';
  document.getElementById('ecSecret').value = '';
  document.getElementById('ecNotes').value = c.notes || '';
  document.getElementById('ecActive').checked = c.is_active == 1;
  document.getElementById('editCarrierForm').action = '/$a/shipping/carriers/'+c.id;
  document.getElementById('editCarrierModal').classList.add('open');
}
";
$content = ob_get_clean();
require APP_PATH . '/Views/admin/layouts/app.php';
