<?php
$pageTitle  = 'استيراد من Shopify';
$breadcrumb = [['label'=>'المنتجات','url'=>adminUrl('products')],['label'=>'استيراد من Shopify']];
$a = ADMIN_PREFIX;
ob_start();
?>
<div class="ph">
  <div class="ph-left"><h1><span style="display:inline-flex;align-items:center;gap:9px"><?= svgIcon('store', 22) ?> استيراد منتجات من Shopify</span></h1><p>ارفع ملف تصدير المنتجات (Products CSV) من متجرك على Shopify</p></div>
  <div class="ph-right"><a href="<?= adminUrl('products') ?>" class="btn btn-secondary">← المنتجات</a></div>
</div>

<div style="display:grid;grid-template-columns:1fr 340px;gap:16px;align-items:start">

  <div class="card">
    <div class="card-header"><span class="card-title">رفع الملف</span></div>
    <div class="card-body">
      <form method="POST" action="<?= adminUrl('import/shopify/preview') ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="form-group" style="margin-bottom:20px">
          <label class="form-label">ملف products_export.csv <span class="req">*</span></label>
          <input type="file" name="csv" class="form-input" accept=".csv" required style="padding:8px">
          <span class="form-hint">Shopify Admin → Products → Export → Export as CSV</span>
        </div>
        <button type="submit" class="btn btn-primary btn-full"><?= svgIcon('search', 15) ?> معاينة المنتجات</button>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><span class="card-title">ℹ️ ملاحظات</span></div>
    <div class="card-body">
      <ul style="font-size:12px;color:var(--t2);line-height:2;padding-right:18px">
        <li>هيتم استيراد: الاسم، الوصف، SKU، السعر، سعر المقارنة، المخزون، الصور، الحالة (نشط/مسودة)</li>
        <li>الـ Vendor في Shopify → هيتحول لماركة</li>
        <li>الـ Type/Category → هيتحول لتصنيف</li>
        <li>لو المنتج له متغيرات (ألوان/مقاسات)، هيتم استيراد بيانات المتغير الأول بس حالياً</li>
        <li>الصور بيتم تحميلها من روابط Shopify وحفظها على السيرفر — ده ممكن ياخد وقت حسب عدد الصور</li>
        <li>لو المنتج موجود بالفعل (نفس Handle أو SKU) هيتم <strong>تحديثه</strong> مش تكراره</li>
      </ul>
    </div>
  </div>

</div>
<?php $content = ob_get_clean(); require APP_PATH . '/Views/admin/layouts/app.php';
