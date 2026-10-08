<?php
$pageTitle  = 'طلب #' . e($order['order_number']);
$breadcrumb = [
    ['label' => 'الطلبات', 'url' => adminUrl('orders')],
    ['label'  => $order['order_number']],
];
$a = ADMIN_PREFIX;
ob_start();
?>
<div class="ph">
  <div class="ph-left">
    <h1 style="font-family:monospace"><?= e($order['order_number']) ?></h1>
    <p>
      <?= formatDateTime($order['created_at']) ?>
      <?php $ot = $order['order_type']??'retail'; ?>
      <?php if ($ot==='wholesale'): ?>
        · <span class="badge badge-purple">🏬 طلب جملة/Business</span>
      <?php else: ?>
        · <span class="badge badge-dim">🛍️ طلب عادي</span>
      <?php endif; ?>
    </p>
  </div>
  <div class="ph-right">
    <a href="<?= adminUrl('orders') ?>" class="btn btn-secondary">← الطلبات</a>
    <button class="btn btn-secondary" onclick="window.print()">🖨️ طباعة</button>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 300px;gap:16px;align-items:start">

  <!-- Left: Items -->
  <div style="display:flex;flex-direction:column;gap:14px">

    <!-- Products -->
    <div class="card">
      <div class="card-header"><span class="card-title">المنتجات</span></div>
      <div class="table-wrap">
        <table>
          <thead><tr><th>المنتج</th><th>السعر</th><th>الكمية</th><th>الإجمالي</th></tr></thead>
          <tbody>
            <?php foreach ($order['items'] ?? [] as $item): ?>
              <tr>
                <td>
                  <div class="prod-cell">
                    <?php if ($item['image'] ?? null): ?>
                      <img src="<?= uploadUrl($item['image']) ?>" class="prod-img" alt="">
                    <?php else: ?>
                      <div class="prod-img-ph">📦</div>
                    <?php endif; ?>
                    <div>
                      <div class="prod-name"><?= e($item['name']) ?></div>
                      <?php if ($item['variant'] ?? null): ?>
                        <div class="prod-sku"><?= e($item['variant']) ?></div>
                      <?php endif; ?>
                      <?php if ($item['sku'] ?? null): ?>
                        <div class="prod-sku" style="font-family:monospace;color:var(--t3)">SKU: <?= e($item['sku']) ?></div>
                      <?php endif; ?>
                    </div>
                  </div>
                </td>
                <td><?= money($item['price']) ?></td>
                <td><?= $item['qty'] ?></td>
                <td style="font-weight:700"><?= money($item['total']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <!-- Totals -->
      <div style="padding:14px 16px;border-top:1px solid var(--bd)">
        <div style="max-width:220px;margin-right:auto">
          <div style="display:flex;justify-content:space-between;font-size:12px;padding:4px 0;color:var(--t2)">
            <span>المجموع الفرعي</span><span><?= money($order['subtotal']) ?></span>
          </div>
          <?php if (($order['discount_amount'] ?? 0) > 0): ?>
            <div style="display:flex;justify-content:space-between;font-size:12px;padding:4px 0;color:var(--green)">
              <span>خصم (<?= e($order['discount_code'] ?? '') ?>)</span>
              <span>- <?= money($order['discount_amount']) ?></span>
            </div>
          <?php endif; ?>
          <div style="display:flex;justify-content:space-between;font-size:12px;padding:4px 0;color:var(--t2)">
            <span>الشحن</span><span><?= money($order['shipping_price'] ?? 0) ?></span>
          </div>
          <div style="display:flex;justify-content:space-between;font-size:15px;font-weight:800;padding:10px 0 0;border-top:1px solid var(--bd);margin-top:5px">
            <span>الإجمالي</span><span><?= money($order['total']) ?></span>
          </div>
        </div>
      </div>
    </div>

    <!-- Notes -->
    <?php if ($order['notes'] ?? null): ?>
      <div class="card">
        <div class="card-header"><span class="card-title">ملاحظة العميل</span></div>
        <div class="card-body"><p style="font-size:13px;color:var(--t2)"><?= e($order['notes']) ?></p></div>
      </div>
    <?php endif; ?>

  </div>

  <!-- Right: Status + Info -->
  <div style="display:flex;flex-direction:column;gap:14px">

    <!-- Update Status -->
    <div class="card">
      <div class="card-header"><span class="card-title">تحديث الحالة</span></div>
      <div class="card-body">
        <form method="POST" action="<?= adminUrl('orders/' . $order['id'] . '/status') ?>">
          <?= csrf_field() ?>
          <div class="form-group" style="margin-bottom:12px">
            <label class="form-label">حالة الطلب</label>
            <select name="status" class="form-select">
              <?php foreach (['pending'=>'⏳ في الانتظار','processing'=>'🔄 قيد المعالجة','shipped'=>'🚚 تم الشحن','delivered'=>'✅ تم التسليم','cancelled'=>'❌ ملغي'] as $v=>$l): ?>
                <option value="<?= $v ?>" <?= ($order['status']===$v)?'selected':'' ?>><?= $l ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group" style="margin-bottom:14px">
            <label class="form-label">حالة الدفع</label>
            <select name="payment_status" class="form-select">
              <?php foreach (['unpaid'=>'❌ غير مدفوع','paid'=>'✅ مدفوع','failed'=>'⚠️ فشل','refunded'=>'↩️ مسترجع'] as $v=>$l): ?>
                <option value="<?= $v ?>" <?= ($order['payment_status']===$v)?'selected':'' ?>><?= $l ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <button type="submit" class="btn btn-primary btn-full">تحديث</button>
        </form>
      </div>
    </div>

    <!-- Current Status -->
    <div class="card">
      <div class="card-header"><span class="card-title">الحالة الحالية</span></div>
      <div class="card-body">
        <div style="display:flex;flex-direction:column;gap:8px">
          <div style="display:flex;justify-content:space-between;align-items:center">
            <span style="font-size:12px;color:var(--t2)">الطلب</span>
            <span class="badge badge-<?= orderStatusColor($order['status']) ?>"><?= orderStatusLabel($order['status']) ?></span>
          </div>
          <div style="display:flex;justify-content:space-between;align-items:center">
            <span style="font-size:12px;color:var(--t2)">الدفع</span>
            <span class="badge <?= ($order['payment_status']==='paid')?'badge-ok':(($order['payment_status']==='failed')?'badge-err':'badge-warn') ?>"><?= paymentStatusLabel($order['payment_status']) ?></span>
          </div>
          <div style="display:flex;justify-content:space-between;align-items:center">
            <span style="font-size:12px;color:var(--t2)">طريقة الدفع</span>
            <span style="font-size:12px"><?= $order['payment_method']==='cod'?'💵 كاش':'💳 فواتيرك' ?></span>
          </div>
        </div>
      </div>
    </div>

    <!-- Customer -->
    <div class="card">
      <div class="card-header"><span class="card-title">العميل</span></div>
      <div class="card-body">
        <div style="display:flex;flex-direction:column;gap:7px;font-size:12px">
          <div style="font-weight:600;font-size:13px"><?= e($order['customer_name']) ?></div>
          <div style="color:var(--t2)">📧 <?= e($order['customer_email']) ?></div>
          <div style="color:var(--t2)">📱 <?= e($order['customer_phone']) ?></div>
        </div>
      </div>
    </div>

    <?php if (!empty($order['placed_by'])): ?>
    <!-- Placed by (Business account) -->
    <div class="card" style="border-color:rgba(123,47,190,.3)">
      <div class="card-header"><span class="card-title">💼 تم الطلب عن طريق</span></div>
      <div class="card-body">
        <div style="display:flex;flex-direction:column;gap:7px;font-size:12px">
          <div style="font-weight:600;font-size:13px">
            <?= ['merchant'=>'🏪 تاجر','distributor'=>'🚛 موزع','rep'=>'🧑‍💼 مندوب'][$order['placed_by']['business_type']] ?? '' ?>: <?= e($order['placed_by']['name']) ?>
          </div>
          <?php if ($order['placed_by']['tier_name']): ?>
            <div><span class="badge badge-purple">🏷️ <?= e($order['placed_by']['tier_name']) ?> (-<?= $order['placed_by']['discount_percent'] ?>%)</span></div>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Shipping -->
    <div class="card">
      <div class="card-header"><span class="card-title">عنوان الشحن</span></div>
      <div class="card-body">
        <div style="font-size:12px;color:var(--t2);line-height:2">
          <?= e($order['shipping_address'] ?? '') ?><br>
          <?= e($order['shipping_city'] ?? '') ?>، <?= e($order['shipping_gov'] ?? '') ?>
        </div>
        <?php if ($order['shipped_at'] ?? null): ?>
          <div style="font-size:11px;color:var(--t3);margin-top:8px">شُحن: <?= formatDateTime($order['shipped_at']) ?></div>
        <?php endif; ?>
        <?php if ($order['delivered_at'] ?? null): ?>
          <div style="font-size:11px;color:var(--green);margin-top:4px">استُلم: <?= formatDateTime($order['delivered_at']) ?></div>
        <?php endif; ?>
      </div>
    </div>

  </div>
</div>

<?php
$content = ob_get_clean();
require APP_PATH . '/Views/admin/layouts/app.php';
