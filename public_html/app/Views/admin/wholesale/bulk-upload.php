<?php
$pageTitle  = 'رفع طلبات جملة';
$breadcrumb = [['label'=>'الجملة','url'=>adminUrl('wholesale')],['label'=>'رفع طلبات CSV']];
$a = ADMIN_PREFIX;
ob_start();
?>
<div class="ph">
  <div class="ph-left"><h1><span style="display:inline-flex;align-items:center;gap:9px"><?= svgIcon('upload', 22) ?> رفع طلبات جملة بالجملة (CSV)</span></h1><p>ارفع ملف واحد فيه عدة طلبات لعدة عملاء دفعة واحدة</p></div>
  <div class="ph-right"><a href="<?= adminUrl('wholesale') ?>" class="btn btn-secondary">← حسابات الجملة</a></div>
</div>

<div style="display:grid;grid-template-columns:1fr 340px;gap:16px;align-items:start">

  <div class="card">
    <div class="card-header"><span class="card-title">رفع الملف</span></div>
    <div class="card-body">
      <form method="POST" action="<?= adminUrl('wholesale/bulk-upload/preview') ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="form-group" style="margin-bottom:20px">
          <label class="form-label">ملف CSV <span class="req">*</span></label>
          <input type="file" name="csv" class="form-input" accept=".csv,.txt" required style="padding:8px">
          <span class="form-hint">هيتم عرض معاينة كاملة للطلبات قبل الحفظ النهائي — مفيش حاجة بتتحفظ أوتوماتيك</span>
        </div>
        <button type="submit" class="btn btn-primary btn-full"><?= svgIcon('search', 15) ?> معاينة الملف</button>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><span class="card-title"><?= svgIcon('receipt', 15) ?> صيغة الملف</span></div>
    <div class="card-body">
      <div style="background:var(--bg3);border:1px solid var(--bd);border-radius:7px;padding:12px;font-family:monospace;font-size:11px;color:var(--t2);line-height:1.9;overflow-x:auto">
        customer_email,customer_name,customer_phone,company_name,sku,qty<br>
        ahmed@shop.com,أحمد,01012345678,محل أحمد,NT-001,50<br>
        ahmed@shop.com,أحمد,01012345678,محل أحمد,NT-002,20<br>
        sara@store.com,سارة,01098765432,متجر سارة,NT-001,100
      </div>
      <p style="font-size:11.5px;color:var(--t3);margin-top:12px;line-height:1.8">
        كل سطر ليه نفس <strong>customer_email</strong> بينضم لنفس الطلب — يعني تقدر تحط أكتر من منتج للعميل الواحد.<br><br>
        لو العميل مش موجود، الطلب هيتحفظ ببياناته من الملف مباشرة (بدون ربطه بحساب).<br><br>
        الأسعار بتتحسب تلقائياً من شرائح أسعار الجملة حسب الكمية.
      </p>
    </div>
  </div>

</div>
<?php $content = ob_get_clean(); require APP_PATH . '/Views/admin/layouts/app.php';
