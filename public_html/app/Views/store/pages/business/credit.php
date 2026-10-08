<?php
$pageTitle = 'N&Credit';
$statusInfo = ['none'=>['⏳','لم يتم التقديم بعد','badge-dim'],'pending'=>['⏳','قيد المراجعة','badge-warn'],'approved'=>['✅','معتمد','badge-ok'],'rejected'=>['❌','مرفوض','badge-err']];
[$icon,$lbl,$cls] = $statusInfo[$customer['credit_status']] ?? $statusInfo['none'];
ob_start();
?>
<div style="display:grid;grid-template-columns:1fr 340px;gap:16px;align-items:start">

  <div class="card">
    <div class="card-header"><span class="card-title">💳 طلب فتح / تعديل N&Credit</span></div>
    <div class="card-body">
      <div style="margin-bottom:18px"><span class="badge <?=$cls?>"><?=$icon?> <?=$lbl?></span></div>

      <?php if ($customer['credit_status']==='approved'): ?>
        <div style="background:var(--bg3);border-radius:10px;padding:16px;margin-bottom:18px">
          <div style="font-size:11px;color:var(--t3)">حد الكريدت المعتمد</div>
          <div style="font-size:24px;font-weight:900"><?= money($customer['credit_limit']) ?></div>
          <div style="font-size:12px;color:var(--t2);margin-top:6px">المستخدم: <?= money($customer['credit_used']) ?> — المتاح: <strong style="color:var(--green)"><?= money(max(0,$customer['credit_limit']-$customer['credit_used'])) ?></strong></div>
        </div>
      <?php endif; ?>

      <form method="POST" action="<?= url('business/credit/apply') ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="form-group">
          <label class="form-label">الرقم القومي / رقم البطاقة <span>*</span></label>
          <input type="text" name="id_card_number" class="form-input" required value="<?= e($customer['id_card_number']??'') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">صورة البطاقة <?= !$customer['id_card_image'] ? '<span>*</span>' : '' ?></label>
          <?php if ($customer['id_card_image']): ?><div style="font-size:11px;color:var(--green);margin-bottom:6px">✓ تم رفع صورة سابقاً</div><?php endif; ?>
          <input type="file" name="id_card_image" class="form-input" accept="image/*" style="padding:8px">
        </div>
        <div class="form-group">
          <label class="form-label">أوراق النشاط التجاري (اختياري)</label>
          <?php if ($customer['company_papers_image']): ?><div style="font-size:11px;color:var(--green);margin-bottom:6px">✓ تم رفع ملف سابقاً</div><?php endif; ?>
          <input type="file" name="company_papers_image" class="form-input" accept="image/*,.pdf" style="padding:8px">
        </div>
        <div class="form-group">
          <label class="form-label">المبلغ المطلوب <span>*</span></label>
          <input type="number" name="credit_requested" class="form-input" step="0.01" min="1" required value="<?= e($customer['credit_requested']??'') ?>">
        </div>
        <button type="submit" class="btn btn-primary btn-full">إرسال الطلب</button>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><span class="card-title">ℹ️ عن N&Credit</span></div>
    <div class="card-body" style="font-size:12.5px;color:var(--t2);line-height:1.9">
      رصيد ائتماني تقدر تستخدمه في الدفع مقابل طلباتك بدل الدفع الفوري. بيتم مراجعة طلبك من فريق نوتش، وممكن يعتمدوا نفس المبلغ اللي طلبته أو مبلغ مختلف حسب التقييم.
    </div>
  </div>

</div>
<?php $content = ob_get_clean(); require APP_PATH . '/Views/store/layouts/business.php';
