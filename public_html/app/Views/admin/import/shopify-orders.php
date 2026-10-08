<?php
$pageTitle  = 'استيراد طلبات من Shopify';
$breadcrumb = [['label'=>'الطلبات','url'=>adminUrl('orders')],['label'=>'استيراد من Shopify']];
$a = ADMIN_PREFIX;
ob_start();
?>
<div class="ph">
  <div class="ph-left"><h1><span style="display:inline-flex;align-items:center;gap:9px"><?= svgIcon('cart', 22) ?> استيراد طلبات من Shopify</span></h1><p>ارفع ملف تصدير الطلبات (Orders CSV)</p></div>
  <div class="ph-right"><a href="<?= adminUrl('orders') ?>" class="btn btn-secondary">← الطلبات</a></div>
</div>
<div style="display:grid;grid-template-columns:1fr 340px;gap:16px;align-items:start">
  <div class="card">
    <div class="card-header"><span class="card-title">رفع الملف</span></div>
    <div class="card-body">
      <form method="POST" action="<?= adminUrl('import/shopify-orders/preview') ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="form-group" style="margin-bottom:20px">
          <label class="form-label">ملف orders_export.csv <span class="req">*</span></label>
          <input type="file" name="csv" class="form-input" accept=".csv" required style="padding:8px">
          <span class="form-hint">Shopify Admin → Orders → Export</span>
        </div>
        <button type="submit" class="btn btn-primary btn-full"><?= svgIcon('search', 15) ?> معاينة الطلبات</button>
      </form>
    </div>
  </div>
  <div class="card">
    <div class="card-header"><span class="card-title">ℹ️ ملاحظات</span></div>
    <div class="card-body">
      <ul style="font-size:12px;color:var(--t2);line-height:2;padding-right:18px">
        <li>هيتم ربط المنتجات تلقائياً لو الـ SKU موجود عندك بالفعل</li>
        <li>لو الطلب مستورد قبل كده (بنفس الرقم) هيتم تخطيه تلقائياً</li>
        <li>حالة الدفع والشحن هيتم تحويلها لأقرب حالة عندنا</li>
        <li>الطلبات دي بتتسجل كـ "مستوردة" — مش هيتبعت لها إشعارات</li>
      </ul>
    </div>
  </div>
</div>
<?php $content = ob_get_clean(); require APP_PATH . '/Views/admin/layouts/app.php';
