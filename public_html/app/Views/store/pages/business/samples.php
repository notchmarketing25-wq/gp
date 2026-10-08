<?php
$pageTitle = 'طلب عينات';
$labels = ['pending'=>['⏳','قيد المراجعة','badge-warn'],'approved'=>['✅','معتمد','badge-ok'],'rejected'=>['❌','مرفوض','badge-err'],'delivered'=>['📦','تم التسليم','badge-ok']];
ob_start();
?>
<div style="display:grid;grid-template-columns:1fr 320px;gap:16px;align-items:start">
  <div class="card">
    <div class="card-header"><span class="card-title">سجل طلبات العينات</span></div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>المنتج</th><th>الكمية</th><th>السبب</th><th>الحالة</th><th>التاريخ</th></tr></thead>
        <tbody>
          <?php if (empty($samples)): ?>
            <tr><td colspan="5" style="text-align:center;color:var(--t3);padding:30px">لا توجد طلبات عينات</td></tr>
          <?php else: foreach ($samples as $sm): [$icon,$lbl,$cls]=$labels[$sm['status']]; ?>
            <tr>
              <td style="font-weight:600"><?= e($sm['product_name']) ?></td>
              <td><?= $sm['qty'] ?></td>
              <td style="color:var(--t2);font-size:12px"><?= e($sm['reason']?:'—') ?></td>
              <td><span class="badge <?=$cls?>"><?=$icon?> <?=$lbl?></span></td>
              <td style="color:var(--t3);font-size:11px"><?= formatDate($sm['created_at']) ?></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="card">
    <div class="card-header"><span class="card-title">+ طلب عينة</span></div>
    <div class="card-body">
      <form method="POST" action="<?= url('business/samples') ?>">
        <?= csrf_field() ?>
        <div class="form-group"><label class="form-label">المنتج <span>*</span></label>
          <select name="product_id" class="form-select" required>
            <option value="">اختر منتج</option>
            <?php foreach($products as $p): ?><option value="<?=$p['id']?>"><?= e($p['name']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="form-group"><label class="form-label">الكمية</label><input type="number" name="qty" class="form-input" value="1" min="1"></div>
        <div class="form-group"><label class="form-label">السبب</label><input type="text" name="reason" class="form-input" placeholder="لعرضه على عميل معين مثلاً"></div>
        <button type="submit" class="btn btn-primary btn-full">إرسال الطلب</button>
      </form>
    </div>
  </div>
</div>
<?php $content = ob_get_clean(); require APP_PATH . '/Views/store/layouts/business.php';
