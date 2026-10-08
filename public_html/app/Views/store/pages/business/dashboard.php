<?php
$pageTitle = 'الرئيسية';
$statusLabels = ['pending'=>['⏳','قيد المراجعة','badge-warn'],'approved'=>['✅','معتمد','badge-ok'],'rejected'=>['❌','مرفوض','badge-err']];
[$stIcon,$stLbl,$stClass] = $statusLabels[$customer['business_status']] ?? ['⏳','قيد المراجعة','badge-warn'];
ob_start();
?>

<?php if ($customer['business_status'] === 'pending'): ?>
  <div style="background:#FFF8E8;border:1px solid rgba(184,112,0,.2);border-radius:14px;padding:18px 22px;margin-bottom:20px;display:flex;align-items:center;gap:14px">
    <div style="font-size:26px">⏳</div>
    <div>
      <div style="font-weight:700;font-size:14px">حسابك قيد المراجعة</div>
      <div style="font-size:12.5px;color:var(--t2);margin-top:2px">هيتم تفعيل حسابك بمجرد اعتماده من فريق نوتش — هتقدر تعمل طلبات بعد التفعيل</div>
    </div>
  </div>
<?php elseif ($customer['business_status'] === 'rejected'): ?>
  <div style="background:#FFF0F0;border:1px solid rgba(255,59,59,.2);border-radius:14px;padding:18px 22px;margin-bottom:20px">
    <div style="font-weight:700;font-size:14px;color:var(--red)">❌ لم تتم الموافقة على حسابك</div>
    <div style="font-size:12.5px;color:var(--t2);margin-top:2px">تواصل مع الدعم لمزيد من التفاصيل</div>
  </div>
<?php endif; ?>

<?php if ($customer['business_type'] === 'rep' && empty($customer['id_card_image'])): ?>
  <div style="background:var(--accent-bg);border:1px solid rgba(var(--accent-rgb),.25);border-radius:14px;padding:18px 22px;margin-bottom:20px;display:flex;align-items:center;gap:14px;flex-wrap:wrap">
    <div style="width:44px;height:44px;border-radius:12px;background:var(--accent);color:#fff;display:flex;align-items:center;justify-content:center;flex-shrink:0"><?= svgIcon('user', 20) ?></div>
    <div style="flex:1;min-width:200px">
      <div style="font-weight:700;font-size:14px">ارفع بطاقتك الشخصية</div>
      <div style="font-size:12.5px;color:var(--t2);margin-top:2px">مطلوب رفع صورة البطاقة عشان تفعيل حسابك كمندوب بشكل كامل</div>
    </div>
    <button class="btn btn-primary btn-sm" onclick="document.getElementById('idCardModal').classList.add('open')">رفع البطاقة</button>
  </div>

  <div class="modal-overlay" id="idCardModal">
    <div class="modal">
      <div class="modal-head"><h3>رفع البطاقة الشخصية</h3><button class="btn btn-ghost btn-icon" onclick="document.getElementById('idCardModal').classList.remove('open')" style="font-size:18px">×</button></div>
      <form method="POST" action="<?= url('business/id-card') ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="form-group">
          <label class="form-label">رقم البطاقة <span>*</span></label>
          <input type="text" name="id_card_number" class="form-input" required>
        </div>
        <div class="form-group">
          <label class="form-label">صورة البطاقة <span>*</span></label>
          <input type="file" name="id_card_image" class="form-input" accept="image/*" required style="padding:8px">
        </div>
        <div class="modal-actions">
          <button type="button" class="btn btn-secondary" onclick="document.getElementById('idCardModal').classList.remove('open')">إلغاء</button>
          <button type="submit" class="btn btn-primary">رفع</button>
        </div>
      </form>
    </div>
  </div>
<?php endif; ?>

<div class="stats">
  <div class="stat">
    <div class="stat-icon" style="background:rgba(34,197,94,.12);color:var(--green,#16a34a)"><?= svgIcon('star', 18) ?></div>
    <div class="stat-value"><?= money($customer['store_credit'] ?? 0) ?></div>
    <div class="stat-label">رصيد محفظتك (N&Credit)</div>
  </div>
  <div class="stat">
    <div class="stat-icon" style="background:var(--acb);color:var(--ac)">🛒</div>
    <div class="stat-value"><?= number_format($customer['total_orders']??0) ?></div>
    <div class="stat-label">إجمالي الطلبات</div>
  </div>
  <div class="stat">
    <div class="stat-icon" style="background:#E8FBF4;color:var(--green)">💰</div>
    <div class="stat-value" style="font-size:18px"><?= money($customer['total_spent']??0) ?></div>
    <div class="stat-label">إجمالي المشتريات</div>
  </div>
  <div class="stat">
    <div class="stat-icon" style="background:#F0E8FF;color:#7B2FBE">🏷️</div>
    <div class="stat-value" style="font-size:16px"><?= $tier ? e($tier['name']) : 'غير محدد' ?></div>
    <div class="stat-label">شريحتك السعرية <?= $tier ? '(-'.$tier['discount_percent'].'%)' : '' ?></div>
  </div>
  <div class="stat">
    <div class="stat-icon" style="background:#E8F0FF;color:#0057FF">💳</div>
    <div class="stat-value" style="font-size:18px"><?= money($customer['credit_limit']??0) ?></div>
    <div class="stat-label">حد N&Credit — <?= $statusLabels[$customer['credit_status']][1] ?? 'لم يُطلب' ?></div>
  </div>
</div>

<div style="display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap">
  <a href="<?= url('business/orders/create') ?>" class="btn btn-primary">🛒 طلب جديد</a>
  <a href="<?= url('business/products') ?>" class="btn btn-secondary">📦 تصفح المنتجات</a>
  <a href="<?= url('business/catalog') ?>" class="btn btn-secondary">📥 تحميل الكاتالوج</a>
</div>

<div class="card" style="margin-bottom:20px">
  <div class="card-header"><span class="card-title">آخر الطلبات</span></div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>رقم الطلب</th><th>المبلغ</th><th>الحالة</th><th>التاريخ</th></tr></thead>
      <tbody>
        <?php if (empty($orders)): ?>
          <tr><td colspan="4" style="text-align:center;color:var(--t3);padding:24px">لا توجد طلبات بعد</td></tr>
        <?php else: foreach ($orders as $o): ?>
          <tr>
            <td style="font-family:monospace;font-weight:700;color:var(--ac)"><?= e($o['order_number']) ?></td>
            <td style="font-weight:700"><?= money($o['total']) ?></td>
            <td><span class="badge badge-<?= $o['status']==='delivered'?'ok':($o['status']==='cancelled'?'err':'warn') ?>"><?= orderStatusLabel($o['status']) ?></span></td>
            <td style="color:var(--t3);font-size:12px"><?= formatDate($o['created_at']) ?></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if ($customer['business_type'] === 'rep'): ?>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
  <div class="card">
    <div class="card-header"><span class="card-title">🚕 آخر المصروفات</span></div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>النوع</th><th>المبلغ</th><th>الحالة</th></tr></thead>
        <tbody>
          <?php if (empty($expenses)): ?><tr><td colspan="3" style="text-align:center;color:var(--t3);padding:20px">لا توجد مصروفات</td></tr>
          <?php else: foreach ($expenses as $ex): ?>
            <tr><td><?= e($ex['type']) ?></td><td style="font-weight:700"><?= money($ex['amount']) ?></td>
            <td><span class="badge <?= $ex['status']==='approved'?'badge-ok':($ex['status']==='rejected'?'badge-err':'badge-warn') ?>"><?= ['pending'=>'قيد المراجعة','approved'=>'معتمد','rejected'=>'مرفوض'][$ex['status']] ?></span></td></tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="card">
    <div class="card-header"><span class="card-title">🎁 آخر طلبات العينات</span></div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>المنتج</th><th>الكمية</th><th>الحالة</th></tr></thead>
        <tbody>
          <?php if (empty($samples)): ?><tr><td colspan="3" style="text-align:center;color:var(--t3);padding:20px">لا توجد طلبات</td></tr>
          <?php else: foreach ($samples as $sm): ?>
            <tr><td><?= e($sm['product_name']) ?></td><td><?= $sm['qty'] ?></td>
            <td><span class="badge <?= $sm['status']==='approved'?'badge-ok':($sm['status']==='rejected'?'badge-err':'badge-warn') ?>"><?= ['pending'=>'قيد المراجعة','approved'=>'معتمد','rejected'=>'مرفوض','delivered'=>'تم التسليم'][$sm['status']] ?></span></td></tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php endif; ?>

<?php $content = ob_get_clean(); require APP_PATH . '/Views/store/layouts/business.php';
