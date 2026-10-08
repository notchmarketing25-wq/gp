<?php
$pageTitle  = 'أذونات المخزون';
$breadcrumb = [['label'=>'المخزون','url'=>adminUrl('warehouse')], ['label'=>'الأذونات']];
$a = ADMIN_PREFIX;
ob_start();
?>
<div class="ph">
  <div class="ph-left"><h1><span style="display:inline-flex;align-items:center;gap:9px"><?= svgIcon('receipt', 22) ?> أذونات المخزون</span></h1><p>سجل رسمي لكل إذن استلام (شراء) أو صرف (بيع) — تتبع دقيقة للكميات</p></div>
  <div class="ph-right">
    <a href="/<?= $a ?>/inventory/create?type=receipt" class="btn btn-secondary"><?= svgIcon('upload', 15) ?> + إذن استلام</a>
    <a href="/<?= $a ?>/inventory/create?type=issue" class="btn btn-primary"><?= svgIcon('truck', 15) ?> + إذن صرف</a>
  </div>
</div>

<div class="card">
  <div class="chan-tabs">
    <?php foreach (['' => 'الكل', 'receipt' => 'أذونات استلام', 'issue' => 'أذونات صرف'] as $tVal => $tLabel): ?>
      <a href="?type=<?= urlencode($tVal) ?>" class="chan-tab <?= ($type??'')===$tVal ? 'active' : '' ?>"><?= $tLabel ?></a>
    <?php endforeach; ?>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>رقم الإذن</th><th>النوع</th><th>المرجع</th><th>عدد الأصناف</th><th>إجمالي الكمية</th><th>الحالة</th><th>التاريخ</th></tr></thead>
      <tbody>
        <?php if (empty($vouchers)): ?>
          <tr><td colspan="7"><div class="empty"><div class="empty-icon"><?= svgIcon('receipt', 32) ?></div><div class="empty-title">لا توجد أذونات بعد</div></div></td></tr>
        <?php else: foreach ($vouchers as $v): ?>
          <tr onclick="location.href='/<?= $a ?>/inventory/<?= $v['id'] ?>'" style="cursor:pointer">
            <td style="font-family:monospace;font-weight:700;color:var(--ac)"><?= e($v['voucher_number']) ?></td>
            <td>
              <?php if ($v['type']==='receipt'): ?>
                <span class="badge badge-ok"><?= svgIcon('upload', 12) ?> استلام/شراء</span>
              <?php else: ?>
                <span class="badge badge-warn"><?= svgIcon('truck', 12) ?> صرف/بيع</span>
              <?php endif; ?>
            </td>
            <td class="td-dim"><?= e($v['reference_name'] ?: '—') ?></td>
            <td><?= (int)$v['items_count'] ?> صنف</td>
            <td style="font-weight:700"><?= number_format($v['total_qty']) ?> قطعة</td>
            <td>
              <?php $statusMap = ['draft'=>['مسودة','badge-dim'],'confirmed'=>['مؤكَّد','badge-ok'],'cancelled'=>['ملغي','badge-err']]; [$sl,$sc] = $statusMap[$v['status']] ?? [$v['status'],'badge-dim']; ?>
              <span class="badge <?= $sc ?>"><?= $sl ?></span>
            </td>
            <td class="td-dim" style="font-size:12px"><?= timeAgo($v['created_at']) ?></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
  <?php if ($lastPage > 1): ?>
    <div class="pagination" style="padding:14px">
      <?php for ($i=1;$i<=$lastPage;$i++): ?>
        <a href="?page=<?=$i?>&type=<?=urlencode($type??'')?>" class="page-btn <?=$i===$page?'active':''?>"><?=$i?></a>
      <?php endfor; ?>
    </div>
  <?php endif; ?>
</div>
<?php
$content = ob_get_clean();
require APP_PATH . '/Views/admin/layouts/app.php';
