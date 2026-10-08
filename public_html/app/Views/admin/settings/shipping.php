<?php
$pageTitle  = 'إعدادات الشحن';
$breadcrumb = [['label'=>'الإعدادات','url'=>adminUrl('settings')],['label'=>'الشحن']];
$a = ADMIN_PREFIX;

$egypt_govs = ['القاهرة','الجيزة','القليوبية','الإسكندرية','البحيرة','كفر الشيخ','الغربية','المنوفية','الدقهلية','الشرقية','دمياط','بورسعيد','الإسماعيلية','السويس','الفيوم','بني سويف','المنيا','أسيوط','سوهاج','قنا','الأقصر','أسوان','البحر الأحمر','مطروح','شمال سيناء','جنوب سيناء','الوادي الجديد'];
ob_start();
?>
<div class="ph">
  <div class="ph-left"><h1><span style="display:inline-flex;align-items:center;gap:9px"><?= svgIcon('truck', 22) ?> مناطق الشحن</span></h1><p><?= count($zones) ?> منطقة</p></div>
  <div class="ph-right">
    <a href="<?= adminUrl('shipping') ?>" class="btn btn-primary">📦 إدارة الشحنات وشركات الشحن ←</a>
    <a href="<?= adminUrl('settings') ?>" class="btn btn-secondary">← الإعدادات</a>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 380px;gap:16px;align-items:start">

  <!-- Zones list -->
  <div class="card">
    <div class="table-wrap">
      <table>
        <thead><tr><th>المنطقة</th><th>المحافظات</th><th>سعر الشحن</th><th>مجاني فوق</th><th>الحالة</th><th></th></tr></thead>
        <tbody>
          <?php if (empty($zones)): ?>
            <tr><td colspan="6"><div class="empty"><div class="empty-icon"><?= svgIcon('truck', 32) ?></div><div class="empty-title">لا توجد مناطق شحن</div><div class="empty-desc">أضف أول منطقة شحن</div></div></td></tr>
          <?php else: foreach ($zones as $z):
            $gs = json_decode($z['governorates']??'[]', true) ?: [];
          ?>
            <tr>
              <td><div style="font-weight:600"><?= e($z['name']) ?></div></td>
              <td style="font-size:11px;color:var(--t2);max-width:180px">
                <?= implode('، ', array_slice($gs,0,4)) ?>
                <?php if(count($gs)>4): ?><span style="color:var(--t3)"> +<?= count($gs)-4 ?></span><?php endif; ?>
                <?php if(empty($gs)): ?><span style="color:var(--t3)">كل المحافظات</span><?php endif; ?>
              </td>
              <td style="font-weight:700"><?= money($z['price']) ?></td>
              <td><?= $z['free_above']>0 ? money($z['free_above']) : '<span style="color:var(--t3)">—</span>' ?></td>
              <td><span class="badge <?= $z['is_active']?'badge-ok':'badge-dim' ?>"><?= $z['is_active']?'نشط':'موقوف' ?></span></td>
              <td>
                <div style="display:flex;gap:5px">
                  <button class="btn btn-secondary btn-sm" onclick="editZone(<?= htmlspecialchars(json_encode($z),ENT_QUOTES) ?>)">تعديل</button>
                  <button class="btn btn-danger btn-sm" onclick="confirmDelete('<?= adminUrl('settings/shipping/'.$z['id'].'/delete') ?>','حذف منطقة الشحن؟','')">حذف</button>
                </div>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Add zone -->
  <div class="card">
    <div class="card-header"><span class="card-title">+ إضافة منطقة شحن</span></div>
    <div class="card-body">
      <form method="POST" action="<?= adminUrl('settings/shipping') ?>" id="addZoneForm">
        <?= csrf_field() ?>
        <div class="form-group" style="margin-bottom:12px">
          <label class="form-label">اسم المنطقة <span style="color:var(--red)">*</span></label>
          <input type="text" name="name" class="form-input" required placeholder="مثال: القاهرة الكبرى">
        </div>
        <div class="form-group" style="margin-bottom:12px">
          <label class="form-label">المحافظات <span style="font-size:10px;color:var(--t3)">(اختياري — فارغ = كل المحافظات)</span></label>
          <div style="background:var(--bg3);border:1px solid var(--bd);border-radius:var(--radius-xs);padding:10px;max-height:180px;overflow-y:auto">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:4px">
              <?php foreach ($egypt_govs as $g): ?>
                <label style="display:flex;align-items:center;gap:5px;font-size:12px;padding:2px 4px;cursor:pointer">
                  <input type="checkbox" name="governorates[]" value="<?= e($g) ?>" style="accent-color:var(--ac)">
                  <?= e($g) ?>
                </label>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
        <div class="form-grid form-row-2" style="margin-bottom:12px">
          <div class="form-group">
            <label class="form-label">سعر الشحن (ج.م) <span style="color:var(--red)">*</span></label>
            <input type="number" name="price" class="form-input" step="0.01" min="0" required placeholder="0">
          </div>
          <div class="form-group">
            <label class="form-label">شحن مجاني فوق (ج.م)</label>
            <input type="number" name="free_above" class="form-input" step="0.01" min="0" placeholder="0 = غير مفعل">
          </div>
        </div>
        <div class="toggle-wrap" style="margin-bottom:14px">
          <label class="toggle"><input type="checkbox" name="is_active" value="1" checked><span class="toggle-slider"></span></label>
          <span style="font-size:13px">منطقة نشطة</span>
        </div>
        <button type="submit" class="btn btn-primary btn-full">+ إضافة المنطقة</button>
      </form>
    </div>
  </div>
</div>

<!-- Edit zone modal -->
<div class="modal-overlay" id="editZoneModal">
  <div class="modal" style="max-width:520px">
    <div class="modal-head">
      <h3>✏️ تعديل منطقة الشحن</h3>
      <button class="btn btn-ghost btn-icon" onclick="this.closest('.modal-overlay').classList.remove('open')" style="font-size:18px">×</button>
    </div>
    <form method="POST" id="editZoneForm">
      <?= csrf_field() ?>
      <input type="hidden" name="_method" value="PUT">
      <div class="form-group" style="margin-bottom:12px">
        <label class="form-label">اسم المنطقة</label>
        <input type="text" name="name" id="editName" class="form-input" required>
      </div>
      <div class="form-group" style="margin-bottom:12px">
        <label class="form-label">المحافظات</label>
        <div style="background:var(--bg3);border:1px solid var(--bd);border-radius:var(--radius-xs);padding:10px;max-height:160px;overflow-y:auto">
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:4px" id="editGovs">
            <?php foreach ($egypt_govs as $g): ?>
              <label style="display:flex;align-items:center;gap:5px;font-size:12px;padding:2px 4px;cursor:pointer">
                <input type="checkbox" name="governorates[]" value="<?= e($g) ?>" class="edit-gov-cb" style="accent-color:var(--ac)">
                <?= e($g) ?>
              </label>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      <div class="form-grid form-row-2" style="margin-bottom:12px">
        <div class="form-group"><label class="form-label">سعر الشحن</label><input type="number" name="price" id="editPrice" class="form-input" step="0.01" min="0"></div>
        <div class="form-group"><label class="form-label">شحن مجاني فوق</label><input type="number" name="free_above" id="editFreeAbove" class="form-input" step="0.01" min="0"></div>
      </div>
      <div class="toggle-wrap" style="margin-bottom:16px">
        <label class="toggle"><input type="checkbox" name="is_active" id="editActive" value="1"><span class="toggle-slider"></span></label>
        <span style="font-size:13px">منطقة نشطة</span>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn btn-secondary" onclick="this.closest('.modal-overlay').classList.remove('open')">إلغاء</button>
        <button type="submit" class="btn btn-primary"><?= svgIcon('check', 15) ?> حفظ التعديلات</button>
      </div>
    </form>
  </div>
</div>

<?php
$extraScript = "
function editZone(z){
  const govs = JSON.parse(z.governorates||'[]');
  document.getElementById('editName').value=z.name;
  document.getElementById('editPrice').value=z.price;
  document.getElementById('editFreeAbove').value=z.free_above||'';
  document.getElementById('editActive').checked=z.is_active==1;
  document.querySelectorAll('.edit-gov-cb').forEach(cb=>{
    cb.checked=govs.includes(cb.value);
  });
  document.getElementById('editZoneForm').action='/$a/settings/shipping/'+z.id;
  document.getElementById('editZoneModal').classList.add('open');
}
";
$content = ob_get_clean();
require APP_PATH . '/Views/admin/layouts/app.php';
