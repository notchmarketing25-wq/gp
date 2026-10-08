<?php
$pageTitle = SettingModel::get('meta_title', APP_NAME);
$pageDesc  = SettingModel::get('meta_description', '');

trackPageView('home');

$productModel    = new ProductModel();
$collectionModel = new CollectionModel();
$brandModel      = new BrandModel();

$featured    = $productModel->getFeatured(8);
$collections = $collectionModel->withProductCount();
$brands      = $brandModel->getActive();
$newArrivals = $productModel->getActive(8);

// Spotlight product: first featured, else first active product
$spotlight = $featured[0] ?? ($newArrivals[0] ?? null);

// Instagram reels carousel ("What the Pros Are Saying")
$instagramReels = [];
try { $instagramReels = Database::fetchAll("SELECT * FROM instagram_reels WHERE is_active=1 ORDER BY sort_order, id"); } catch (\Throwable $e) {}

// Banners (hero slider / medium / small)
$sliders = []; $mediums = []; $smalls = []; $middles = [];
try {
    $sliders = Database::fetchAll("SELECT * FROM banners WHERE type='slider' AND is_active=1 ORDER BY sort_order,id");
    $mediums = Database::fetchAll("SELECT * FROM banners WHERE type='medium' AND is_active=1 ORDER BY sort_order,id");
    $smalls  = Database::fetchAll("SELECT * FROM banners WHERE type='small'  AND is_active=1 ORDER BY sort_order,id");
    $middles = Database::fetchAll("SELECT * FROM banners WHERE type='middle' AND is_active=1 ORDER BY sort_order,id");
} catch (\Throwable $e) {}

// Category icon map (simple emoji fallback when no image uploaded)

ob_start(); ?>

<!-- ══════════════════════════════════
     HERO
══════════════════════════════════ -->
<?php if (!empty($sliders)): ?>
<section class="hero-carousel" id="heroSlider">
  <div class="hc-track" id="hcTrack">
    <?php foreach ($sliders as $i => $s): ?>
      <div class="hc-slide <?= $i===0?'active':'' ?>" data-slide="<?= $i ?>">
        <div class="hc-slide-bg">
          <?php if (($s['media_type'] ?? 'image') === 'video'): ?>
            <video src="<?= uploadUrl($s['image']) ?>" autoplay muted loop playsinline preload="<?= $i===0?'auto':'metadata' ?>"></video>
          <?php else: ?>
            <img src="<?= uploadUrl($s['image']) ?>" alt="<?= e($s['title']) ?>" loading="<?= $i===0?'eager':'lazy' ?>">
          <?php endif; ?>
        </div>
        <?php if (!empty($s['title'])): ?>
        <div class="hc-slide-shade"></div>
        <div class="hero-left">
          <?php if ($s['subtitle']): ?><div class="hero-badge"><span class="hero-badge-dot"></span> <?= e($s['subtitle']) ?></div><?php endif; ?>
          <div class="hero-title"><?= nl2br(e($s['title'])) ?></div>
          <?php if ($s['btn_text']): ?>
            <div class="hero-ctas">
              <a href="<?= filter_var($s['link'],FILTER_VALIDATE_URL) ? e($s['link']) : url(ltrim($s['link']??'products','/')) ?>" class="btn btn-primary btn-lg">
                <?= e($s['btn_text']) ?>
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"/></svg>
              </a>
              <a href="<?= url('products') ?>" class="btn btn-ghost btn-lg">تصفح المنتجات</a>
            </div>
          <?php endif; ?>
        </div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
  <?php if (count($sliders) > 1): ?>
    <button class="hc-arrow hc-arrow-prev" onclick="hcMove(-1)" aria-label="السابق">
      <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="m15 18-6-6 6-6"/></svg>
    </button>
    <button class="hc-arrow hc-arrow-next" onclick="hcMove(1)" aria-label="التالي">
      <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"/></svg>
    </button>
    <div class="hc-dots">
      <?php foreach ($sliders as $i => $s): ?>
        <button class="hc-dot <?= $i===0?'active':'' ?>" onclick="hcGo(<?= $i ?>)" aria-label="سلايد <?= $i+1 ?>"></button>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>
<?php else: ?>
<section class="hero">
  <div class="hero-left">
    <div class="hero-badge"><span class="hero-badge-dot"></span> <?= e(SettingModel::get('store_description','متجر الإلكترونيات الرائد')) ?></div>
    <div class="hero-title"><?= e(SettingModel::get('hero_title', 'أحدث التقنيات')) ?> <span class="blue"><?= e(SettingModel::get('hero_subtitle','بين يديك')) ?></span></div>
    <div class="hero-subtitle">تسوق أفضل المنتجات الإلكترونية من أشهر الماركات العالمية — أصلية 100% وبضمان حقيقي</div>
    <div class="hero-ctas">
      <a href="<?= url(ltrim(SettingModel::get('hero_btn_url', 'products'), '/')) ?>" class="btn btn-primary btn-lg">
        <?= e(SettingModel::get('hero_btn_text', 'تسوق الآن')) ?>
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"/></svg>
      </a>
      <a href="<?= url('collections') ?>" class="btn btn-ghost btn-lg">استكشف التصنيفات</a>
    </div>
  </div>
  <?php if ($spotlight && $spotlight['thumbnail']): ?>
  <div class="hero-right">
    <img class="hero-product-img" src="<?= uploadUrl($spotlight['thumbnail']) ?>" alt="<?= e($spotlight['name']) ?>">
  </div>
  <?php endif; ?>
</section>
<?php endif; ?>

<!-- ══════════════════════════════════
     TRUST STRIP
══════════════════════════════════ -->
<div class="trust-strip">
  <div class="trust-inner">
    <?php foreach ([
      ['zap','دفع مرن','فواتيرك أو الاستلام'],
      ['shield','ضمان حقيقي','على كل منتج نبيعه'],
      ['truck','توصيل سريع','لكل محافظات مصر'],
      ['check','منتجات أصلية','100% Original'],
      ['message','دعم فوري','فريقنا في خدمتك'],
    ] as [$icon,$label,$val]): ?>
      <div class="trust-item">
        <div class="trust-icon"><?= svgIcon($icon, 17) ?></div>
        <div class="trust-text"><div class="trust-label"><?= $label ?></div><div class="trust-val" style="font-size:12.5px;font-family:var(--font-body);font-weight:700"><?= $val ?></div></div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- ══════════════════════════════════
     WARRANTY ACTIVATION PROMO
══════════════════════════════════ -->
<div class="section-sm">
  <div class="container">
    <div style="background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius);padding:20px 24px;display:flex;align-items:center;gap:16px;flex-wrap:wrap">
      <div style="width:48px;height:48px;border-radius:12px;background:var(--accent-bg);display:flex;align-items:center;justify-content:center;font-size:24px;flex-shrink:0">🛡️</div>
      <div style="flex:1;min-width:200px">
        <div style="font-weight:800;font-size:15px;color:var(--text)">فعّل ضمان منتجك دلوقتي</div>
        <div style="font-size:12.5px;color:var(--text2);margin-top:2px">سجّل الرقم التسلسلي واحمي منتجك — بياخد دقيقة بس</div>
      </div>
      <a href="<?= url('warranty') ?>" class="btn btn-primary">🛡️ تفعيل الضمان ←</a>
    </div>
  </div>
</div>

<!-- ══════════════════════════════════
     MEDIUM BANNERS
══════════════════════════════════ -->
<?php if (!empty($mediums)): ?>
<section class="section-sm">
  <div class="container">
    <div class="mb-grid">
      <?php foreach ($mediums as $m): ?>
        <a href="<?= filter_var($m['link'],FILTER_VALIDATE_URL) ? e($m['link']) : url(ltrim($m['link']??'products','/')) ?>" class="mb-item">
          <?php if (($m['media_type'] ?? 'image') === 'video'): ?>
            <video src="<?= uploadUrl($m['image']) ?>" autoplay muted loop playsinline preload="metadata"></video>
          <?php else: ?>
            <img src="<?= uploadUrl($m['image']) ?>" alt="<?= e($m['title']) ?>" loading="lazy">
          <?php endif; ?>
          <?php if ($m['title']): ?>
            <div class="mb-overlay">
              <h3><?= e($m['title']) ?></h3><?php if($m['subtitle']): ?><p><?= e($m['subtitle']) ?></p><?php endif; ?>
              <span class="mb-cta">اكتشف المزيد</span>
            </div>
          <?php endif; ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ══════════════════════════════════
     CATEGORIES
══════════════════════════════════ -->
<?php if (!empty($collections)): ?>
<section class="section cat-section" <?= empty($mediums) ? '' : 'style="padding-top:0"' ?>>
  <div class="section-header">
    <div><div class="section-title">تسوق حسب <span>الفئة</span></div></div>
    <a href="<?= url('products') ?>" class="sec-link">
      عرض الكل
      <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"/></svg>
    </a>
  </div>
  <div class="cat-carousel-wrap">
    <button type="button" class="cat-arrow cat-arrow-prev" onclick="catScroll(-1)" aria-label="السابق">
      <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="m15 18-6-6 6-6"/></svg>
    </button>
    <div class="cat-carousel" id="catCarousel">
      <?php foreach ($collections as $c): ?>
        <a href="<?= url('collections/' . $c['slug']) ?>" class="cat-tile">
          <div class="cat-tile-img">
            <?php if ($c['image']): ?>
              <img src="<?= uploadUrl($c['image']) ?>" alt="<?= e($c['name']) ?>">
            <?php else: ?>
              <?= svgIcon('package', 40, 1.3) ?>
            <?php endif; ?>
          </div>
          <div class="cat-tile-name"><?= e($c['name']) ?></div>
        </a>
      <?php endforeach; ?>
    </div>
    <button type="button" class="cat-arrow cat-arrow-next" onclick="catScroll(1)" aria-label="التالي">
      <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"/></svg>
    </button>
  </div>
</section>
<script>
function catScroll(dir){
  const el = document.getElementById('catCarousel');
  if (!el) return;
  const tile = el.querySelector('.cat-tile');
  const step = tile ? (tile.getBoundingClientRect().width + 16) * 2 : 400;
  el.scrollBy({left: dir * step * (document.documentElement.dir === 'rtl' ? -1 : 1), behavior: 'smooth'});
}
</script>
<?php endif; ?>

<!-- ══════════════════════════════════
     FEATURED PRODUCTS
══════════════════════════════════ -->
<?php if (!empty($featured)): ?>
<section class="section" style="padding-top:0">
  <div class="section-header">
    <div><div class="section-title">المنتجات <span>المميزة</span></div></div>
    <a href="<?= url('products') ?>" class="sec-link">عرض الكل <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"/></svg></a>
  </div>
  <div class="products-grid">
    <?php foreach ($featured as $p): include APP_PATH . '/Views/store/partials/product-card.php'; endforeach; ?>
  </div>
</section>
<?php endif; ?>

<!-- ══════════════════════════════════
     SPOTLIGHT (best product highlight)
══════════════════════════════════ -->
<?php if ($spotlight): ?>
<div class="spotlight">
  <div class="spot-inner">
    <div>
      <div class="spot-tag">الأكثر تميزاً</div>
      <div class="spot-title"><?= e($spotlight['name']) ?></div>
      <?php if ($spotlight['description']??null): ?>
        <div class="spot-desc"><?= e(mb_substr(strip_tags($spotlight['description']),0,180)) ?>…</div>
      <?php endif; ?>
      <div class="spot-specs-grid">
        <div class="spot-spec"><div class="spot-spec-num"><?= money($spotlight['price']) ?></div><div class="spot-spec-desc">السعر الحالي</div></div>
        <?php if ($spotlight['warranty_period']??null): ?>
          <div class="spot-spec"><div class="spot-spec-num"><?= e($spotlight['warranty_period']) ?></div><div class="spot-spec-desc">مدة الضمان</div></div>
        <?php endif; ?>
        <?php if (($spotlight['stock']??0) > 0 || !($spotlight['track_stock']??1)): ?>
          <div class="spot-spec"><div class="spot-spec-num">متوفر<span> ✓</span></div><div class="spot-spec-desc">حالة المخزون</div></div>
        <?php endif; ?>
        <div class="spot-spec"><div class="spot-spec-num">أصلي<span> 100%</span></div><div class="spot-spec-desc">ضمان الجودة</div></div>
      </div>
      <a class="btn btn-primary btn-lg" href="<?= url('products/'.$spotlight['slug']) ?>">
        تسوق الآن
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"/></svg>
      </a>
    </div>
    <?php if ($spotlight['thumbnail']): ?>
    <div class="spot-img">
      <div class="spot-img-ring"></div>
      <div class="spot-img-ring2"></div>
      <img src="<?= uploadUrl($spotlight['thumbnail']) ?>" alt="<?= e($spotlight['name']) ?>">
    </div>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<!-- ══════════════════════════════════
     INSTAGRAM REELS ("What the Pros Are Saying")
══════════════════════════════════ -->
<?php if (!empty($instagramReels)): ?>
<section class="section ig-section">
  <div class="section-header">
    <div><div class="section-title">آراء <span>الصناع والمؤثرين</span></div></div>
  </div>
  <div class="ig-carousel-wrap">
    <button type="button" class="cat-arrow ig-arrow-prev" onclick="igScroll(-1)" aria-label="السابق">
      <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="m15 18-6-6 6-6"/></svg>
    </button>
    <div class="ig-carousel" id="igCarousel">
      <?php foreach ($instagramReels as $reel): ?>
        <div class="ig-tile">
          <blockquote class="instagram-media" data-instgrm-permalink="<?= e($reel['url']) ?>" data-instgrm-version="14" style="width:100%;margin:0"></blockquote>
        </div>
      <?php endforeach; ?>
    </div>
    <button type="button" class="cat-arrow ig-arrow-next" onclick="igScroll(1)" aria-label="التالي">
      <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"/></svg>
    </button>
  </div>
</section>
<script>
// Load Instagram's embed script only once this section actually nears the viewport, instead of
// on every homepage load — this was the main cause of the jank/jitter: a heavy third-party script
// forcing repeated reflows during the initial page load, before the person even scrolled to it.
(function(){
  const section = document.querySelector('.ig-section');
  if (!section) return;
  let loaded = false;
  function loadInstagramEmbeds(){
    if (loaded) return;
    loaded = true;
    const s = document.createElement('script');
    s.src = '//www.instagram.com/embed.js';
    s.async = true;
    s.onload = function(){
      if (window.instgrm) window.instgrm.Embeds.process();
      // Mark tiles loaded (fades out the shimmer placeholder) once Instagram's iframes settle in
      setTimeout(() => document.querySelectorAll('.ig-tile').forEach(t => t.classList.add('loaded')), 1200);
    };
    document.body.appendChild(s);
  }
  const io = new IntersectionObserver(entries => {
    entries.forEach(e => { if (e.isIntersecting) { loadInstagramEmbeds(); io.disconnect(); } });
  }, {rootMargin: '400px'});
  io.observe(section);
})();
function igScroll(dir){
  const el = document.getElementById('igCarousel');
  if (!el) return;
  const tile = el.querySelector('.ig-tile');
  const step = tile ? (tile.getBoundingClientRect().width + 16) * 2 : 400;
  el.scrollBy({left: dir * step * (document.documentElement.dir === 'rtl' ? -1 : 1), behavior: 'smooth'});
}
</script>
<?php endif; ?>

<!-- ══════════════════════════════════
     BRANDS
══════════════════════════════════ -->
<?php if (!empty($brands)): ?>
<div style="background:var(--bg2);border-top:1px solid var(--border);border-bottom:1px solid var(--border);padding:22px 0">
  <div class="container">
    <div style="display:flex;align-items:center;justify-content:center;gap:40px;flex-wrap:wrap">
      <?php foreach ($brands as $b): ?>
        <a href="<?= url('brands/' . $b['slug']) ?>" style="opacity:.55;transition:opacity .2s" onmouseover="this.style.opacity=1" onmouseout="this.style.opacity='.55'">
          <?php if ($b['logo']): ?><img src="<?= uploadUrl($b['logo']) ?>" alt="<?= e($b['name']) ?>" style="height:26px;width:auto;object-fit:contain"><?php else: ?><span style="font-size:14px;font-weight:800;color:var(--text2)"><?= e($b['name']) ?></span><?php endif; ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- ══════════════════════════════════
     NEW ARRIVALS
══════════════════════════════════ -->
<?php if (!empty($newArrivals)): ?>
<section class="section">
  <div class="section-header">
    <div><div class="section-title">وصل <span>حديثاً</span></div></div>
    <a href="<?= url('products') ?>" class="sec-link">عرض الكل <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"/></svg></a>
  </div>
  <div class="products-grid">
    <?php foreach ($newArrivals as $p): include APP_PATH . '/Views/store/partials/product-card.php'; endforeach; ?>
  </div>
</section>
<?php endif; ?>

<!-- ══════════════════════════════════
     SMALL BANNERS STRIP
══════════════════════════════════ -->
<?php if (!empty($smalls)): ?>
<section class="section-sm">
  <div class="container">
    <div class="sb-grid">
      <?php foreach ($smalls as $s): ?>
        <a href="<?= filter_var($s['link'],FILTER_VALIDATE_URL) ? e($s['link']) : url(ltrim($s['link']??'products','/')) ?>" class="sb-item">
          <img src="<?= uploadUrl($s['image']) ?>" alt="<?= e($s['title']) ?>" loading="lazy">
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ══════════════════════════════════
     MIDDLE AD BANNER (full-width, between sections)
══════════════════════════════════ -->
<?php if (!empty($middles)): $mid = $middles[array_rand($middles)]; ?>
<section class="section-sm">
  <div class="container">
    <a href="<?= filter_var($mid['link'],FILTER_VALIDATE_URL) ? e($mid['link']) : url(ltrim($mid['link']??'products','/')) ?>" class="ad-banner">
      <img src="<?= uploadUrl($mid['image']) ?>" alt="<?= e($mid['title']) ?>" loading="lazy">
      <?php if ($mid['title']): ?>
        <div class="ad-banner-overlay">
          <h3><?= e($mid['title']) ?></h3>
          <?php if($mid['subtitle']): ?><p><?= e($mid['subtitle']) ?></p><?php endif; ?>
          <?php if($mid['btn_text']): ?><span class="btn btn-primary btn-sm" style="margin-top:10px;width:fit-content"><?= e($mid['btn_text']) ?></span><?php endif; ?>
        </div>
      <?php endif; ?>
    </a>
  </div>
</section>
<?php endif; ?>

<!-- ══════════════════════════════════
     WHY US
══════════════════════════════════ -->
<section class="section">
  <div class="section-header"><div><div class="section-title">لماذا <span><?= e(SettingModel::get('store_name','نوتش')) ?></span></div></div></div>
  <div class="why-grid">
    <?php foreach ([
      ['01','⚡','جودة موثوقة','كل منتج نبيعه أصلي 100% ومُختبر — بدون أي مساومة على الجودة.'],
      ['02','🛡️','ضمان حقيقي','نقف خلف كل منتج نبيعه بضمان فعلي واستبدال سهل عند الحاجة.'],
      ['03','🚚','شحن لكل مصر','توصيل سريع لجميع المحافظات مع تتبع مباشر لطلبك.'],
      ['04','💬','دعم حقيقي','فريق دعم متواجد فعلاً للرد على استفساراتك ومشاكلك.'],
    ] as [$num,$icon,$title,$desc]): ?>
      <div class="why-card">
        <div class="why-num"><?= $num ?></div>
        <div class="why-icon"><?= $icon ?></div>
        <div class="why-title"><?= $title ?></div>
        <div class="why-desc"><?= $desc ?></div>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- ══════════════════════════════════
     WHOLESALE / BULK ORDERS CTA
══════════════════════════════════ -->
<div class="wholesale-section">
  <div class="wholesale-inner">
    <div>
      <div class="wholesale-label">لأصحاب المحلات والموزعين</div>
      <div class="wholesale-title">هل تريد الشراء بالجملة؟</div>
      <div class="wholesale-desc">افتح حساب جملة واحصل على أسعار خاصة للكميات الكبيرة، ودعم مخصص لمتجرك أو محلك.</div>
      <div class="wholesale-perks">
        <?php foreach (['أسعار خاصة للكميات الكبيرة','دعم مباشر لطلبات الجملة','متابعة شحن مخصصة لطلبك'] as $perk): ?>
          <div class="wholesale-perk"><span class="wholesale-perk-dot">✓</span> <?= $perk ?></div>
        <?php endforeach; ?>
      </div>
      <a href="<?= url('wholesale') ?>" class="btn btn-primary">افتح حساب جملة ←</a>
    </div>
    <div class="wholesale-card">
      <div style="font-size:11px;font-weight:700;color:rgba(255,255,255,.4);letter-spacing:.08em;text-transform:uppercase;margin-bottom:10px">طرق التواصل</div>
      <?php if ($phone = SettingModel::get('store_phone')): ?><div style="font-size:15px;font-weight:700;margin-bottom:8px">📞 <?= e($phone) ?></div><?php endif; ?>
      <?php if ($wa = SettingModel::get('contact_whatsapp')): ?><div style="font-size:15px;font-weight:700;margin-bottom:8px">💬 واتساب: <?= e($wa) ?></div><?php endif; ?>
      <?php if ($email = SettingModel::get('store_email')): ?><div style="font-size:15px;font-weight:700">✉️ <?= e($email) ?></div><?php endif; ?>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
require APP_PATH . '/Views/store/layouts/app.php';
