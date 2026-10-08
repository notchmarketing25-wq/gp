<?php
$pageTitle  = 'الماركات';
$breadcrumb = [['label' => 'الماركات']];
$a = ADMIN_PREFIX;
ob_start();
?>
<div class="ph">
  <div class="ph-left"><h1>الماركات</h1></div>
  <div class="ph-right"><button class="btn btn-secondary" onclick="document.getElementById('mergeBrandModal').classList.add('open')"><?= svgIcon('package', 15) ?> دمج ماركة مكررة</button></div>
</div>
<div style="display:grid;grid-template-columns:1fr 300px;gap:16px;align-items:start">
  <div class="card">
    <div class="table-wrap"><table>
      <thead><tr><th>الماركة</th><th>الحالة</th><th>الترتيب</th><th></th></tr></thead>
      <tbody>
        <?php if (empty($brands)): ?>
          <tr><td colspan="4"><div class="empty"><div class="empty-icon"><?= svgIcon('tag', 32) ?></div><div class="empty-title">لا توجد ماركات</div></div></td></tr>
        <?php else: foreach ($brands as $b): ?>
          <tr>
            <td>
              <div style="display:flex;align-items:center;gap:9px">
                <?php if ($b['logo']??null): ?><img src="<?= uploadUrl($b['logo']) ?>" style="height:24px;width:auto;max-width:60px;object-fit:contain"><?php endif; ?>
                <span style="font-weight:500"><?= e($b['name']) ?></span>
              </div>
            </td>
            <td><span class="badge <?= $b['is_active'] ? 'badge-ok' : 'badge-dim' ?>"><?= $b['is_active'] ? 'نشط' : 'مخفي' ?></span></td>
            <td class="td-dim"><?= $b['sort_order'] ?></td>
            <td style="text-align:left">
              <div style="display:flex;gap:5px;justify-content:flex-end">
                <button class="btn btn-secondary btn-sm" onclick='editBrand(<?= htmlspecialchars(json_encode($b),ENT_QUOTES) ?>)'>تعديل</button>
                <button class="btn btn-danger btn-sm" onclick="confirmDelete('/<?= $a ?>/brands/<?= $b['id'] ?>/delete','حذف الماركة؟','')">حذف</button>
              </div>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table></div>
  </div>
  <div class="card">
    <div class="card-header"><span class="card-title">إضافة ماركة</span></div>
    <div class="card-body">
      <form method="POST" action="/<?= $a ?>/brands/create" enctype="multipart/form-data">
        <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
        <div class="form-group" style="margin-bottom:12px"><label class="form-label">الاسم <span>*</span></label><input type="text" name="name" class="form-input" required></div>
        <div class="form-group" style="margin-bottom:12px"><label class="form-label">الشعار</label><input type="file" name="logo" class="form-input" accept="image/*" style="padding:6px"></div>
        <div class="form-group" style="margin-bottom:12px"><label class="form-label">الترتيب</label><input type="number" name="sort_order" class="form-input" value="0"></div>
        <div class="toggle-wrap" style="margin-bottom:14px"><label class="toggle"><input type="checkbox" name="is_active" value="1" checked><span class="toggle-slider"></span></label><span style="font-size:13px">نشط</span></div>
        <button type="submit" class="btn btn-primary btn-full">+ إضافة</button>
      </form>
    </div>
  </div>
</div>

<!-- Edit brand modal -->
<div class="modal-overlay" id="editBrandModal">
  <div class="modal">
    <div class="modal-head">
      <h3>✏️ تعديل الماركة</h3>
      <button class="btn btn-ghost btn-icon" onclick="document.getElementById('editBrandModal').classList.remove('open')" style="font-size:18px">×</button>
    </div>
    <form method="POST" id="editBrandForm" enctype="multipart/form-data">
      <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
      <div class="form-group" style="margin-bottom:12px"><label class="form-label">الاسم</label><input type="text" name="name" id="ebName" class="form-input" required></div>
      <div class="form-group" style="margin-bottom:12px">
        <label class="form-label">الشعار</label>
        <div id="ebLogoPreview" style="margin-bottom:8px"></div>
        <input type="file" name="logo" class="form-input" accept="image/*" style="padding:6px">
      </div>
      <div class="form-group" style="margin-bottom:12px"><label class="form-label">الترتيب</label><input type="number" name="sort_order" id="ebSort" class="form-input"></div>
      <div class="toggle-wrap" style="margin-bottom:16px"><label class="toggle"><input type="checkbox" name="is_active" id="ebActive" value="1"><span class="toggle-slider"></span></label><span style="font-size:13px">نشط</span></div>
      <div class="modal-actions">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('editBrandModal').classList.remove('open')">إلغاء</button>
        <button type="submit" class="btn btn-primary"><?= svgIcon('check', 15) ?> حفظ</button>
      </div>
    </form>
  </div>
</div>

<!-- Merge duplicate brands -->
<div class="modal-overlay" id="mergeBrandModal">
  <div class="modal">
    <div class="modal-head"><h3>🔗 دمج ماركة مكررة</h3><button class="btn btn-ghost btn-icon" onclick="document.getElementById('mergeBrandModal').classList.remove('open')" style="font-size:18px">×</button></div>
    <p style="margin-bottom:16px">كل منتجات "الماركة المكررة" هتتنقل تلقائياً للماركة الرئيسية، وبعدها هيتم حذف المكررة.</p>
    <form method="POST" action="<?= adminUrl('brands/merge') ?>">
      <?= csrf_field() ?>
      <div class="form-group" style="margin-bottom:12px">
        <label class="form-label">الماركة المكررة (هتتحذف)</label>
        <select name="from_id" class="form-select" required>
          <option value="">اختر...</option>
          <?php foreach ($brands as $b): ?><option value="<?=$b['id']?>"><?= e($b['name']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="form-group" style="margin-bottom:18px">
        <label class="form-label">دمجها في (الماركة الرئيسية)</label>
        <select name="to_id" class="form-select" required>
          <option value="">اختر...</option>
          <?php foreach ($brands as $b): ?><option value="<?=$b['id']?>"><?= e($b['name']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('mergeBrandModal').classList.remove('open')">إلغاء</button>
        <button type="submit" class="btn btn-primary" onclick="return confirm('تأكيد الدمج؟ الإجراء ده نهائي.')"><?= svgIcon('package', 15) ?> دمج</button>
      </div>
    </form>
  </div>
</div>

<?php
$extraScript = "
function editBrand(b){
  document.getElementById('ebName').value=b.name;
  document.getElementById('ebSort').value=b.sort_order;
  document.getElementById('ebActive').checked=b.is_active==1;
  document.getElementById('ebLogoPreview').innerHTML = b.logo ? '<img src=\\'".addslashes(APP_URL)."/uploads/'+b.logo+'\\' style=\\'height:36px;border-radius:6px\\'>' : '';
  document.getElementById('editBrandForm').action='/$a/brands/'+b.id+'/edit';
  document.getElementById('editBrandModal').classList.add('open');
}
";
$content = ob_get_clean();
require APP_PATH . '/Views/admin/layouts/app.php';
