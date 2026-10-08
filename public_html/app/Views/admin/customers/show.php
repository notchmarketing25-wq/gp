<?php
$pageTitle  = e($customer['name']);
$breadcrumb = [['label'=>'العملاء','url'=>adminUrl('customers')],['label'=>$customer['name']]];
$a = ADMIN_PREFIX;
ob_start();
?>
<div class="ph">
  <div class="ph-left">
    <h1><?= e($customer['name']) ?></h1>
    <p>
      عضو منذ <?= formatDate($customer['created_at']) ?>
      <?php if (($customer['account_type']??'retail')==='wholesale'): ?>
        · <span class="badge <?= match($customer['wholesale_status']){'approved'=>'badge-ok','rejected'=>'badge-err',default=>'badge-warn'} ?>">
          🏬 <?= match($customer['wholesale_status']){'approved'=>'حساب جملة معتمد','rejected'=>'طلب جملة مرفوض','pending'=>'طلب جملة قيد المراجعة',default=>''} ?>
        </span>
      <?php endif; ?>
    </p>
  </div>
  <div class="ph-right">
    <a href="<?= adminUrl('customers') ?>" class="btn btn-secondary">← العملاء</a>
    <form method="POST" action="<?= adminUrl('customers/'.$customer['id'].'/toggle') ?>" style="display:inline">
      <?= csrf_field() ?>
      <button type="submit" class="btn <?= $customer['is_active'] ? 'btn-danger' : 'btn-primary' ?>">
        <?= $customer['is_active'] ? '🚫 إيقاف' : '✅ تفعيل' ?>
      </button>
    </form>
  </div>
</div>

<?php if (($customer['account_type']??'retail')==='wholesale' && $customer['wholesale_status']==='pending'): ?>
  <div style="background:#FFF8E8;border:1px solid rgba(184,112,0,.2);border-radius:var(--radius);padding:16px 20px;margin-bottom:18px;display:flex;align-items:center;gap:14px;flex-wrap:wrap">
    <div style="font-size:24px">⏳</div>
    <div style="flex:1;min-width:200px">
      <div style="font-weight:700;font-size:13.5px">هذا العميل قدّم طلب حساب جملة وينتظر المراجعة</div>
      <div style="font-size:12px;color:var(--t2);margin-top:2px"><?= e($customer['company_name'] ?? '') ?></div>
    </div>
    <div style="display:flex;gap:8px">
      <form method="POST" action="<?= adminUrl('customers/'.$customer['id'].'/wholesale/approve') ?>"><?= csrf_field() ?><button class="btn" style="background:var(--green);color:#fff"><?= svgIcon('check', 15) ?> اعتماد الحساب</button></form>
      <form method="POST" action="<?= adminUrl('customers/'.$customer['id'].'/wholesale/reject') ?>"><?= csrf_field() ?><button class="btn btn-danger"><?= svgIcon('close', 15) ?> رفض</button></form>
    </div>
  </div>
<?php endif; ?>

<!-- Top stats row (Shopify-style) -->
<div class="stats" style="grid-template-columns:repeat(4,1fr)">
  <div class="stat">
    <div class="stat-label">إجمالي الإنفاق</div>
    <div class="stat-value" style="font-size:20px"><?= money(array_sum(array_column($orders,'total'))) ?></div>
  </div>
  <div class="stat">
    <div class="stat-label">الطلبات</div>
    <div class="stat-value"><?= count($orders) ?></div>
  </div>
  <div class="stat">
    <div class="stat-label">عميل منذ</div>
    <div class="stat-value" style="font-size:16px;font-family:var(--font-body,inherit)"><?= formatDate($customer['created_at']) ?></div>
  </div>
  <div class="stat">
    <div class="stat-label">رصيد N&Credit</div>
    <div class="stat-value" style="font-size:20px;color:<?= ($customer['store_credit']??0)>0?'var(--green)':'var(--t1)' ?>"><?= money($customer['store_credit']??0) ?></div>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 300px;gap:16px;align-items:start">

  <!-- Left: Last order + full history -->
  <div style="display:flex;flex-direction:column;gap:14px">

    <?php if (empty($orders)): ?>
      <div class="card">
        <div class="card-header"><span class="card-title">آخر طلب</span></div>
        <div style="padding:40px 20px;text-align:center">
          <div style="font-size:40px;margin-bottom:10px">🛒</div>
          <div style="font-weight:600;margin-bottom:14px">لم يقم هذا العميل بأي طلب بعد</div>
          <a href="<?= adminUrl('orders') ?>" class="btn btn-primary btn-sm">إنشاء طلب</a>
        </div>
      </div>
    <?php else: ?>
      <div class="card">
        <div class="card-header"><span class="card-title">سجل الطلبات (<?= count($orders) ?>)</span></div>
        <div class="table-wrap">
          <table>
            <thead><tr><th>رقم الطلب</th><th>المبلغ</th><th>الدفع</th><th>الحالة</th><th>التاريخ</th></tr></thead>
            <tbody>
              <?php foreach ($orders as $o): ?>
                <tr>
                  <td><a href="<?= adminUrl('orders/'.$o['id']) ?>" style="color:var(--ac);font-weight:700;font-family:monospace"><?= e($o['order_number']) ?></a></td>
                  <td style="font-weight:700"><?= money($o['total']) ?></td>
                  <td><span class="badge <?= ($o['payment_status']==='paid')?'badge-ok':(($o['payment_status']==='failed')?'badge-err':'badge-warn') ?>"><?= paymentStatusLabel($o['payment_status']??'unpaid') ?></span></td>
                  <td><span class="badge badge-<?= orderStatusColor($o['status']??'pending') ?>"><?= orderStatusLabel($o['status']??'pending') ?></span></td>
                  <td class="td-dim"><?= formatDate($o['created_at']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php endif; ?>

    <!-- Recently viewed products (browsing activity) -->
    <?php if (!empty($recentViews)): ?>
    <div class="card">
      <div class="card-header"><span class="card-title"><?= svgIcon('search', 15) ?> آخر المنتجات اللي شافها</span></div>
      <div class="table-wrap">
        <table>
          <thead><tr><th>المنتج</th><th>عدد المشاهدات</th><th>آخر مشاهدة</th></tr></thead>
          <tbody>
            <?php foreach ($recentViews as $rv): ?>
              <tr>
                <td>
                  <div style="display:flex;align-items:center;gap:8px">
                    <?php if($rv['thumbnail']): ?><img src="<?= uploadUrl($rv['thumbnail']) ?>" style="width:28px;height:28px;object-fit:cover;border-radius:6px"><?php endif; ?>
                    <a href="<?= adminUrl('products/'.$rv['id']) ?>" style="font-weight:600;color:var(--t1)"><?= e($rv['name']) ?></a>
                  </div>
                </td>
                <td><?= $rv['view_count'] ?></td>
                <td class="td-dim" style="font-size:12px"><?= timeAgo($rv['last_viewed']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>

    <!-- N& Credit history -->
    <div class="card">
      <div class="card-header">
        <span class="card-title"><?= svgIcon('star', 15) ?> سجل رصيد N&Credit</span>
        <button class="btn btn-primary btn-sm" onclick="document.getElementById('creditModal').classList.add('open')">تعديل الرصيد</button>
      </div>
      <?php if (empty($creditHistory)): ?>
        <div style="padding:24px;text-align:center;color:var(--t3);font-size:12px">لا توجد حركات على الرصيد بعد</div>
      <?php else: ?>
        <div class="table-wrap">
          <table>
            <thead><tr><th>الحركة</th><th>السبب</th><th>الرصيد بعدها</th><th>التاريخ</th></tr></thead>
            <tbody>
              <?php foreach ($creditHistory as $t): ?>
                <tr>
                  <td style="font-weight:700;color:<?= $t['amount']>=0?'var(--green)':'var(--red)' ?>"><?= $t['amount']>=0?'+':'' ?><?= money($t['amount']) ?></td>
                  <td class="td-dim" style="font-size:12px"><?= e($t['reason']??'—') ?></td>
                  <td style="font-weight:600"><?= money($t['balance_after']) ?></td>
                  <td class="td-dim" style="font-size:11px"><?= formatDateTime($t['created_at']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

  </div>

  <!-- Right: Info sidebar -->
  <div style="display:flex;flex-direction:column;gap:12px">

    <!-- Contact info -->
    <div class="card">
      <div class="card-header"><span class="card-title">بيانات الاتصال</span></div>
      <div class="card-body">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;padding-bottom:14px;border-bottom:1px solid var(--bd)">
          <div style="width:44px;height:44px;border-radius:50%;background:var(--acb);border:2px solid var(--ac);display:flex;align-items:center;justify-content:center;font-size:18px;font-weight:700;color:var(--ac);flex-shrink:0">
            <?= mb_substr($customer['name'],0,1) ?>
          </div>
          <div>
            <div style="font-weight:600;font-size:14px"><?= e($customer['name']) ?></div>
            <span class="badge <?= $customer['is_active']?'badge-ok':'badge-err' ?>" style="margin-top:3px"><?= $customer['is_active']?'نشط':'موقوف' ?></span>
          </div>
        </div>
        <?php foreach([
          ['📧','البريد', $customer['email']],
          ['📱','الهاتف', $customer['phone']??'—'],
        ] as [$icon,$label,$val]): ?>
          <div style="display:flex;gap:10px;padding:8px 0;border-bottom:1px solid var(--bd);font-size:12px">
            <span><?=$icon?></span>
            <span style="color:var(--t2);min-width:60px"><?=$label?></span>
            <span style="font-weight:500;word-break:break-all"><?= e($val) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Default address -->
    <div class="card">
      <div class="card-header"><span class="card-title">العنوان الافتراضي</span></div>
      <div class="card-body">
        <?php if ($address): ?>
          <div style="font-size:12.5px;line-height:2;color:var(--t1)">
            <strong><?= e($address['name']) ?></strong><br>
            <?= e($address['address']) ?><br>
            <?= e($address['city']) ?>، <?= e($address['governorate']) ?><br>
            📱 <?= e($address['phone']) ?>
          </div>
        <?php else: ?>
          <div style="font-size:12px;color:var(--t3)">لا يوجد عنوان محفوظ</div>
        <?php endif; ?>
      </div>
    </div>

    <!-- N& Credit balance card -->
    <div class="card" style="background:linear-gradient(135deg,var(--ac),rgba(var(--acr),.7));border:none">
      <div class="card-body" style="color:#fff">
        <div style="font-size:11px;opacity:.85;text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px">💳 رصيد N&Credit</div>
        <div style="font-size:26px;font-weight:900;margin-bottom:14px"><?= money($customer['store_credit']??0) ?></div>
        <button class="btn btn-full" style="background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.3)" onclick="document.getElementById('creditModal').classList.add('open')">
          ✏️ تعديل الرصيد
        </button>
      </div>
    </div>

    <?php if (($customer['account_type']??'retail')==='wholesale'): ?>
    <div class="card">
      <div class="card-header"><span class="card-title"><?= svgIcon('store', 15) ?> بيانات النشاط التجاري</span></div>
      <div class="card-body">
        <?php foreach([
          ['اسم الشركة/المحل', $customer['company_name'] ?? '—'],
          ['العنوان',          $customer['company_address'] ?? '—'],
          ['الرقم الضريبي',    $customer['tax_number'] ?? '—'],
        ] as [$label,$val]): ?>
          <div style="padding:8px 0;border-bottom:1px solid var(--bd);font-size:12px">
            <div style="color:var(--t3);margin-bottom:2px"><?=$label?></div>
            <div style="font-weight:500"><?= e($val) ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

  </div>
</div>

<!-- Credit adjustment modal -->
<div class="modal-overlay" id="creditModal">
  <div class="modal">
    <div class="modal-head">
      <h3>💳 تعديل رصيد N&Credit</h3>
      <button class="btn btn-ghost btn-icon" onclick="document.getElementById('creditModal').classList.remove('open')" style="font-size:18px">×</button>
    </div>
    <p style="margin-bottom:14px">الرصيد الحالي: <strong style="color:var(--t1)"><?= money($customer['store_credit']??0) ?></strong></p>
    <form method="POST" action="<?= adminUrl('customers/'.$customer['id'].'/credit') ?>">
      <?= csrf_field() ?>
      <div class="form-group" style="margin-bottom:12px">
        <label class="form-label">نوع العملية</label>
        <select name="type" class="form-select">
          <option value="add">➕ إضافة رصيد</option>
          <option value="deduct">➖ خصم رصيد</option>
        </select>
      </div>
      <div class="form-group" style="margin-bottom:12px">
        <label class="form-label">المبلغ</label>
        <input type="number" name="amount" class="form-input" step="0.01" min="0.01" required autofocus>
      </div>
      <div class="form-group" style="margin-bottom:18px">
        <label class="form-label">السبب</label>
        <input type="text" name="reason" class="form-input" placeholder="مثال: تعويض عن تأخير الشحن">
      </div>
      <div class="modal-actions">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('creditModal').classList.remove('open')">إلغاء</button>
        <button type="submit" class="btn btn-primary"><?= svgIcon('check', 15) ?> حفظ</button>
      </div>
    </form>
  </div>
</div>

<?php $content = ob_get_clean(); require APP_PATH . '/Views/admin/layouts/app.php';
