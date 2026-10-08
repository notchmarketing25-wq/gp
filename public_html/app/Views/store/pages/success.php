<?php
$pageTitle = 'تم تأكيد طلبك — ' . SettingModel::get('store_name', APP_NAME);
ob_start(); ?>
<div class="container-sm" style="padding:60px 24px;text-align:center">
  <div style="font-size:64px;margin-bottom:20px">🎉</div>
  <h1 style="font-size:28px;font-weight:800;margin-bottom:8px">تم تأكيد طلبك!</h1>
  <p style="color:var(--text2);font-size:15px;margin-bottom:32px">شكراً لك! سيتم التواصل معك قريباً لتأكيد التسليم.</p>

  <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:24px;max-width:440px;margin:0 auto 32px;text-align:right">
    <div style="font-size:14px;font-weight:700;margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid var(--border)">
      تفاصيل الطلب
    </div>
    <div style="display:flex;flex-direction:column;gap:10px;font-size:13px">
      <div style="display:flex;justify-content:space-between">
        <span style="color:var(--text2)">رقم الطلب</span>
        <span style="font-weight:700;font-family:monospace;color:var(--accent2)"><?= e($order['order_number']) ?></span>
      </div>
      <div style="display:flex;justify-content:space-between">
        <span style="color:var(--text2)">طريقة الدفع</span>
        <span><?= match($order['payment_method']) { 'cod' => '💵 دفع عند الاستلام', 'credit' => '💰 رصيد N&Credit', default => '💳 بطاقة إلكترونية' } ?></span>
      </div>
      <div style="display:flex;justify-content:space-between">
        <span style="color:var(--text2)">إجمالي الطلب</span>
        <span style="font-weight:800;font-size:16px"><?= money($order['total']) ?></span>
      </div>
      <div style="display:flex;justify-content:space-between">
        <span style="color:var(--text2)">عنوان الشحن</span>
        <span style="text-align:left;max-width:200px"><?= e($order['shipping_city']) ?>، <?= e($order['shipping_gov']) ?></span>
      </div>
    </div>
  </div>

  <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap">
    <a href="<?= url('products') ?>" class="btn btn-primary btn-lg">متابعة التسوق</a>
    <?php if (isStoreLoggedIn()): ?>
      <a href="<?= url('account/orders') ?>" class="btn btn-outline btn-lg">تتبع طلباتي</a>
    <?php endif; ?>
  </div>
</div>
<?php $content = ob_get_clean();
require APP_PATH . '/Views/store/layouts/app.php';
