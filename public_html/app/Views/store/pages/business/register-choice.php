<?php
$pageTitle = 'انضم لـ Notch Business — ' . SettingModel::get('store_name', APP_NAME);
ob_start();
?>
<div class="container" style="padding-top:36px;padding-bottom:80px;max-width:900px;margin:0 auto">
  <div style="text-align:center;margin-bottom:24px">
    <div class="hero-badge" style="margin:0 auto 16px"><span class="hero-badge-dot"></span> Notch Business</div>
    <h1 style="font-family:var(--font-body);font-size:28px;font-weight:900;margin-bottom:10px">اختر نوع حسابك</h1>
    <p style="color:var(--text2);font-size:14px">كل نوع حساب له مميزات وأسعار خاصة</p>
  </div>

  <div style="background:var(--accent-bg);border:1px solid rgba(var(--accent-rgb),.25);border-radius:14px;padding:16px 22px;margin-bottom:28px;display:flex;align-items:center;gap:14px;max-width:560px;margin-inline:auto">
    <div style="width:38px;height:38px;border-radius:11px;background:var(--accent);color:#fff;display:flex;align-items:center;justify-content:center;flex-shrink:0"><?= svgIcon('star', 18) ?></div>
    <div style="font-size:13px;color:var(--text2)"><b style="color:var(--text)">هدية تسجيل: 1000 جنيه رصيد مجاني</b> — بمجرد اعتماد حسابك، هتلاقي 1000 جنيه رصيد N&Credit مضافين لحسابك تلقائي</div>
  </div>

  <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px">
    <?php foreach([
      ['merchant','store','تاجر','لأصحاب المحلات — أسعار جملة حسب شريحتك، وطلب مباشر لنفسك'],
      ['distributor','truck','موزع','بتوزّع على عملاء؟ اعمل طلبات باسم عملائك بأسعار خاصة'],
      ['rep','briefcase','مندوب','اعمل مع نوتش كمندوب مبيعات — سجّل تجار، واطلب عينات ومصاريف'],
    ] as [$type,$icon,$title,$desc]): ?>
      <a href="<?= url('business/register/'.$type) ?>" style="background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:28px 20px;text-align:center;transition:all .2s;display:block" onmouseover="this.style.borderColor='var(--accent)';this.style.transform='translateY(-3px)'" onmouseout="this.style.borderColor='var(--border)';this.style.transform='none'">
        <div style="width:56px;height:56px;border-radius:16px;background:var(--accent-bg);color:var(--accent);display:flex;align-items:center;justify-content:center;margin:0 auto 14px"><?= svgIcon($icon, 26, 1.6) ?></div>
        <div style="font-weight:800;font-size:16px;margin-bottom:8px"><?= $title ?></div>
        <div style="font-size:12.5px;color:var(--text2);line-height:1.7"><?= $desc ?></div>
      </a>
    <?php endforeach; ?>
  </div>

  <div style="text-align:center;margin-top:28px;font-size:13px;color:var(--text3)">
    عندك حساب Business بالفعل؟ <a href="<?= url('login') ?>" style="color:var(--accent);font-weight:700">سجّل دخولك من هنا</a>
  </div>
</div>
<?php
$content = ob_get_clean();
require APP_PATH . '/Views/store/layouts/app.php';
