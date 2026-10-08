<?php
$storeName = SettingModel::get('store_name', APP_NAME);
$logo = logoUrl('dark');
$color = SettingModel::get('primary_color', '#0057FF') ?: '#0057FF';
$discountPct = $tier ? (float)$tier['discount_percent'] : 0;
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>كاتالوج الأسعار — <?= e($customer['shop_name'] ?: $customer['name']) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Cairo',Tahoma,Arial,sans-serif;color:#0A0A0A;padding:26px;background:#fff;font-variant-numeric:tabular-nums}
.header{display:flex;align-items:center;justify-content:space-between;background:#0A0A0A;color:#fff;padding:22px 28px;border-radius:14px;margin-bottom:18px}
.header img{height:38px;max-width:180px;object-fit:contain}
.header h1{font-size:19px;font-weight:800}
.header .sub{font-size:11px;opacity:.65;margin-top:3px}
.meta{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;background:#F8F8F8;border:1px solid #EEE;padding:16px 20px;border-radius:12px;margin-bottom:20px}
.meta-item b{display:block;color:<?= $color ?>;font-size:10.5px;font-weight:800;text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px}
.meta-item span{font-size:14px;font-weight:700}
table{width:100%;border-collapse:collapse;font-variant-numeric:tabular-nums}
thead th{background:<?= $color ?>;color:#fff;padding:11px 14px;font-size:11.5px;font-weight:700;text-align:right;letter-spacing:.02em}
thead th:last-child, tbody td:last-child{text-align:left}
tbody td{padding:10px 14px;font-size:13px;border-bottom:1px solid #EEE;vertical-align:middle}
tbody tr:nth-child(even){background:#FAFAFA}
.prodname{font-weight:800;font-size:13.5px}
.sku{color:#999;font-size:11px;font-family:monospace}
.prod-img{width:52px;height:52px;object-fit:contain;border-radius:8px;border:1px solid #EEE;background:#FAFAFA;flex-shrink:0}
.prod-cell{display:flex;align-items:center;gap:10px}
.qty-range{color:#555;font-weight:600}
.price{color:<?= $color ?>;font-weight:900;font-size:15px;letter-spacing:-.01em}
.currency{font-size:11px;font-weight:600;opacity:.7}
.print-btn{position:fixed;top:18px;left:18px;background:<?= $color ?>;color:#fff;border:none;padding:11px 22px;border-radius:9px;font-size:13px;font-weight:700;cursor:pointer;box-shadow:0 4px 14px rgba(0,0,0,.2);z-index:10}
.footer-note{margin-top:20px;text-align:center;font-size:11px;color:#999}
@media print{
  .print-btn{display:none}
  body{padding:0}
  .header{border-radius:0}
  table{page-break-inside:auto}
  tr{page-break-inside:avoid;page-break-after:auto}
  thead{display:table-header-group}
}
@media(max-width:640px){
  body{padding:14px}
  .print-btn{position:static;width:100%;margin-bottom:14px}
  .header{flex-direction:column;align-items:flex-start;gap:10px;padding:16px}
  .header img{height:30px}
  .meta{grid-template-columns:1fr;gap:8px;padding:14px}
  table,thead,tbody,tr{display:block;width:100%}
  thead{display:none}
  tbody tr{border:1px solid #EEE;border-radius:10px;margin-bottom:10px;padding:10px;background:#fff !important}
  tbody td{display:block;border-bottom:none;padding:4px 0}
  tbody td[rowspan]{padding-bottom:8px;border-bottom:1px solid #EEE;margin-bottom:6px}
  .prod-img{width:44px;height:44px}
  .qty-range::before{content:'الكمية: ';color:#999;font-weight:400}
  .price::before{content:'السعر: ';color:#999;font-weight:400;font-size:11px}
}
</style>
</head>
<body>
<button class="print-btn" onclick="window.print()">🖨️ طباعة / حفظ PDF</button>
<div class="header">
  <div><h1><?= e($storeName) ?></h1><div class="sub">كاتالوج أسعار الجملة الخاص</div></div>
  <?php if ($logo): ?><img src="<?= $logo ?>"><?php endif; ?>
</div>
<div class="meta">
  <div class="meta-item"><b>التاجر / المحل</b><span><?= e($customer['shop_name'] ?: $customer['name']) ?></span></div>
  <div class="meta-item"><b>الشريحة السعرية</b><span><?= $tier ? e($tier['name']) : 'حسب الكمية' ?></span></div>
  <div class="meta-item"><b>تاريخ الإصدار</b><span><?= date('Y-m-d') ?></span></div>
</div>
<table>
  <thead><tr><th>المنتج</th><th>الكمية</th><th>سعر القطعة</th></tr></thead>
  <tbody>
    <?php foreach ($products as $p): ?>
      <?php if ($p['has_tiers']): foreach ($p['tiers'] as $i => $t):
          $next = $p['tiers'][$i+1]['min_qty'] ?? null;
          $range = $next ? (number_format($t['min_qty']).' - '.number_format($next-1)) : (number_format($t['min_qty']).'+');
      ?>
        <tr>
          <?php if ($i === 0): ?>
            <td rowspan="<?= count($p['tiers']) ?>">
              <div class="prod-cell">
                <?php if ($p['thumbnail']): ?><img src="<?= uploadUrl($p['thumbnail']) ?>" class="prod-img"><?php endif; ?>
                <div>
                  <div class="prodname"><?= e($p['name']) ?></div>
                  <?php if($p['sku']): ?><div class="sku"><?= e($p['sku']) ?></div><?php endif; ?>
                </div>
              </div>
            </td>
          <?php endif; ?>
          <td class="qty-range"><?= $range ?> قطعة</td>
          <td class="price"><?= number_format($t['price'],2) ?> <span class="currency"><?= APP_CURRENCY_SYMBOL ?></span></td>
        </tr>
      <?php endforeach; else: ?>
        <tr>
          <td>
            <div class="prod-cell">
              <?php if ($p['thumbnail']): ?><img src="<?= uploadUrl($p['thumbnail']) ?>" class="prod-img"><?php endif; ?>
              <div>
                <div class="prodname"><?= e($p['name']) ?></div>
                <?php if($p['sku']): ?><div class="sku"><?= e($p['sku']) ?></div><?php endif; ?>
              </div>
            </div>
          </td>
          <td class="qty-range">أي كمية</td>
          <td class="price"><?= number_format($p['tier_price'],2) ?> <span class="currency"><?= APP_CURRENCY_SYMBOL ?></span></td>
        </tr>
      <?php endif; ?>
    <?php endforeach; ?>
  </tbody>
</table>
<div class="footer-note">© <?= date('Y') ?> <?= e($storeName) ?> — الأسعار قابلة للتغيير بدون إشعار مسبق</div>
</body>
</html>
