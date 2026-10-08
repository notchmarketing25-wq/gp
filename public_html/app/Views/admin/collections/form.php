<?php
$isEdit     = isset($collection);
$pageTitle  = $isEdit ? 'تعديل التصنيف' : 'إضافة تصنيف';
$breadcrumb = [['label'=>'التصنيفات','url'=>adminUrl('collections')],['label'=>$pageTitle]];
$v          = $collection ?? [];
ob_start(); ?>

<div class="page-header">
  <div class="page-header-left"><h1><?= $pageTitle ?></h1></div>
  <div class="page-header-actions"><a href="<?= adminUrl('collections') ?>" class="btn btn-secondary">← رجوع</a></div>
</div>

<form method="POST" enctype="multipart/form-data"
  action="<?= $isEdit ? adminUrl('collections/'.$collection['id'].'/edit') : adminUrl('collections/create') ?>">
  <?= csrf_field() ?>
  <div style="display:grid;grid-template-columns:1fr 300px;gap:18px">
    <div class="card">
      <div class="card-header"><span class="card-title">معلومات التصنيف</span></div>
      <div class="card-body">
        <div class="form-grid" style="gap:14px">
          <div class="form-group"><label class="form-label">الاسم <span>*</span></label><input type="text" name="name" class="form-input" value="<?= e($v['name']??'') ?>" required id="colName" oninput="document.getElementById('colSlug').value=mkSlug(this.value)"></div>
          <div class="form-group"><label class="form-label">Slug</label><input type="text" name="slug" class="form-input" value="<?= e($v['slug']??'') ?>" id="colSlug"></div>
          <div class="form-group"><label class="form-label">الوصف</label><textarea name="description" class="form-textarea" rows="4"><?= e($v['description']??'') ?></textarea></div>
          <div class="form-group"><label class="form-label">Meta Title</label><input type="text" name="meta_title" class="form-input" value="<?= e($v['meta_title']??'') ?>"></div>
          <div class="form-group"><label class="form-label">Meta Description</label><textarea name="meta_desc" class="form-textarea" rows="2" style="min-height:60px"><?= e($v['meta_desc']??'') ?></textarea></div>
        </div>
      </div>
    </div>
    <div style="display:flex;flex-direction:column;gap:14px">
      <div class="card">
        <div class="card-header"><span class="card-title">النشر</span></div>
        <div class="card-body" style="display:flex;flex-direction:column;gap:12px">
          <div class="toggle-wrap"><label class="toggle"><input type="checkbox" name="is_active" value="1" <?= ($v['is_active']??1)?'checked':'' ?>><span class="toggle-slider"></span></label><span style="font-size:13px">نشط</span></div>
          <div class="form-group"><label class="form-label">الترتيب</label><input type="number" name="sort_order" class="form-input" value="<?= e($v['sort_order']??0) ?>"></div>
          <button type="submit" class="btn btn-primary" style="justify-content:center">💾 <?= $isEdit?'حفظ':'إضافة' ?></button>
        </div>
      </div>
      <div class="card">
        <div class="card-header"><span class="card-title">الصورة</span></div>
        <div class="card-body">
          <?php if (!empty($v['image'])): ?>
            <img src="<?= uploadUrl($v['image']) ?>" style="width:100%;height:120px;object-fit:cover;border-radius:7px;margin-bottom:10px">
          <?php endif; ?>
          <input type="file" name="image" class="form-input" accept="image/*" style="padding:6px">
        </div>
      </div>
    </div>
  </div>
</form>

<?php $content = ob_get_clean(); require APP_PATH . '/Views/admin/layouts/app.php';
