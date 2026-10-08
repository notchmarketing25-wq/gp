<?php
$pageTitle  = $type === 'receipt' ? 'إذن استلام جديد' : 'إذن صرف جديد';
$breadcrumb = [['label'=>'المخزون','url'=>adminUrl('warehouse')], ['label'=>'الأذونات','url'=>adminUrl('inventory')], ['label'=>$pageTitle]];
$a = ADMIN_PREFIX;
$isReceipt = $type === 'receipt';
$accentColor = $isReceipt ? 'var(--green,#16a34a)' : 'var(--red,#dc2626)';
$accentBg    = $isReceipt ? 'rgba(34,197,94,.1)' : 'rgba(220,38,38,.1)';
ob_start();
?>
<div class="ph">
  <div class="ph-left">
    <h1>
      <span style="display:inline-flex;align-items:center;gap:9px">
        <span style="width:38px;height:38px;border-radius:10px;background:<?= $accentBg ?>;color:<?= $accentColor ?>;display:inline-flex;align-items:center;justify-content:center"><?= svgIcon($isReceipt?'upload':'truck', 19) ?></span>
        <?= $pageTitle ?>
      </span>
    </h1>
    <p><?= $isReceipt ? 'تسجيل استلام بضاعة جديدة (شراء) — يزود المخزون' : 'تسجيل صرف بضاعة (بيع أو تالف) — ينقص المخزون' ?></p>
  </div>
</div>

<form method="POST" action="/<?= $a ?>/inventory/create">
  <?= csrf_field() ?>
  <input type="hidden" name="type" value="<?= e($type) ?>">

  <div class="card" style="margin-bottom:16px;border-top:3px solid <?= $accentColor ?>">
    <div class="card-body">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
        <div class="form-group" style="margin:0">
          <label class="form-label"><?= $isReceipt ? 'اسم المورد' : 'اسم العميل / السبب' ?></label>
          <input type="text" name="reference_name" class="form-input" placeholder="<?= $isReceipt ? 'مثال: شركة التوريدات المصرية' : 'مثال: بضاعة تالفة / جرد' ?>">
        </div>
        <div class="form-group" style="margin:0">
          <label class="form-label">ملاحظات عامة</label>
          <input type="text" name="notes" class="form-input">
        </div>
      </div>
    </div>
  </div>

  <div class="card" style="margin-bottom:16px">
    <div class="card-header">
      <span class="card-title"><?= svgIcon('tag', 15) ?> الأصناف</span>
      <button type="button" class="btn btn-secondary btn-sm" onclick="addVoucherLine()">+ إضافة صنف</button>
    </div>
    <div class="table-wrap">
      <table>
        <thead><tr><th style="width:44px"></th><th>المنتج</th><th style="width:110px">الكمية</th><th>ملاحظات</th><th style="width:40px"></th></tr></thead>
        <tbody id="voucherLines"></tbody>
      </table>
    </div>
    <div id="voucherEmptyState" style="text-align:center;padding:36px 20px;color:var(--t3)">
      <div style="margin-bottom:10px"><?= svgIcon('tag', 30, 1.3) ?></div>
      <p style="font-size:13px;margin-bottom:12px">لسه مفيش أصناف مضافة</p>
      <button type="button" class="btn btn-secondary btn-sm" onclick="addVoucherLine()">+ إضافة أول صنف</button>
    </div>
    <div id="voucherSummary" style="display:none;padding:12px 20px;border-top:1px solid var(--bd);background:var(--bg3);font-size:12.5px;font-weight:600;color:var(--t2);display:flex;justify-content:space-between">
      <span id="voucherLineCount">0 صنف</span>
      <span id="voucherQtyTotal" style="color:<?= $accentColor ?>">إجمالي الكمية: 0</span>
    </div>
  </div>

  <div style="display:flex;gap:8px">
    <a href="/<?= $a ?>/inventory" class="btn btn-secondary">إلغاء</a>
    <button type="submit" class="btn btn-primary">حفظ كمسودة</button>
  </div>
  <p style="font-size:11.5px;color:var(--t3);margin-top:8px">هيتحفظ كمسودة أول حاجة — لازم "تأكيد" الإذن بعد كده عشان يتطبق فعلياً على المخزون</p>
</form>

<?php
$extraScript = "
let lineIndex = 0;
function updateVoucherSummary(){
  const rows = document.querySelectorAll('#voucherLines tr');
  const empty = document.getElementById('voucherEmptyState');
  const summary = document.getElementById('voucherSummary');
  if (rows.length === 0) {
    empty.style.display = 'block';
    summary.style.display = 'none';
    return;
  }
  empty.style.display = 'none';
  summary.style.display = 'flex';
  let qtyTotal = 0;
  rows.forEach(r => { const q = parseInt(r.querySelector('input[name=\"qty[]\"]')?.value) || 0; qtyTotal += q; });
  document.getElementById('voucherLineCount').textContent = rows.length + ' صنف';
  document.getElementById('voucherQtyTotal').textContent = 'إجمالي الكمية: ' + qtyTotal;
}
function addVoucherLine(){
  const tbody = document.getElementById('voucherLines');
  const row = document.createElement('tr');
  row.innerHTML = `
    <td><div class=\"vl-thumb\" style=\"width:32px;height:32px;border-radius:6px;background:var(--bg3);display:flex;align-items:center;justify-content:center;overflow:hidden;color:var(--t3)\"></div></td>
    <td><input type=\"text\" class=\"form-input product-search\" placeholder=\"دوّر بالاسم أو SKU...\" oninput=\"searchProducts(this)\" autocomplete=\"off\">
        <input type=\"hidden\" name=\"product_id[]\">
        <div class=\"product-search-results\" style=\"position:relative\"></div></td>
    <td><input type=\"number\" name=\"qty[]\" class=\"form-input\" min=\"1\" value=\"1\" onchange=\"updateVoucherSummary()\"></td>
    <td><input type=\"text\" name=\"line_notes[]\" class=\"form-input\"></td>
    <td><button type=\"button\" class=\"btn btn-danger btn-sm\" onclick=\"this.closest('tr').remove();updateVoucherSummary()\">×</button></td>
  `;
  tbody.appendChild(row);
  updateVoucherSummary();
}
let searchTimeout;
function searchProducts(input){
  clearTimeout(searchTimeout);
  const resultsBox = input.parentElement.querySelector('.product-search-results');
  const q = input.value.trim();
  if (q.length < 2) { resultsBox.innerHTML=''; return; }
  searchTimeout = setTimeout(() => {
    fetch('/$a/products/search-json?q=' + encodeURIComponent(q))
      .then(r => r.json())
      .then(data => {
        resultsBox.innerHTML = '<div style=\"position:absolute;top:2px;right:0;left:0;background:var(--bg2);border:1px solid var(--bd);border-radius:8px;max-height:220px;overflow-y:auto;z-index:10;box-shadow:var(--shadow-md,0 4px 16px rgba(0,0,0,.1))\">' +
          data.map(p => `<div style=\"display:flex;align-items:center;gap:8px;padding:8px 12px;cursor:pointer;font-size:12.5px;border-bottom:1px solid var(--bd)\" onclick='selectProduct(this,${JSON.stringify(p.id)},${JSON.stringify(p.name)},${JSON.stringify(p.thumbnail_url)})'>
              ${p.thumbnail_url ? `<img src=\"${p.thumbnail_url}\" style=\"width:24px;height:24px;object-fit:cover;border-radius:5px\">` : ''}
              <span>\\${p.name} <span style=\"color:var(--t3)\">(\\${p.sku||'—'}) — مخزون: \\${p.stock}</span></span>
            </div>`).join('') +
          '</div>';
      });
  }, 300);
}
function selectProduct(el, id, name, thumbUrl){
  const cell = el.closest('td');
  cell.querySelector('.product-search').value = name;
  cell.querySelector('input[type=hidden]').value = id;
  cell.querySelector('.product-search-results').innerHTML = '';
  const thumbCell = cell.closest('tr').querySelector('.vl-thumb');
  thumbCell.innerHTML = thumbUrl ? `<img src=\"${thumbUrl}\" style=\"width:100%;height:100%;object-fit:cover\">` : '';
}
updateVoucherSummary();
";
$content = ob_get_clean();
require APP_PATH . '/Views/admin/layouts/app.php';
