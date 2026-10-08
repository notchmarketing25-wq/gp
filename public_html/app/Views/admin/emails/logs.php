<?php
$pageTitle  = 'سجل الإيميلات';
$breadcrumb = [['label'=>'الإيميلات','url'=>adminUrl('emails/templates')],['label'=>'سجل الإرسال']];
$a = ADMIN_PREFIX;
ob_start();
?>
<div class="ph">
  <div class="ph-left"><h1><span style="display:inline-flex;align-items:center;gap:9px"><?= svgIcon('chart', 22) ?> سجل إرسال الإيميلات</span></h1><p>تتبع كل رسالة اتبعتت من النظام</p></div>
  <div class="ph-right"><a href="<?= adminUrl('emails/templates') ?>" class="btn btn-secondary">← القوالب</a></div>
</div>

<div class="stats" style="grid-template-columns:repeat(3,1fr);margin-bottom:20px">
  <div class="stat"><div class="stat-icon" style="background:#E8F0FF;color:var(--blue)"><?= svgIcon('mail', 20) ?></div><div class="stat-value"><?= number_format($stats['total']) ?></div><div class="stat-label">إجمالي الإيميلات</div></div>
  <div class="stat"><div class="stat-icon" style="background:#E8FBF4;color:var(--green)"><?= svgIcon('check', 20) ?></div><div class="stat-value"><?= number_format($stats['sent']) ?></div><div class="stat-label">تم الإرسال</div></div>
  <div class="stat"><div class="stat-icon" style="background:#FFF0F0;color:var(--red)"><?= svgIcon('close', 20) ?></div><div class="stat-value"><?= number_format($stats['failed']) ?></div><div class="stat-label">فشل الإرسال</div></div>
</div>

<div class="card" style="margin-bottom:14px">
  <div class="card-body">
    <form method="GET" style="display:grid;grid-template-columns:1fr 200px auto;gap:10px">
      <input type="text" name="search" class="form-input" placeholder="بحث بالبريد أو الموضوع..." value="<?= e($search??'') ?>">
      <select name="status" class="form-select">
        <option value="">كل الحالات</option>
        <option value="sent" <?= ($status??'')==='sent'?'selected':'' ?>>✅ تم الإرسال</option>
        <option value="failed" <?= ($status??'')==='failed'?'selected':'' ?>>❌ فشل</option>
      </select>
      <button class="btn btn-primary">بحث</button>
    </form>
  </div>
</div>

<div class="card">
  <div class="table-wrap">
    <table>
      <thead><tr><th>المستلم</th><th>الموضوع</th><th>القالب</th><th>الحالة</th><th>التاريخ</th></tr></thead>
      <tbody>
        <?php if (empty($logs)): ?>
          <tr><td colspan="5"><div class="empty"><div class="empty-icon"><?= svgIcon('mail', 32) ?></div><div class="empty-title">لا توجد إيميلات مُرسلة بعد</div></div></td></tr>
        <?php else: foreach ($logs as $l): ?>
          <tr>
            <td><div style="font-weight:600"><?= e($l['to_name']?:'—') ?></div><div style="font-size:11px;color:var(--t3)"><?= e($l['to_email']) ?></div></td>
            <td style="font-size:12px"><?= e($l['subject']) ?></td>
            <td class="td-dim td-mono" style="font-size:11px"><?= e($l['template_key']) ?></td>
            <td>
              <?php if ($l['status']==='sent'): ?><span class="badge badge-ok">✅ تم الإرسال</span>
              <?php else: ?><span class="badge badge-err" title="<?= e($l['error_message']) ?>">❌ فشل</span><?php endif; ?>
            </td>
            <td class="td-dim" style="font-size:11px"><?= formatDateTime($l['created_at']) ?></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php $content = ob_get_clean(); require APP_PATH . '/Views/admin/layouts/app.php';
