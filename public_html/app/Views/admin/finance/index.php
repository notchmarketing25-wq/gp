<?php
$pageTitle  = 'صافي الدخل';
$breadcrumb = [['label'=>'التحليلات','url'=>adminUrl('analytics')], ['label'=>'صافي الدخل']];
$a = ADMIN_PREFIX;
$monthLabel = date('F Y', strtotime($month.'-01'));
ob_start();
?>
<?php if (!empty($migrationMissing)): ?>
<div style="background:rgba(220,38,38,.08);border:1px solid rgba(220,38,38,.25);border-radius:10px;padding:16px 20px;margin-bottom:20px">
  <div style="font-weight:800;font-size:14px;color:var(--red);margin-bottom:6px">⚠️ نظام المحاسبة لسه مش مفعّل</div>
  <div style="font-size:12.5px;color:var(--t2);line-height:1.8">
    لازم تشغّل ملف <code style="background:var(--bg3);padding:2px 6px;border-radius:5px;direction:ltr;display:inline-block">database/accounting_migration.sql</code> على قاعدة البيانات الأول.
  </div>
</div>
<?php else: ?>
<div class="ph">
  <div class="ph-left"><h1><span style="display:inline-flex;align-items:center;gap:9px"><?= svgIcon('chart', 22) ?> صافي الدخل</span></h1><p>الإيرادات ناقص تكلفة المنتجات ناقص المصروفات — الصورة المالية الكاملة للشركة</p></div>
  <div class="ph-right">
    <form method="GET" style="display:flex;gap:8px">
      <input type="month" name="month" value="<?= e($month) ?>" class="form-input" onchange="this.form.submit()">
    </form>
  </div>
</div>

<?php if ($missingCostCount > 0): ?>
<div style="background:rgba(245,158,11,.1);border:1px solid rgba(245,158,11,.3);border-radius:10px;padding:12px 16px;margin-bottom:16px;font-size:12.5px;color:#b45309;display:flex;align-items:center;gap:10px">
  <?= svgIcon('zap', 16) ?> <?= $missingCostCount ?> عنصر مبيع في <?= $monthLabel ?> ملوش سعر تكلفة مسجّل — تكلفة البضاعة (COGS) تحت هنا أقل من الحقيقي. <a href="<?= adminUrl('products') ?>" style="color:#b45309;font-weight:700;text-decoration:underline">راجع أسعار التكلفة</a>
</div>
<?php endif; ?>

<!-- Waterfall: Revenue → COGS → Gross Profit → Expenses → Net Profit -->
<div class="card" style="margin-bottom:16px">
  <div class="card-body">
    <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:0;text-align:center">
      <div style="padding:16px 10px;border-left:1px solid var(--bd)">
        <div style="font-size:11px;color:var(--t3);font-weight:700;margin-bottom:6px">الإيرادات</div>
        <div style="font-size:19px;font-weight:900;font-family:var(--font-num)"><?= money($revenue) ?></div>
      </div>
      <div style="padding:16px 10px;border-left:1px solid var(--bd)">
        <div style="font-size:11px;color:var(--red);font-weight:700;margin-bottom:6px">− تكلفة المنتجات</div>
        <div style="font-size:19px;font-weight:900;font-family:var(--font-num);color:var(--red)"><?= money($cogs) ?></div>
      </div>
      <div style="padding:16px 10px;border-left:1px solid var(--bd);background:var(--bg3)">
        <div style="font-size:11px;color:var(--t3);font-weight:700;margin-bottom:6px">= الربح الإجمالي</div>
        <div style="font-size:19px;font-weight:900;font-family:var(--font-num)"><?= money($grossProfit) ?></div>
      </div>
      <div style="padding:16px 10px;border-left:1px solid var(--bd)">
        <div style="font-size:11px;color:var(--red);font-weight:700;margin-bottom:6px">− المصروفات</div>
        <div style="font-size:19px;font-weight:900;font-family:var(--font-num);color:var(--red)"><?= money($totalExpenses) ?></div>
      </div>
      <div style="padding:16px 10px">
        <div style="font-size:11px;color:var(--ac);font-weight:700;margin-bottom:6px">= صافي الربح</div>
        <div style="font-size:22px;font-weight:900;font-family:var(--font-num);color:<?= $netProfit>=0?'var(--green,#16a34a)':'var(--red)' ?>"><?= money($netProfit) ?></div>
        <div style="font-size:10.5px;color:var(--t3);margin-top:2px"><?= $netMargin ?>% هامش</div>
      </div>
    </div>
  </div>
</div>

<div class="page-grid-2" style="display:grid;grid-template-columns:1.4fr 1fr;gap:16px">
  <div class="card">
    <div class="card-header"><span class="card-title">اتجاه صافي الربح — آخر 6 شهور</span></div>
    <div class="card-body">
      <?php
      $vals = array_column($trend, 'net');
      $max = max(array_map('abs', $vals) ?: [1]) ?: 1;
      $pts = [];
      foreach ($trend as $i => $t) {
          $x = ($i / (count($trend)-1 ?: 1)) * 280;
          $y = 60 - (($t['net'] / $max) * 55);
          $pts[] = "$x,$y";
      }
      ?>
      <svg viewBox="0 0 280 75" style="width:100%;height:80px">
        <line x1="0" y1="60" x2="280" y2="60" stroke="var(--bd)" stroke-width="1"/>
        <polyline points="<?= implode(' ', $pts) ?>" fill="none" stroke="var(--ac)" stroke-width="2" stroke-linejoin="round"/>
      </svg>
      <div style="display:flex;justify-content:space-between;margin-top:8px">
        <?php foreach ($trend as $t): ?>
          <div style="font-size:10px;color:var(--t3)"><?= date('M', strtotime($t['month'].'-01')) ?></div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><span class="card-title">المصروفات حسب النوع</span></div>
    <div class="card-body">
      <?php if ($totalExpenses == 0): ?>
        <div style="text-align:center;color:var(--t3);padding:20px;font-size:12px">لا توجد مصروفات مسجّلة في <?= $monthLabel ?></div>
      <?php else: foreach ($expenses as $e): if ($e['total'] <= 0) continue; ?>
        <div style="display:flex;align-items:center;gap:10px;padding:7px 0">
          <div style="width:26px;height:26px;border-radius:7px;background:var(--bg3);display:flex;align-items:center;justify-content:center;color:var(--t2)"><?= svgIcon($e['icon']?:'tag', 13) ?></div>
          <div style="flex:1;font-size:12.5px;font-weight:600"><?= e($e['name']) ?></div>
          <div style="font-size:12.5px;font-weight:700"><?= money($e['total']) ?></div>
        </div>
      <?php endforeach; endif; ?>
      <a href="<?= adminUrl('expenses?month='.$month) ?>" class="btn btn-secondary btn-sm" style="width:100%;margin-top:12px">إدارة المصروفات ←</a>
    </div>
  </div>
</div>

<div class="card" style="margin-top:16px">
  <div class="card-header"><span class="card-title">تفاصيل الإيرادات</span></div>
  <div class="card-body" style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px">
    <div><div style="font-size:11px;color:var(--t3)">إجمالي المبيعات</div><div style="font-weight:700;font-size:15px"><?= money($revenue) ?></div></div>
    <div><div style="font-size:11px;color:var(--t3)">خصومات ممنوحة</div><div style="font-weight:700;font-size:15px;color:var(--red)"><?= money($discounts) ?></div></div>
    <div><div style="font-size:11px;color:var(--t3)">تكلفة الشحن</div><div style="font-weight:700;font-size:15px"><?= money($shipping) ?></div></div>
  </div>
</div>
<?php endif; ?>
<?php
$content = ob_get_clean();
require APP_PATH . '/Views/admin/layouts/app.php';
