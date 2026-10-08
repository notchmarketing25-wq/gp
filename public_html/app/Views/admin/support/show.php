<?php
$pageTitle  = 'تذكرة ' . $ticket['ticket_number'];
$breadcrumb = [['label'=>'دعم العملاء','url'=>adminUrl('support')], ['label'=>$ticket['ticket_number']]];
ob_start();
$statusMap = ['open'=>'مفتوحة','in_progress'=>'قيد المعالجة','resolved'=>'تم الحل','closed'=>'مغلقة'];
?>
<div class="ph">
  <div class="ph-left"><h1><?= e($ticket['subject']) ?></h1><p>تذكرة <?= e($ticket['ticket_number']) ?> — <?= formatDateTime($ticket['created_at']) ?></p></div>
</div>

<div style="display:grid;grid-template-columns:1fr 320px;gap:16px;align-items:start">
  <div>
    <div class="card" style="margin-bottom:16px">
      <div class="card-body">
        <div style="font-weight:700;font-size:13px;margin-bottom:6px">👤 <?= e($ticket['name']) ?> <span class="td-dim" style="font-weight:400">(<?= e($ticket['email']) ?>)</span></div>
        <p style="font-size:13.5px;color:var(--t2);line-height:1.7;margin-bottom:10px"><?= nl2br(e($ticket['message'])) ?></p>
        <?php if ($ticket['attachment']): ?><a href="<?= uploadUrl($ticket['attachment']) ?>" target="_blank" class="btn btn-secondary btn-sm">📎 عرض المرفق</a><?php endif; ?>
      </div>
    </div>

    <?php foreach ($replies as $r): ?>
      <div class="card" style="margin-bottom:12px;<?= $r['sender_type']==='admin'?'border-color:var(--ac)':'' ?>">
        <div class="card-body">
          <div style="font-weight:700;font-size:12.5px;margin-bottom:6px"><?= $r['sender_type']==='admin'?'🎧 '.e($r['sender_name']):e($r['sender_name']) ?> <span class="td-dim" style="font-weight:400;font-size:11px"><?= formatDateTime($r['created_at']) ?></span></div>
          <p style="font-size:13px;color:var(--t2);line-height:1.6"><?= nl2br(e($r['message'])) ?></p>
        </div>
      </div>
    <?php endforeach; ?>

    <div class="card">
      <div class="card-header"><span class="card-title">✏️ رد</span></div>
      <div class="card-body">
        <form method="POST" action="<?= adminUrl('support/'.$ticket['id'].'/reply') ?>" enctype="multipart/form-data">
          <?= csrf_field() ?>
          <textarea name="message" class="form-textarea" rows="4" required placeholder="اكتب ردك هنا..." style="margin-bottom:10px"></textarea>
          <input type="file" name="attachment" class="form-input" accept="image/*" style="padding:6px;margin-bottom:10px">
          <button type="submit" class="btn btn-primary">إرسال الرد</button>
        </form>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><span class="card-title">⚙️ إدارة التذكرة</span></div>
    <div class="card-body">
      <form method="POST" action="<?= adminUrl('support/'.$ticket['id'].'/status') ?>">
        <?= csrf_field() ?>
        <div class="form-group">
          <label class="form-label">الحالة</label>
          <select name="status" class="form-select">
            <?php foreach ($statusMap as $k=>$l): ?><option value="<?=$k?>" <?= $ticket['status']===$k?'selected':'' ?>><?= $l ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">مسندة لـ</label>
          <select name="assigned_to" class="form-select">
            <option value="">— غير مسندة —</option>
            <?php foreach ($admins as $a): ?><option value="<?=$a['id']?>" <?= $ticket['assigned_to']==$a['id']?'selected':'' ?>><?= e($a['name']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <button type="submit" class="btn btn-primary btn-full">تحديث</button>
      </form>
      <div style="margin-top:16px;padding-top:16px;border-top:1px solid var(--bd);font-size:12.5px;color:var(--t3);display:flex;flex-direction:column;gap:6px">
        <?php if($ticket['phone']): ?><div>📞 <?= e($ticket['phone']) ?></div><?php endif; ?>
        <?php if($ticket['order_number']): ?><div>📦 طلب: <?= e($ticket['order_number']) ?></div><?php endif; ?>
        <div>النوع: <?= e($ticket['category']) ?></div>
      </div>
    </div>
  </div>
</div>
<?php
$content = ob_get_clean();
require APP_PATH . '/Views/admin/layouts/app.php';
