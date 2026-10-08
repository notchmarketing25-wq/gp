<?php
$pageTitle = 'حسابي — ' . SettingModel::get('store_name', APP_NAME);
$user      = storeUser();
$tab       = $tab ?? (isset($wishlist) ? 'wishlist' : 'orders');
ob_start();
?>

<div class="container" style="padding-top:32px;padding-bottom:80px">
  <div class="page-grid-sidebar" style="display:grid;grid-template-columns:220px 1fr;gap:22px;align-items:start">

    <!-- Sidebar -->
    <div style="background:var(--surface);border:1px solid var(--border);border-radius:12px;overflow:hidden;position:sticky;top:75px">
      <div style="padding:20px;text-align:center;border-bottom:1px solid var(--border)">
        <div style="width:52px;height:52px;border-radius:50%;background:var(--accent-bg);border:2px solid var(--accent);display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:700;color:var(--accent);margin:0 auto 10px">
          <?= mb_substr($user['name'] ?? 'U', 0, 1) ?>
        </div>
        <div style="font-weight:600;font-size:14px"><?= e($user['name'] ?? '') ?></div>
        <div style="font-size:11px;color:var(--text2);margin-top:2px"><?= e($user['email'] ?? '') ?></div>
      </div>
      <?php
      $creditBal = 0;
      try { $c = (new CustomerModel())->find($user['id']); $creditBal = (float)($c['store_credit'] ?? 0); } catch (\Throwable $e) {}
      ?>
      <?php if ($creditBal > 0): ?>
      <div style="margin:14px;background:linear-gradient(135deg,var(--accent),rgba(var(--accent-rgb),.7));border-radius:10px;padding:14px;color:#fff">
        <div style="font-size:10.5px;opacity:.85;text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px">💳 رصيد N&Credit</div>
        <div style="font-size:20px;font-weight:900"><?= money($creditBal) ?></div>
      </div>
      <?php endif; ?>
      <nav style="padding:8px">
        <?php foreach (['orders' => ['🛒','طلباتي',url('account')], 'warranties' => ['🛡️','ضماناتي',url('account/warranties')], 'wishlist' => ['❤️','المفضلة',url('account/wishlist')], 'profile' => ['👤','الملف الشخصي',url('account/profile')]] as $t => [$icon,$label,$link]): ?>
          <a href="<?= $link ?>" style="display:flex;align-items:center;gap:9px;padding:9px 12px;border-radius:8px;font-size:13px;font-weight:500;margin-bottom:2px;color:<?= $tab===$t?'var(--accent)':'var(--text2)' ?>;background:<?= $tab===$t?'var(--accent-bg)':'transparent' ?>;transition:all .15s">
            <?= $icon ?> <?= $label ?>
          </a>
        <?php endforeach; ?>
        <div style="border-top:1px solid var(--border);margin:8px 0;padding-top:8px">
          <a href="<?= url('logout') ?>" style="display:flex;align-items:center;gap:9px;padding:9px 12px;border-radius:8px;font-size:13px;color:var(--red, #ef4444)">⎋ تسجيل الخروج</a>
        </div>
      </nav>
    </div>

    <!-- Content -->
    <div>

      <?php if ($tab === 'orders'): ?>
        <h2 style="font-size:18px;font-weight:700;margin-bottom:16px">طلباتي</h2>
        <?php if (empty($orders)): ?>
          <div style="background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:52px;text-align:center">
            <div style="font-size:48px;margin-bottom:14px">🛒</div>
            <div style="font-weight:600;font-size:16px;margin-bottom:8px">لا توجد طلبات بعد</div>
            <p style="color:var(--text2);margin-bottom:18px">ابدأ تسوقك الآن</p>
            <a href="<?= url('products') ?>" class="btn btn-primary">تسوق الآن →</a>
          </div>
        <?php else: ?>
          <div style="background:var(--surface);border:1px solid var(--border);border-radius:12px;overflow:hidden">
            <?php foreach ($orders as $o):
              $sc = match($o['status']??'pending'){'delivered'=>'#22c55e','shipped'=>'#8b5cf6','processing'=>'#3b82f6','cancelled'=>'#ef4444',default=>'#f59e0b'};
            ?>
              <div style="padding:16px 20px;border-bottom:1px solid var(--border)">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
                  <div>
                    <div style="font-family:monospace;font-size:13px;font-weight:700;color:var(--accent)"><?= e($o['order_number']) ?></div>
                    <div style="font-size:11px;color:var(--text3);margin-top:2px"><?= formatDate($o['created_at']) ?></div>
                  </div>
                  <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
                    <span style="font-size:15px;font-weight:800"><?= money($o['total']) ?></span>
                    <span style="padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;background:<?=$sc?>1a;color:<?=$sc?>"><?= orderStatusLabel($o['status']??'pending') ?></span>
                    <a href="<?= url('account/orders/'.$o['id']) ?>" style="font-size:12px;color:var(--accent);font-weight:500">تفاصيل ←</a>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

      <?php elseif ($tab === 'warranties'): ?>
        <h2 style="font-size:18px;font-weight:700;margin-bottom:16px">🛡️ ضماناتي</h2>
        <?php if (empty($warranties)): ?>
          <div style="background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:52px;text-align:center">
            <div style="font-size:48px;margin-bottom:14px">🛡️</div>
            <div style="font-weight:600;font-size:16px;margin-bottom:8px">لسه ما فعّلتش أي ضمان</div>
            <a href="<?= url('warranty') ?>" class="btn btn-primary" style="margin-top:10px">فعّل ضمان منتجك ←</a>
          </div>
        <?php else: $statusMap = ['active'=>['✅','ساري','var(--green,#16a34a)'],'expired'=>['⏰','منتهي','var(--red,#ef4444)'],'void'=>['❌','ملغي','var(--red,#ef4444)'],'claimed'=>['🔧','تم استخدامه','#d97706']]; ?>
          <div style="display:flex;flex-direction:column;gap:12px">
            <?php foreach ($warranties as $w): [$icon,$label,$color] = $statusMap[$w['status']] ?? ['❓',$w['status'],'var(--text)']; ?>
              <div style="background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:18px;display:flex;align-items:center;gap:14px;flex-wrap:wrap">
                <?php if ($w['thumbnail']): ?><img src="<?= uploadUrl($w['thumbnail']) ?>" style="width:52px;height:52px;border-radius:10px;object-fit:cover;flex-shrink:0"><?php else: ?><div style="width:52px;height:52px;border-radius:10px;background:var(--bg2);display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0">🛡️</div><?php endif; ?>
                <div style="flex:1;min-width:180px">
                  <div style="font-weight:700;font-size:14px"><?= e($w['product_name'] ?: 'منتج') ?></div>
                  <div style="font-size:11.5px;color:var(--text2);margin-top:2px">رقم تسلسلي: <?= e($w['serial_number']) ?></div>
                  <div style="font-size:11.5px;color:var(--text2)">ينتهي في: <?= e($w['expiry_date']) ?></div>
                </div>
                <span style="font-weight:700;color:<?= $color ?>;font-size:13px"><?= $icon ?> <?= $label ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

      <?php elseif ($tab === 'wishlist'): ?>
        <h2 style="font-size:18px;font-weight:700;margin-bottom:16px">المفضلة</h2>
        <?php if (empty($wishlist)): ?>
          <div style="background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:52px;text-align:center">
            <div style="font-size:48px;margin-bottom:14px">🤍</div>
            <div style="font-weight:600;font-size:16px;margin-bottom:18px">قائمة المفضلة فارغة</div>
            <a href="<?= url('products') ?>" class="btn btn-primary">استكشف المنتجات</a>
          </div>
        <?php else: ?>
          <div class="products-grid">
            <?php foreach ($wishlist as $p): include APP_PATH . '/Views/store/partials/product-card.php'; endforeach; ?>
          </div>
        <?php endif; ?>

      <?php elseif ($tab === 'profile'): ?>
        <h2 style="font-size:18px;font-weight:700;margin-bottom:16px">الملف الشخصي</h2>
        <div style="background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:24px">
          <form method="POST" action="<?= url('account/profile') ?>">
            <?= csrf_field() ?>
            <div class="page-grid-form-2" style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px">
              <div>
                <label style="display:block;font-size:12px;font-weight:500;color:var(--text2);margin-bottom:5px">الاسم الكامل</label>
                <input type="text" name="name" value="<?= e($user['name']??'') ?>" required class="form-input" style="width:100%">
              </div>
              <div>
                <label style="display:block;font-size:12px;font-weight:500;color:var(--text2);margin-bottom:5px">رقم الهاتف</label>
                <input type="tel" name="phone" value="<?= e($user['phone']??'') ?>" class="form-input" style="width:100%">
              </div>
            </div>
            <div style="margin-bottom:18px">
              <label style="display:block;font-size:12px;font-weight:500;color:var(--text2);margin-bottom:5px">البريد الإلكتروني</label>
              <input type="email" value="<?= e($user['email']??'') ?>" disabled class="form-input" style="width:100%;opacity:.5;cursor:not-allowed">
            </div>
            <button type="submit" class="btn btn-primary">💾 حفظ التغييرات</button>
          </form>
        </div>
      <?php endif; ?>

    </div>
  </div>
</div>

<style>
.form-input{background:var(--surface);border:1px solid var(--border);border-radius:8px;color:var(--text);padding:9px 12px;font-size:13px;font-family:inherit;outline:none;transition:border-color .12s}
.form-input:focus{border-color:var(--accent);box-shadow:0 0 0 3px rgba(109,90,205,.1)}
</style>

<?php
$content = ob_get_clean();
require APP_PATH . '/Views/store/layouts/app.php';
