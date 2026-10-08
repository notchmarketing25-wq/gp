<?php
$pageTitle  = 'رفع عملاء جملة';
$breadcrumb = [['label'=>'الجملة','url'=>adminUrl('wholesale')],['label'=>'رفع عملاء CSV']];
$a = ADMIN_PREFIX;
ob_start();
?>
<div class="ph">
  <div class="ph-left"><h1><span style="display:inline-flex;align-items:center;gap:9px"><?= svgIcon('user', 22) ?> رفع عملاء جملة بالجملة (CSV)</span></h1><p>استورد عدة حسابات جملة دفعة واحدة</p></div>
  <div class="ph-right"><a href="<?= adminUrl('wholesale') ?>" class="btn btn-secondary">← حسابات الجملة</a></div>
</div>

<div style="display:grid;grid-template-columns:1fr 340px;gap:16px;align-items:start">

  <div class="card">
    <div class="card-header"><span class="card-title">رفع الملف</span></div>
    <div class="card-body">
      <form method="POST" action="<?= adminUrl('wholesale/customers/bulk-upload/preview') ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="form-group" style="margin-bottom:16px">
          <label class="form-label">الحالة الافتراضية للحسابات</label>
          <select name="default_status" class="form-select">
            <option value="pending">⏳ قيد المراجعة (تعتمدهم بنفسك بعدين)</option>
            <option value="approved">✅ معتمد مباشرة (نقل بيانات من نظام قديم)</option>
          </select>
        </div>
        <div class="form-group" style="margin-bottom:20px">
          <label class="form-label">ملف CSV <span class="req">*</span></label>
          <input type="file" name="csv" class="form-input" accept=".csv,.txt" required style="padding:8px">
        </div>
        <button type="submit" class="btn btn-primary btn-full"><?= svgIcon('search', 15) ?> معاينة الملف</button>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><span class="card-title"><?= svgIcon('receipt', 15) ?> صيغة الملف</span></div>
    <div class="card-body">
      <div style="background:var(--bg3);border:1px solid var(--bd);border-radius:7px;padding:12px;font-family:monospace;font-size:11px;color:var(--t2);line-height:1.9;overflow-x:auto">
        name,email,phone,company_name,company_address,tax_number<br>
        أحمد محمد,ahmed@shop.com,01012345678,محل أحمد,القاهرة,12345<br>
        سارة علي,sara@store.com,01098765432,متجر سارة,الجيزة,
      </div>
      <p style="font-size:11.5px;color:var(--t3);margin-top:12px;line-height:1.8">
        لو الإيميل موجود مسبقاً، هيتم تحديث بياناته وتحويله لحساب جملة. غير كده هيتنشأ حساب جديد بكلمة سر عشوائية (العميل يقدر يعمل "نسيت كلمة المرور" لاحقاً).
      </p>
    </div>
  </div>

</div>
<?php $content = ob_get_clean(); require APP_PATH . '/Views/admin/layouts/app.php';
