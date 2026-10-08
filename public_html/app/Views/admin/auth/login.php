<?php
$adm = defined('ADMIN_PREFIX') ? ADMIN_PREFIX : 'admin';
$appUrl = defined('APP_URL') ? APP_URL : '';
try { $storeName = SettingModel::get('store_name', 'Notch Technology'); } catch(\Throwable $e) { $storeName = 'Notch Technology'; }
try { $errMsg = Session::flash('error'); } catch(\Throwable $e) { $errMsg = null; }
try { $csrf = Session::csrf(); } catch(\Throwable $e) { $csrf = ''; }
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>تسجيل الدخول — <?= htmlspecialchars($storeName) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Inter','Segoe UI',sans-serif;background:#09090b;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;position:relative;overflow:hidden}
body::before{content:'';position:fixed;width:500px;height:500px;background:radial-gradient(circle,rgba(109,90,205,.15) 0%,transparent 70%);top:-150px;right:-150px;pointer-events:none}
.card{width:100%;max-width:400px;background:#111113;border:1px solid #1f1f23;border-radius:18px;padding:44px 38px;position:relative;z-index:1}
.logo{text-align:center;margin-bottom:36px}
.logo-mark{width:50px;height:50px;background:linear-gradient(135deg,#6d5acd,#8b75e8);border-radius:13px;display:inline-flex;align-items:center;justify-content:center;font-size:22px;margin-bottom:14px}
.logo h1{font-size:20px;font-weight:800;color:#fff;letter-spacing:-.5px}
.logo p{color:#52525b;font-size:12px;margin-top:4px}
.alert-err{background:#1c0a0a;border:1px solid #7f1d1d;color:#fca5a5;padding:11px 14px;border-radius:9px;font-size:13px;margin-bottom:20px;display:flex;align-items:center;gap:8px}
.fg{margin-bottom:16px}
label{display:block;font-size:12px;font-weight:500;color:#a1a1aa;margin-bottom:6px}
input{width:100%;background:#09090b;border:1px solid #27272a;border-radius:9px;color:#fff;padding:11px 14px;font-size:13px;font-family:inherit;transition:border-color .15s,box-shadow .15s;outline:none}
input:focus{border-color:#6d5acd;box-shadow:0 0 0 3px rgba(109,90,205,.15)}
input::placeholder{color:#3f3f46}
.btn{width:100%;padding:12px;background:#6d5acd;color:#fff;border:none;border-radius:9px;font-size:14px;font-weight:600;cursor:pointer;font-family:inherit;transition:background .15s;margin-top:6px}
.btn:hover{background:#5a48b8}
.back{text-align:center;margin-top:22px}
.back a{color:#52525b;text-decoration:none;font-size:12px;transition:color .15s}
.back a:hover{color:#a1a1aa}
</style>
</head>
<body>
<div class="card">
  <div class="logo">
    <div class="logo-mark">⚡</div>
    <h1><?= htmlspecialchars($storeName) ?></h1>
    <p>لوحة التحكم</p>
  </div>

  <?php if ($errMsg): ?>
    <div class="alert-err">⚠️ <?= htmlspecialchars($errMsg) ?></div>
  <?php endif; ?>

  <form method="POST" action="/<?= $adm ?>/login">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
    <div class="fg">
      <label>البريد الإلكتروني</label>
      <input type="email" name="email" placeholder="admin@notchtech.co" required autofocus>
    </div>
    <div class="fg">
      <label>كلمة المرور</label>
      <input type="password" name="password" placeholder="••••••••" required>
    </div>
    <button type="submit" class="btn">تسجيل الدخول</button>
  </form>

  <div class="back"><a href="/">← العودة إلى المتجر</a></div>
</div>
</body>
</html>
