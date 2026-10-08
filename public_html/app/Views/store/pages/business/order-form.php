<?php
$pageTitle = 'طلب جملة جديد';
$needsEndCustomer = in_array($customer['business_type'], ['distributor','rep']);
ob_start();
?>
<div style="background:#0A0A0A;border-radius:14px;padding:18px 22px;margin-bottom:16px;display:flex;align-items:center;gap:12px;color:#fff">
  <div style="font-size:24px">🏬</div>
  <div>
    <div style="font-weight:800;font-size:15px">طلب جملة جديد</div>
    <div style="font-size:12px;color:rgba(255,255,255,.55)">الأسعار المعروضة هي أسعارك الخاصة حسب شريحتك السعرية</div>
  </div>
</div>

<form method="POST" action="<?= url('business/orders') ?>" id="orderForm">
  <?= csrf_field() ?>

  <div style="display:grid;grid-template-columns:1fr 320px;gap:16px;align-items:start">
    <div>
      <?php if ($needsEndCustomer): ?>
      <div class="card" style="margin-bottom:16px">
        <div class="card-header"><span class="card-title">👤 بيانات العميل (اختياري — اتركه فارغ لو الطلب لنفسك)</span></div>
        <div class="card-body" style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
          <div class="form-group" style="margin:0"><label class="form-label">اسم العميل</label><input type="text" name="end_customer_name" class="form-input"></div>
          <div class="form-group" style="margin:0"><label class="form-label">هاتف العميل</label><input type="text" name="end_customer_phone" class="form-input"></div>
          <div class="form-group" style="margin:0;grid-column:span 2"><label class="form-label">بريد العميل (اختياري)</label><input type="email" name="end_customer_email" class="form-input"></div>
        </div>
      </div>
      <?php endif; ?>

      <div class="card">
        <div class="card-header">
          <span class="card-title">📦 اختر المنتجات</span>
          <input type="text" id="prodSearch" class="form-input" placeholder="بحث..." style="width:180px" oninput="filterProducts(this.value)">
        </div>
        <div class="table-wrap">
          <table id="prodTable">
            <thead><tr><th style="width:36px"></th><th>المنتج</th><th>سعرك</th><th>الكمية</th><th>الإجمالي</th></tr></thead>
            <tbody>
              <?php foreach ($products as $p): ?>
                <tr data-name="<?= strtolower(e($p['name'])) ?>" data-row-pid="<?= $p['id'] ?>" data-tiers='<?= htmlspecialchars(json_encode($p['has_tiers'] ? array_map(fn($t)=>['qty'=>(int)$t['min_qty'],'price'=>(float)$t['price']], $p['tiers']) : [['qty'=>1,'price'=>(float)$p['tier_price']]]), ENT_QUOTES) ?>'>
                  <td><input type="checkbox" class="row-cb" onchange="toggleRow(this,<?= $p['id'] ?>)" style="accent-color:var(--ac);cursor:pointer;width:16px;height:16px"></td>
                  <td>
                    <div style="display:flex;align-items:center;gap:9px">
                      <?php if($p['thumbnail']): ?><img src="<?= uploadUrl($p['thumbnail']) ?>" style="width:38px;height:38px;border-radius:8px;object-fit:cover;flex-shrink:0"><?php else: ?><div style="width:38px;height:38px;border-radius:8px;background:var(--bg3);flex-shrink:0"></div><?php endif; ?>
                      <span style="font-weight:500;font-size:13px"><?= e($p['name']) ?></span>
                      <input type="hidden" name="product_id[]" data-pid="<?= $p['id'] ?>" value="<?= $p['id'] ?>" disabled>
                    </div>
                  </td>
                  <td class="row-unit-price" style="font-weight:800;color:var(--ac);font-family:var(--font-num);font-size:14px">
                    <span class="row-price-val"><?= money($p['tier_price']) ?></span>
                    <?php if ($p['has_tiers']): ?><div class="row-tier-note" style="font-size:10px;color:var(--t3);font-weight:500">أقل كمية: <?= $p['min_qty'] ?></div><?php endif; ?>
                  </td>
                  <td>
                    <div style="display:flex;align-items:center;border:1px solid var(--bd);border-radius:8px;width:fit-content">
                      <button type="button" class="btn btn-secondary btn-sm" style="border-radius:8px 0 0 8px;padding:6px 10px" onclick="stepQty(<?= $p['id'] ?>,-1)" disabled>−</button>
                      <input type="number" name="qty[]" data-qid="<?= $p['id'] ?>" value="<?= $p['min_qty'] ?>" min="<?= $p['min_qty'] ?>" class="form-input qty-input" style="width:52px;border:none;text-align:center;padding:6px 2px" disabled oninput="updateSummary()">
                      <button type="button" class="btn btn-secondary btn-sm" style="border-radius:0 8px 8px 0;padding:6px 10px" onclick="stepQty(<?= $p['id'] ?>,1)" disabled>+</button>
                    </div>
                  </td>
                  <td class="row-total" style="font-weight:700">—</td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Live order summary — sticky sidebar -->
    <div class="card" style="position:sticky;top:16px">
      <div class="card-header"><span class="card-title">🧾 ملخص الطلب</span></div>
      <div class="card-body">
        <div id="summaryItems" style="display:flex;flex-direction:column;gap:8px;margin-bottom:14px;max-height:220px;overflow-y:auto">
          <div style="text-align:center;color:var(--t3);font-size:12px;padding:14px 0" id="emptySummary">لسه مخترتش منتجات</div>
        </div>
        <div style="border-top:1px solid var(--bd);padding-top:12px;margin-bottom:14px">
          <div style="display:flex;justify-content:space-between;font-size:14px;font-weight:800">
            <span>الإجمالي</span>
            <span id="grandTotal" style="color:var(--ac);font-family:var(--font-num);font-size:18px">0.00</span>
          </div>
        </div>
        <div class="form-group"><label class="form-label">طريقة الدفع</label>
          <select name="payment_method" class="form-select" id="payMethodSelect">
            <option value="cod">💵 الدفع عند الاستلام</option>
            <?php if ($customer['credit_status'] === 'approved' && $creditAvailable > 0): ?>
              <option value="credit">💳 N&Credit (متاح: <?= money($creditAvailable) ?>)</option>
            <?php endif; ?>
          </select>
          <?php if ($customer['credit_status'] !== 'approved'): ?>
            <span class="form-hint">💳 N&Credit غير متاح — حسابك لسه ما اتوافقش على طلب الكريدت. <a href="<?= url('business/credit') ?>" style="color:var(--ac)">قدّم طلب ←</a></span>
          <?php elseif ($creditAvailable <= 0): ?>
            <span class="form-hint">💳 رصيد N&Credit المتاح عندك خلص حالياً</span>
          <?php endif; ?>
        </div>
        <div class="form-group"><label class="form-label">ملاحظات</label><input type="text" name="notes" class="form-input"></div>
        <button type="submit" class="btn btn-primary btn-full" id="submitBtn" disabled>✅ تأكيد الطلب</button>
      </div>
    </div>
  </div>
</form>

<script>
const CURRENCY = '<?= addslashes(APP_CURRENCY_SYMBOL) ?>';
function toggleRow(cb, pid) {
  const row = cb.closest('tr');
  row.querySelectorAll('input[data-pid], input[data-qid], button').forEach(el => { if(el.type!=='checkbox') el.disabled = !cb.checked; });
  updateSummary();
}
function stepQty(pid, dir) {
  const inp = document.querySelector('input.qty-input[data-qid="'+pid+'"]');
  const min = parseInt(inp.min) || 1;
  inp.value = Math.max(min, (parseInt(inp.value)||min) + dir);
  updateSummary();
}
function filterProducts(q) {
  q = q.toLowerCase();
  document.querySelectorAll('#prodTable tbody tr').forEach(r => { r.style.display = r.dataset.name.includes(q) ? '' : 'none'; });
}
// Picks the best matching tier price for a given quantity — mirrors ProductModel::wholesalePriceFor()
function priceForQty(tiers, qty) {
  let best = tiers[0];
  for (const t of tiers) { if (t.qty <= qty && t.qty >= best.qty) best = t; }
  return best;
}
function updateSummary() {
  let total = 0, html = '';
  document.querySelectorAll('#prodTable tbody tr').forEach(row => {
    const cb = row.querySelector('.row-cb');
    const totalCell = row.querySelector('.row-total');
    const tiers = JSON.parse(row.dataset.tiers || '[]');
    const qty = parseInt(row.querySelector('.qty-input').value) || 1;
    const tier = priceForQty(tiers, qty);
    const price = tier ? tier.price : 0;

    // Update the displayed unit price + "next tier" hint live as qty changes
    const priceCell = row.querySelector('.row-unit-price');
    if (priceCell) {
      const valEl = priceCell.querySelector('.row-price-val');
      const noteEl = priceCell.querySelector('.row-tier-note');
      if (valEl) valEl.textContent = price.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2}) + ' ' + CURRENCY;
      if (tiers.length > 1 && noteEl) {
        const next = tiers.find(t => t.qty > qty);
        noteEl.textContent = next ? ('اطلب ' + next.qty + '+ للسعر ' + next.price.toFixed(2)) : 'أقل سعر متاح';
      }
    }

    if (cb.checked) {
      const lineTotal = price * qty;
      total += lineTotal;
      totalCell.textContent = lineTotal.toFixed(2);
      const name = row.querySelector('span').textContent;
      html += '<div style="display:flex;justify-content:space-between;font-size:12px"><span style="color:var(--t2)">'+name+' × '+qty+'</span><span style="font-weight:700">'+lineTotal.toFixed(2)+'</span></div>';
    } else {
      totalCell.textContent = '—';
    }
  });
  document.getElementById('summaryItems').innerHTML = html || '<div style="text-align:center;color:var(--t3);font-size:12px;padding:14px 0">لسه مخترتش منتجات</div>';
  document.getElementById('grandTotal').textContent = total.toFixed(2);
  document.getElementById('submitBtn').disabled = total === 0;
}
</script>
<?php $content = ob_get_clean(); require APP_PATH . '/Views/store/layouts/business.php';
