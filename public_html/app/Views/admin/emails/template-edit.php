<?php
$pageTitle  = 'تعديل: ' . $tpl['name'];
$breadcrumb = [['label'=>'الإيميلات','url'=>adminUrl('emails/templates')],['label'=>$tpl['name']]];
$a = ADMIN_PREFIX;
ob_start();
?>
<div class="ph">
  <div class="ph-left"><h1><span style="display:inline-flex;align-items:center;gap:9px"><?= svgIcon('edit', 22) ?> <?= e($tpl['name']) ?></span></h1><p>المتغيرات المتاحة: <code style="background:var(--bg3);padding:1px 6px;border-radius:4px"><?= e($tpl['variables_hint']) ?></code></p></div>
  <div class="ph-right"><a href="<?= adminUrl('emails/templates') ?>" class="btn btn-secondary">← القوالب</a></div>
</div>

<div style="display:grid;grid-template-columns:1fr 380px;gap:16px;align-items:start">

  <form method="POST" action="<?= adminUrl('emails/templates/'.$tpl['id']) ?>">
    <?= csrf_field() ?>
    <div class="card" style="margin-bottom:16px">
      <div class="card-header">
        <span class="card-title">محتوى القالب</span>
        <div class="toggle-wrap">
          <label class="toggle"><input type="checkbox" name="is_active" value="1" <?= $tpl['is_active']?'checked':'' ?>><span class="toggle-slider"></span></label>
          <span style="font-size:12px;color:var(--t2)">مفعّل</span>
        </div>
      </div>
      <div class="card-body">
        <div class="form-group" style="margin-bottom:14px">
          <label class="form-label">اسم القالب (داخلي)</label>
          <input type="text" name="name" class="form-input" value="<?= e($tpl['name']) ?>">
        </div>
        <div class="form-group" style="margin-bottom:14px">
          <label class="form-label">عنوان الإيميل (Subject)</label>
          <input type="text" name="subject" class="form-input" value="<?= e($tpl['subject']) ?>" id="subjectInput" oninput="updatePreview()">
        </div>
        <div class="form-group" style="margin-bottom:0">
          <label class="form-label">محتوى الإيميل (HTML)</label>
          <textarea name="html_body" id="bodyInput" class="form-textarea" rows="16" style="font-family:monospace;font-size:12.5px;direction:ltr;text-align:left" oninput="updatePreview()"><?= e($tpl['html_body']) ?></textarea>
          <span class="form-hint">استخدم وسوم HTML بسيطة (h2, p, a, table, div) — التصميم العام (الهيدر بالشعار والفوتر) بيتضاف تلقائياً حوالين المحتوى ده</span>
        </div>
      </div>
    </div>
    <button type="submit" class="btn btn-primary"><?= svgIcon('check', 15) ?> حفظ القالب</button>
  </form>

  <div style="display:flex;flex-direction:column;gap:14px">
    <div class="card">
      <div class="card-header"><span class="card-title"><?= svgIcon('search', 15) ?> معاينة حية</span></div>
      <div class="card-body" style="padding:0">
        <div style="background:#F4F4F5;padding:20px">
          <div style="background:#0A0A0A;padding:14px 18px;border-radius:10px 10px 0 0;color:#fff;font-weight:800;font-size:14px">
            <?= e(SettingModel::get('store_name','Notch')) ?>
          </div>
          <div id="previewBody" style="background:#fff;padding:20px;border-radius:0 0 10px 10px;font-size:13px;line-height:1.8;color:#0A0A0A"><?= $tpl['html_body'] ?></div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><span class="card-title"><?= svgIcon('mail', 15) ?> إرسال تجريبي</span></div>
      <div class="card-body">
        <form method="POST" action="<?= adminUrl('emails/templates/'.$tpl['id'].'/test') ?>">
          <?= csrf_field() ?>
          <div class="form-group" style="margin-bottom:12px">
            <input type="email" name="test_email" class="form-input" placeholder="بريدك الإلكتروني" required>
          </div>
          <button type="submit" class="btn btn-secondary btn-full">إرسال نسخة تجريبية</button>
        </form>
      </div>
    </div>
  </div>

</div>

<?php
$extraScript = "
function updatePreview(){
  document.getElementById('previewBody').innerHTML = document.getElementById('bodyInput').value;
}
";
$content = ob_get_clean();
require APP_PATH . '/Views/admin/layouts/app.php';
