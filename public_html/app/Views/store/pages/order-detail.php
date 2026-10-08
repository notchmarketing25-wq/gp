<?php
$pageTitle = 'طلب #' . e($order['order_number']) . ' — ' . SettingModel::get('store_name', APP_NAME);
ob_start();
?>
<div class="container" style="padding-top:28px;padding-bottom:80px">
  <div class="breadcrumb" style="display:flex;align-items:center;gap:8px;font-size:13px;color:var(--text2);margin-bottom:24px">
    <a href="<?= url('account') ?>" style="color:var(--text2)">حسابي</a>
    <span style="color:var(--text3)">/</span>
    <span>طلب <?= e($order['order_number']) ?></span>
  </div>

  <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:24px">
    <div>
      <h1 style="font-size:20px;font-weight:800;font-family:monospace;color:var(--accent2)"><?= e($order['order_number']) ?></h1>
      <p style="color:var(--text2);font-size:13px;margin-top:3px"><?= formatDateTime($order['created_at']) ?></p>
    </div>
    <?php $sc = match($order['status']??'pending'){'delivered'=>'#22c55e','shipped'=>'#8b5cf6','processing'=>'#3b82f6','cancelled'=>'#ef4444',default=>'#f59e0b'}; ?>
    <span style="padding:5px 14px;border-radius:20px;font-size:12px;font-weight:600;background:<?=$sc?>1a;color:<?=$sc?>"><?= orderStatusLabel($order['status']??'pending') ?></span>
  </div>

  <div class="page-grid-sidebar" style="display:grid;grid-template-columns:1fr 280px;gap:16px;align-items:start">

    <!-- Items -->
    <div style="background:var(--surface);border:1px solid var(--border);border-radius:12px;overflow:hidden">
      <div style="padding:14px 18px;border-bottom:1px solid var(--border);font-weight:600;font-size:14px">المنتجات</div>
      <?php foreach ($order['items'] ?? [] as $item): ?>
        <div style="display:flex;align-items:center;gap:14px;padding:14px 18px;border-bottom:1px solid var(--border)">
          <?php if ($item['image']??null): ?>
            <img src="<?= uploadUrl($item['image']) ?>" style="width:52px;height:52px;object-fit:cover;border-radius:9px;border:1px solid var(--border);flex-shrink:0">
          <?php else: ?>
            <div style="width:52px;height:52px;border-radius:9px;background:var(--bg2);display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0">📦</div>
          <?php endif; ?>
          <div style="flex:1">
            <div style="font-weight:500;font-size:13px"><?= e($item['name']) ?></div>
            <?php if ($item['variant']??null): ?><div style="font-size:11px;color:var(--text2)"><?= e($item['variant']) ?></div><?php endif; ?>
            <div style="font-size:11px;color:var(--text3)">× <?= $item['qty'] ?></div>
          </div>
          <div style="font-weight:700"><?= money($item['total']) ?></div>
        </div>
      <?php endforeach; ?>
      <div style="padding:16px 18px">
        <div style="max-width:200px;margin-right:auto">
          <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--text2);padding:4px 0"><span>المجموع الفرعي</span><span><?= money($order['subtotal']) ?></span></div>
          <?php if (($order['discount_amount']??0)>0): ?><div style="display:flex;justify-content:space-between;font-size:12px;color:#22c55e;padding:4px 0"><span>خصم</span><span>- <?= money($order['discount_amount']) ?></span></div><?php endif; ?>
          <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--text2);padding:4px 0"><span>الشحن</span><span><?= money($order['shipping_price']??0) ?></span></div>
          <div style="display:flex;justify-content:space-between;font-size:16px;font-weight:800;padding:10px 0 0;border-top:1px solid var(--border);margin-top:5px"><span>الإجمالي</span><span><?= money($order['total']) ?></span></div>
        </div>
      </div>
    </div>

    <!-- Info -->
    <div style="display:flex;flex-direction:column;gap:12px">
      <div style="background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:18px">
        <div style="font-weight:600;margin-bottom:12px;font-size:13px">عنوان الشحن</div>
        <div style="font-size:12px;color:var(--text2);line-height:2">
          <?= e($order['shipping_address']??'') ?><br>
          <?= e($order['shipping_city']??'') ?>، <?= e($order['shipping_gov']??'') ?>
        </div>
      </div>
      <div style="background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:18px">
        <div style="font-weight:600;margin-bottom:12px;font-size:13px">طريقة الدفع</div>
        <div style="font-size:13px"><?= $order['payment_method']==='cod'?'💵 الدفع عند الاستلام':'💳 فواتيرك' ?></div>
        <div style="margin-top:8px">
          <span style="padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;background:<?=($order['payment_status']==='paid')?'rgba(34,197,94,.1)':'rgba(245,158,11,.1)'?>;color:<?=($order['payment_status']==='paid')?'#22c55e':'#f59e0b'?>">
            <?= paymentStatusLabel($order['payment_status']??'unpaid') ?>
          </span>
        </div>
      </div>
      <a href="<?= url('account') ?>" class="btn btn-outline" style="text-align:center">← رجوع للحساب</a>
    </div>

  </div>
</div>
<?php
$content = ob_get_clean();
require APP_PATH . '/Views/store/layouts/app.php';
