<?php
$pageTitle  = 'طلبات العينات';
$breadcrumb = [['label'=>'Business','url'=>adminUrl('business')],['label'=>'العينات']];
$a = ADMIN_PREFIX;
ob_start();
?>
<div class="ph"><div class="ph-left"><h1><span style="display:inline-flex;align-items:center;gap:9px"><?= svgIcon('gift', 22) ?> طلبات العينات</span></h1><p><?= count($samples) ?> طلب</p></div><div class="ph-right"><a href="<?= adminUrl('business') ?>" class="btn btn-secondary">← Business</a></div></div>
<div style="display:flex;gap:8px;margin-bottom:16px">
  <?php foreach(['pending'=>'⏳ قيد المراجعة','approved'=>'✅ معتمد','delivered'=>'📦 تم التسليم','rejected'=>'❌ مرفوض','all'=>'📋 الكل'] as $k=>$l): ?>
    <a href="?status=<?=$k?>"><button class="btn <?= ($status??'pending')===$k?'btn-primary':'btn-secondary' ?> btn-sm"><?=$l?></button></a>
  <?php endforeach; ?>
</div>
<div class="card">
  <div class="table-wrap">
    <table>
      <thead><tr><th>المندوب</th><th>المنتج</th><th>الكمية</th><th>السبب</th><th>الحالة</th><th></th></tr></thead>
      <tbody>
        <?php if (empty($samples)): ?><tr><td colspan="6"><div class="empty"><div class="empty-icon"><?= svgIcon('gift', 32) ?></div><div class="empty-title">لا توجد طلبات</div></div></td></tr>
        <?php else: foreach ($samples as $sm): ?>
          <tr>
            <td style="font-weight:600"><?= e($sm['rep_name']) ?></td>
            <td><?= e($sm['product_name']) ?></td>
            <td><?= $sm['qty'] ?></td>
            <td class="td-dim" style="font-size:12px"><?= e($sm['reason']?:'—') ?></td>
            <td><span class="badge <?= $sm['status']==='approved'||$sm['status']==='delivered'?'badge-ok':($sm['status']==='rejected'?'badge-err':'badge-warn') ?>"><?= ['pending'=>'قيد المراجعة','approved'=>'معتمد','rejected'=>'مرفوض','delivered'=>'تم التسليم'][$sm['status']] ?></span></td>
            <td>
              <?php if ($sm['status']==='pending'): ?>
                <div style="display:flex;gap:5px">
                  <form method="POST" action="<?= adminUrl('business/samples/'.$sm['id'].'/decide') ?>"><?= csrf_field() ?><input type="hidden" name="decision" value="approved"><button class="btn btn-sm" style="background:var(--green);color:#fff"><?= svgIcon('check', 15) ?></button></form>
                  <form method="POST" action="<?= adminUrl('business/samples/'.$sm['id'].'/decide') ?>"><?= csrf_field() ?><input type="hidden" name="decision" value="rejected"><button class="btn btn-danger btn-sm"><?= svgIcon('close', 15) ?></button></form>
                </div>
              <?php elseif ($sm['status']==='approved'): ?>
                <form method="POST" action="<?= adminUrl('business/samples/'.$sm['id'].'/decide') ?>"><?= csrf_field() ?><input type="hidden" name="decision" value="delivered"><button class="btn btn-secondary btn-sm"><?= svgIcon('package', 15) ?> تم التسليم</button></form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php $content = ob_get_clean(); require APP_PATH . '/Views/admin/layouts/app.php';
