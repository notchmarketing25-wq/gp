<?php
$pageTitle = e($product['name']) . ' — ' . SettingModel::get('store_name', APP_NAME);
$pageDesc  = e($product['short_desc'] ?? '');

trackPageView('product', $product['id']);

$productModel = new ProductModel();
$images       = $product['images'] ?? [];
$variants     = $product['variants'] ?? [];
$rating       = $product['rating'] ?? ['avg' => 0, 'count' => 0];

// Live stock: once a product has variants, availability is tracked PER VARIANT — the parent
// product's own `stock` field is a separate, independently-edited number that no longer reflects
// what's actually sellable, so every "in stock" signal on this page (buy button, stock badge,
// mobile sticky bar) must read the currently-selected variant's stock instead. This computes the
// figure for the default-selected variant (the first one, matching the button that renders
// "selected" below) for the initial page render; selectVariant() in the script below keeps it in
// sync when the shopper picks a different option.
$defaultVariant = $variants ? reset($variants) : null;
$displayStock   = $defaultVariant ? (int)$defaultVariant['stock'] : (int)$product['stock'];
$inStock        = !$product['track_stock'] || $displayStock > 0;

// Add-ons ("خد الشاحن مع الكابل بسعر خاص") — complementary products the admin configured for
// this product, shown as an optional checklist below the buy button.
$addons = [];
try {
    $addons = Database::fetchAll(
        "SELECT pa.*, p.name addon_name, p.price addon_price, p.thumbnail addon_thumbnail, p.slug addon_slug, p.stock addon_stock, p.track_stock addon_track_stock
         FROM product_addons pa
         JOIN products p ON p.id = pa.addon_product_id AND p.status = 'active'
         WHERE pa.product_id = ? ORDER BY pa.sort_order ASC, pa.id ASC",
        [$product['id']]
    );
} catch (\Throwable $e) {}

// Related products
$related = $product['collection_id']
    ? $productModel->getByCollection($product['collection_id'], 4)
    : $productModel->getFeatured(4);
$related = array_filter($related, fn($r) => $r['id'] !== $product['id']);

// Reviews
$reviews = Database::fetchAll(
    "SELECT * FROM `reviews` WHERE product_id = ? AND is_approved = 1 ORDER BY created_at DESC LIMIT 10",
    [$product['id']]
);

// Wholesale pricing
$wholesaleTiers   = !empty($product['wholesale_enabled']) ? $productModel->getPriceTiers($product['id']) : [];
$isWholesaleUser  = Cart::isApprovedWholesaleCustomer();

$discount = 0;
if (!empty($product['compare_price']) && $product['compare_price'] > $product['price']) {
    $discount = round((1 - $product['price'] / $product['compare_price']) * 100);
}

$allImages = [];
if ($product['thumbnail']) $allImages[] = ['image' => $product['thumbnail'], 'alt' => $product['name']];
foreach ($images as $img) $allImages[] = $img;

$extraHead = '<style>
.product-main-grid{display:grid;grid-template-columns:1fr 1fr;gap:48px;margin-bottom:60px}
.img-gallery{display:grid;grid-template-columns:80px 1fr;gap:12px}
.thumbs-wrap{display:flex;flex-direction:column;align-items:center;gap:6px}
.thumbs{display:flex;flex-direction:column;gap:8px;max-height:420px;overflow-y:auto;scroll-behavior:smooth;scrollbar-width:none}
.thumbs::-webkit-scrollbar{display:none}
.thumb{width:80px;height:80px;object-fit:cover;border-radius:8px;border:2px solid transparent;cursor:pointer;transition:border-color .15s;background:var(--bg3);flex-shrink:0}
.thumb:hover,.thumb.active{border-color:var(--accent)}
.thumb-arrow{width:100%;height:26px;background:var(--surface);border:1px solid var(--border);border-radius:8px;display:flex;align-items:center;justify-content:center;color:var(--text2);cursor:pointer;transition:all .15s;flex-shrink:0}
.thumb-arrow:hover{background:var(--accent);color:#fff;border-color:var(--accent)}
.main-img{width:100%;aspect-ratio:1;object-fit:cover;border-radius:var(--radius);background:var(--bg3)}
.main-img-arrow{position:absolute;top:50%;transform:translateY(-50%);width:38px;height:38px;border-radius:50%;background:rgba(0,0,0,.45);backdrop-filter:blur(6px);border:1px solid rgba(255,255,255,.15);color:#fff;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:background .15s;z-index:2}
.main-img-arrow:hover{background:rgba(0,0,0,.7)}
.main-img-arrow-next{left:12px}
.main-img-arrow-prev{right:12px}
.main-img-counter{position:absolute;bottom:12px;left:50%;transform:translateX(-50%);background:rgba(0,0,0,.55);backdrop-filter:blur(6px);color:#fff;font-size:11px;font-weight:600;padding:4px 12px;border-radius:20px;z-index:2}
.variant-btn{
  padding:8px 16px;border-radius:8px;font-size:13px;font-weight:500;
  background:var(--surface);border:1px solid var(--border);color:var(--text2);
  cursor:pointer;transition:all .15s;
}
.variant-btn:hover,.variant-btn.selected{background:var(--accent-bg);border-color:var(--accent);color:var(--text)}
.qty-ctrl{display:flex;align-items:center;gap:0;border:1px solid var(--border);border-radius:8px;overflow:hidden}
.qty-btn{width:38px;height:38px;background:var(--surface);border:none;color:var(--text);font-size:18px;cursor:pointer;transition:background .12s}
.qty-btn:hover{background:var(--surface2)}
.qty-val{width:50px;text-align:center;background:var(--bg);border:none;color:var(--text);font-size:14px;font-weight:600;border-left:1px solid var(--border);border-right:1px solid var(--border)}

/* Buy box row — qty + CTAs + wishlist. Was a single flex row that squeezed/truncated the CTA
   button text on narrow phones; now wraps into a clean stacked layout on mobile instead. */
.buybox-row{display:flex;gap:12px;align-items:center;margin-bottom:16px;flex-wrap:wrap}
.buybox-ctas{display:flex;gap:12px;flex:1;min-width:220px}
.buybox-ctas .btn{flex:1;white-space:nowrap}
.buybox-wishlist{padding:11px 14px;flex-shrink:0}
@media(max-width:420px){
  .buybox-row{flex-direction:column;align-items:stretch}
  .qty-ctrl{align-self:flex-start}
  .buybox-ctas{width:100%;min-width:0}
  .buybox-wishlist{width:100%}
}

@media(max-width:768px){
  .product-main-grid{grid-template-columns:1fr;gap:24px;margin-bottom:36px}
  .img-gallery{grid-template-columns:1fr;grid-template-areas:"main" "thumbs"}
  .img-gallery > div:last-child{grid-area:main}
  .thumbs-wrap{grid-area:thumbs;flex-direction:row}
  .thumbs{flex-direction:row;max-height:none;overflow-x:auto;overflow-y:hidden;padding-bottom:2px}
  .thumb{width:62px;height:62px}
  .thumb-arrow{width:26px;height:44px;flex-shrink:0}
  .thumb-arrow-up svg{transform:rotate(-90deg)}
  .thumb-arrow-down svg{transform:rotate(-90deg)}
  .product-specs-grid{grid-template-columns:1fr !important}
}

/* ── Modern skin 2.0 (Ugreen/Anker style) ── */
.product-main-grid > div:last-child{position:sticky;top:calc(var(--nav-h) + 20px);align-self:start}
.main-img{border-radius:22px;background:linear-gradient(160deg,var(--bg2),var(--bg3));object-fit:contain;padding:20px;transition:transform .5s cubic-bezier(.16,1,.3,1)}
.thumb{border-radius:12px;object-fit:contain;background:var(--bg2);padding:4px}
.thumb.active{border-width:2px;box-shadow:0 0 0 4px rgba(var(--accent-rgb),.12)}
.variant-btn{border-radius:100px;padding:9px 20px;font-weight:600}
.variant-btn.selected{box-shadow:0 0 0 3px rgba(var(--accent-rgb),.15)}
.qty-ctrl{border-radius:100px;overflow:hidden}
.qty-btn{width:42px;height:42px}
.qty-val{border:none;background:transparent}
@media(max-width:768px){
  .product-main-grid > div:last-child{position:static}
  body{padding-bottom:70px}
}/* Description/Reviews tabs — a dedicated component instead of reusing .btn-ghost, which is
   styled for dark backgrounds (white text) and was rendering nearly invisible here since this
   section sits on the plain light page background. */
.product-tabs-bar{display:flex;gap:4px;border-bottom:1px solid var(--border);margin-bottom:32px}
.product-tab-btn{background:none;border:none;border-bottom:2px solid transparent;padding:14px 18px;font-size:14px;font-weight:600;color:var(--text3);cursor:pointer;transition:color .15s,border-color .15s;margin-bottom:-1px}
.product-tab-btn:hover{color:var(--text2)}
.product-tab-btn.active{color:var(--text);border-bottom-color:var(--accent);font-weight:800}
</style>';

$extraScript = "
const images = " . json_encode(array_values($allImages)) . ";
const uploadUrl = '" . APP_URL . "/uploads/';
let currentImgIdx = 0;

function switchImg(idx) {
  const main = document.getElementById('mainImg');
  const img  = images[idx];
  if (img && main) {
    currentImgIdx = idx;
    main.src = img.image.startsWith('http') ? img.image : uploadUrl + img.image;
    document.querySelectorAll('.thumb').forEach((t,i) => t.classList.toggle('active', i === idx));
    const counter = document.getElementById('imgCounter');
    if (counter) counter.textContent = (idx+1) + ' / ' + images.length;
    const activeThumb = document.querySelectorAll('.thumb')[idx];
    if (activeThumb) activeThumb.scrollIntoView({block:'nearest', inline:'nearest', behavior:'smooth'});
  }
}
function stepImg(dir) {
  if (!images.length) return;
  let next = (currentImgIdx + dir + images.length) % images.length;
  switchImg(next);
}
function scrollThumbs(dir) {
  const track = document.getElementById('thumbsTrack');
  if (!track) return;
  const isMobile = window.innerWidth <= 768;
  if (isMobile) track.scrollBy({left: dir*90, behavior:'smooth'});
  else track.scrollBy({top: dir*90, behavior:'smooth'});
}

// Variant selection
let selectedVariant = null;
function selectVariant(id, price, btn, stock) {
  selectedVariant = id;
  document.querySelectorAll('.variant-btn').forEach(b => b.classList.remove('selected'));
  btn.classList.add('selected');
  // Must stay in Western digits (en-US) to match the PHP-rendered price on initial page load —
  // 'ar-EG' switches to Arabic-Indic numerals (٠١٢٣...), so selecting a variant used to visibly
  // change the price from English digits to Arabic digits mid-page, which read as a language mix.
  const formatted = parseFloat(price).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2}) + ' " . APP_CURRENCY_SYMBOL . "';
  document.getElementById('currentPrice').textContent = formatted;
  const mobilePrice = document.getElementById('mobileBuyPrice');
  if (mobilePrice) mobilePrice.textContent = formatted;

  // Stock is per-variant once a product has variants — the add-to-cart button, the stock badge,
  // and the mobile sticky bar must all reflect THIS variant's own stock, not the parent
  // product's (now-irrelevant) aggregate stock field.
  const addBtnEl = document.getElementById('addToCartBtn');
  const addLblEl = document.getElementById('addToCartLabel');
  const buyNowEl = document.getElementById('buyNowBtn');
  const mobileBuyBtnEl = document.getElementById('mobileBuyBtn');
  const mobileBuyNowEl = document.getElementById('mobileBuyNowBtn');
  const stockIndicatorEl = document.getElementById('stockIndicator');
  const inStock = stock > 0 || stock === undefined;

  if (addBtnEl) {
    addBtnEl.disabled = !inStock;
    if (addLblEl) addLblEl.textContent = inStock ? '🛒 أضف للسلة' : '❌ نفذ من المخزون';
  }
  if (buyNowEl) buyNowEl.style.display = inStock ? '' : 'none';
  if (mobileBuyBtnEl) {
    mobileBuyBtnEl.disabled = !inStock;
    mobileBuyBtnEl.textContent = inStock ? '🛒 أضف للسلة' : 'نفذ من المخزون';
  }
  if (mobileBuyNowEl) mobileBuyNowEl.style.display = inStock ? '' : 'none';

  if (stockIndicatorEl && stock !== undefined) {
    if (stock > 10) {
      stockIndicatorEl.innerHTML = '<span style=\"color:var(--green)\">✅ متوفر في المخزون</span>';
    } else if (stock > 0) {
      stockIndicatorEl.innerHTML = '<span style=\"color:var(--yellow)\">⚠️ آخر ' + stock + ' قطع</span>';
    } else {
      stockIndicatorEl.innerHTML = '<span style=\"color:var(--red)\">❌ نفذ من المخزون</span>';
    }
  }
}

// Qty
function changeQty(delta) {
  const el = document.getElementById('qtyInput');
  el.value = Math.max(1, parseInt(el.value) + delta);
}

// Add to cart — with press animation + success pulse + cart badge bump
const addBtn = document.getElementById('addToCartBtn');
const addLbl = document.getElementById('addToCartLabel');
addBtn?.addEventListener('click', function() {
  const qty = parseInt(document.getElementById('qtyInput').value) || 1;
  this.disabled = true;
  this.style.transform = 'scale(.96)';
  addLbl.textContent = '⏳ جاري الإضافة...';
  const form = new FormData();
  form.append('product_id', " . $product['id'] . ");
  form.append('qty', qty);
  if (selectedVariant) form.append('variant_id', selectedVariant);
  form.append('_csrf', '" . csrf_token() . "');
  fetch('" . url('cart/add') . "', {method:'POST',body:form})
    .then(r=>r.json())
    .then(d=>{
      this.style.transform = '';
      if(d.success){
        addLbl.textContent = '✅ تمت الإضافة!';
        this.classList.add('added');
        setTimeout(() => this.classList.remove('added'), 350);
        if (typeof flyToCart === 'function') flyToCart(this);
        updateCartBadge(d.data.count);
        this.disabled = false;
        setTimeout(() => addLbl.textContent = '🛒 أضف للسلة', 1800);
      } else {
        addLbl.textContent = '⚠️ ' + (d.message||'خطأ');
        this.disabled = false;
        setTimeout(() => addLbl.textContent = '🛒 أضف للسلة', 1800);
      }
    })
    .catch(() => {
      this.style.transform = '';
      addLbl.textContent = '⚠️ حدث خطأ، حاول مرة أخرى';
      this.disabled = false;
      setTimeout(() => addLbl.textContent = '🛒 أضف للسلة', 1800);
    });
});

// Add-ons total — recalculated from the currently-checked boxes' server-trusted prices
// (rendered by PHP from product_addons; nothing here is user-editable).
function updateAddonTotal() {
  const boxes = document.querySelectorAll('.addon-checkbox:checked');
  let total = " . (float)$product['price'] . ";
  boxes.forEach(b => total += parseFloat(b.dataset.price));
  const el = document.getElementById('addonTotal');
  if (el) el.textContent = total.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2}) + ' " . APP_CURRENCY_SYMBOL . "';
}
if (document.getElementById('addonTotal')) updateAddonTotal();

// Adds the main product + every checked add-on in one request (CartController::addBundle),
// which re-derives each add-on's bundle price server-side rather than trusting the page.
function addBundleToCart() {
  const btn = document.getElementById('addBundleBtn');
  const qty = parseInt(document.getElementById('qtyInput')?.value) || 1;
  const addonIds = Array.from(document.querySelectorAll('.addon-checkbox:checked')).map(b => parseInt(b.dataset.id));
  btn.disabled = true;
  const original = btn.textContent;
  btn.textContent = '⏳ جاري الإضافة...';
  const form = new FormData();
  form.append('main_product_id', " . $product['id'] . ");
  form.append('main_qty', qty);
  if (selectedVariant) form.append('main_variant_id', selectedVariant);
  form.append('addon_ids', JSON.stringify(addonIds));
  form.append('_csrf', '" . csrf_token() . "');
  fetch('" . url('cart/add-bundle') . "', {method:'POST', body:form})
    .then(r => r.json())
    .then(d => {
      btn.disabled = false;
      if (d.success) {
        btn.textContent = '✅ تمت الإضافة!';
        updateCartBadge(d.data.count);
        setTimeout(() => btn.textContent = original, 1800);
      } else {
        btn.textContent = '⚠️ ' + (d.message || 'خطأ');
        setTimeout(() => btn.textContent = original, 2200);
      }
    })
    .catch(() => { btn.disabled = false; btn.textContent = '⚠️ حدث خطأ'; setTimeout(() => btn.textContent = original, 1800); });
}

// Buy Now — adds to cart then goes straight to checkout, skipping the cart page
const buyNowBtn = document.getElementById('buyNowBtn');
buyNowBtn?.addEventListener('click', function() {
  const qty = parseInt(document.getElementById('qtyInput').value) || 1;
  const originalText = this.textContent;
  this.disabled = true;
  this.textContent = 'جارِ التجهيز...';
  const form = new FormData();
  form.append('product_id', " . $product['id'] . ");
  form.append('qty', qty);
  if (selectedVariant) form.append('variant_id', selectedVariant);
  form.append('_csrf', '" . csrf_token() . "');
  fetch('" . url('cart/add') . "', {method:'POST',body:form})
    .then(r=>r.json())
    .then(d=>{
      if (d.success) {
        window.location.href = '" . url('checkout') . "';
      } else {
        this.disabled = false;
        this.textContent = originalText;
        alert(d.message || 'حدث خطأ');
      }
    })
    .catch(() => { this.disabled = false; this.textContent = originalText; alert('حدث خطأ، حاول مرة أخرى'); });
});

// Sticky buy bar — appears once the person scrolls past the main buy box
const stickyBar = document.getElementById('stickyBuyBar');
const mainBuyBox = addBtn ? addBtn.closest('div') : null;
if (stickyBar && mainBuyBox) {
  const io = new IntersectionObserver(entries => {
    entries.forEach(e => stickyBar.classList.toggle('visible', !e.isIntersecting));
  }, {rootMargin: '-80px 0px 0px 0px'});
  io.observe(mainBuyBox);
}
";

ob_start(); ?>

<div class="container" style="padding-top:24px;padding-bottom:80px">
  <!-- Breadcrumb -->
  <div class="breadcrumb">
    <a href="<?= url() ?>">الرئيسية</a>
    <span class="breadcrumb-sep">/</span>
    <a href="<?= url('products') ?>">المنتجات</a>
    <?php if ($product['collection_name'] ?? null): ?>
      <span class="breadcrumb-sep">/</span>
      <a href="<?= url('collections/' . ($product['collection_slug'] ?? '')) ?>"><?= e($product['collection_name']) ?></a>
    <?php endif; ?>
    <span class="breadcrumb-sep">/</span>
    <span class="breadcrumb-current"><?= e($product['name']) ?></span>
  </div>

  <!-- Product main -->
  <div class="product-main-grid">

    <!-- Images -->
    <div class="img-gallery">
      <?php if (count($allImages) > 1): ?>
        <div class="thumbs-wrap">
          <button class="thumb-arrow thumb-arrow-up" onclick="scrollThumbs(-1)" aria-label="السابق">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="18 15 12 9 6 15"/></svg>
          </button>
          <div class="thumbs" id="thumbsTrack">
            <?php foreach ($allImages as $i => $img): ?>
              <img src="<?= uploadUrl($img['image']) ?>"
                   class="thumb <?= $i===0?'active':'' ?>"
                   onclick="switchImg(<?= $i ?>)"
                   alt="<?= e($img['alt'] ?? $product['name']) ?>">
            <?php endforeach; ?>
          </div>
          <button class="thumb-arrow thumb-arrow-down" onclick="scrollThumbs(1)" aria-label="التالي">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
          </button>
        </div>
      <?php endif; ?>
      <div style="position:relative">
        <?php $firstImg = $allImages[0]['image'] ?? null; ?>
        <?php if ($firstImg): ?>
          <img src="<?= uploadUrl($firstImg) ?>" class="main-img" id="mainImg" alt="<?= e($product['name']) ?>">
        <?php else: ?>
          <div class="main-img" style="display:flex;align-items:center;justify-content:center;font-size:80px">📦</div>
        <?php endif; ?>
        <?php if (count($allImages) > 1): ?>
          <button class="main-img-arrow main-img-arrow-next" onclick="stepImg(1)" aria-label="الصورة التالية">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
          </button>
          <button class="main-img-arrow main-img-arrow-prev" onclick="stepImg(-1)" aria-label="الصورة السابقة">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
          </button>
          <div class="main-img-counter" id="imgCounter">1 / <?= count($allImages) ?></div>
        <?php endif; ?>
      </div>
    </div>


    <!-- Details -->
    <div>
      <!-- Brand -->
      <?php if (!empty($product['brand_name'])): ?>
        <a href="<?= url('brands/' . slug($product['brand_name'])) ?>"
           style="font-size:11px;font-weight:700;color:var(--accent);text-transform:uppercase;letter-spacing:1px">
          <?= e($product['brand_name']) ?>
        </a>
      <?php endif; ?>

      <h1 style="font-size:26px;font-weight:800;color:var(--text);letter-spacing:-.5px;margin:8px 0 12px;line-height:1.3;unicode-bidi:plaintext">
        <?= e($product['name']) ?>
      </h1>

      <!-- Rating -->
      <?php if ($rating['count'] > 0): ?>
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:16px">
          <div style="color:var(--yellow);font-size:16px"><?= str_repeat('★', min(5, round($rating['avg']))) ?><?= str_repeat('☆', 5 - min(5, round($rating['avg']))) ?></div>
          <span style="font-size:13px;color:var(--text3)"><?= $rating['avg'] ?> (<?= $rating['count'] ?> تقييم)</span>
        </div>
      <?php endif; ?>

      <!-- Price -->
      <div style="display:flex;align-items:baseline;gap:10px;margin-bottom:20px">
        <div style="font-size:32px;font-weight:900;color:var(--text);font-family:var(--font-en);letter-spacing:-.01em" id="currentPrice"><?= money($product['price']) ?></div>
        <?php if ($discount > 0): ?>
          <div style="font-size:16px;color:var(--text3);text-decoration:line-through"><?= money($product['compare_price']) ?></div>
          <div class="badge badge-sale">-<?= $discount ?>%</div>
        <?php endif; ?>
      </div>

      <!-- Wholesale tiers -->
      <?php if (!empty($wholesaleTiers) && $isWholesaleUser): ?>
        <!-- Approved wholesale customer: show real tier pricing -->
        <div style="border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;margin-bottom:20px">
          <div style="background:var(--black);color:#fff;padding:10px 16px;font-size:12.5px;font-weight:700;display:flex;align-items:center;gap:8px">
            📦 أسعار الجملة
            <span style="margin-right:auto;color:var(--accent);font-size:11px;background:rgba(255,255,255,.1);padding:2px 8px;border-radius:20px">✓ حساب جملة معتمد</span>
          </div>
          <table style="width:100%;border-collapse:collapse">
            <thead>
              <tr style="background:var(--bg2)">
                <th style="padding:8px 16px;font-size:11px;color:var(--text3);text-align:right;font-weight:700">الكمية</th>
                <th style="padding:8px 16px;font-size:11px;color:var(--text3);text-align:right;font-weight:700">سعر القطعة</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($wholesaleTiers as $i => $t):
                $nextQty = $wholesaleTiers[$i+1]['min_qty'] ?? null;
                $range = $nextQty ? ($t['min_qty'].' - '.($nextQty-1)) : ($t['min_qty'].'+');
              ?>
                <tr style="border-top:1px solid var(--border)">
                  <td style="padding:9px 16px;font-size:13px;font-weight:600"><?= $range ?> قطعة</td>
                  <td style="padding:9px 16px;font-size:14px;font-weight:800;color:var(--accent);font-family:var(--font-en)"><?= money($t['price']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php elseif (!empty($product['wholesale_enabled']) && !$isWholesaleUser): ?>
        <!-- Not a wholesale account: no prices shown, just a CTA to apply. Covers BOTH cases —
             products with their own quantity tiers AND products that rely on the customer's
             account-level tier discount (which has no product-specific tiers to check, so the
             old "!empty($wholesaleTiers)" condition was silently skipping the banner for them). -->
        <a href="<?= url('business/register') ?>" style="display:flex;align-items:center;gap:12px;background:var(--black);color:#fff;border-radius:var(--radius);padding:16px;margin-bottom:20px;text-decoration:none;transition:filter .15s" onmouseover="this.style.filter='brightness(1.15)'" onmouseout="this.style.filter=''">
          <div style="font-size:26px">🏬</div>
          <div style="flex:1">
            <div style="font-weight:700;font-size:13.5px">عندك محل أو بتشتري بالجملة؟</div>
            <div style="font-size:12px;color:rgba(255,255,255,.55);margin-top:2px">سجّل كتاجر أو موزع واحصل على أسعار جملة خاصة لهذا المنتج</div>
          </div>
          <div style="color:#7aa6ff;font-size:20px">←</div>
        </a>
      <?php endif; ?>

      <!-- Short desc -->
      <?php if ($product['short_desc']): ?>
        <p style="font-size:14px;color:var(--text2);line-height:1.7;margin-bottom:20px">
          <?= nl2br(e($product['short_desc'])) ?>
        </p>
      <?php endif; ?>

      <!-- Variants -->
      <?php if (!empty($variants)): ?>
        <div style="margin-bottom:20px">
          <div style="font-size:12px;font-weight:600;color:var(--text2);margin-bottom:10px">الاختيارات:</div>
          <div style="display:flex;gap:8px;flex-wrap:wrap">
            <?php foreach ($variants as $v): ?>
              <button class="variant-btn <?= $v === reset($variants) ? 'selected' : '' ?>"
                onclick="selectVariant(<?= $v['id'] ?>,'<?= $v['price'] ?>',this,<?= (int)$v['stock'] ?>)">
                <?= e($v['title']) ?>
                <?php if ((int)$v['stock'] <= 0 && $product['track_stock']): ?><span style="color:var(--red);font-size:10px">(نفذ)</span><?php endif; ?>
                <?php if ($v['price'] != $product['price']): ?>
                  <span style="font-size:11px;color:var(--text3)"> — <?= money($v['price']) ?></span>
                <?php endif; ?>
              </button>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <!-- Qty + Add to cart -->
      <div class="buybox-row">
        <div class="qty-ctrl">
          <button class="qty-btn" onclick="changeQty(-1)">−</button>
          <input type="number" id="qtyInput" class="qty-val" value="1" min="1" max="99">
          <button class="qty-btn" onclick="changeQty(1)">+</button>
        </div>

        <div class="buybox-ctas">
          <button id="addToCartBtn" class="btn btn-primary btn-lg" style="transition:transform .15s,filter .15s" <?= !$inStock?'disabled':'' ?>>
            <span id="addToCartLabel"><?= $inStock ? '🛒 أضف للسلة' : '❌ نفذ من المخزون' ?></span>
          </button>
          <button id="buyNowBtn" class="btn btn-outline btn-lg" <?= !$inStock?'style="display:none"':'' ?>>اشتري الآن</button>
        </div>

        <button class="btn btn-outline buybox-wishlist" onclick="toggleWishlist(<?= $product['id'] ?>,this)" title="أضف للمفضلة">🤍</button>
      </div>

      <!-- Stock indicator — kept in sync with the selected variant by selectVariant() in the script below -->
      <?php if ($product['track_stock']): ?>
        <div id="stockIndicator" style="font-size:12px;margin-bottom:16px">
          <?php if ($displayStock > 10): ?>
            <span style="color:var(--green)">✅ متوفر في المخزون</span>
          <?php elseif ($displayStock > 0): ?>
            <span style="color:var(--yellow)">⚠️ آخر <?= $displayStock ?> قطع</span>
          <?php else: ?>
            <span style="color:var(--red)">❌ نفذ من المخزون</span>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <!-- Add-ons ("خد الشاحن مع الكابل بسعر خاص") -->
      <?php if (!empty($addons)): ?>
        <div style="border:1px solid var(--border);border-radius:var(--radius);padding:16px;background:var(--surface);margin-bottom:16px">
          <div style="font-weight:700;font-size:14px;margin-bottom:12px">🎁 أضفها مع المنتج ووفّر</div>
          <?php foreach ($addons as $ad):
              $adPrice = (float)$ad['addon_price'];
              $adFinal = $adPrice;
              if ($ad['discount_type'] === 'percent') $adFinal = $adPrice * (1 - ((float)$ad['discount_value'] / 100));
              elseif ($ad['discount_type'] === 'fixed') $adFinal = max(0, $adPrice - (float)$ad['discount_value']);
              $adOutOfStock = $ad['addon_track_stock'] && (int)$ad['addon_stock'] <= 0;
          ?>
            <label class="addon-row" style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--border);cursor:<?= $adOutOfStock?'default':'pointer' ?>;opacity:<?= $adOutOfStock?'.5':'1' ?>">
              <input type="checkbox" class="addon-checkbox" data-id="<?= $ad['addon_product_id'] ?>" data-price="<?= $adFinal ?>"
                     <?= $ad['is_checked_default'] && !$adOutOfStock ? 'checked' : '' ?> <?= $adOutOfStock?'disabled':'' ?>
                     onchange="updateAddonTotal()" style="width:18px;height:18px;flex-shrink:0">
              <?php if ($ad['addon_thumbnail']): ?>
                <img src="<?= uploadUrl($ad['addon_thumbnail']) ?>" style="width:40px;height:40px;object-fit:cover;border-radius:7px;flex-shrink:0">
              <?php endif; ?>
              <span style="flex:1;font-size:13px"><?= e($ad['addon_name']) ?><?= $adOutOfStock ? ' <span style="color:var(--red);font-size:11px">(نفذ)</span>' : '' ?></span>
              <span style="font-weight:700;font-size:13px;white-space:nowrap">
                <?= money($adFinal) ?>
                <?php if ($adFinal < $adPrice): ?>
                  <span style="text-decoration:line-through;color:var(--text3);font-weight:400;font-size:11px;margin-inline-start:4px"><?= money($adPrice) ?></span>
                <?php endif; ?>
              </span>
            </label>
          <?php endforeach; ?>
          <div style="display:flex;align-items:center;justify-content:space-between;margin-top:14px">
            <div style="font-size:13px;color:var(--text2)">الإجمالي: <span id="addonTotal" style="font-weight:800;font-size:16px;color:var(--text)"></span></div>
            <button type="button" id="addBundleBtn" class="btn btn-primary btn-sm" onclick="addBundleToCart()">🛒 أضف المحدد للسلة</button>
          </div>
        </div>
      <?php endif; ?>

      <!-- Trust badges -->
      <div style="border:1px solid var(--border);border-radius:var(--radius);padding:16px;background:var(--surface)">
        <div class="product-specs-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:10px;font-size:12px;color:var(--text2)">
          <div>🚚 شحن لجميع المحافظات</div>
          <div>↩️ إرجاع خلال 14 يوم</div>
          <div>🔒 دفع آمن 100%</div>
          <div>💵 دفع عند الاستلام</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Description + Reviews tabs -->
  <div class="product-tabs-bar" id="productTabs">
    <button class="product-tab-btn active" onclick="switchTab('desc',this)">الوصف</button>
    <button class="product-tab-btn" onclick="switchTab('reviews',this)">التقييمات (<?= count($reviews) ?>)</button>
  </div>

  <!-- Description -->
  <div id="tab-desc" style="line-height:1.9;color:var(--text2);font-size:14px;max-width:800px;margin-bottom:40px">
    <?= !empty($product['description']) ? $product['description'] : '<p style="color:var(--text3)">لا يوجد وصف متاح</p>' ?>
  </div>

  <!-- Reviews -->
  <div id="tab-reviews" style="display:none;max-width:700px;margin-bottom:40px">
    <?php if (empty($reviews)): ?>
      <div style="text-align:center;padding:40px;color:var(--text3)">
        <div style="font-size:36px;margin-bottom:12px">⭐</div>
        <div>لا توجد تقييمات بعد. كن أول من يقيّم هذا المنتج!</div>
      </div>
    <?php else: ?>
      <?php foreach ($reviews as $r): ?>
        <div style="border-bottom:1px solid var(--border);padding:16px 0">
          <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px">
            <div style="width:36px;height:36px;border-radius:50%;background:var(--accent-bg);display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:700;color:var(--accent)">
              <?= mb_substr($r['name'], 0, 1) ?>
            </div>
            <div>
              <div style="font-size:13px;font-weight:600"><?= e($r['name']) ?></div>
              <div style="font-size:11px;color:var(--text3)"><?= formatDate($r['created_at']) ?></div>
            </div>
            <div style="margin-right:auto;color:var(--yellow)"><?= str_repeat('★', $r['rating']) ?></div>
          </div>
          <?php if ($r['title']): ?>
            <div style="font-size:13px;font-weight:600;margin-bottom:4px"><?= e($r['title']) ?></div>
          <?php endif; ?>
          <p style="font-size:13px;color:var(--text2)"><?= e($r['body']) ?></p>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>

    <!-- Review form -->
    <?php if (isStoreLoggedIn()): ?>
      <div style="margin-top:24px;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:20px">
        <div style="font-size:14px;font-weight:600;margin-bottom:16px">اكتب تقييمك</div>
        <form method="POST" action="<?= url('products/' . $product['slug']) ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="review">
          <div class="form-group">
            <label class="form-label">التقييم</label>
            <div style="display:flex;gap:8px">
              <?php for ($i = 5; $i >= 1; $i--): ?>
                <label style="cursor:pointer;font-size:24px;color:var(--text3)" title="<?= $i ?> نجوم">
                  <input type="radio" name="rating" value="<?= $i ?>" style="display:none" required>
                  ★
                </label>
              <?php endfor; ?>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">العنوان</label>
            <input type="text" name="title" class="form-input" placeholder="ملخص رأيك">
          </div>
          <div class="form-group">
            <label class="form-label">التعليق <span>*</span></label>
            <textarea name="body" class="form-textarea" rows="4" placeholder="شاركنا تجربتك مع المنتج..." required></textarea>
          </div>
          <button type="submit" class="btn btn-primary">إرسال التقييم</button>
        </form>
      </div>
    <?php else: ?>
      <div style="text-align:center;padding:20px;color:var(--text3);font-size:13px">
        <a href="<?= url('login') ?>" style="color:var(--accent)">سجل دخولك</a> لكتابة تقييم
      </div>
    <?php endif; ?>
  </div>

  <!-- Related products -->
  <?php if (!empty($related)): ?>
    <div>
      <h3 style="font-size:20px;font-weight:700;margin-bottom:20px">منتجات مشابهة</h3>
      <div class="products-grid">
        <?php foreach (array_slice(array_values($related), 0, 4) as $p): ?>
          <?php include APP_PATH . '/Views/store/partials/product-card.php'; ?>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>
</div>

<script>
function switchTab(id, btn) {
  document.querySelectorAll('[id^="tab-"]').forEach(t => t.style.display = 'none');
  document.getElementById('tab-' + id).style.display = 'block';
  document.querySelectorAll('.product-tab-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
}

// Star rating hover
document.querySelectorAll('[name="rating"]').forEach((r, i, arr) => {
  r.parentElement.addEventListener('mouseover', () => {
    arr.forEach((s, j) => s.parentElement.style.color = j >= i ? 'var(--yellow)' : 'var(--text3)');
  });
  r.addEventListener('change', () => {
    arr.forEach((s, j) => s.parentElement.style.color = j >= i ? 'var(--yellow)' : 'var(--text3)');
  });
});
</script>

<!-- Mobile sticky buy bar (Ugreen/Anker style) -->
<div class="mobile-buy-bar" id="stickyBuyBar">
  <div>
    <div style="font-size:10px;color:var(--text3);font-weight:600">السعر</div>
    <div class="mb-price" id="mobileBuyPrice"><?= money($product['price']) ?></div>
  </div>
  <button class="btn btn-primary" id="mobileBuyBtn" <?= !$inStock?'disabled':'' ?> onclick="document.getElementById('addToCartBtn')?.click()">
    <?= $inStock ? '🛒 أضف للسلة' : 'نفذ من المخزون' ?>
  </button>
  <button class="btn btn-outline" id="mobileBuyNowBtn" <?= !$inStock?'style="display:none"':'' ?> onclick="document.getElementById('buyNowBtn')?.click()">اشتري الآن</button>
</div>

<?php
$content = ob_get_clean();
require APP_PATH . '/Views/store/layouts/app.php';
