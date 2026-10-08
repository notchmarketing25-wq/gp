<?php
$pageTitle  = 'مصروفات المندوبين';
$breadcrumb = [['label'=>'Business','url'=>adminUrl('business')],['label'=>'المصروفات']];
$a = ADMIN_PREFIX;
ob_start();
?>
<div class="ph"><div class="ph-left"><h1><span style="display:inline-flex;align-items:center;gap:9px"><?= svgIcon('car', 22) ?> مصروفات المندوبين</span></h1><p><?= count($expenses) ?> مصروف</p></div><div class="ph-right"><a href="<?= adminUrl('business') ?>" class="btn btn-secondary">← Business</a></div></div>
<div style="display:flex;gap:8px;margin-bottom:16px">
  <?php foreach(['pending'=>'⏳ قيد المراجعة','approved'=>'✅ معتمد','rejected'=>'❌ مرفوض','all'=>'📋 الكل'] as $k=>$l): ?>
    <a href="?status=<?=$k?>"><button class="btn <?= ($status??'pending')===$k?'btn-primary':'btn-secondary' ?> btn-sm"><?=$l?></button></a>
  <?php endforeach; ?>
</div>
<div class="card">
  <div class="table-wrap">
    <table>
      <thead><tr><th>المندوب</th><th>النوع</th><th>المبلغ</th><th>الوصف</th><th>الإيصال</th><th>الحالة</th><th></th></tr></thead>
      <tbody>
        <?php if (empty($expenses)): ?><tr><td colspan="7"><div class="empty"><div class="empty-icon"><?= svgIcon('car', 32) ?></div><div class="empty-title">لا توجد مصروفات</div></div></td></tr>
        <?php else: foreach ($expenses as $ex): ?>
          <tr>
            <td style="font-weight:600"><?= e($ex['rep_name']) ?></td>
            <td><?= e($ex['type']) ?></td>
            <td style="font-weight:700"><?= money($ex['amount']) ?></td>
            <td class="td-dim" style="font-size:12px"><?= e($ex['description']?:'—') ?></td>
            <td><?php if($ex['receipt_image']): ?><a href="<?= uploadUrl($ex['receipt_image']) ?>" target="_blank" style="color:var(--ac)">عرض 🧾</a><?php else: ?>—<?php endif; ?></td>
            <td><span class="badge <?= $ex['status']==='approved'?'badge-ok':($ex['status']==='rejected'?'badge-err':'badge-warn') ?>"><?= ['pending'=>'قيد المراجعة','approved'=>'معتمد','rejected'=>'مرفوض'][$ex['status']] ?></span></td>
            <td>
              <?php if ($ex['status']==='pending'): ?>
                <div style="display:flex;gap:5px">
                  <form method="POST" action="<?= adminUrl('business/expenses/'.$ex['id'].'/decide') ?>"><?= csrf_field() ?><input type="hidden" name="decision" value="approved"><button class="btn btn-sm" style="background:var(--green);color:#fff"><?= svgIcon('check', 15) ?></button></form>
                  <form method="POST" action="<?= adminUrl('business/expenses/'.$ex['id'].'/decide') ?>"><?= csrf_field() ?><input type="hidden" name="decision" value="rejected"><button class="btn btn-danger btn-sm"><?= svgIcon('close', 15) ?></button></form>
                </div>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php $content = ob_get_clean(); require APP_PATH . '/Views/admin/layouts/app.php';
