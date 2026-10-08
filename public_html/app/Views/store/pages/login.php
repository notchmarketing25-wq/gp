<?php
$pageTitle = 'تسجيل الدخول — ' . SettingModel::get('store_name', APP_NAME);
ob_start(); ?>
<div class="container-sm" style="padding:60px 24px">
  <div style="max-width:420px;margin:0 auto">
    <h1 style="font-size:26px;font-weight:800;margin-bottom:6px">تسجيل الدخول</h1>
    <p style="color:var(--text2);margin-bottom:28px">مرحباً بعودتك! أدخل بياناتك للمتابعة.</p>
    <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:28px">
      <form method="POST" action="<?= url('login') ?>">
        <?= csrf_field() ?>
        <div class="form-group" style="margin-bottom:16px">
          <label class="form-label">البريد الإلكتروني</label>
          <input type="email" name="email" class="form-input" required autofocus>
        </div>
        <div class="form-group" style="margin-bottom:20px">
          <label class="form-label">كلمة المرور</label>
          <input type="password" name="password" class="form-input" required>
        </div>
        <button type="submit" class="btn btn-primary btn-full">تسجيل الدخول</button>
      </form>
      <p style="text-align:center;font-size:13px;color:var(--text2);margin-top:16px">
        ليس لديك حساب؟ <a href="<?= url('register') ?>" style="color:var(--accent2)">سجل الآن</a>
      </p>
    </div>
  </div>
</div>
<?php $content = ob_get_clean();
require APP_PATH . '/Views/store/layouts/app.php';
