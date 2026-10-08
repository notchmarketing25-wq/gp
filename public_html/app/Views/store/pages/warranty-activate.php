<?php
$pageTitle = 'تفعيل الضمان — ' . SettingModel::get('store_name', APP_NAME);
ob_start();
?>
<div class="container" style="padding-top:32px;padding-bottom:80px;max-width:600px;margin:0 auto">
  <div style="text-align:center;margin-bottom:28px">
    <div class="hero-badge" style="margin:0 auto 16px"><span class="hero-badge-dot"></span> حماية منتجك</div>
    <h1 style="font-family:var(--font-body);font-size:26px;font-weight:900;margin-bottom:8px">🛡️ تفعيل الضمان</h1>
    <p style="color:var(--text2);font-size:14px">سجّل منتجك دلوقتي عشان تضمن حقك في الصيانة والاستبدال</p>
  </div>

  <div style="display:flex;gap:8px;margin-bottom:20px">
    <button class="btn btn-primary" style="flex:1" onclick="showTab('activate')" id="tabActivateBtn">✅ تفعيل ضمان جديد</button>
    <button class="btn btn-secondary" style="flex:1" onclick="showTab('check')" id="tabCheckBtn">🔍 الاستعلام عن ضمان</button>
  </div>

  <!-- Activation form -->
  <div id="tabActivate" style="background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:28px">
    <form method="POST" action="<?= url('warranty/activate') ?>" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <div class="form-group">
        <label class="form-label">المنتج <span>*</span></label>
        <select name="product_id" class="form-select" required>
          <option value="">اختر المنتج</option>
          <?php foreach ($products as $p): ?><option value="<?=$p['id']?>"><?= e($p['name']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">الرقم التسلسلي (Serial Number) <span>*</span></label>
        <input type="text" name="serial_number" class="form-input" required placeholder="مطبوع على المنتج أو علبته">
      </div>
      <div class="form-group">
        <label class="form-label">الباركود (اختياري)</label>
        <input type="text" name="barcode" class="form-input">
      </div>
      <div class="form-group">
        <label class="form-label">اسمك <span>*</span></label>
        <input type="text" name="customer_name" class="form-input" required value="<?= isStoreLoggedIn() ? e(storeUser()['name']) : '' ?>">
      </div>
      <div class="form-group">
        <label class="form-label">رقم الهاتف <span>*</span></label>
        <input type="tel" name="customer_phone" class="form-input" required>
      </div>
      <div class="form-group">
        <label class="form-label">البريد الإلكتروني (اختياري)</label>
        <input type="email" name="customer_email" class="form-input" value="<?= isStoreLoggedIn() ? e(storeUser()['email']) : '' ?>">
      </div>
      <div class="form-group">
        <label class="form-label">اشتريت من (اختياري)</label>
        <select name="retailer_id" class="form-select">
          <option value="">— لم يحدد —</option>
          <?php foreach ($retailers as $r): ?><option value="<?=$r['id']?>"><?= e($r['name']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">تاريخ الشراء <span>*</span></label>
        <input type="date" name="purchase_date" class="form-input" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>" required>
      </div>
      <div class="form-group">
        <label class="form-label">صورة الإيصال (اختياري)</label>
        <input type="file" name="receipt_image" class="form-input" accept="image/*" style="padding:8px">
      </div>
      <div class="form-group">
        <label class="form-label">صورة الفاتورة (اختياري)</label>
        <input type="file" name="invoice_image" class="form-input" accept="image/*" style="padding:8px">
      </div>
      <div class="form-group">
        <label class="form-label">صورة كارت الضمان (اختياري)</label>
        <input type="file" name="warranty_card_image" class="form-input" accept="image/*" style="padding:8px">
      </div>
      <button type="submit" class="btn btn-primary btn-full btn-lg" style="margin-top:8px">🛡️ تفعيل الضمان</button>
    </form>
  </div>

  <!-- Check status -->
  <div id="tabCheck" style="display:none;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:28px">
    <div class="form-group">
      <label class="form-label">الرقم التسلسلي أو الباركود</label>
      <div style="display:flex;gap:8px">
        <input type="text" id="checkSerialInput" class="form-input" placeholder="أدخل الرقم">
        <button class="btn btn-primary" onclick="checkWarranty()">بحث</button>
      </div>
    </div>
    <div id="checkResult" style="margin-top:16px"></div>
  </div>
</div>

<script>
function showTab(t){
  document.getElementById('tabActivate').style.display = t==='activate' ? 'block' : 'none';
  document.getElementById('tabCheck').style.display = t==='check' ? 'block' : 'none';
  document.getElementById('tabActivateBtn').className = 'btn ' + (t==='activate' ? 'btn-primary' : 'btn-secondary');
  document.getElementById('tabCheckBtn').className = 'btn ' + (t==='check' ? 'btn-primary' : 'btn-secondary');
}
async function checkWarranty(){
  const serial = document.getElementById('checkSerialInput').value.trim();
  const result = document.getElementById('checkResult');
  if (!serial) { result.innerHTML = '<p style="color:var(--red);font-size:13px">أدخل رقم صحيح</p>'; return; }
  result.innerHTML = '<p style="color:var(--text3);font-size:13px">⏳ جاري البحث...</p>';
  try {
    const fd = new FormData();
    fd.append('check_serial', serial);
    const r = await fetch('<?= url('warranty/check') ?>', {method:'POST', body:fd});
    const d = await r.json();
    if (!d.ok) { result.innerHTML = '<p style="color:var(--red);font-size:13px">'+d.msg+'</p>'; return; }
    const w = d.warranty;
    const statusMap = {active:['✅','ساري','var(--green)'],expired:['⏰','منتهي','var(--red)'],void:['❌','ملغي','var(--red)'],claimed:['🔧','تم استخدامه','var(--yellow)']};
    const [icon,label,color] = statusMap[w.status] || ['❓',w.status,'var(--text)'];
    result.innerHTML = `
      <div style="background:var(--bg2);border-radius:10px;padding:16px">
        <div style="font-weight:700;margin-bottom:8px">${w.product_name || 'منتج'}</div>
        <div style="font-size:13px;color:var(--text2);line-height:2">
          الحالة: <strong style="color:${color}">${icon} ${label}</strong><br>
          تاريخ الشراء: ${w.purchase_date}<br>
          ينتهي في: ${w.expiry_date}
        </div>
      </div>`;
  } catch(e) { result.innerHTML = '<p style="color:var(--red);font-size:13px">تعذر الاتصال بالسيرفر</p>'; }
}
</script>
<?php
$content = ob_get_clean();
require APP_PATH . '/Views/store/layouts/app.php';
