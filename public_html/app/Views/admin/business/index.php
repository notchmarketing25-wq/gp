<?php
$pageTitle  = 'Notch Business';
$breadcrumb = [['label'=>'Business']];
$a = ADMIN_PREFIX;
$typeLabels = ['merchant'=>'🏪 تاجر','distributor'=>'🚛 موزع','rep'=>'🧑‍💼 مندوب'];
ob_start();
?>
<div class="ph">
  <div class="ph-left"><h1><span style="display:inline-flex;align-items:center;gap:9px"><?= svgIcon('briefcase', 22) ?> Notch Business</span></h1><p>إدارة حسابات التجار والموزعين والمندوبين</p></div>
  <div class="ph-right">
    <a href="<?= adminUrl('business/tiers') ?>" class="btn btn-secondary">🏷️ الشرائح السعرية</a>
    <a href="<?= adminUrl('business/expenses') ?>" class="btn btn-secondary">🚕 مصروفات المندوبين</a>
    <a href="<?= adminUrl('business/samples') ?>" class="btn btn-secondary">🎁 طلبات العينات</a>
  </div>
</div>

<div class="card" style="margin-bottom:16px">
  <div class="chan-tabs">
    <?php foreach(['pending'=>['قيد المراجعة','clock',$counts['pending']],'approved'=>['معتمد','check',$counts['approved']],'all'=>['الكل','grid',null]] as $k=>[$lbl,$icon,$cnt]): ?>
      <a href="?status=<?=$k?>&type=<?=e($type??'')?>" class="chan-tab <?= ($status??'pending')===$k?'active':'' ?>">
        <?= svgIcon($icon, 13) ?> <?=$lbl?><?php if($cnt!==null): ?> <span class="chan-tab-count"><?=$cnt?></span><?php endif;?>
      </a>
    <?php endforeach; ?>
  </div>
  <div class="chan-tabs" style="border-top:1px solid var(--bd)">
    <a href="?type=&status=<?=e($status??'pending')?>" class="chan-tab <?= empty($type)?'active':'' ?>"><?= svgIcon('grid', 13) ?> كل الأنواع</a>
    <?php foreach(['merchant'=>['تجار','store'],'distributor'=>['موزعين','truck'],'rep'=>['مندوبين','briefcase']] as $k=>[$lbl,$icon]): ?>
      <a href="?type=<?= ($type??'')===$k?'':$k ?>&status=<?=e($status??'pending')?>" class="chan-tab <?= ($type??'')===$k?'active':'' ?>"><?= svgIcon($icon, 13) ?> <?=$lbl?></a>
    <?php endforeach; ?>
  </div>
</div>

<div class="card">
  <div class="table-wrap">
    <table>
      <thead><tr><th>الحساب</th><th>النوع</th><th>المحل/النشاط</th><th>المنطقة</th><th>الشريحة</th><th>N&Credit</th><th>الحالة</th><th></th></tr></thead>
      <tbody>
        <?php if (empty($accounts)): ?>
          <tr><td colspan="8"><div class="empty"><div class="empty-icon"><?= svgIcon('briefcase', 32) ?></div><div class="empty-title">لا توجد حسابات</div></div></td></tr>
        <?php else: foreach ($accounts as $acc): ?>
          <tr>
            <td>
              <a href="<?= adminUrl('business/'.$acc['id']) ?>" style="display:flex;align-items:center;gap:9px">
                <div style="width:30px;height:30px;border-radius:50%;background:var(--acb2);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;color:var(--ac)"><?= mb_substr($acc['name'],0,1) ?></div>
                <div><div style="font-weight:600"><?= e($acc['name']) ?></div><div style="font-size:11px;color:var(--t3)"><?= e($acc['email']) ?></div></div>
              </a>
            </td>
            <td><?= $typeLabels[$acc['business_type']] ?? $acc['business_type'] ?></td>
            <td class="td-dim"><?= e($acc['shop_name'] ?: ($acc['rep_name'] ? 'بواسطة: '.$acc['rep_name'] : '—')) ?></td>
            <td class="td-dim" style="font-size:12px"><?= e($acc['area']) ?><?= $acc['governorate']?'، '.e($acc['governorate']):'' ?></td>
            <td><?php if($acc['tier_name']): ?><span class="badge badge-purple"><?= e($acc['tier_name']) ?></span><?php else: ?><span class="td-dim">—</span><?php endif; ?></td>
            <td>
              <?php if ($acc['credit_status']==='approved'): ?><span style="font-weight:700;color:var(--green)"><?= money($acc['credit_limit']) ?></span>
              <?php elseif($acc['credit_status']==='pending'): ?><span class="badge badge-warn">⏳ طلب معلّق</span>
              <?php else: ?><span class="td-dim">—</span><?php endif; ?>
            </td>
            <td><span class="badge <?= match($acc['business_status']){'approved'=>'badge-ok','rejected'=>'badge-err',default=>'badge-warn'} ?>"><?= match($acc['business_status']){'approved'=>'معتمد','rejected'=>'مرفوض',default=>'قيد المراجعة'} ?></span></td>
            <td><a href="<?= adminUrl('business/'.$acc['id']) ?>" class="btn btn-secondary btn-sm">التفاصيل</a></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php $content = ob_get_clean(); require APP_PATH . '/Views/admin/layouts/app.php';
