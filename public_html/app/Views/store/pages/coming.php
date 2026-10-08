<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head><meta charset="UTF-8"><title><?= defined('APP_NAME') ? APP_NAME : 'Notch Technology' ?></title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Segoe UI',sans-serif;background:#0a0a0a;color:#e0e0e0;display:flex;align-items:center;justify-content:center;min-height:100vh;text-align:center}
.wrap{padding:40px}.logo{font-size:32px;font-weight:800;color:#fff;margin-bottom:8px}
.logo span{color:#6d5acd}.sub{color:#555;font-size:14px;margin-bottom:32px}
.badge{background:#6d5acd;color:#fff;padding:6px 16px;border-radius:20px;font-size:12px;display:inline-block;margin-bottom:24px}
a{color:#6d5acd;text-decoration:none;font-size:13px;border:1px solid #6d5acd;padding:8px 20px;border-radius:7px;transition:all .15s}
a:hover{background:#6d5acd;color:#fff}
</style>
</head>
<body>
<div class="wrap">
  <div class="logo">Notch <span>Technology</span></div>
  <div class="sub">متجر الإلكترونيات الرائد</div>
  <div class="badge">🚀 قريباً — Phase 3</div>
  <br><br>
  <a href="<?= defined('APP_URL') ? APP_URL . '/admin' : '/admin' ?>">الذهاب لـ لوحة التحكم</a>
</div>
</body>
</html>
