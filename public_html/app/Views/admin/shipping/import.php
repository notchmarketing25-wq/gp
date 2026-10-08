<?php
$pageTitle  = 'استيراد شحنات من Shopify';
$breadcrumb = [['label'=>'الشحنات','url'=>adminUrl('shipping')],['label'=>'استيراد']];
$a = ADMIN_PREFIX;
ob_start();
?>
<div class="ph">
  <div class="ph-left"><h1><span style="display:inline-flex;align-items:center;gap:9px"><?= svgIcon('store', 22) ?> استيراد شحنات من Shopify</span></h1><p>ارفع نفس ملف تصدير الطلبات — هناخد بيانات الشحن والتتبع بس</p></div>
  <div class="ph-right"><a href="<?= adminUrl('shipping') ?>" class="btn btn-secondary">← الشحنات</a></div>
</div>

<div style="display:grid;grid-template-columns:1fr 340px;gap:16px;align-items:start">
  <div class="card">
    <div class="card-header"><span class="card-title">رفع الملف</span></div>
    <div class="card-body">
      <form method="POST" action="<?= adminUrl('shipping/import/preview') ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="form-group" style="margin-bottom:16px">
          <label class="form-label">شركة الشحن لكل الشحنات المستوردة</label>
          <select name="carrier_id" class="form-select">
            <?php foreach($carriers as $c): ?><option value="<?=$c['id']?>"><?= e($c['name']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="form-group" style="margin-bottom:20px">
          <label class="form-label">ملف orders_export.csv <span class="req">*</span></label>
          <input type="file" name="csv" class="form-input" accept=".csv" required style="padding:8px">
        </div>
        <button type="submit" class="btn btn-primary btn-full"><?= svgIcon('search', 15) ?> معاينة الشحنات</button>
      </form>
    </div>
  </div>
  <div class="card">
    <div class="card-header"><span class="card-title">ℹ️ ملاحظات</span></div>
    <div class="card-body">
      <ul style="font-size:12px;color:var(--t2);line-height:2;padding-right:18px">
        <li>هيتم استخراج: العنوان، رقم التتبع، حالة الشحن، وربطها بنفس عميل الطلب (أو إنشاء عميل جديد لو مش موجود)</li>
        <li>لو رقم التتبع موجود بالفعل عندنا، هيتم تخطي الصف تلقائياً</li>
        <li>حالة الدفع بتتحدد كـ "محصّل" لو الطلب كان مدفوع في Shopify</li>
      </ul>
    </div>
  </div>
</div>
<?php $content = ob_get_clean(); require APP_PATH . '/Views/admin/layouts/app.php';
