<?php
$pageTitle  = 'التقييمات';
$breadcrumb = [['label' => 'التقييمات']];
$a = ADMIN_PREFIX;
ob_start();
?>
<div class="ph"><div class="ph-left"><h1>التقييمات</h1></div></div>
<div class="card">
  <div class="table-wrap"><table>
    <thead><tr><th>المنتج</th><th>العميل</th><th>التقييم</th><th>التعليق</th><th>الحالة</th><th></th></tr></thead>
    <tbody>
      <?php if (empty($reviews)): ?>
        <tr><td colspan="6"><div class="empty"><div class="empty-icon"><?= svgIcon('star', 32) ?></div><div class="empty-title">لا توجد تقييمات</div></div></td></tr>
      <?php else: foreach ($reviews as $r): ?>
        <tr>
          <td style="font-size:12px;font-weight:500"><?= e($r['product_name']??'—') ?></td>
          <td><?= e($r['name']) ?></td>
          <td style="color:var(--yellow)"><?= str_repeat('★',(int)$r['rating']) ?></td>
          <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:12px;color:var(--t2)"><?= e($r['body']) ?></td>
          <td><span class="badge <?= $r['is_approved']?'badge-ok':'badge-warn' ?>"><?= $r['is_approved']?'معتمد':'انتظار' ?></span></td>
          <td>
            <div style="display:flex;gap:5px">
              <?php if (!$r['is_approved']): ?>
                <form method="POST" action="/<?= $a ?>/reviews/<?= $r['id'] ?>/approve"><input type="hidden" name="_csrf" value="<?= csrf_token() ?>"><button class="btn btn-secondary btn-sm"><?= svgIcon('check', 15) ?> موافقة</button></form>
              <?php endif; ?>
              <button class="btn btn-danger btn-sm" onclick="confirmDelete('/<?= $a ?>/reviews/<?= $r['id'] ?>/delete','حذف التقييم؟','')">حذف</button>
            </div>
          </td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table></div>
</div>
<?php $content = ob_get_clean(); require APP_PATH . '/Views/admin/layouts/app.php';
