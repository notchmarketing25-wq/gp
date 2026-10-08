<?php
$pageTitle = 'نسبتي';
$statusLabels = ['pending'=>'قيد الانتظار','processing'=>'قيد المعالجة','shipped'=>'تم الشحن','delivered'=>'تم التسليم','cancelled'=>'ملغي'];
ob_start();
?>
<div class="commission-hero">
  <div class="commission-hero-item">
    <div class="commission-hero-label">نسبتك الحالية</div>
    <div class="commission-hero-value"><?= e($customer['commission_percent'] ?? 0) ?>%</div>
    <div class="commission-hero-sub">من قيمة كل فاتورة تعملها</div>
  </div>
  <div class="commission-hero-divider"></div>
  <div class="commission-hero-item">
    <div class="commission-hero-label">إجمالي العمولة</div>
    <div class="commission-hero-value"><?= money($totalCommission) ?></div>
    <div class="commission-hero-sub">من كل الفواتير</div>
  </div>
  <div class="commission-hero-divider"></div>
  <div class="commission-hero-item">
    <div class="commission-hero-label">عمولة هذا الشهر</div>
    <div class="commission-hero-value"><?= money($monthCommission) ?></div>
    <div class="commission-hero-sub"><?= date('F Y') ?></div>
  </div>
</div>

<?php if ((float)($customer['commission_percent'] ?? 0) === 0.0): ?>
  <div style="background:var(--bg3);border-radius:12px;padding:20px;text-align:center;color:var(--t3);font-size:13px;margin-bottom:16px">
    لسه مفيش نسبة عمولة محددة لحسابك — تواصل مع الإدارة لتحديدها
  </div>
<?php endif; ?>

<div class="card">
  <div class="card-header"><span class="card-title">تفاصيل العمولة لكل فاتورة</span></div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>رقم الطلب</th><th>العميل</th><th>قيمة الفاتورة</th><th>النسبة</th><th>عمولتك</th><th>الحالة</th><th>التاريخ</th></tr></thead>
      <tbody>
        <?php if (empty($orders)): ?>
          <tr><td colspan="7" style="text-align:center;color:var(--t3);padding:40px">لسه مفيش عمولات مسجلة</td></tr>
        <?php else: foreach ($orders as $o): ?>
          <tr>
            <td style="font-family:monospace;font-weight:700;color:var(--ac)"><?= e($o['order_number']) ?></td>
            <td><?= e($o['customer_name']) ?></td>
            <td><?= money($o['total']) ?></td>
            <td class="td-dim"><?= e($o['rep_commission_percent']) ?>%</td>
            <td style="font-weight:800;color:var(--green,#16a34a)"><?= money($o['rep_commission_amount']) ?></td>
            <td><span class="badge badge-dim"><?= $statusLabels[$o['status']] ?? $o['status'] ?></span></td>
            <td class="td-dim" style="font-size:12px"><?= formatDate($o['created_at']) ?></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<style>
.commission-hero{display:grid;grid-template-columns:1fr auto 1fr auto 1fr;align-items:center;gap:20px;background:#0A0A0A;border-radius:16px;padding:26px 30px;margin-bottom:20px;color:#fff}
.commission-hero-label{font-size:11px;color:rgba(255,255,255,.55);font-weight:600;margin-bottom:6px}
.commission-hero-value{font-size:28px;font-weight:900;font-family:var(--font-num,inherit)}
.commission-hero-sub{font-size:11px;color:rgba(255,255,255,.5);margin-top:4px}
.commission-hero-divider{width:1px;height:50px;background:rgba(255,255,255,.15)}
@media(max-width:700px){
  .commission-hero{grid-template-columns:1fr;text-align:center;gap:14px;padding:20px}
  .commission-hero-divider{width:100%;height:1px}
}
</style>
<?php
$content = ob_get_clean();
require APP_PATH . '/Views/store/layouts/business.php';
