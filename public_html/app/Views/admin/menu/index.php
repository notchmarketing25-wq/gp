<?php
$pageTitle  = 'إدارة المنيو';
$breadcrumb = [['label'=>'إدارة المنيو']];
$a = ADMIN_PREFIX;

function _menuItemRow($item, $a, $isChild = false) { ?>
  <tr style="<?= $isChild ? 'background:var(--bg3)' : '' ?>">
    <td style="padding-right:<?= $isChild ? '36px' : '16px' ?>">
      <?= $isChild ? '↳ ' : '' ?><?= e($item['icon'] ?? '') ?> <span style="font-weight:600"><?= e($item['label']) ?></span>
    </td>
    <td class="td-mono td-dim"><?= e($item['url']) ?></td>
    <td><span class="badge <?= $item['is_active']?'badge-ok':'badge-dim' ?>"><?= $item['is_active']?'ظاهر':'مخفي' ?></span></td>
    <td>
      <div style="display:flex;gap:4px;justify-content:flex-end">
        <form method="POST" action="<?= adminUrl('menu/'.$item['id'].'/reorder') ?>" style="display:inline"><?= csrf_field() ?><input type="hidden" name="direction" value="up"><button class="btn btn-secondary btn-sm" title="لأعلى">↑</button></form>
        <form method="POST" action="<?= adminUrl('menu/'.$item['id'].'/reorder') ?>" style="display:inline"><?= csrf_field() ?><input type="hidden" name="direction" value="down"><button class="btn btn-secondary btn-sm" title="لأسفل">↓</button></form>
        <button class="btn btn-secondary btn-sm" onclick='editMenuItem(<?= htmlspecialchars(json_encode($item),ENT_QUOTES) ?>)'>تعديل</button>
        <button class="btn btn-danger btn-sm" onclick="confirmDelete('<?= adminUrl('menu/'.$item['id'].'/delete') ?>','حذف العنصر؟ (وأي عناصر فرعية تحته)','')">حذف</button>
      </div>
    </td>
  </tr>
<?php }
ob_start();
?>
<div class="ph">
  <div class="ph-left"><h1><span style="display:inline-flex;align-items:center;gap:9px"><?= svgIcon('menu', 22) ?> إدارة المنيو</span></h1><p>تحكم كامل في روابط القائمة الرئيسية والفرعية</p></div>
  <div class="ph-right"><button class="btn btn-primary" onclick="openAddMenu()">+ إضافة عنصر</button></div>
</div>

<div class="card" style="margin-bottom:20px">
  <div class="card-header"><span class="card-title">📋 القائمة الرئيسية (Main Nav)</span></div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>العنصر</th><th>الرابط</th><th>الحالة</th><th></th></tr></thead>
      <tbody>
        <?php if (empty($tree['main'])): ?>
          <tr><td colspan="4"><div class="empty"><div class="empty-icon"><?= svgIcon('menu', 32) ?></div><div class="empty-title">لا توجد عناصر</div></div></td></tr>
        <?php else: foreach ($tree['main'] as $item): ?>
          <?php _menuItemRow($item, $a); ?>
          <?php foreach ($item['children'] as $child): _menuItemRow($child, $a, true); endforeach; ?>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="card">
  <div class="card-header"><span class="card-title">📋 الفوتر (Footer)</span></div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>العنصر</th><th>الرابط</th><th>الحالة</th><th></th></tr></thead>
      <tbody>
        <?php if (empty($tree['footer'])): ?>
          <tr><td colspan="4"><div class="empty"><div class="empty-icon"><?= svgIcon('menu', 32) ?></div><div class="empty-title">لا توجد عناصر</div></div></td></tr>
        <?php else: foreach ($tree['footer'] as $item): ?>
          <?php _menuItemRow($item, $a); ?>
          <?php foreach ($item['children'] as $child): _menuItemRow($child, $a, true); endforeach; ?>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add/Edit modal -->
<div class="modal-overlay" id="menuModal">
  <div class="modal">
    <div class="modal-head"><h3 id="mmTitle">+ إضافة عنصر</h3><button class="btn btn-ghost btn-icon" onclick="document.getElementById('menuModal').classList.remove('open')" style="font-size:18px">×</button></div>
    <form method="POST" id="menuForm">
      <?= csrf_field() ?>
      <div class="form-group" style="margin-bottom:12px"><label class="form-label">النص الظاهر <span class="req">*</span></label><input type="text" name="label" id="mmLabel" class="form-input" required></div>
      <div class="form-group" style="margin-bottom:12px"><label class="form-label">الرابط <span class="req">*</span></label><input type="text" name="url" id="mmUrl" class="form-input" required placeholder="/products أو رابط كامل"></div>
      <div class="form-group" style="margin-bottom:12px"><label class="form-label">أيقونة (اختياري — إيموجي)</label><input type="text" name="icon" id="mmIcon" class="form-input" placeholder="🛒"></div>
      <div class="form-group" style="margin-bottom:12px"><label class="form-label">المكان</label>
        <select name="location" id="mmLocation" class="form-select">
          <option value="main">القائمة الرئيسية</option>
          <option value="footer">الفوتر</option>
        </select>
      </div>
      <div class="form-group" style="margin-bottom:16px"><label class="form-label">عنصر فرعي تابع لـ (اختياري — لعمل قائمة منسدلة)</label>
        <select name="parent_id" id="mmParent" class="form-select">
          <option value="">— عنصر رئيسي —</option>
          <?php foreach ($topLevelItems as $t): ?><option value="<?=$t['id']?>"><?= e($t['label']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="toggle-wrap" style="margin-bottom:16px"><label class="toggle"><input type="checkbox" name="is_active" id="mmActive" value="1" checked><span class="toggle-slider"></span></label><span style="font-size:13px">ظاهر</span></div>
      <div class="modal-actions">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('menuModal').classList.remove('open')">إلغاء</button>
        <button type="submit" class="btn btn-primary"><?= svgIcon('check', 15) ?> حفظ</button>
      </div>
    </form>
  </div>
</div>

<?php
$extraScript = "
function openAddMenu(){
  document.getElementById('mmTitle').textContent='+ إضافة عنصر';
  document.getElementById('menuForm').reset();
  document.getElementById('menuForm').action='/$a/menu/create';
  document.getElementById('menuModal').classList.add('open');
}
function editMenuItem(item){
  document.getElementById('mmTitle').textContent='✏️ تعديل العنصر';
  document.getElementById('menuForm').action='/$a/menu/'+item.id;
  document.getElementById('mmLabel').value=item.label;
  document.getElementById('mmUrl').value=item.url;
  document.getElementById('mmIcon').value=item.icon||'';
  document.getElementById('mmLocation').value=item.location;
  document.getElementById('mmParent').value=item.parent_id||'';
  document.getElementById('mmActive').checked=item.is_active==1;
  document.getElementById('menuModal').classList.add('open');
}
";
$content = ob_get_clean();
require APP_PATH . '/Views/admin/layouts/app.php';
