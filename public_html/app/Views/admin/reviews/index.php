<?php
$pageTitle  = 'التقييمات';
$breadcrumb = [['label'=>'التقييمات']];
$a = ADMIN_PREFIX;
ob_start();
?>
<div class="ph">
  <div class="ph-left"><h1>⭐ التقييمات</h1><p><?= count($reviews) ?> تقييم</p></div>
</div>

<!-- Stats -->
<div class="stats" style="grid-template-columns:repeat(3,1fr);margin-bottom:20px">
  <?php
  $approved = array_filter($reviews, fn($r)=>$r['is_approved']);
  $pending  = array_filter($reviews, fn($r)=>!$r['is_approved']);
  $avg      = count($reviews) ? round(array_sum(array_column($reviews,'rating'))/count($reviews),1) : 0;
  ?>
  <div class="stat"><div class="stat-icon" style="background:rgba(245,158,11,.1)"><?= svgIcon('star', 20) ?></div><div class="stat-label">متوسط التقييم</div><div class="stat-value"><?= $avg ?>/5</div></div>
  <div class="stat"><div class="stat-icon" style="background:rgba(16,185,129,.1)"><?= svgIcon('check', 20) ?></div><div class="stat-label">معتمدة</div><div class="stat-value"><?= count($approved) ?></div></div>
  <div class="stat"><div class="stat-icon" style="background:rgba(245,158,11,.1)"><?= svgIcon('clock', 20) ?></div><div class="stat-label">في الانتظار</div><div class="stat-value"><?= count($pending) ?></div></div>
</div>

<div class="card">
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>المنتج</th><th>العميل</th><th>التقييم</th><th>التعليق</th><th>الحالة</th><th>التاريخ</th><th></th></tr>
      </thead>
      <tbody>
        <?php if (empty($reviews)): ?>
          <tr><td colspan="7"><div class="empty"><div class="empty-icon"><?= svgIcon('star', 32) ?></div><div class="empty-title">لا توجد تقييمات بعد</div></div></td></tr>
        <?php else: foreach ($reviews as $r): ?>
          <tr>
            <td style="font-size:12px;font-weight:500;max-width:140px"><?= e($r['product_name']??'—') ?></td>
            <td>
              <div style="font-weight:500;font-size:13px"><?= e($r['name']) ?></div>
              <?php if ($r['email']??null): ?><div style="font-size:11px;color:var(--t3)"><?= e($r['email']) ?></div><?php endif; ?>
            </td>
            <td>
              <div style="color:var(--yellow);font-size:15px;letter-spacing:1px"><?= str_repeat('★',(int)$r['rating']) ?><span style="color:var(--bd2)"><?= str_repeat('★',5-(int)$r['rating']) ?></span></div>
              <div style="font-size:10px;color:var(--t3)"><?= $r['rating'] ?>/5</div>
            </td>
            <td style="max-width:220px">
              <div style="font-size:13px;color:var(--t2);overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= e($r['body']??$r['comment']??'—') ?></div>
            </td>
            <td>
              <span class="badge <?= $r['is_approved']?'badge-ok':'badge-warn' ?>">
                <?= $r['is_approved']?'معتمد':'انتظار' ?>
              </span>
            </td>
            <td class="td-dim" style="font-size:11px"><?= formatDate($r['created_at']) ?></td>
            <td>
              <div style="display:flex;gap:5px">
                <?php if (!$r['is_approved']): ?>
                  <form method="POST" action="<?= adminUrl('reviews/'.$r['id'].'/approve') ?>">
                    <?= csrf_field() ?>
                    <button class="btn btn-sm" style="background:var(--green);color:#fff;padding:4px 10px"><?= svgIcon('check', 15) ?> موافقة</button>
                  </form>
                <?php endif; ?>
                <button class="btn btn-danger btn-sm" onclick="confirmDelete('<?= adminUrl('reviews/'.$r['id'].'/delete') ?>','حذف التقييم؟','لا يمكن التراجع.')">حذف</button>
              </div>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php $content = ob_get_clean(); require APP_PATH . '/Views/admin/layouts/app.php';
