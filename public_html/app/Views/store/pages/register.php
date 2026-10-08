<?php
$pageTitle = 'إنشاء حساب — ' . SettingModel::get('store_name', APP_NAME);
ob_start(); ?>
<div class="container-sm" style="padding:60px 24px">
  <div style="max-width:460px;margin:0 auto">
    <h1 style="font-size:26px;font-weight:800;margin-bottom:6px">إنشاء حساب جديد</h1>
    <p style="color:var(--text2);margin-bottom:28px">سجل الآن وابدأ التسوق!</p>
    <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:28px">
      <form method="POST" action="<?= url('register') ?>">
        <?= csrf_field() ?>
        <div class="page-grid-form-2" style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px">
          <div class="form-group">
            <label class="form-label">الاسم الكامل <span>*</span></label>
            <input type="text" name="name" class="form-input" required>
          </div>
          <div class="form-group">
            <label class="form-label">رقم الهاتف</label>
            <input type="tel" name="phone" class="form-input" placeholder="01xxxxxxxxx">
          </div>
        </div>
        <div class="form-group" style="margin-bottom:14px">
          <label class="form-label">البريد الإلكتروني <span>*</span></label>
          <input type="email" name="email" class="form-input" required>
        </div>
        <div class="form-group" style="margin-bottom:20px">
          <label class="form-label">كلمة المرور <span>*</span></label>
          <input type="password" name="password" class="form-input" required minlength="8" placeholder="8 أحرف على الأقل">
        </div>
        <button type="submit" class="btn btn-primary btn-full">إنشاء الحساب</button>
      </form>
      <p style="text-align:center;font-size:13px;color:var(--text2);margin-top:16px">
        لديك حساب؟ <a href="<?= url('login') ?>" style="color:var(--accent2)">سجل دخولك</a>
      </p>
    </div>
  </div>
</div>
<?php $content = ob_get_clean();
require APP_PATH . '/Views/store/layouts/app.php';
