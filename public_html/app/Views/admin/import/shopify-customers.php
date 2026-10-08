<?php
$pageTitle  = 'استيراد عملاء من Shopify';
$breadcrumb = [['label'=>'العملاء','url'=>adminUrl('customers')],['label'=>'استيراد من Shopify']];
$a = ADMIN_PREFIX;
ob_start();
?>
<div class="ph">
  <div class="ph-left"><h1><span style="display:inline-flex;align-items:center;gap:9px"><?= svgIcon('user', 22) ?> استيراد عملاء من Shopify</span></h1><p>ارفع ملف تصدير العملاء (Customers CSV)</p></div>
  <div class="ph-right"><a href="<?= adminUrl('customers') ?>" class="btn btn-secondary">← العملاء</a></div>
</div>
<div style="display:grid;grid-template-columns:1fr 340px;gap:16px;align-items:start">
  <div class="card">
    <div class="card-header"><span class="card-title">رفع الملف</span></div>
    <div class="card-body">
      <form method="POST" action="<?= adminUrl('import/shopify-customers/preview') ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="form-group" style="margin-bottom:20px">
          <label class="form-label">ملف customers_export.csv <span class="req">*</span></label>
          <input type="file" name="csv" class="form-input" accept=".csv" required style="padding:8px">
          <span class="form-hint">Shopify Admin → Customers → Export</span>
        </div>
        <button type="submit" class="btn btn-primary btn-full"><?= svgIcon('search', 15) ?> معاينة العملاء</button>
      </form>
    </div>
  </div>
  <div class="card">
    <div class="card-header"><span class="card-title">ℹ️ ملاحظات</span></div>
    <div class="card-body">
      <p style="font-size:12px;color:var(--t2);line-height:1.9">
        هيتم استيراد: الاسم، البريد، الهاتف. لو الإيميل موجود مسبقاً هيتم تحديث بياناته فقط.
        العملاء الجدد هيتنشأوا بكلمة سر عشوائية — يقدروا يستخدموا "نسيت كلمة المرور".
      </p>
    </div>
  </div>
</div>
<?php $content = ob_get_clean(); require APP_PATH . '/Views/admin/layouts/app.php';
