<?php
$pageTitle  = 'سجل حركات المخزون';
$breadcrumb = [['label'=>'المخزن','url'=>adminUrl('warehouse')],['label'=>'سجل الحركات']];
$a = ADMIN_PREFIX;
ob_start();
?>
<div class="ph">
  <div class="ph-left"><h1>سجل حركات المخزون</h1><p><?= number_format($total) ?> حركة</p></div>
  <div class="ph-right"><a href="<?= adminUrl('warehouse') ?>" class="btn btn-secondary">← المخزن</a></div>
</div>
<div class="card">
  <div style="padding:12px 14px;border-bottom:1px solid var(--bd)">
    <form method="GET" class="filter-bar">
      <select name="type" class="filter-select" onchange="this.form.submit()">
        <option value="">كل الأنواع</option>
        <option value="in"         <?= $type==='in'?'selected':''         ?>>➕ وارد</option>
        <option value="out"        <?= $type==='out'?'selected':''        ?>>➖ صادر</option>
        <option value="adjustment" <?= $type==='adjustment'?'selected':'' ?>>🔄 تسوية</option>
        <option value="transfer"   <?= $type==='transfer'?'selected':''   ?>>📤 تحويل</option>
      </select>
    </form>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>المنتج</th><th>SKU</th><th>النوع</th><th>الكمية</th><th>قبل</th><th>بعد</th><th>السبب</th><th>التاريخ</th></tr></thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr><td colspan="8" style="text-align:center;color:var(--t3);padding:28px">لا توجد حركات</td></tr>
        <?php else: foreach ($rows as $m): ?>
          <tr>
            <td style="font-weight:500;font-size:13px"><?= e($m['product_name']) ?></td>
            <td class="td-mono td-dim"><?= e($m['sku']??'—') ?></td>
            <td><span class="badge <?= match($m['type']){'in'=>'badge-ok','out'=>'badge-err','adjustment'=>'badge-warn',default=>'badge-info'} ?>"><?= match($m['type']){'in'=>'وارد','out'=>'صادر','adjustment'=>'تسوية','transfer'=>'تحويل',default=>$m['type']} ?></span></td>
            <td style="font-weight:700;color:<?= $m['type']==='in'?'var(--green)':($m['type']==='out'?'var(--red)':'var(--t1)') ?>"><?= ($m['type']==='in'?'+':($m['type']==='out'?'-':'±')) . abs($m['qty']) ?></td>
            <td class="td-dim"><?= $m['qty_before'] ?></td>
            <td style="font-weight:600"><?= $m['qty_after'] ?></td>
            <td class="td-dim" style="font-size:12px"><?= e($m['reason']??'—') ?></td>
            <td class="td-dim" style="font-size:11px"><?= formatDateTime($m['created_at']) ?></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
  <?php if ($lastPage > 1): ?>
    <div class="pagination">
      <?php for ($i=1;$i<=$lastPage;$i++): ?>
        <a href="?page=<?=$i?>&type=<?=urlencode($type??'')?>" class="page-btn <?=$i===$page?'active':''?>"><?=$i?></a>
      <?php endfor; ?>
    </div>
  <?php endif; ?>
</div>
<?php $content = ob_get_clean(); require APP_PATH . '/Views/admin/layouts/app.php';
