<?php
$pageTitle  = 'إعدادات البريد';
$breadcrumb = [['label'=>'الإيميلات','url'=>adminUrl('emails/templates')],['label'=>'إعدادات SMTP']];
$a = ADMIN_PREFIX;
$v = fn(string $k, string $d='') => htmlspecialchars($s[$k] ?? $d, ENT_QUOTES);
ob_start();
?>
<div class="ph">
  <div class="ph-left"><h1><span style="display:inline-flex;align-items:center;gap:9px"><?= svgIcon('settings', 22) ?> إعدادات البريد الإلكتروني</span></h1><p>SMTP اختياري — بدونه هيتم الإرسال بطريقة PHP mail() الافتراضية</p></div>
  <div class="ph-right"><a href="<?= adminUrl('emails/templates') ?>" class="btn btn-secondary">← القوالب</a></div>
</div>

<form method="POST" action="<?= adminUrl('emails/settings') ?>">
  <?= csrf_field() ?>
  <div style="display:grid;grid-template-columns:1fr 300px;gap:16px;align-items:start">
    <div class="card">
      <div class="card-header">
        <span class="card-title">إعدادات SMTP</span>
        <div class="toggle-wrap">
          <label class="toggle"><input type="checkbox" name="smtp_enabled" value="1" <?= ($s['smtp_enabled']??'0')==='1'?'checked':'' ?>><span class="toggle-slider"></span></label>
          <span style="font-size:12px;color:var(--t2)">استخدام SMTP</span>
        </div>
      </div>
      <div class="card-body form-grid form-row-2">
        <div class="form-group"><label class="form-label">SMTP Host</label><input type="text" name="smtp_host" class="form-input" value="<?= $v('smtp_host') ?>" placeholder="smtp.gmail.com"></div>
        <div class="form-group"><label class="form-label">Port</label><input type="number" name="smtp_port" class="form-input" value="<?= $v('smtp_port','587') ?>"></div>
        <div class="form-group"><label class="form-label">Username</label><input type="text" name="smtp_username" class="form-input" value="<?= $v('smtp_username') ?>"></div>
        <div class="form-group"><label class="form-label">Password</label><input type="password" name="smtp_password" class="form-input" placeholder="<?= $s['smtp_password']??'' ? '••••••••' : '' ?>"></div>
        <div class="form-group"><label class="form-label">التشفير</label>
          <select name="smtp_encryption" class="form-select">
            <option value="tls" <?= ($s['smtp_encryption']??'tls')==='tls'?'selected':'' ?>>TLS</option>
            <option value="ssl" <?= ($s['smtp_encryption']??'')==='ssl'?'selected':'' ?>>SSL</option>
            <option value="none" <?= ($s['smtp_encryption']??'')==='none'?'selected':'' ?>>بدون</option>
          </select>
        </div>
        <div class="form-group"><label class="form-label">From Email</label><input type="email" name="smtp_from_email" class="form-input" value="<?= $v('smtp_from_email') ?>"></div>
        <div class="form-group" style="grid-column:span 2"><label class="form-label">From Name</label><input type="text" name="smtp_from_name" class="form-input" value="<?= $v('smtp_from_name', SettingModel::get('store_name','')) ?>"></div>
      </div>
    </div>

    <div style="display:flex;flex-direction:column;gap:14px">
      <div class="card" style="border-color:rgba(0,87,255,.3)">
        <div class="card-header"><span class="card-title">💡 مهم — لماذا الإيميلات بتفشل؟</span></div>
        <div class="card-body" style="font-size:12.5px;color:var(--t2);line-height:1.9">
          استضافات مثل Hostinger غالباً بتعطّل أو تقيّد دالة <code style="background:var(--bg3);padding:1px 5px;border-radius:4px">mail()</code> الافتراضية في PHP، فالإيميلات بتفشل بصمت.
          <br><br>
          <strong style="color:var(--t1)">الحل: فعّل SMTP</strong> باستخدام بيانات بريدك من نفس Hostinger (تقدر تنشئ إيميل زي <code style="background:var(--bg3);padding:1px 5px;border-radius:4px">no-reply@yourdomain.com</code> من لوحة تحكم الاستضافة نفسها تحت "Email Accounts")، وعادةً بتكون:
          <br><br>
          Host: <code style="background:var(--bg3);padding:1px 5px;border-radius:4px">smtp.hostinger.com</code><br>
          Port: <code style="background:var(--bg3);padding:1px 5px;border-radius:4px">465</code> (SSL) أو <code style="background:var(--bg3);padding:1px 5px;border-radius:4px">587</code> (TLS)<br>
          Username/Password: بيانات الإيميل اللي أنشأته
        </div>
      </div>
      <div class="card">
        <div class="card-header"><span class="card-title">🔔 التفعيل العام</span></div>
        <div class="card-body">
          <div class="toggle-wrap">
            <label class="toggle"><input type="checkbox" name="email_notifications" value="1" <?= ($s['email_notifications']??'1')==='1'?'checked':'' ?>><span class="toggle-slider"></span></label>
            <span style="font-size:13px">إرسال كل إشعارات البريد</span>
          </div>
          <p style="font-size:11.5px;color:var(--t3);margin-top:10px">لو عطّلته، هيتوقف إرسال كل الإيميلات التلقائية (ترحيب، تأكيد طلب...الخ) بدون حذف القوالب.</p>
        </div>
      </div>
      <button type="submit" class="btn btn-primary btn-full"><?= svgIcon('check', 15) ?> حفظ الإعدادات</button>
    </div>
  </div>
</form>
<?php $content = ob_get_clean(); require APP_PATH . '/Views/admin/layouts/app.php';
