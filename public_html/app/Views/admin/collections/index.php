<?php
$pageTitle  = 'التصنيفات';
$breadcrumb = [['label' => 'التصنيفات']];
$a = ADMIN_PREFIX;
ob_start();
?>
<div class="ph">
  <div class="ph-left"><h1>التصنيفات</h1></div>
  <div class="ph-right"><button class="btn btn-secondary" onclick="document.getElementById('mergeModal').classList.add('open')"><?= svgIcon('package', 15) ?> دمج تصنيف مكرر</button></div>
</div>
<div style="display:grid;grid-template-columns:1fr 320px;gap:16px;align-items:start">
  <div class="card">
    <div class="table-wrap"><table>
      <thead><tr><th>التصنيف</th><th>Slug</th><th>المنتجات</th><th>الحالة</th><th></th></tr></thead>
      <tbody>
        <?php if (empty($collections)): ?>
          <tr><td colspan="5"><div class="empty"><div class="empty-icon"><?= svgIcon('grid', 32) ?></div><div class="empty-title">لا توجد تصنيفات</div></div></td></tr>
        <?php else: foreach ($collections as $c): ?>
          <tr>
            <td>
              <div style="display:flex;align-items:center;gap:9px">
                <?php if ($c['image']??null): ?><img src="<?= uploadUrl($c['image']) ?>" style="width:32px;height:32px;object-fit:cover;border-radius:6px;border:1px solid var(--bd)"><?php endif; ?>
                <span style="font-weight:500"><?= e($c['name']) ?></span>
              </div>
            </td>
            <td class="td-mono td-dim"><?= e($c['slug']) ?></td>
            <td><span class="badge badge-purple"><?= $c['products_count'] ?? 0 ?> منتج</span></td>
            <td><span class="badge <?= $c['is_active'] ? 'badge-ok' : 'badge-dim' ?>"><?= $c['is_active'] ? 'نشط' : 'مخفي' ?></span></td>
            <td style="text-align:left">
              <div style="display:flex;gap:5px;justify-content:flex-end">
                <a href="<?= adminUrl('collections/'.$c['id'].'/edit') ?>" class="btn btn-secondary btn-sm">تعديل</a>
                <button class="btn btn-danger btn-sm" onclick="confirmDelete('/<?= $a ?>/collections/<?= $c['id'] ?>/delete','حذف التصنيف؟','')">حذف</button>
              </div>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table></div>
  </div>
  <div class="card">
    <div class="card-header"><span class="card-title">إضافة تصنيف</span></div>
    <div class="card-body">
      <form method="POST" action="/<?= $a ?>/collections/create" enctype="multipart/form-data">
        <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
        <div class="form-group" style="margin-bottom:12px"><label class="form-label">الاسم <span>*</span></label><input type="text" name="name" class="form-input" required></div>
        <div class="form-group" style="margin-bottom:12px"><label class="form-label">الصورة</label><input type="file" name="image" class="form-input" accept="image/*" style="padding:6px"></div>
        <div class="form-group" style="margin-bottom:12px"><label class="form-label">الترتيب</label><input type="number" name="sort_order" class="form-input" value="0"></div>
        <div class="toggle-wrap" style="margin-bottom:14px"><label class="toggle"><input type="checkbox" name="is_active" value="1" checked><span class="toggle-slider"></span></label><span style="font-size:13px">نشط</span></div>
        <button type="submit" class="btn btn-primary btn-full">+ إضافة</button>
      </form>
    </div>
  </div>
</div>

<!-- Merge duplicate collections -->
<div class="modal-overlay" id="mergeModal">
  <div class="modal">
    <div class="modal-head"><h3>🔗 دمج تصنيف مكرر</h3><button class="btn btn-ghost btn-icon" onclick="document.getElementById('mergeModal').classList.remove('open')" style="font-size:18px">×</button></div>
    <p style="margin-bottom:16px">كل منتجات "التصنيف المكرر" هتتنقل تلقائياً للتصنيف الرئيسي، وبعدها هيتم حذف المكرر.</p>
    <form method="POST" action="<?= adminUrl('collections/merge') ?>">
      <?= csrf_field() ?>
      <div class="form-group" style="margin-bottom:12px">
        <label class="form-label">التصنيف المكرر (هيتحذف)</label>
        <select name="from_id" class="form-select" required>
          <option value="">اختر...</option>
          <?php foreach ($collections as $c): ?><option value="<?=$c['id']?>"><?= e($c['name']) ?> (<?= $c['products_count']??0 ?> منتج)</option><?php endforeach; ?>
        </select>
      </div>
      <div class="form-group" style="margin-bottom:18px">
        <label class="form-label">دمجه في (التصنيف الرئيسي)</label>
        <select name="to_id" class="form-select" required>
          <option value="">اختر...</option>
          <?php foreach ($collections as $c): ?><option value="<?=$c['id']?>"><?= e($c['name']) ?> (<?= $c['products_count']??0 ?> منتج)</option><?php endforeach; ?>
        </select>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('mergeModal').classList.remove('open')">إلغاء</button>
        <button type="submit" class="btn btn-primary" onclick="return confirm('تأكيد الدمج؟ الإجراء ده نهائي.')"><?= svgIcon('package', 15) ?> دمج</button>
      </div>
    </form>
  </div>
</div>
<?php $content = ob_get_clean(); require APP_PATH . '/Views/admin/layouts/app.php';
