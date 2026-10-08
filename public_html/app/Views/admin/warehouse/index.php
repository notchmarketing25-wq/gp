<?php
$pageTitle  = 'المخزن';
$breadcrumb = [['label'=>'المخزن']];
$a = ADMIN_PREFIX;
ob_start();
?>

<!-- Stats -->
<div class="ph">
  <div class="ph-left"><h1>إدارة المخزن</h1><p>نظرة عامة على المخزون</p></div>
  <div class="ph-right">
    <a href="<?= adminUrl('warehouse/movements') ?>" class="btn btn-secondary">📋 سجل الحركات</a>
    <button class="btn btn-primary" onclick="document.getElementById('bulkModal').classList.add('open')"><?= svgIcon('upload', 15) ?> استيراد CSV</button>
  </div>
</div>

<div class="stats">
  <div class="stat"><div class="stat-icon" style="background:rgba(124,92,252,.12)"><?= svgIcon('package', 20) ?></div><div class="stat-label">إجمالي المنتجات</div><div class="stat-value"><?= number_format($stats['total_products']) ?></div></div>
  <div class="stat"><div class="stat-icon" style="background:rgba(239,68,68,.1)"><?= svgIcon('close', 20) ?></div><div class="stat-label">نفذت من المخزون</div><div class="stat-value" style="color:var(--red)"><?= number_format($stats['out_of_stock']) ?></div></div>
  <div class="stat"><div class="stat-icon" style="background:rgba(245,158,11,.1)"><?= svgIcon('shield', 20) ?></div><div class="stat-label">مخزون منخفض (≤5)</div><div class="stat-value" style="color:var(--yellow)"><?= number_format($stats['low_stock']) ?></div></div>
  <div class="stat"><div class="stat-icon" style="background:rgba(34,197,94,.1)"><?= svgIcon('store', 20) ?></div><div class="stat-label">قيمة المخزون</div><div class="stat-value" style="font-size:16px"><?= money($stats['total_value']) ?></div><div class="stat-sub">تكلفة: <?= money($stats['total_cost']) ?></div></div>
</div>

<!-- Adjust stock modal -->
<div class="modal-overlay" id="adjustModal">
  <div class="modal" style="max-width:440px">
    <h3 style="margin-bottom:4px">تعديل المخزون</h3>
    <p id="adjustProductName" style="color:var(--t2);margin-bottom:18px;font-size:13px"></p>
    <input type="hidden" id="adjustProductId">
    <input type="hidden" id="adjustVariantId" value="">
    <div class="form-group" style="margin-bottom:12px">
      <label class="form-label">نوع التعديل</label>
      <select id="adjustType" class="form-select">
        <option value="in">➕ إضافة للمخزون (وارد)</option>
        <option value="out">➖ خصم من المخزون (صادر)</option>
        <option value="adjustment">🔄 ضبط الكمية (تحديد مباشر)</option>
      </select>
    </div>
    <div class="form-group" style="margin-bottom:12px">
      <label class="form-label">الكمية</label>
      <input type="number" id="adjustQty" class="form-input" min="0" placeholder="0">
    </div>
    <div class="form-group" style="margin-bottom:12px">
      <label class="form-label">السبب</label>
      <select id="adjustReason" class="form-select">
        <option value="شراء بضاعة">شراء بضاعة</option>
        <option value="مرتجع عميل">مرتجع عميل</option>
        <option value="تلف/فقدان">تلف/فقدان</option>
        <option value="جرد">جرد</option>
        <option value="تحويل">تحويل</option>
        <option value="تصحيح خطأ">تصحيح خطأ</option>
        <option value="">أخرى</option>
      </select>
    </div>
    <div class="form-group" style="margin-bottom:18px">
      <label class="form-label">ملاحظات</label>
      <textarea id="adjustNotes" class="form-textarea" rows="2" placeholder="تفاصيل إضافية..."></textarea>
    </div>
    <div class="modal-actions">
      <button class="btn btn-secondary" onclick="closeAdjust()">إلغاء</button>
      <button class="btn btn-primary" onclick="submitAdjust()">تأكيد التعديل</button>
    </div>
  </div>
</div>

<!-- Bulk import modal -->
<div class="modal-overlay" id="bulkModal">
  <div class="modal" style="max-width:460px">
    <h3 style="margin-bottom:4px">استيراد مخزون CSV</h3>
    <p style="color:var(--t2);margin-bottom:16px;font-size:13px">ارفع ملف CSV يحتوي على: <code style="background:var(--bg3);padding:2px 6px;border-radius:4px">sku,qty</code></p>
    <div style="background:var(--bg3);border:1px solid var(--bd);border-radius:7px;padding:12px;margin-bottom:14px;font-family:monospace;font-size:12px;color:var(--t2)">
      sku,qty<br>
      NT-001,50<br>
      NT-002,30<br>
      NT-003,0
    </div>
    <form method="POST" action="<?= adminUrl('warehouse/bulk-adjust') ?>" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <div class="form-group" style="margin-bottom:18px">
        <label class="form-label">ملف CSV</label>
        <input type="file" name="csv" class="form-input" accept=".csv,.txt" required style="padding:7px">
      </div>
      <div class="modal-actions">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('bulkModal').classList.remove('open')">إلغاء</button>
        <button type="submit" class="btn btn-primary">استيراد</button>
      </div>
    </form>
  </div>
</div>

<!-- Products table -->
<div class="card">
  <div class="card-header">
    <span class="card-title">المنتجات والمخزون</span>
    <div style="display:flex;gap:8px">
      <input type="text" id="stockSearch" class="search-input" placeholder="بحث..." style="width:200px" oninput="filterTable(this.value)">
      <select id="stockFilter" class="filter-select" onchange="filterByStatus(this.value)">
        <option value="">الكل</option>
        <option value="out">نفذ</option>
        <option value="low">منخفض</option>
        <option value="ok">متاح</option>
      </select>
      <button class="btn btn-primary btn-sm" id="quickEditBtn" onclick="toggleQuickEdit()"><?= svgIcon('edit', 15) ?> تعديل سريع</button>
    </div>
  </div>
  <div class="table-wrap">
    <table id="stockTable">
      <thead>
        <tr>
          <th>المنتج</th>
          <th>SKU</th>
          <th>الفئة</th>
          <th>المخزون الحالي</th>
          <th>قيمة المخزون</th>
          <th>الحالة</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($products as $p):
          $hasVariants = !empty($p['has_variants']);
          $stock  = (int)$p['effective_stock'];
          $status = !$p['track_stock'] ? 'none' : ($stock === 0 ? 'out' : ($stock <= 5 ? 'low' : 'ok'));
          $badgeClass = match($status){'out'=>'badge-err','low'=>'badge-warn','ok'=>'badge-ok',default=>'badge-dim'};
          $badgeLabel = match($status){'out'=>'نفذ','low'=>'منخفض','ok'=>'متاح',default=>'لا يُتتبع'};
        ?>
          <tr data-status="<?= $status ?>" data-name="<?= strtolower(e($p['name'])) ?>" data-pid="<?= $p['id'] ?>">
            <td>
              <div class="prod-cell">
                <?php if ($hasVariants): ?>
                  <button id="variantToggleIcon-<?= $p['id'] ?>" class="variant-toggle-btn" onclick="toggleVariants(<?= $p['id'] ?>)" title="عرض الاختيارات" style="background:none;border:1px solid var(--bd);border-radius:6px;width:24px;height:24px;cursor:pointer;color:var(--t2);flex-shrink:0;transition:transform .15s">▸</button>
                <?php endif; ?>
                <?php if ($p['thumbnail']): ?>
                  <img src="<?= uploadUrl($p['thumbnail']) ?>" class="prod-img" alt="">
                <?php else: ?>
                  <div class="prod-img-ph">📦</div>
                <?php endif; ?>
                <div>
                  <div class="prod-name"><?= e($p['name']) ?></div>
                  <?php if ($hasVariants): ?>
                    <div class="prod-sku" style="color:var(--ac)"><?= count($p['variants']) ?> اختيار</div>
                  <?php elseif ($p['brand_name']): ?>
                    <div class="prod-sku"><?= e($p['brand_name']) ?></div>
                  <?php endif; ?>
                </div>
              </div>
            </td>
            <td class="td-mono td-dim"><?= e($p['sku']??'—') ?></td>
            <td class="td-dim"><?= e($p['collection_name']??'—') ?></td>
            <td>
              <?php if (!$p['track_stock']): ?>
                <span style="color:var(--t3)">غير محدود</span>
              <?php elseif ($hasVariants): ?>
                <span style="font-size:18px;font-weight:800;color:<?= $stock===0?'var(--red)':($stock<=5?'var(--yellow)':'var(--t1)') ?>"><?= number_format($stock) ?></span>
                <div style="font-size:10.5px;color:var(--t3)">إجمالي الاختيارات</div>
              <?php else: ?>
                <span class="stock-view" style="font-size:18px;font-weight:800;color:<?= $stock===0?'var(--red)':($stock<=5?'var(--yellow)':'var(--t1)') ?>"><?= number_format($stock) ?></span>
                <input type="number" class="stock-edit-input" data-pid="<?= $p['id'] ?>" data-original="<?= $stock ?>" value="<?= $stock ?>" min="0" style="display:none;width:80px;padding:5px 8px;border:1px solid var(--ac);border-radius:6px;background:var(--bg1);color:var(--t1);font-weight:700;font-size:14px">
              <?php endif; ?>
            </td>
            <td style="font-size:12px;color:var(--t2)"><?= $p['track_stock'] ? money($stock * ($p['price']??0)) : '—' ?></td>
            <td><span class="badge <?= $badgeClass ?>"><?= $badgeLabel ?></span></td>
            <td>
              <?php if ($hasVariants): ?>
                <button class="btn btn-secondary btn-sm" onclick="toggleVariants(<?= $p['id'] ?>)">
                  الاختيارات ▾
                </button>
              <?php else: ?>
                <button class="btn btn-secondary btn-sm"
                  onclick="openAdjust(<?= $p['id'] ?>,'<?= e(addslashes($p['name'])) ?>')">
                  ✏️ تعديل
                </button>
              <?php endif; ?>
            </td>
          </tr>
          <?php if ($hasVariants): ?>
            <tr class="variant-row" id="variantRow-<?= $p['id'] ?>" style="display:none">
              <td colspan="7" style="background:var(--bg2);padding:0">
                <table style="width:100%">
                  <thead>
                    <tr style="font-size:11px;color:var(--t3)">
                      <th style="padding:8px 16px;text-align:right;width:40%">الاختيار</th>
                      <th style="padding:8px;text-align:right">SKU</th>
                      <th style="padding:8px;text-align:right">المخزون</th>
                      <th style="padding:8px;text-align:right"></th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($p['variants'] as $v):
                      $vStock = (int)$v['stock'];
                      $vColor = $vStock===0?'var(--red)':($vStock<=5?'var(--yellow)':'var(--t1)');
                    ?>
                      <tr>
                        <td style="padding:8px 16px 8px 36px;font-size:13px"><?= e($v['title']) ?></td>
                        <td class="td-mono td-dim" style="padding:8px;font-size:12px"><?= e($v['sku']??'—') ?></td>
                        <td style="padding:8px">
                          <span class="variant-stock-view" style="font-weight:700;color:<?= $vColor ?>"><?= number_format($vStock) ?></span>
                          <input type="number" class="variant-stock-edit-input" data-vid="<?= $v['id'] ?>" data-original="<?= $vStock ?>" value="<?= $vStock ?>" min="0" style="display:none;width:70px;padding:4px 7px;border:1px solid var(--ac);border-radius:6px;background:var(--bg1);color:var(--t1);font-weight:700;font-size:13px">
                        </td>
                        <td style="padding:8px">
                          <button class="btn btn-secondary btn-sm" onclick="openAdjust(<?= $p['id'] ?>,'<?= e(addslashes($p['name'] . ' — ' . $v['title'])) ?>',<?= $v['id'] ?>)">
                            ✏️ تعديل
                          </button>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </td>
            </tr>
          <?php endif; ?>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Recent movements -->
<?php if (!empty($movements)): ?>
<div class="card" style="margin-top:16px">
  <div class="card-header">
    <span class="card-title">آخر حركات المخزون</span>
    <a href="<?= adminUrl('warehouse/movements') ?>" class="btn btn-secondary btn-sm">عرض الكل</a>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>المنتج</th><th>النوع</th><th>الكمية</th><th>قبل</th><th>بعد</th><th>السبب</th><th>التاريخ</th></tr></thead>
      <tbody>
        <?php foreach (array_slice($movements,0,10) as $m): ?>
          <tr>
            <td style="font-weight:500;font-size:12px"><?= e($m['product_name']) ?></td>
            <td>
              <span class="badge <?= match($m['type']){'in'=>'badge-ok','out'=>'badge-err','adjustment'=>'badge-warn','transfer'=>'badge-info'} ?>">
                <?= match($m['type']){'in'=>'وارد','out'=>'صادر','adjustment'=>'تسوية','transfer'=>'تحويل'} ?>
              </span>
            </td>
            <td style="font-weight:700;color:<?= $m['type']==='in'?'var(--green)':'var(--red)' ?>"><?= ($m['type']==='in'?'+':($m['type']==='out'?'-':'')) . abs($m['qty']) ?></td>
            <td class="td-dim"><?= $m['qty_before'] ?></td>
            <td style="font-weight:600"><?= $m['qty_after'] ?></td>
            <td class="td-dim" style="font-size:11px"><?= e($m['reason']??'—') ?></td>
            <td class="td-dim" style="font-size:11px"><?= formatDateTime($m['created_at']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<!-- Floating quick-edit save bar -->
<div id="quickEditBar" style="display:none;position:sticky;bottom:16px;margin-top:14px">
  <div class="card" style="padding:14px 20px;display:flex;align-items:center;justify-content:space-between;box-shadow:var(--shadow-lg)">
    <div style="font-size:13px;color:var(--t2)">تعديل سريع مفعّل — غيّر أي رقم في المخزون واضغط حفظ. <span id="changedCount" style="font-weight:700;color:var(--t1)">0</span> تغيير</div>
    <div style="display:flex;gap:8px">
      <button class="btn btn-secondary" onclick="toggleQuickEdit()">إلغاء</button>
      <button class="btn btn-primary" onclick="saveAllStock()"><?= svgIcon('check', 15) ?> حفظ كل التغييرات</button>
    </div>
  </div>
</div>

<?php
$csrf = Session::csrf();
$bulkSaveUrl = adminUrl('warehouse/bulk-save');
$extraScript = "
function openAdjust(id, name, variantId) {
  document.getElementById('adjustProductId').value = id;
  document.getElementById('adjustVariantId').value = variantId || '';
  document.getElementById('adjustProductName').textContent = name;
  document.getElementById('adjustModal').classList.add('open');
}
function closeAdjust() {
  document.getElementById('adjustModal').classList.remove('open');
}
async function submitAdjust() {
  const pid    = document.getElementById('adjustProductId').value;
  const vid    = document.getElementById('adjustVariantId').value;
  const type   = document.getElementById('adjustType').value;
  const qty    = document.getElementById('adjustQty').value;
  const reason = document.getElementById('adjustReason').value;
  const notes  = document.getElementById('adjustNotes').value;
  if (!qty) { alert('أدخل الكمية'); return; }
  const form = new FormData();
  form.append('product_id', pid);
  if (vid) form.append('variant_id', vid);
  form.append('type', type);
  form.append('qty', qty);
  form.append('reason', reason);
  form.append('notes', notes);
  form.append('_csrf', '<?= addslashes(Session::csrf()) ?>');
  const r = await fetch('" . adminUrl('warehouse/adjust') . "', {method:'POST',body:form});
  const d = await r.json();
  if (d.ok) {
    closeAdjust();
    location.reload();
  } else {
    alert(d.msg || 'خطأ');
  }
}
function toggleVariants(pid) {
  const row  = document.getElementById('variantRow-' + pid);
  const icon = document.getElementById('variantToggleIcon-' + pid);
  if (!row) return;
  const open = row.style.display !== 'none';
  row.style.display = open ? 'none' : '';
  if (icon) icon.style.transform = open ? '' : 'rotate(90deg)';
}
document.getElementById('adjustModal')?.addEventListener('click', e => {
  if (e.target === document.getElementById('adjustModal')) closeAdjust();
});
document.getElementById('bulkModal')?.addEventListener('click', e => {
  if (e.target === document.getElementById('bulkModal')) document.getElementById('bulkModal').classList.remove('open');
});
function filterTable(q) {
  q = q.toLowerCase();
  document.querySelectorAll('#stockTable tbody tr[data-pid]').forEach(r => {
    const show = r.dataset.name?.includes(q);
    r.style.display = show ? '' : 'none';
    // Keep a hidden product's expanded variant row hidden along with it.
    const vrow = document.getElementById('variantRow-' + r.dataset.pid);
    if (!show && vrow) vrow.style.display = 'none';
  });
}
function filterByStatus(s) {
  document.querySelectorAll('#stockTable tbody tr[data-pid]').forEach(r => {
    const show = (!s || r.dataset.status === s);
    r.style.display = show ? '' : 'none';
    const vrow = document.getElementById('variantRow-' + r.dataset.pid);
    if (!show && vrow) vrow.style.display = 'none';
  });
}

// Quick edit mode (spreadsheet-style bulk stock editing)
let quickEditOn = false;
function toggleQuickEdit(){
  quickEditOn = !quickEditOn;
  document.querySelectorAll('.stock-view, .variant-stock-view').forEach(el => el.style.display = quickEditOn ? 'none' : '');
  document.querySelectorAll('.stock-edit-input, .variant-stock-edit-input').forEach(el => { el.style.display = quickEditOn ? 'inline-block' : 'none'; el.value = el.dataset.original; });
  // Quick-edit on a variant product happens at the variant level, so auto-expand every
  // product that has variants once quick-edit turns on, instead of making the admin open each one.
  if (quickEditOn) {
    document.querySelectorAll('.variant-row').forEach(row => { row.style.display = ''; });
    document.querySelectorAll('.variant-toggle-btn').forEach(icon => icon.style.transform = 'rotate(90deg)');
  }
  document.getElementById('quickEditBar').style.display = quickEditOn ? 'block' : 'none';
  document.getElementById('quickEditBtn').textContent = quickEditOn ? '✕ إلغاء التعديل' : '✏️ تعديل سريع';
  updateChangedCount();
}
document.querySelectorAll('.stock-edit-input, .variant-stock-edit-input').forEach(inp => inp.addEventListener('input', updateChangedCount));
function updateChangedCount(){
  let n = 0;
  document.querySelectorAll('.stock-edit-input, .variant-stock-edit-input').forEach(inp => { if (inp.value !== inp.dataset.original) n++; });
  const el = document.getElementById('changedCount');
  if (el) el.textContent = n;
}
async function saveAllStock(){
  const fd = new FormData();
  fd.append('_csrf', '" . addslashes($csrf) . "');
  let n = 0;
  document.querySelectorAll('.stock-edit-input').forEach(inp => {
    if (inp.value !== inp.dataset.original) { fd.append('stock['+inp.dataset.pid+']', inp.value); n++; }
  });
  document.querySelectorAll('.variant-stock-edit-input').forEach(inp => {
    if (inp.value !== inp.dataset.original) { fd.append('variant_stock['+inp.dataset.vid+']', inp.value); n++; }
  });
  if (!n) { alert('لا توجد تغييرات لحفظها'); return; }
  const r = await fetch('" . $bulkSaveUrl . "', {method:'POST', body:fd});
  const d = await r.json();
  if (d.ok) { location.reload(); } else { alert(d.msg || 'خطأ'); }
}
";
$content = ob_get_clean();
require APP_PATH . '/Views/admin/layouts/app.php';
