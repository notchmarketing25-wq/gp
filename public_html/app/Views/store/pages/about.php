<?php
$pageTitle = 'من نحن — ' . SettingModel::get('store_name', APP_NAME);
ob_start(); ?>
<div class="container-sm" style="padding:60px 24px;text-align:center">
  <div class="section-label">🏢 من نحن</div>
  <h1 class="section-title" style="margin-bottom:16px"><?= e(SettingModel::get('store_name','Notch Technology')) ?></h1>
  <p style="font-size:16px;color:var(--text2);max-width:600px;margin:0 auto 40px;line-height:1.8"><?= e(SettingModel::get('store_description','متجر الإلكترونيات الرائد في مصر')) ?></p>
  <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:20px;max-width:700px;margin:0 auto">
    <?php foreach ([['🚚','شحن سريع','لجميع أنحاء مصر'],['🔒','دفع آمن','بوابات دفع موثوقة'],['💬','دعم متواصل','فريق دعم 24/7']] as [$i,$t,$s]): ?>
      <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:24px">
        <div style="font-size:32px;margin-bottom:10px"><?= $i ?></div>
        <div style="font-weight:600;margin-bottom:5px"><?= $t ?></div>
        <div style="font-size:13px;color:var(--text2)"><?= $s ?></div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<?php $content = ob_get_clean();
require APP_PATH . '/Views/store/layouts/app.php';
