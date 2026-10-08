<?php
$pageTitle = 'تتبع تذكرة الدعم — ' . SettingModel::get('store_name', APP_NAME);
ob_start();
?>
<div class="container" style="padding-top:32px;padding-bottom:80px;max-width:600px;margin:0 auto">
  <h1 style="font-size:22px;font-weight:800;margin-bottom:20px;text-align:center">🔍 تتبع طلب الدعم</h1>

  <form method="GET" style="display:flex;gap:8px;margin-bottom:28px">
    <input type="text" name="ticket" class="form-input" placeholder="رقم التذكرة مثال: TKT-A1B2C3" value="<?= e($number) ?>" required>
    <button class="btn btn-primary">بحث</button>
  </form>

  <?php if ($number && !$ticket): ?>
    <div style="text-align:center;padding:40px 20px;color:var(--text3)">لم يتم العثور على تذكرة بهذا الرقم</div>
  <?php elseif ($ticket): ?>
    <?php $statusMap = ['open'=>['مفتوحة','var(--accent)'],'in_progress'=>['قيد المعالجة','#d97706'],'resolved'=>['تم الحل','var(--green,#16a34a)'],'closed'=>['مغلقة','var(--text3)']]; [$sLabel,$sColor] = $statusMap[$ticket['status']] ?? [$ticket['status'],'var(--text)']; ?>
    <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:22px;margin-bottom:20px">
      <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:14px">
        <div>
          <div style="font-weight:800;font-size:16px"><?= e($ticket['subject']) ?></div>
          <div style="font-size:12px;color:var(--text3);margin-top:2px">تذكرة رقم <?= e($ticket['ticket_number']) ?> — <?= formatDateTime($ticket['created_at']) ?></div>
        </div>
        <span style="font-weight:700;font-size:12.5px;color:<?= $sColor ?>"><?= $sLabel ?></span>
      </div>
      <p style="font-size:13.5px;color:var(--text2);line-height:1.7"><?= nl2br(e($ticket['message'])) ?></p>
    </div>

    <?php if (!empty($replies)): ?>
      <div style="display:flex;flex-direction:column;gap:12px">
        <?php foreach ($replies as $r): ?>
          <div style="background:<?= $r['sender_type']==='admin'?'var(--accent-bg)':'var(--bg2)' ?>;border-radius:14px;padding:14px 16px">
            <div style="font-weight:700;font-size:12.5px;margin-bottom:4px"><?= $r['sender_type']==='admin'?'🎧 فريق الدعم':e($r['sender_name']) ?></div>
            <p style="font-size:13px;color:var(--text2);line-height:1.6"><?= nl2br(e($r['message'])) ?></p>
            <div style="font-size:10.5px;color:var(--text3);margin-top:6px"><?= formatDateTime($r['created_at']) ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</div>
<?php
$content = ob_get_clean();
require APP_PATH . '/Views/store/layouts/app.php';
