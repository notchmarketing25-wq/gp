<?php
$pageTitle  = 'الضمانات';
$breadcrumb = [['label'=>'الضمانات']];
$a = ADMIN_PREFIX;
$products = Database::fetchAll("SELECT id,name FROM products WHERE status='active' ORDER BY name");
ob_start();
?>
<div class="ph">
  <div class="ph-left"><h1><span style="display:inline-flex;align-items:center;gap:9px"><?= svgIcon('shield', 22) ?> إدارة الضمانات</span></h1><p><?= number_format($total) ?> ضمان مسجل</p></div>
  <div class="ph-right">
    <button class="btn btn-secondary" onclick="document.getElementById('checkModal').classList.add('open')"><?= svgIcon('search', 15) ?> تحقق من ضمان</button>
    <button class="btn btn-primary" onclick="document.getElementById('addModal').classList.add('open')">+ تسجيل ضمان</button>
  </div>
</div>

<!-- Check warranty modal -->
<div class="modal-overlay" id="checkModal">
  <div class="modal">
    <div class="modal-head"><h3>🔍 التحقق من الضمان</h3><button class="btn btn-ghost btn-icon" onclick="this.closest('.modal-overlay').classList.remove('open')" style="font-size:18px">×</button></div>
    <div class="form-group" style="margin-bottom:14px">
      <label class="form-label">الرقم التسلسلي أو الباركود</label>
      <input type="text" id="checkSerial" class="form-input" placeholder="أدخل الرقم التسلسلي أو امسح الباركود..." autofocus>
    </div>
    <button class="btn btn-primary btn-full" onclick="checkWarranty()">تحقق</button>
    <div id="checkResult" style="margin-top:16px"></div>
  </div>
</div>

<!-- Add warranty modal -->
<div class="modal-overlay" id="addModal">
  <div class="modal modal-lg">
    <div class="modal-head"><h3>+ تسجيل ضمان جديد</h3><button class="btn btn-ghost btn-icon" onclick="this.closest('.modal-overlay').classList.remove('open')" style="font-size:18px">×</button></div>
    <form method="POST" action="<?= adminUrl('warranties/create') ?>">
      <?= csrf_field() ?>
      <div class="form-grid form-row-2" style="margin-bottom:14px">
        <div class="form-group"><label class="form-label">المنتج <span class="req">*</span></label>
          <select name="product_id" class="form-select" required>
            <option value="">اختر منتج</option>
            <?php foreach ($products as $p): ?><option value="<?=$p['id']?>"><?= e($p['name']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="form-group"><label class="form-label">رقم الطلب</label><input type="text" name="order_id" class="form-input" placeholder="اختياري"></div>
        <div class="form-group"><label class="form-label">الرقم التسلسلي <span class="req">*</span></label><input type="text" name="serial_number" class="form-input" required placeholder="SN-XXXXXXXX"></div>
        <div class="form-group"><label class="form-label">الباركود</label><input type="text" name="barcode" class="form-input" placeholder="اختياري"></div>
        <div class="form-group"><label class="form-label">اسم العميل <span class="req">*</span></label><input type="text" name="customer_name" class="form-input" required></div>
        <div class="form-group"><label class="form-label">هاتف العميل <span class="req">*</span></label><input type="text" name="customer_phone" class="form-input" required></div>
        <div class="form-group"><label class="form-label">البريد الإلكتروني</label><input type="email" name="customer_email" class="form-input"></div>
        <div class="form-group"><label class="form-label">تاريخ الشراء</label><input type="date" name="purchase_date" class="form-input" value="<?= date('Y-m-d') ?>"></div>
        <div class="form-group"><label class="form-label">فترة الضمان</label>
          <select name="warranty_period" class="form-select">
            <option value="6 أشهر">6 أشهر</option>
            <option value="1 سنة" selected>سنة واحدة</option>
            <option value="2 سنة">سنتان</option>
            <option value="3 سنة">3 سنوات</option>
          </select>
        </div>
        <div class="form-group"><label class="form-label">ملاحظات</label><input type="text" name="notes" class="form-input" placeholder="اختياري"></div>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn btn-secondary" onclick="this.closest('.modal-overlay').classList.remove('open')">إلغاء</button>
        <button type="submit" class="btn btn-primary"><?= svgIcon('shield', 15) ?> تسجيل الضمان</button>
      </div>
    </form>
  </div>
</div>

<!-- Filters -->
<div class="card" style="margin-bottom:14px">
  <div class="card-body" style="padding:12px 16px">
    <form method="GET" class="filter-bar">
      <div class="search-box">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
        <input type="text" name="search" class="search-input" value="<?= e($search??'') ?>" placeholder="بحث بالسيريال، الاسم، الهاتف...">
      </div>
      <select name="status" class="filter-select" onchange="this.form.submit()">
        <option value="">كل الحالات</option>
        <option value="active"  <?= ($status??'')==='active'?'selected':'' ?>>✅ نشط</option>
        <option value="expired" <?= ($status??'')==='expired'?'selected':'' ?>>⏰ منتهي</option>
        <option value="claimed" <?= ($status??'')==='claimed'?'selected':'' ?>>🔧 مُطالب به</option>
        <option value="void"    <?= ($status??'')==='void'?'selected':'' ?>>❌ ملغي</option>
      </select>
      <button class="btn btn-secondary">بحث</button>
    </form>
  </div>
</div>

<div class="card">
  <div class="table-wrap">
    <table>
      <thead><tr><th>الرقم التسلسلي</th><th>المنتج</th><th>المتجر</th><th>العميل</th><th>تاريخ الشراء</th><th>ينتهي في</th><th>المستندات</th><th>الحالة</th><th></th></tr></thead>
      <tbody>
        <?php if (empty($warranties)): ?>
          <tr><td colspan="9"><div class="empty"><div class="empty-icon"><?= svgIcon('shield', 32) ?></div><div class="empty-title">لا توجد ضمانات مسجلة</div></div></td></tr>
        <?php else: foreach ($warranties as $w):
          $expired = strtotime($w['expiry_date']) < time();
          $daysLeft = (int)ceil((strtotime($w['expiry_date'])-time())/86400);
        ?>
          <tr>
            <td><code style="font-size:12px;color:var(--ac);font-family:monospace;font-weight:600"><?= e($w['serial_number']) ?></code></td>
            <td style="font-size:12px"><?= e($w['product_name']??'—') ?></td>
            <td class="td-dim" style="font-size:12px"><?= e($w['retailer_name']??'—') ?></td>
            <td>
              <div style="font-weight:500;font-size:13px"><?= e($w['customer_name']) ?></div>
              <div style="font-size:11px;color:var(--t3)"><?= e($w['customer_phone']) ?></div>
            </td>
            <td class="td-dim"><?= formatDate($w['purchase_date']) ?></td>
            <td>
              <div style="font-size:12px"><?= formatDate($w['expiry_date']) ?></div>
              <?php if ($w['status']==='active' && !$expired): ?>
                <div style="font-size:11px;color:<?=$daysLeft<=30?'var(--yellow)':'var(--green)' ?>"><?= $daysLeft ?> يوم متبقي</div>
              <?php endif; ?>
            </td>
            <td>
              <div style="display:flex;gap:4px">
                <?php if ($w['receipt_image']??null): ?><a href="<?= uploadUrl($w['receipt_image']) ?>" target="_blank" title="الإيصال">🧾</a><?php endif; ?>
                <?php if ($w['invoice_image']??null): ?><a href="<?= uploadUrl($w['invoice_image']) ?>" target="_blank" title="الفاتورة">📄</a><?php endif; ?>
                <?php if ($w['warranty_card_image']??null): ?><a href="<?= uploadUrl($w['warranty_card_image']) ?>" target="_blank" title="كارت الضمان">🪪</a><?php endif; ?>
                <?php if (!($w['receipt_image']??null) && !($w['invoice_image']??null) && !($w['warranty_card_image']??null)): ?><span class="td-dim">—</span><?php endif; ?>
              </div>
            </td>
            <td>
              <span class="badge <?= match($w['status']){'active'=>'badge-ok','expired'=>'badge-err','claimed'=>'badge-warn',default=>'badge-dim'} ?>">
                <?= match($w['status']){'active'=>'نشط','expired'=>'منتهي','claimed'=>'مُطالب','void'=>'ملغي',default=>$w['status']} ?>
              </span>
            </td>
            <td>
              <button class="btn btn-secondary btn-sm" onclick="editWarranty(<?= htmlspecialchars(json_encode($w),ENT_QUOTES) ?>)">تعديل</button>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
  <?php if ($lastPage > 1): ?>
    <div class="pagination">
      <?php for ($i=1;$i<=$lastPage;$i++): ?>
        <a href="?page=<?=$i?>&search=<?=urlencode($search??'')?>&status=<?=urlencode($status??'')?>" class="page-btn <?=$i===$page?'active':''?>"><?=$i?></a>
      <?php endfor; ?>
    </div>
  <?php endif; ?>
</div>

<!-- Edit modal -->
<div class="modal-overlay" id="editModal">
  <div class="modal">
    <div class="modal-head"><h3>✏️ تعديل الضمان</h3><button class="btn btn-ghost btn-icon" onclick="this.closest('.modal-overlay').classList.remove('open')" style="font-size:18px">×</button></div>
    <form method="POST" id="editForm">
      <?= csrf_field() ?>
      <div class="form-group" style="margin-bottom:12px">
        <label class="form-label">الحالة</label>
        <select name="status" id="editStatus" class="form-select">
          <option value="active">✅ نشط</option>
          <option value="expired">⏰ منتهي</option>
          <option value="claimed">🔧 مُطالب به</option>
          <option value="void">❌ ملغي</option>
        </select>
      </div>
      <div class="form-group" style="margin-bottom:14px"><label class="form-label">ملاحظات</label><textarea name="notes" id="editNotes" class="form-textarea" rows="2"></textarea></div>
      <div class="modal-actions"><button type="button" class="btn btn-secondary" onclick="this.closest('.modal-overlay').classList.remove('open')">إلغاء</button><button type="submit" class="btn btn-primary">حفظ</button></div>
    </form>
  </div>
</div>

<?php
$extraScript = "
async function checkWarranty(){
  const serial=document.getElementById('checkSerial').value.trim();
  if(!serial) return;
  const fd=new FormData(); fd.append('serial',serial);
  const r=await fetch('/$a/warranties/check',{method:'POST',body:fd});
  const d=await r.json();
  const div=document.getElementById('checkResult');
  if(d.ok){
    const w=d.warranty;
    const expired=new Date(w.expiry_date)<new Date();
    const color=w.status==='active'&&!expired?'var(--green)':w.status==='expired'?'var(--red)':'var(--yellow)';
    div.innerHTML='<div style=\"background:var(--bg3);border:1px solid var(--bd);border-radius:10px;padding:16px\">'+
      '<div style=\"font-size:18px;font-weight:800;color:'+color+';margin-bottom:10px\">'+(w.status==='active'&&!expired?'✅ ضمان ساري':'❌ ضمان '+w.status)+'</div>'+
      '<div style=\"display:grid;grid-template-columns:1fr 1fr;gap:8px;font-size:12px\">'+
      '<div><span style=\"color:var(--t3)\">المنتج:</span> <strong>'+w.product_name+'</strong></div>'+
      '<div><span style=\"color:var(--t3)\">العميل:</span> <strong>'+w.customer_name+'</strong></div>'+
      '<div><span style=\"color:var(--t3)\">الهاتف:</span> <strong>'+w.customer_phone+'</strong></div>'+
      '<div><span style=\"color:var(--t3)\">ينتهي:</span> <strong>'+w.expiry_date+'</strong></div>'+
      '</div></div>';
  } else {
    div.innerHTML='<div style=\"background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.2);border-radius:8px;padding:12px;color:var(--red);font-size:13px\">❌ '+d.msg+'</div>';
  }
}
document.getElementById('checkSerial')?.addEventListener('keypress',e=>{if(e.key==='Enter')checkWarranty()});
function editWarranty(w){
  document.getElementById('editForm').action='/$a/warranties/'+w.id+'/update';
  document.getElementById('editStatus').value=w.status;
  document.getElementById('editNotes').value=w.notes||'';
  document.getElementById('editModal').classList.add('open');
}
";
$content = ob_get_clean();
require APP_PATH . '/Views/admin/layouts/app.php';
