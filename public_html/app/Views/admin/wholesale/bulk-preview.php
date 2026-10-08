<?php
$pageTitle  = 'معاينة طلبات الجملة';
$breadcrumb = [['label'=>'الجملة','url'=>adminUrl('wholesale')],['label'=>'معاينة الطلبات']];
$a = ADMIN_PREFIX;

$totalOrders = count($orders);
$totalItems  = array_sum(array_map(fn($o)=>count($o['items']), $orders));
$grandTotal  = array_sum(array_column($orders, 'subtotal'));
$withErrors  = count(array_filter($orders, fn($o)=>!empty($o['errors'])));
$newCustomers = count(array_filter($orders, fn($o)=>!$o['customer_found']));

ob_start();
?>
<div class="ph">
  <div class="ph-left"><h1><span style="display:inline-flex;align-items:center;gap:9px"><?= svgIcon('search', 22) ?> معاينة قبل الحفظ</span></h1><p>راجع البيانات كويس — لسه مفيش حاجة اتحفظت</p></div>
  <div class="ph-right"><a href="<?= adminUrl('wholesale/bulk-upload') ?>" class="btn btn-secondary">← رفع ملف تاني</a></div>
</div>

<div class="stats" style="grid-template-columns:repeat(4,1fr);margin-bottom:20px">
  <div class="stat"><div class="stat-icon" style="background:#E8F0FF;color:var(--blue)"><?= svgIcon('package', 20) ?></div><div class="stat-label">طلبات هتتنشئ</div><div class="stat-value"><?= $totalOrders ?></div></div>
  <div class="stat"><div class="stat-icon" style="background:#F0E8FF;color:var(--purple)"><?= svgIcon('receipt', 20) ?></div><div class="stat-label">إجمالي المنتجات</div><div class="stat-value"><?= $totalItems ?></div></div>
  <div class="stat"><div class="stat-icon" style="background:#E8FBF4;color:var(--green)"><?= svgIcon('store', 20) ?></div><div class="stat-label">القيمة الإجمالية</div><div class="stat-value" style="font-size:20px"><?= money($grandTotal) ?></div></div>
  <div class="stat"><div class="stat-icon" style="background:#FFF8E8;color:var(--yellow)"><?= svgIcon('shield', 20) ?></div><div class="stat-label">طلبات فيها تحذيرات</div><div class="stat-value" style="color:<?= $withErrors?'var(--yellow)':'var(--t1)' ?>"><?= $withErrors ?></div></div>
</div>

<?php if ($newCustomers > 0): ?>
  <div style="background:#E8F0FF;border:1px solid rgba(0,87,255,.2);border-radius:var(--radius);padding:14px 18px;margin-bottom:16px;font-size:13px">
    ℹ️ <?= $newCustomers ?> طلب لعملاء مش مسجلين كحسابات جملة معتمدة حالياً — الطلب هيتحفظ برضو ببياناتهم من الملف، بس بدون ربطه بحساب موجود.
  </div>
<?php endif; ?>

<div style="display:flex;flex-direction:column;gap:14px;margin-bottom:20px">
  <?php foreach ($orders as $o): ?>
    <div class="card">
      <div class="card-header">
        <div>
          <div class="card-title"><?= e($o['company'] ?: $o['name']) ?></div>
          <div style="font-size:11px;color:var(--t3);margin-top:2px">
            <?= e($o['email']) ?>
            <?php if ($o['customer_found']): ?>
              <span class="badge <?= $o['is_wholesale']?'badge-ok':'badge-dim' ?>" style="margin-right:6px"><?= $o['is_wholesale']?'✓ حساب جملة معتمد':'حساب موجود (مش جملة)' ?></span>
            <?php else: ?>
              <span class="badge badge-warn" style="margin-right:6px">عميل جديد</span>
            <?php endif; ?>
          </div>
        </div>
        <div style="font-family:var(--font-num);font-size:18px;font-weight:800"><?= money($o['subtotal']) ?></div>
      </div>
      <?php if (!empty($o['errors'])): ?>
        <div style="padding:10px 20px;background:#FFF0F0;color:var(--red);font-size:12px;border-bottom:1px solid var(--bd)">
          ⚠️ <?= implode(' — ', $o['errors']) ?>
        </div>
      <?php endif; ?>
      <?php if (!empty($o['items'])): ?>
        <div class="table-wrap">
          <table>
            <thead><tr><th>المنتج</th><th>SKU</th><th>الكمية</th><th>السعر</th><th>الإجمالي</th></tr></thead>
            <tbody>
              <?php foreach ($o['items'] as $it): ?>
                <tr>
                  <td style="font-weight:500;font-size:13px"><?= e($it['name']) ?> <?php if($it['wholesale']): ?><span class="badge badge-purple" style="margin-right:4px">جملة</span><?php endif; ?></td>
                  <td class="td-mono td-dim"><?= e($it['sku']) ?></td>
                  <td><?= $it['qty'] ?></td>
                  <td><?= money($it['price']) ?></td>
                  <td style="font-weight:700"><?= money($it['price']*$it['qty']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div style="padding:16px 20px;color:var(--t3);font-size:12px">لا توجد منتجات صالحة في هذا الطلب — هيتم تخطيه</div>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
</div>

<form method="POST" action="<?= adminUrl('wholesale/bulk-upload/confirm') ?>" style="position:sticky;bottom:16px">
  <?= csrf_field() ?>
  <input type="hidden" name="batch_id" value="<?= e($batchId) ?>">
  <div class="card" style="padding:16px 20px;display:flex;align-items:center;justify-content:space-between;box-shadow:var(--shadow-lg)">
    <div style="font-size:13px;color:var(--t2)">جاهز لإنشاء <strong style="color:var(--t1)"><?= $totalOrders ?></strong> طلب جملة بقيمة <strong style="color:var(--green)"><?= money($grandTotal) ?></strong></div>
    <button type="submit" class="btn btn-primary btn-lg" onclick="return confirm('تأكيد إنشاء <?= $totalOrders ?> طلب؟')"><?= svgIcon('check', 15) ?> تأكيد وإنشاء الطلبات</button>
  </div>
</form>

<?php $content = ob_get_clean(); require APP_PATH . '/Views/admin/layouts/app.php';
