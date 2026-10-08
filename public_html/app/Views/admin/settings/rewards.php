<?php
$pageTitle  = 'قواعد المكافآت';
$breadcrumb = [['label'=>'الإعدادات','url'=>adminUrl('settings')], ['label'=>'المكافآت']];
$a = ADMIN_PREFIX;
$accountLabels = ['all'=>'الكل','merchant'=>'تاجر','distributor'=>'موزع','rep'=>'مندوب','customer'=>'عميل عادي'];
$triggerLabels = ['registration'=>'عند تسجيل الحساب','per_order'=>'على كل فاتورة'];
ob_start();
?>
<div class="ph">
  <div class="ph-left"><h1><span style="display:inline-flex;align-items:center;gap:9px"><?= svgIcon('star', 22) ?> قواعد المكافآت</span></h1><p>تحكم في المبالغ اللي بتنزل تلقائياً في محفظة N&Credit للتجار والموزعين والمناديب والعملاء</p></div>
  <div class="ph-right"><button class="btn btn-primary" onclick="openAddRule()">+ إضافة قاعدة</button></div>
</div>

<div class="card">
  <div class="table-wrap">
    <table>
      <thead><tr><th>القاعدة</th><th>متى تتفعل</th><th>لمين</th><th>المكافأة</th><th>الحالة</th><th></th></tr></thead>
      <tbody>
        <?php if (empty($rules)): ?>
          <tr><td colspan="6"><div class="empty"><div class="empty-icon"><?= svgIcon('star', 32) ?></div><div class="empty-title">لا توجد قواعد مكافآت بعد</div></div></td></tr>
        <?php else: foreach ($rules as $r): ?>
          <tr>
            <td style="font-weight:600"><?= e($r['label']) ?></td>
            <td class="td-dim"><?= $triggerLabels[$r['trigger_type']] ?? $r['trigger_type'] ?></td>
            <td><span class="badge badge-dim"><?= $accountLabels[$r['account_type']] ?? $r['account_type'] ?></span></td>
            <td style="font-weight:700;color:var(--ac)">
              <?= $r['reward_type']==='percent' ? e($r['reward_value']).'%' : money($r['reward_value']) ?>
              <?php if ($r['trigger_type']==='per_order' && $r['min_order_amount']): ?><div style="font-size:11px;color:var(--t3);font-weight:400">لفواتير أكبر من <?= money($r['min_order_amount']) ?></div><?php endif; ?>
            </td>
            <td>
              <form method="POST" action="<?= adminUrl('settings/rewards/'.$r['id'].'/toggle') ?>" style="display:inline">
                <?= csrf_field() ?>
                <button class="badge <?= $r['is_active']?'badge-ok':'badge-dim' ?>" style="border:none;cursor:pointer"><?= $r['is_active']?'نشطة':'موقوفة' ?></button>
              </form>
            </td>
            <td>
              <div style="display:flex;gap:5px;justify-content:flex-end">
                <button class="btn btn-secondary btn-sm" onclick='openEditRule(<?= htmlspecialchars(json_encode($r),ENT_QUOTES) ?>)'>تعديل</button>
                <button class="btn btn-danger btn-sm" onclick="confirmDelete('<?= adminUrl('settings/rewards/'.$r['id'].'/delete') ?>','حذف القاعدة؟','')">حذف</button>
              </div>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal-overlay" id="ruleModal">
  <div class="modal">
    <div class="modal-head"><h3 id="rmTitle">+ إضافة قاعدة</h3><button class="btn btn-ghost btn-icon" onclick="document.getElementById('ruleModal').classList.remove('open')" style="font-size:18px">×</button></div>
    <form method="POST" id="ruleForm">
      <?= csrf_field() ?>
      <div class="form-group" style="margin-bottom:12px">
        <label class="form-label">اسم القاعدة <span class="req">*</span></label>
        <input type="text" name="label" id="rmLabel" class="form-input" required placeholder="مثال: هدية تسجيل المناديب">
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:12px">
        <div class="form-group" style="margin:0">
          <label class="form-label">تتفعل عند</label>
          <select name="trigger_type" id="rmTrigger" class="form-select" onchange="toggleMinOrder()">
            <option value="registration">تسجيل الحساب (مرة واحدة)</option>
            <option value="per_order">كل فاتورة</option>
          </select>
        </div>
        <div class="form-group" style="margin:0">
          <label class="form-label">لمين</label>
          <select name="account_type" id="rmAccountType" class="form-select">
            <?php foreach ($accountLabels as $k=>$l): ?><option value="<?=$k?>"><?= $l ?></option><?php endforeach; ?>
          </select>
        </div>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:12px">
        <div class="form-group" style="margin:0">
          <label class="form-label">نوع المكافأة</label>
          <select name="reward_type" id="rmRewardType" class="form-select">
            <option value="fixed">مبلغ ثابت (جنيه)</option>
            <option value="percent">نسبة % من قيمة الفاتورة</option>
          </select>
        </div>
        <div class="form-group" style="margin:0">
          <label class="form-label">القيمة <span class="req">*</span></label>
          <input type="number" name="reward_value" id="rmValue" class="form-input" step="0.01" min="0" required>
        </div>
      </div>
      <div class="form-group" id="rmMinOrderWrap" style="margin-bottom:12px;display:none">
        <label class="form-label">حد أدنى لقيمة الفاتورة (اختياري)</label>
        <input type="number" name="min_order_amount" id="rmMinOrder" class="form-input" step="0.01" min="0" placeholder="مفيش حد أدنى">
      </div>
      <div class="toggle-wrap" style="margin-bottom:16px"><label class="toggle"><input type="checkbox" name="is_active" id="rmActive" value="1" checked><span class="toggle-slider"></span></label><span style="font-size:13px">نشطة</span></div>
      <div class="modal-actions">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('ruleModal').classList.remove('open')">إلغاء</button>
        <button type="submit" class="btn btn-primary">حفظ</button>
      </div>
    </form>
  </div>
</div>

<?php
$extraScript = "
function toggleMinOrder(){
  document.getElementById('rmMinOrderWrap').style.display = document.getElementById('rmTrigger').value==='per_order' ? 'block' : 'none';
}
function openAddRule(){
  document.getElementById('rmTitle').textContent='+ إضافة قاعدة';
  document.getElementById('ruleForm').reset();
  document.getElementById('ruleForm').action='/$a/settings/rewards/create';
  toggleMinOrder();
  document.getElementById('ruleModal').classList.add('open');
}
function openEditRule(r){
  document.getElementById('rmTitle').textContent='تعديل القاعدة';
  document.getElementById('ruleForm').action='/$a/settings/rewards/'+r.id;
  document.getElementById('rmLabel').value=r.label;
  document.getElementById('rmTrigger').value=r.trigger_type;
  document.getElementById('rmAccountType').value=r.account_type;
  document.getElementById('rmRewardType').value=r.reward_type;
  document.getElementById('rmValue').value=r.reward_value;
  document.getElementById('rmMinOrder').value=r.min_order_amount || '';
  document.getElementById('rmActive').checked = r.is_active == 1;
  toggleMinOrder();
  document.getElementById('ruleModal').classList.add('open');
}
";
$content = ob_get_clean();
require APP_PATH . '/Views/admin/layouts/app.php';
