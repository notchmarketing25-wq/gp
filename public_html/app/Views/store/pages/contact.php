<?php
$pageTitle = 'تواصل معنا — ' . SettingModel::get('store_name', APP_NAME);
ob_start(); ?>
<div class="container-sm" style="padding:60px 24px">
  <div style="max-width:560px;margin:0 auto">
    <div class="section-label">📞 تواصل معنا</div>
    <h1 class="section-title" style="margin-bottom:8px">نحن هنا لمساعدتك</h1>
    <p style="color:var(--text2);margin-bottom:32px">تواصل معنا في أي وقت وسنرد عليك في أقرب وقت ممكن.</p>
    <div class="page-grid-2" style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:28px">
      <?php if ($p=SettingModel::get('store_phone')): ?>
        <a href="tel:<?= e($p) ?>" style="background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:18px;display:flex;align-items:center;gap:12px;transition:border-color .15s" onmouseover="this.style.borderColor='var(--accent)'" onmouseout="this.style.borderColor='var(--border)'">
          <div style="font-size:24px">📱</div>
          <div><div style="font-size:11px;color:var(--text2)">هاتف</div><div style="font-weight:600"><?= e($p) ?></div></div>
        </a>
      <?php endif; ?>
      <?php if ($em=SettingModel::get('store_email')): ?>
        <a href="mailto:<?= e($em) ?>" style="background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:18px;display:flex;align-items:center;gap:12px;transition:border-color .15s" onmouseover="this.style.borderColor='var(--accent)'" onmouseout="this.style.borderColor='var(--border)'">
          <div style="font-size:24px">✉️</div>
          <div><div style="font-size:11px;color:var(--text2)">بريد إلكتروني</div><div style="font-weight:600"><?= e($em) ?></div></div>
        </a>
      <?php endif; ?>
    </div>
    <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:24px">
      <div style="font-weight:600;margin-bottom:18px">أرسل لنا رسالة</div>
      <form method="POST" action="<?= url('contact') ?>">
        <?= csrf_field() ?>
        <div class="page-grid-form-2" style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px">
          <div class="form-group"><label class="form-label">الاسم</label><input type="text" name="name" class="form-input" required></div>
          <div class="form-group"><label class="form-label">البريد الإلكتروني</label><input type="email" name="email" class="form-input" required></div>
        </div>
        <div class="form-group" style="margin-bottom:12px"><label class="form-label">الموضوع</label><input type="text" name="subject" class="form-input"></div>
        <div class="form-group" style="margin-bottom:18px"><label class="form-label">الرسالة <span>*</span></label><textarea name="message" class="form-textarea" rows="4" required></textarea></div>
        <button type="submit" class="btn btn-primary">إرسال الرسالة →</button>
      </form>
    </div>
  </div>
</div>
<?php $content = ob_get_clean();
require APP_PATH . '/Views/store/layouts/app.php';
