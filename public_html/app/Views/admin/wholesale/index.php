<?php
$pageTitle  = 'حسابات الجملة';
$breadcrumb = [['label'=>'الجملة']];
$a = ADMIN_PREFIX;
ob_start();
?>
<div class="ph">
  <div class="ph-left"><h1><span style="display:inline-flex;align-items:center;gap:9px"><?= svgIcon('store', 22) ?> حسابات الجملة</span></h1><p>إدارة طلبات وحسابات الموزعين</p></div>
  <div class="ph-right">
    <a href="<?= adminUrl('wholesale/customers/bulk-upload') ?>" class="btn btn-secondary">👥 رفع عملاء CSV</a>
    <a href="<?= adminUrl('wholesale/bulk-upload') ?>" class="btn btn-primary">📤 رفع طلبات جملة CSV</a>
  </div>
</div>

<div class="card" style="margin-bottom:16px">
  <div class="chan-tabs">
  <?php foreach ([
    'pending'  => ['قيد المراجعة', 'clock', $counts['pending']],
    'approved' => ['معتمد',        'check', $counts['approved']],
    'rejected' => ['مرفوض',        'close', $counts['rejected']],
    'all'      => ['الكل',         'grid',  null],
  ] as $k => [$lbl,$icon,$cnt]): ?>
    <a href="?status=<?= $k ?>" class="chan-tab <?= $status===$k?'active':'' ?>">
      <?= svgIcon($icon, 13) ?> <?= $lbl ?><?php if($cnt!==null): ?> <span class="chan-tab-count"><?=$cnt?></span><?php endif; ?>
    </a>
  <?php endforeach; ?>
  </div>
</div>

<div class="card">
  <div class="table-wrap">
    <table>
      <thead><tr><th>الشركة/المحل</th><th>العميل</th><th>الهاتف</th><th>الطلبات</th><th>إجمالي المشتريات</th><th>تاريخ الطلب</th><th>الحالة</th><th></th></tr></thead>
      <tbody>
        <?php if (empty($accounts)): ?>
          <tr><td colspan="8"><div class="empty"><div class="empty-icon"><?= svgIcon('store', 32) ?></div><div class="empty-title">لا توجد حسابات جملة</div></div></td></tr>
        <?php else: foreach ($accounts as $c): ?>
          <tr>
            <td style="font-weight:600"><?= e($c['company_name'] ?: '—') ?></td>
            <td>
              <a href="<?= adminUrl('customers/'.$c['id']) ?>" style="color:var(--ac);font-weight:500"><?= e($c['name']) ?></a>
              <div style="font-size:11px;color:var(--t3)"><?= e($c['email']) ?></div>
            </td>
            <td class="td-dim"><?= e($c['phone']??'—') ?></td>
            <td style="font-weight:600"><?= number_format($c['orders_count']) ?></td>
            <td style="font-weight:600"><?= money($c['orders_total']) ?></td>
            <td class="td-dim"><?= $c['wholesale_applied_at'] ? formatDate($c['wholesale_applied_at']) : '—' ?></td>
            <td>
              <span class="badge <?= match($c['wholesale_status']){'approved'=>'badge-ok','rejected'=>'badge-err',default=>'badge-warn'} ?>">
                <?= match($c['wholesale_status']){'approved'=>'معتمد','rejected'=>'مرفوض','pending'=>'قيد المراجعة',default=>$c['wholesale_status']} ?>
              </span>
            </td>
            <td>
              <?php if ($c['wholesale_status']==='pending'): ?>
                <div style="display:flex;gap:5px">
                  <form method="POST" action="<?= adminUrl('customers/'.$c['id'].'/wholesale/approve') ?>"><?= csrf_field() ?><button class="btn btn-sm" style="background:var(--green);color:#fff"><?= svgIcon('check', 15) ?> اعتماد</button></form>
                  <form method="POST" action="<?= adminUrl('customers/'.$c['id'].'/wholesale/reject') ?>"><?= csrf_field() ?><button class="btn btn-danger btn-sm"><?= svgIcon('close', 15) ?> رفض</button></form>
                </div>
              <?php else: ?>
                <a href="<?= adminUrl('customers/'.$c['id']) ?>" class="btn btn-secondary btn-sm">التفاصيل</a>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php $content = ob_get_clean(); require APP_PATH . '/Views/admin/layouts/app.php';
