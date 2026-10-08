<?php
$pageTitle  = e($account['name']);
$breadcrumb = [['label'=>'Business','url'=>adminUrl('business')],['label'=>$account['name']]];
$a = ADMIN_PREFIX;
$typeLabels = ['merchant'=>'🏪 تاجر','distributor'=>'🚛 موزع','rep'=>'🧑‍💼 مندوب'];
ob_start();
?>
<div class="ph">
  <div class="ph-left">
    <h1><?= e($account['name']) ?></h1>
    <p><?= $typeLabels[$account['business_type']] ?? '' ?> · <?= e($account['shop_name'] ?: '') ?></p>
  </div>
  <div class="ph-right"><a href="<?= adminUrl('business') ?>" class="btn btn-secondary">← Business</a></div>
</div>

<?php if ($account['business_status']==='pending'): ?>
  <div style="background:#FFF8E8;border:1px solid rgba(184,112,0,.2);border-radius:var(--radius);padding:16px 20px;margin-bottom:18px;display:flex;align-items:center;gap:14px;flex-wrap:wrap">
    <div style="font-size:24px">⏳</div>
    <div style="flex:1">هذا الحساب بانتظار الاعتماد</div>
    <div style="display:flex;gap:8px">
      <form method="POST" action="<?= adminUrl('business/'.$account['id'].'/approve') ?>"><?= csrf_field() ?><button class="btn" style="background:var(--green);color:#fff"><?= svgIcon('check', 15) ?> اعتماد الحساب</button></form>
      <form method="POST" action="<?= adminUrl('business/'.$account['id'].'/reject') ?>"><?= csrf_field() ?><button class="btn btn-danger"><?= svgIcon('close', 15) ?> رفض</button></form>
    </div>
  </div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 320px;gap:16px;align-items:start">
  <div style="display:flex;flex-direction:column;gap:14px">

    <div class="card">
      <div class="card-header">
        <span class="card-title">بيانات الحساب</span>
        <button class="btn btn-secondary btn-sm" onclick="document.getElementById('accountViewMode').style.display='none';document.getElementById('accountEditMode').style.display='block'"><?= svgIcon('edit', 13) ?> تعديل</button>
      </div>
      <div class="card-body" id="accountViewMode" style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
        <?php foreach([
          ['البريد',$account['email']],['الهاتف',$account['phone']??'—'],
          ['المحافظة',$account['governorate']??'—'],['المنطقة',$account['area']??'—'],
          ['المحل/النشاط',$account['shop_name']??'—'],['مسجّل بواسطة',$account['rep_name']??'—'],
        ] as [$l,$v]): ?>
          <div><div style="font-size:11px;color:var(--t3);margin-bottom:2px"><?=$l?></div><div style="font-weight:600"><?= e($v) ?></div></div>
        <?php endforeach; ?>
      </div>
      <div class="card-body" id="accountEditMode" style="display:none">
        <form method="POST" action="<?= adminUrl('business/'.$account['id'].'/location') ?>">
          <?= csrf_field() ?>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px">
            <div class="form-group" style="margin:0">
              <label class="form-label">المحافظة</label>
              <select name="governorate" class="form-select">
                <option value="">— بدون —</option>
                <?php foreach (['القاهرة','الجيزة','الإسكندرية','القليوبية','المنوفية','الغربية','الدقهلية','الشرقية','البحيرة','كفر الشيخ','دمياط','بورسعيد','الإسماعيلية','السويس','شمال سيناء','جنوب سيناء','الفيوم','بني سويف','المنيا','أسيوط','سوهاج','قنا','الأقصر','أسوان','البحر الأحمر','مطروح','الوادي الجديد'] as $gov): ?>
                  <option value="<?= e($gov) ?>" <?= ($account['governorate']??'')===$gov?'selected':'' ?>><?= e($gov) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group" style="margin:0">
              <label class="form-label">المنطقة</label>
              <input type="text" name="area" class="form-input" value="<?= e($account['area']??'') ?>">
            </div>
          </div>
          <div class="form-group" style="margin-bottom:12px">
            <label class="form-label">المحل / النشاط</label>
            <input type="text" name="shop_name" class="form-input" value="<?= e($account['shop_name']??'') ?>">
          </div>
          <div style="display:flex;gap:8px">
            <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('accountEditMode').style.display='none';document.getElementById('accountViewMode').style.display='grid'">إلغاء</button>
            <button type="submit" class="btn btn-primary btn-sm">حفظ التعديلات</button>
          </div>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><span class="card-title">سجل الطلبات (<?= count($orders) ?>)</span></div>
      <div class="table-wrap">
        <table>
          <thead><tr><th>رقم الطلب</th><th>المبلغ</th><th>الحالة</th><th>التاريخ</th></tr></thead>
          <tbody>
            <?php if (empty($orders)): ?><tr><td colspan="4" style="text-align:center;color:var(--t3);padding:24px">لا توجد طلبات</td></tr>
            <?php else: foreach ($orders as $o): ?>
              <tr>
                <td><a href="<?= adminUrl('orders/'.$o['id']) ?>" style="color:var(--ac);font-weight:700;font-family:monospace"><?= e($o['order_number']) ?></a></td>
                <td style="font-weight:700"><?= money($o['total']) ?></td>
                <td><span class="badge badge-<?= orderStatusColor($o['status']) ?>"><?= orderStatusLabel($o['status']) ?></span></td>
                <td class="td-dim"><?= formatDate($o['created_at']) ?></td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Credit application review -->
    <div class="card">
      <div class="card-header"><span class="card-title"><?= svgIcon('star', 15) ?> طلب N&Credit</span></div>
      <div class="card-body">
        <?php if ($account['credit_status']==='none' || !$account['credit_requested']): ?>
          <div style="text-align:center;color:var(--t3);padding:20px">لم يتم تقديم طلب كريدت بعد</div>
        <?php else: ?>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:16px">
            <div><div style="font-size:11px;color:var(--t3)">رقم البطاقة</div><div style="font-weight:700;font-family:monospace"><?= e($account['id_card_number']) ?></div></div>
            <div><div style="font-size:11px;color:var(--t3)">المبلغ المطلوب</div><div style="font-weight:700"><?= money($account['credit_requested']) ?></div></div>
          </div>
          <div style="display:flex;gap:10px;margin-bottom:16px;flex-wrap:wrap">
            <?php if ($account['id_card_image']): ?>
              <a href="<?= uploadUrl($account['id_card_image']) ?>" target="_blank" style="display:block">
                <img src="<?= uploadUrl($account['id_card_image']) ?>" style="width:140px;height:90px;object-fit:cover;border-radius:8px;border:1px solid var(--bd)">
                <div style="font-size:10px;text-align:center;margin-top:4px;color:var(--t3)">صورة البطاقة</div>
              </a>
            <?php endif; ?>
            <?php if ($account['company_papers_image']): ?>
              <a href="<?= uploadUrl($account['company_papers_image']) ?>" target="_blank" style="display:block">
                <img src="<?= uploadUrl($account['company_papers_image']) ?>" style="width:140px;height:90px;object-fit:cover;border-radius:8px;border:1px solid var(--bd)">
                <div style="font-size:10px;text-align:center;margin-top:4px;color:var(--t3)">أوراق النشاط</div>
              </a>
            <?php endif; ?>
          </div>
          <?php if ($account['credit_status']==='pending'): ?>
            <form method="POST" action="<?= adminUrl('business/'.$account['id'].'/credit/approve') ?>" style="display:flex;gap:8px;align-items:end">
              <?= csrf_field() ?>
              <div class="form-group" style="margin:0;flex:1"><label class="form-label">المبلغ المعتمد</label><input type="number" name="credit_limit" class="form-input" step="0.01" value="<?= e($account['credit_requested']) ?>"></div>
              <button type="submit" class="btn btn-primary"><?= svgIcon('check', 15) ?> اعتماد</button>
            </form>
            <form method="POST" action="<?= adminUrl('business/'.$account['id'].'/credit/reject') ?>" style="margin-top:8px"><?= csrf_field() ?><button class="btn btn-danger btn-full"><?= svgIcon('close', 15) ?> رفض الطلب</button></form>
          <?php elseif ($account['credit_status']==='approved'): ?>
            <span class="badge badge-ok">✅ معتمد بحد <?= money($account['credit_limit']) ?></span>
          <?php else: ?>
            <span class="badge badge-err">❌ مرفوض</span>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>

  </div>

  <div style="display:flex;flex-direction:column;gap:12px">
    <div class="card">
      <div class="card-header"><span class="card-title"><?= svgIcon('tag', 15) ?> الشريحة السعرية</span></div>
      <div class="card-body">
        <?php if (!empty($account['expected_monthly_sales'])): ?>
          <div style="background:var(--bg3);border-radius:8px;padding:8px 12px;margin-bottom:12px;font-size:12px;color:var(--t2)">
            حجم المبيعات المتوقع اللي حدده وقت التسجيل: <b style="color:var(--t1)"><?= money($account['expected_monthly_sales']) ?></b>/شهر
          </div>
        <?php endif; ?>
        <form method="POST" action="<?= adminUrl('business/'.$account['id'].'/tier') ?>">
          <?= csrf_field() ?>
          <select name="price_tier_id" class="form-select" style="margin-bottom:10px">
            <option value="">بدون شريحة</option>
            <?php foreach($tiers as $t): ?><option value="<?=$t['id']?>" <?= $account['price_tier_id']==$t['id']?'selected':'' ?>><?= e($t['name']) ?> (-<?=$t['discount_percent']?>%)</option><?php endforeach; ?>
          </select>
          <button type="submit" class="btn btn-primary btn-full btn-sm">تحديث الشريحة</button>
        </form>
      </div>
    </div>
    <?php if ($account['business_type'] === 'rep'): ?>
    <div class="card">
      <div class="card-header"><span class="card-title"><?= svgIcon('star', 15) ?> نسبة العمولة</span></div>
      <div class="card-body">
        <p style="font-size:11.5px;color:var(--t3);margin-bottom:10px">النسبة اللي ياخدها المندوب من قيمة كل فاتورة يعملها لعملائه</p>
        <form method="POST" action="<?= adminUrl('business/'.$account['id'].'/commission') ?>" style="display:flex;gap:8px">
          <?= csrf_field() ?>
          <div style="position:relative;flex:1">
            <input type="number" name="commission_percent" step="0.5" min="0" max="100" value="<?= e($account['commission_percent'] ?? 0) ?>" class="form-input" style="padding-left:26px">
            <span style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--t3);font-size:13px">%</span>
          </div>
          <button type="submit" class="btn btn-primary btn-sm">حفظ</button>
        </form>
      </div>
    </div>
    <?php endif; ?>
    <div class="card">
      <div class="card-header"><span class="card-title"><?= svgIcon('chart', 15) ?> إحصائيات</span></div>
      <div class="card-body">
        <div style="display:flex;justify-content:space-between;padding:6px 0;font-size:12px"><span style="color:var(--t3)">إجمالي الطلبات</span><strong><?= $account['total_orders']??0 ?></strong></div>
        <div style="display:flex;justify-content:space-between;padding:6px 0;font-size:12px"><span style="color:var(--t3)">إجمالي المشتريات</span><strong><?= money($account['total_spent']??0) ?></strong></div>
        <div style="display:flex;justify-content:space-between;padding:6px 0;font-size:12px"><span style="color:var(--t3)">رصيد N&Credit عادي</span><strong><?= money($account['store_credit']??0) ?></strong></div>
      </div>
    </div>
  </div>
</div>

<?php $content = ob_get_clean(); require APP_PATH . '/Views/admin/layouts/app.php';
