<?php
$pageTitle  = 'إذن ' . $voucher['voucher_number'];
$breadcrumb = [['label'=>'المخزون','url'=>adminUrl('warehouse')], ['label'=>'الأذونات','url'=>adminUrl('inventory')], ['label'=>$voucher['voucher_number']]];
$statusMap = ['draft'=>['مسودة','badge-dim'],'confirmed'=>['مؤكَّد','badge-ok'],'cancelled'=>['ملغي','badge-err']];
[$sLabel, $sBadge] = $statusMap[$voucher['status']] ?? [$voucher['status'], 'badge-dim'];
$totalQty = array_sum(array_column($items, 'qty'));
ob_start();
?>
<div class="ph">
  <div class="ph-left">
    <h1>
      <span style="display:inline-flex;align-items:center;gap:9px">
        <?= svgIcon($voucher['type']==='receipt'?'upload':'truck', 22) ?>
        <?= e($voucher['voucher_number']) ?>
        <span class="badge <?= $sBadge ?>"><?= $sLabel ?></span>
      </span>
    </h1>
    <p><?= $voucher['type']==='receipt' ? 'إذن استلام/شراء' : 'إذن صرف/بيع' ?> — <?= formatDateTime($voucher['created_at']) ?></p>
  </div>
  <div class="ph-right">
    <?php if ($voucher['status'] === 'draft'): ?>
      <form method="POST" action="<?= adminUrl('inventory/'.$voucher['id'].'/cancel') ?>" style="display:inline" onsubmit="return confirm('إلغاء الإذن؟')">
        <?= csrf_field() ?><button class="btn btn-danger">إلغاء الإذن</button>
      </form>
      <form method="POST" action="<?= adminUrl('inventory/'.$voucher['id'].'/confirm') ?>" style="display:inline" onsubmit="return confirm('تأكيد الإذن هيطبّق التغيير على المخزون فوراً — متأكد؟')">
        <?= csrf_field() ?><button class="btn btn-primary"><?= svgIcon('check', 15) ?> تأكيد الإذن</button>
      </form>
    <?php endif; ?>
  </div>
</div>

<?php if ($voucher['reference_name'] || $voucher['notes']): ?>
<div class="card" style="margin-bottom:16px">
  <div class="card-body" style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
    <?php if ($voucher['reference_name']): ?>
      <div><div style="font-size:11px;color:var(--t3)"><?= $voucher['type']==='receipt'?'المورد':'العميل/السبب' ?></div><div style="font-weight:600"><?= e($voucher['reference_name']) ?></div></div>
    <?php endif; ?>
    <?php if ($voucher['notes']): ?>
      <div><div style="font-size:11px;color:var(--t3)">ملاحظات</div><div style="font-weight:600"><?= e($voucher['notes']) ?></div></div>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<div class="card">
  <div class="card-header"><span class="card-title">الأصناف (<?= count($items) ?>) — إجمالي <?= number_format($totalQty) ?> قطعة</span></div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>المنتج</th><th>SKU</th><th>الكمية</th><th>المخزون الحالي</th><th>ملاحظات</th></tr></thead>
      <tbody>
        <?php foreach ($items as $item): ?>
          <tr>
            <td>
              <div style="display:flex;align-items:center;gap:8px">
                <?php if($item['thumbnail']): ?><img src="<?= uploadUrl($item['thumbnail']) ?>" style="width:28px;height:28px;object-fit:cover;border-radius:6px"><?php endif; ?>
                <a href="<?= adminUrl('products/'.$item['product_id']) ?>" style="font-weight:600;color:var(--t1)"><?= e($item['product_name']) ?></a>
              </div>
            </td>
            <td class="td-mono td-dim"><?= e($item['sku'] ?: '—') ?></td>
            <td style="font-weight:700"><?= number_format($item['qty']) ?></td>
            <td class="td-dim"><?= number_format($item['current_stock']) ?></td>
            <td class="td-dim" style="font-size:12px"><?= e($item['notes'] ?: '—') ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php
$content = ob_get_clean();
require APP_PATH . '/Views/admin/layouts/app.php';
