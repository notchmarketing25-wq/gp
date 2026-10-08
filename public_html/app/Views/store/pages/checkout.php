<?php
$pageTitle = 'إتمام الشراء — ' . SettingModel::get('store_name', APP_NAME);
$cartItems = Cart::get();
$summary   = Cart::summary();

if (empty($cartItems)) {
    header('Location: ' . url('cart')); exit;
}

$shippingZones = Database::fetchAll("SELECT * FROM `shipping_zones` WHERE is_active=1 ORDER BY price ASC");
$customer      = storeUser();
$govOptions    = ['القاهرة','الجيزة','الإسكندرية','القليوبية','المنوفية','الغربية','الدقهلية','الشرقية','البحيرة','كفر الشيخ','دمياط','بورسعيد','الإسماعيلية','السويس','شمال سيناء','جنوب سيناء','الفيوم','بني سويف','المنيا','أسيوط','سوهاج','قنا','الأقصر','أسوان','البحر الأحمر','مطروح','الوادي الجديد'];

// If the previous submission failed (e.g. payment gateway error), restore what the customer typed
$oldInput = Session::get('_checkout_old_input', []);
Session::remove('_checkout_old_input');
$old = fn(string $key, string $default = '') => e($oldInput[$key] ?? $default);

$discount = Cart::getDiscount();

$extraHead = '<style>
.co-section{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:22px}
.co-section-head{display:flex;align-items:center;gap:10px;margin-bottom:18px}
.co-section-num{width:24px;height:24px;border-radius:100px;background:var(--accent);color:#fff;display:inline-flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;flex-shrink:0}
.co-section-title{font-size:15px;font-weight:700}
.pay-option{display:flex;align-items:center;gap:12px;padding:14px;border:1.5px solid var(--border);border-radius:10px;cursor:pointer;transition:border-color .15s,background .15s}
.pay-option.selected{border-color:var(--accent);background:var(--accent-bg)}
.pay-option input{accent-color:var(--accent);width:18px;height:18px;flex-shrink:0}
.card-badge{display:inline-flex;align-items:center;justify-content:center;font-size:9px;font-weight:800;letter-spacing:.3px;border-radius:4px;padding:3px 6px;color:#fff;font-family:var(--font-en)}
.co-item-row{display:flex;gap:12px;margin-bottom:14px;align-items:flex-start}
.co-item-thumb{position:relative;flex-shrink:0;width:50px;height:50px}
.co-item-qty{position:absolute;top:-7px;left:-7px;background:var(--text3);color:var(--surface);font-size:10px;font-weight:800;min-width:18px;height:18px;border-radius:100px;display:flex;align-items:center;justify-content:center;padding:0 4px;border:2px solid var(--surface)}
.co-discount-row{display:flex;gap:8px;margin-bottom:16px}
</style>';

ob_start(); ?>

<div class="container-sm" style="padding-top:24px;padding-bottom:80px">
  <div class="breadcrumb">
    <a href="<?= url() ?>">الرئيسية</a>
    <span class="breadcrumb-sep">/</span>
    <a href="<?= url('cart') ?>">السلة</a>
    <span class="breadcrumb-sep">/</span>
    <span class="breadcrumb-current">إتمام الشراء</span>
  </div>

  <h1 style="font-size:24px;font-weight:800;margin-bottom:16px">إتمام الشراء</h1>

  <!-- Checkout steps -->
  <div style="display:flex;align-items:center;gap:8px;margin-bottom:28px;font-size:12px;font-weight:700;flex-wrap:wrap">
    <span style="display:flex;align-items:center;gap:6px;color:var(--text3)"><span style="width:22px;height:22px;border-radius:100px;background:var(--green);color:#fff;display:inline-flex;align-items:center;justify-content:center;font-size:11px">✓</span> السلة</span>
    <span style="width:24px;height:1.5px;background:var(--border2)"></span>
    <span style="display:flex;align-items:center;gap:6px;color:var(--accent)"><span style="width:22px;height:22px;border-radius:100px;background:var(--accent);color:#fff;display:inline-flex;align-items:center;justify-content:center;font-size:11px">2</span> البيانات والدفع</span>
    <span style="width:24px;height:1.5px;background:var(--border)"></span>
    <span style="display:flex;align-items:center;gap:6px;color:var(--text3)"><span style="width:22px;height:22px;border-radius:100px;background:var(--bg3);color:var(--text3);display:inline-flex;align-items:center;justify-content:center;font-size:11px">3</span> التأكيد</span>
  </div>

  <form method="POST" action="<?= url('checkout') ?>" id="checkoutForm">
    <?= csrf_field() ?>

    <div class="page-grid-sidebar" style="display:grid;grid-template-columns:1fr 360px;gap:24px;align-items:start">

      <!-- Left: Form -->
      <div style="display:flex;flex-direction:column;gap:18px">

        <!-- 1. Contact info -->
        <div class="co-section">
          <div class="co-section-head"><span class="co-section-num">1</span><span class="co-section-title">معلومات الاتصال</span></div>
          <div class="page-grid-form-2" style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
            <div class="form-group">
              <label class="form-label">الاسم الكامل <span>*</span></label>
              <input type="text" name="customer_name" class="form-input" value="<?= $old('customer_name', $customer['name'] ?? '') ?>" required placeholder="محمد أحمد">
            </div>
            <div class="form-group">
              <label class="form-label">رقم الهاتف <span>*</span></label>
              <input type="tel" name="customer_phone" class="form-input" value="<?= $old('customer_phone', $customer['phone'] ?? '') ?>" required placeholder="01xxxxxxxxx">
            </div>
            <div class="form-group" style="grid-column:span 2">
              <label class="form-label">البريد الإلكتروني <span>*</span></label>
              <input type="email" name="customer_email" class="form-input" value="<?= $old('customer_email', $customer['email'] ?? '') ?>" required placeholder="email@example.com">
            </div>
          </div>
        </div>

        <!-- 2. Delivery -->
        <div class="co-section">
          <div class="co-section-head"><span class="co-section-num">2</span><span class="co-section-title">عنوان التوصيل</span></div>
          <div class="page-grid-form-2" style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
            <div class="form-group">
              <label class="form-label">المحافظة <span>*</span></label>
              <select name="shipping_gov" class="form-select" required onchange="updateShipping(this.value)">
                <option value="">اختر المحافظة</option>
                <?php foreach ($govOptions as $gov): ?>
                  <option value="<?= e($gov) ?>"><?= e($gov) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">المدينة / المنطقة <span>*</span></label>
              <input type="text" name="shipping_city" class="form-input" value="<?= $old('shipping_city') ?>" required placeholder="المدينة">
            </div>
            <div class="form-group" style="grid-column:span 2">
              <label class="form-label">العنوان التفصيلي <span>*</span></label>
              <textarea name="shipping_address" class="form-textarea" rows="3" required placeholder="الشارع، المبنى، الشقة..."><?= $old('shipping_address') ?></textarea>
            </div>
          </div>
        </div>

        <!-- 3. Shipping method (derived from the selected governorate's zone) -->
        <div class="co-section" id="shippingMethodSection" style="display:none">
          <div class="co-section-head"><span class="co-section-num">3</span><span class="co-section-title">طريقة الشحن</span></div>
          <div class="pay-option selected" style="cursor:default">
            <div style="font-size:22px">🚚</div>
            <div style="flex:1">
              <div style="font-size:13px;font-weight:600">شحن قياسي</div>
              <div style="font-size:11px;color:var(--text3)" id="shippingEtaLabel">يصل خلال 2-5 أيام عمل</div>
            </div>
            <div style="font-size:14px;font-weight:800" id="shippingPriceLabel">—</div>
          </div>
        </div>

        <!-- N& Credit -->
        <?php
        $creditBalance = 0;
        if (isStoreLoggedIn()) {
            $cust = (new CustomerModel())->find(storeUser()['id']);
            $creditBalance = (float)($cust['store_credit'] ?? 0);
        }
        ?>
        <?php if ($creditBalance > 0): ?>
        <div class="co-section">
          <label style="display:flex;align-items:center;gap:12px;cursor:pointer">
            <input type="checkbox" name="use_credit" value="1" id="useCreditCb" style="width:18px;height:18px;accent-color:var(--accent)">
            <div style="flex:1">
              <div style="font-size:14px;font-weight:700">استخدام رصيد N&Credit</div>
              <div style="font-size:12px;color:var(--text2)">رصيدك المتاح: <strong style="color:var(--green)"><?= money($creditBalance) ?></strong></div>
            </div>
            <div style="font-size:22px">💳</div>
          </label>
        </div>
        <?php endif; ?>

        <!-- 4. Payment -->
        <div class="co-section">
          <div class="co-section-head"><span class="co-section-num">4</span><span class="co-section-title">طريقة الدفع</span></div>
          <div style="display:flex;flex-direction:column;gap:10px">
            <?php if (SettingModel::get('cod_active', '1') === '1'): ?>
              <label class="pay-option" id="pay-cod">
                <input type="radio" name="payment_method" value="cod" required onchange="updatePayLabel(this)">
                <div style="font-size:24px">💵</div>
                <div style="flex:1">
                  <div style="font-size:13px;font-weight:600"><?= e(SettingModel::get('cod_label', 'الدفع عند الاستلام')) ?></div>
                  <div style="font-size:11px;color:var(--text3)">ادفع نقداً عند استلام الطلب</div>
                </div>
              </label>
            <?php endif; ?>
            <?php if (SettingModel::get('fawateerk_active', '0') === '1'): ?>
              <label class="pay-option" id="pay-fawateerk">
                <input type="radio" name="payment_method" value="fawateerk" onchange="updatePayLabel(this)">
                <div style="font-size:24px">💳</div>
                <div style="flex:1">
                  <div style="font-size:13px;font-weight:600">بطاقة ائتمانية / مدى</div>
                  <div style="font-size:11px;color:var(--text3)">دفع إلكتروني آمن عبر فواتيرك</div>
                </div>
                <div style="display:flex;gap:4px;flex-shrink:0">
                  <span class="card-badge" style="background:#1a1f71">VISA</span>
                  <span class="card-badge" style="background:#eb001b">MC</span>
                </div>
              </label>
            <?php endif; ?>
          </div>
        </div>

        <!-- Notes -->
        <div class="co-section">
          <label class="form-label">ملاحظات (اختياري)</label>
          <textarea name="notes" class="form-textarea" rows="2" placeholder="أي تعليمات خاصة للطلب..."><?= $old('notes') ?></textarea>
        </div>
      </div>

      <!-- Right: Order summary -->
      <div class="co-section" style="position:sticky;top:80px">
        <div style="font-size:15px;font-weight:700;margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid var(--border)">
          ملخص الطلب (<?= Cart::count() ?> منتج)
        </div>

        <!-- Items -->
        <?php foreach ($cartItems as $item): ?>
          <div class="co-item-row">
            <div class="co-item-thumb">
              <span class="co-item-qty"><?= $item['qty'] ?></span>
              <?php if ($item['image']): ?>
                <img src="<?= uploadUrl($item['image']) ?>" style="width:50px;height:50px;object-fit:cover;border-radius:8px;border:1px solid var(--border)">
              <?php else: ?>
                <div style="width:50px;height:50px;border-radius:8px;background:var(--bg3);display:flex;align-items:center;justify-content:center;font-size:20px">📦</div>
              <?php endif; ?>
            </div>
            <div style="flex:1;min-width:0">
              <div style="font-size:12px;font-weight:500;line-height:1.3"><?= e($item['name']) ?></div>
              <?php if ($item['variant']): ?><div style="font-size:10px;color:var(--text3)"><?= e($item['variant']) ?></div><?php endif; ?>
            </div>
            <div style="font-size:13px;font-weight:700;flex-shrink:0"><?= money($item['price'] * $item['qty']) ?></div>
          </div>
        <?php endforeach; ?>

        <!-- Discount code -->
        <div class="co-discount-row" style="margin-top:4px">
          <input type="text" id="discountCode" class="form-input" placeholder="كود الخصم" style="flex:1;font-size:13px" value="<?= e($discount['code']) ?>">
          <button type="button" onclick="applyCheckoutDiscount()" class="btn btn-outline btn-sm">تطبيق</button>
        </div>
        <div id="discountMsg" style="font-size:12px;margin-bottom:10px"></div>

        <div style="border-top:1px solid var(--border);padding-top:12px;margin-top:4px;display:flex;flex-direction:column;gap:8px;font-size:13px">
          <div style="display:flex;justify-content:space-between;color:var(--text2)">
            <span>المجموع الفرعي</span><span id="subtotalDisplay"><?= money($summary['subtotal']) ?></span>
          </div>
          <div id="discountRow" style="display:<?= $summary['discount_amount'] > 0 ? 'flex' : 'none' ?>;justify-content:space-between;color:var(--green)">
            <span>خصم (<span id="discountCodeLabel"><?= e($summary['discount_code']) ?></span>)</span><span id="discountAmountDisplay">- <?= money($summary['discount_amount']) ?></span>
          </div>
          <div style="display:flex;justify-content:space-between;color:var(--text2)">
            <span>الشحن</span>
            <span id="shippingDisplay">يُحدد عند اختيار المحافظة</span>
          </div>
          <input type="hidden" name="shipping_price" id="shippingPrice" value="0">
          <div style="display:flex;justify-content:space-between;font-size:17px;font-weight:800;padding-top:10px;border-top:1px solid var(--border)">
            <span>الإجمالي</span>
            <span id="totalDisplay"><?= money($summary['total']) ?></span>
          </div>
        </div>

        <button type="submit" class="btn btn-primary btn-full" style="margin-top:18px;font-size:15px" id="submitBtn">
          تأكيد الطلب →
        </button>
        <p style="font-size:11px;color:var(--text3);text-align:center;margin-top:10px">🔒 دفع آمن ومشفر</p>
      </div>

    </div>
  </form>
</div>

<script>
const zones = <?= json_encode($shippingZones, JSON_UNESCAPED_UNICODE) ?>;
let subtotal = <?= $summary['subtotal'] - $summary['discount_amount'] ?>;

function updateShipping(gov) {
  let price = 0, label = 'غير متاح';
  const section = document.getElementById('shippingMethodSection');
  for (const z of zones) {
    const govs = JSON.parse(z.governorates || '[]');
    if (govs.length === 0 || govs.includes(gov)) {
      price = parseFloat(z.price);
      label = price === 0 ? 'مجاني' : price.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2}) + ' ج.م';
      if (z.free_above && subtotal >= parseFloat(z.free_above)) {
        price = 0; label = 'مجاني (فوق ' + parseFloat(z.free_above).toLocaleString('en-US') + ' ج.م)';
      }
      break;
    }
  }
  document.getElementById('shippingDisplay').textContent = label;
  document.getElementById('shippingPrice').value = price;
  if (section) {
    section.style.display = gov ? 'block' : 'none';
    document.getElementById('shippingPriceLabel').textContent = label;
  }
  const total = subtotal + price;
  document.getElementById('totalDisplay').textContent = total.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2}) + ' ج.م';
}

function updatePayLabel(radio) {
  document.querySelectorAll('.pay-option').forEach(el => el.classList.remove('selected'));
  const el = document.getElementById('pay-' + radio.value);
  if (el) el.classList.add('selected');
}

// Applying a discount here uses the same /cart/discount endpoint the cart page uses, then
// refreshes the on-page totals (subtotal/discount/total) without a full reload, and re-syncs
// `subtotal` so the shipping calc above keeps using the post-discount figure.
function applyCheckoutDiscount() {
  const code = document.getElementById('discountCode').value.trim();
  if (!code) return;
  const form = new FormData();
  form.append('code', code);
  form.append('_csrf', '<?= csrf_token() ?>');
  fetch('<?= url('cart/discount') ?>', {method:'POST', body:form})
    .then(r => r.json())
    .then(d => {
      const msg = document.getElementById('discountMsg');
      msg.style.color = d.success ? 'var(--green)' : 'var(--red)';
      msg.textContent = (d.success ? '✅ ' : '❌ ') + d.message;
      if (d.success) {
        const newSubtotal = <?= $summary['subtotal'] ?>;
        const discountAmount = parseFloat(d.data.amount) || 0;
        subtotal = newSubtotal - discountAmount;
        document.getElementById('discountRow').style.display = discountAmount > 0 ? 'flex' : 'none';
        document.getElementById('discountAmountDisplay').textContent = '- ' + discountAmount.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2}) + ' ج.م';
        document.getElementById('discountCodeLabel').textContent = code.toUpperCase();
        const shippingPrice = parseFloat(document.getElementById('shippingPrice').value) || 0;
        document.getElementById('totalDisplay').textContent = (subtotal + shippingPrice).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2}) + ' ج.م';
      }
    });
}

document.getElementById('checkoutForm').addEventListener('submit', function(e) {
  const btn = document.getElementById('submitBtn');
  btn.disabled = true;
  btn.textContent = '⏳ جاري المعالجة...';
});
</script>

<?php $content = ob_get_clean();
require APP_PATH . '/Views/store/layouts/app.php';
