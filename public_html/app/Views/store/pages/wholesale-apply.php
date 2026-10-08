<?php
$pageTitle = 'حساب الجملة — ' . SettingModel::get('store_name', APP_NAME);
$status = $customer['wholesale_status'] ?? 'none';
$isGuest = !isStoreLoggedIn();
ob_start();
?>
<div class="container" style="padding-top:32px;padding-bottom:80px;max-width:640px;margin:0 auto">

  <div class="breadcrumb">
    <a href="<?= url() ?>">الرئيسية</a>
    <span class="breadcrumb-sep">/</span>
    <span class="breadcrumb-current">حساب الجملة</span>
  </div>

  <?php if ($isGuest): ?>
    <!-- Guest — show pitch + login/register CTA -->
    <div style="background:var(--black);color:#fff;border-radius:var(--radius);padding:36px;margin-bottom:24px;text-align:center">
      <div class="hero-badge" style="margin:0 auto 16px"><span class="hero-badge-dot"></span> للموزعين وأصحاب المحلات</div>
      <h1 style="font-family:var(--font-body);font-size:26px;font-weight:900;margin-bottom:10px">افتح حساب جملة</h1>
      <p style="color:rgba(255,255,255,.55);font-size:14px;line-height:1.8;max-width:440px;margin:0 auto">
        احصل على أسعار خاصة تقل كل ما زادت الكمية، ودعم مباشر لطلباتك.
      </p>
    </div>
    <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:32px;text-align:center">
      <p style="color:var(--text2);font-size:14px;margin-bottom:20px">سجّل دخولك أو أنشئ حساب جديد أولاً عشان تقدر تقدّم طلب حساب الجملة</p>
      <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap">
        <a href="<?= url('login') ?>?next=<?= urlencode('/wholesale') ?>" class="btn btn-primary btn-lg">تسجيل الدخول</a>
        <a href="<?= url('register') ?>?next=<?= urlencode('/wholesale') ?>" class="btn btn-outline btn-lg">إنشاء حساب جديد</a>
      </div>
    </div>

  <?php elseif ($status === 'approved'): ?>
    <!-- Approved -->
    <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:40px;text-align:center">
      <div style="font-size:52px;margin-bottom:16px">✅</div>
      <h1 style="font-family:var(--font-en);font-size:24px;font-weight:900;margin-bottom:10px">حساب الجملة مُفعّل</h1>
      <p style="color:var(--text2);font-size:14px;line-height:1.8;margin-bottom:26px">
        حسابك معتمد كحساب جملة. الآن هتشوف أسعار جملة خاصة على المنتجات المؤهلة حسب الكمية اللي هتطلبها.
      </p>
      <a href="<?= url('products') ?>" class="btn btn-primary btn-lg">تصفح المنتجات ←</a>
    </div>

  <?php elseif ($status === 'pending'): ?>
    <!-- Pending -->
    <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:40px;text-align:center">
      <div style="font-size:52px;margin-bottom:16px">⏳</div>
      <h1 style="font-family:var(--font-en);font-size:24px;font-weight:900;margin-bottom:10px">طلبك قيد المراجعة</h1>
      <p style="color:var(--text2);font-size:14px;line-height:1.8">
        استلمنا طلبك لحساب الجملة وجاري مراجعته. هنتواصل معك قريباً.
      </p>
    </div>

  <?php elseif ($status === 'rejected'): ?>
    <!-- Rejected — allow reapply -->
    <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:32px;margin-bottom:20px;text-align:center">
      <div style="font-size:40px;margin-bottom:12px">❌</div>
      <h2 style="font-size:17px;font-weight:700;margin-bottom:6px">لم تتم الموافقة على الطلب السابق</h2>
      <p style="color:var(--text2);font-size:13px">يمكنك تحديث بياناتك والتقديم مرة أخرى.</p>
    </div>
    <?php include __DIR__.'/../partials/wholesale-form.php'; ?>

  <?php else: ?>
    <!-- No application yet — show pitch + form -->
    <div style="background:var(--black);color:#fff;border-radius:var(--radius);padding:36px;margin-bottom:24px;text-align:center">
      <div class="hero-badge" style="margin:0 auto 16px"><span class="hero-badge-dot"></span> للموزعين وأصحاب المحلات</div>
      <h1 style="font-family:var(--font-body);font-size:26px;font-weight:900;margin-bottom:10px">افتح حساب جملة</h1>
      <p style="color:rgba(255,255,255,.55);font-size:14px;line-height:1.8;max-width:440px;margin:0 auto">
        احصل على أسعار خاصة تقل كل ما زادت الكمية، ودعم مباشر لطلباتك.
      </p>
    </div>
    <?php include __DIR__.'/../partials/wholesale-form.php'; ?>
  <?php endif; ?>

</div>
<?php
$content = ob_get_clean();
require APP_PATH . '/Views/store/layouts/app.php';
