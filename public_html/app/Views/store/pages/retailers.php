<?php
$pageTitle = 'الموزعون المعتمدون — ' . SettingModel::get('store_name', APP_NAME);
$mapMarkerLogo = logoUrl('light') ?: logoUrl('dark');
ob_start();
?>
<div class="container" style="padding-top:32px;padding-bottom:80px">
  <div class="breadcrumb">
    <a href="<?= url() ?>">الرئيسية</a>
    <span class="breadcrumb-sep">/</span>
    <span class="breadcrumb-current">الموزعون المعتمدون</span>
  </div>

  <div style="text-align:center;margin-bottom:32px;max-width:600px;margin-left:auto;margin-right:auto">
    <div class="hero-badge" style="margin:0 auto 16px"><span class="hero-badge-dot"></span> اشترِ بثقة</div>
    <h1 style="font-family:var(--font-body);font-size:26px;font-weight:900;margin-bottom:10px;display:flex;align-items:center;justify-content:center;gap:10px"><?= svgIcon('store', 24, 1.6) ?> الموزعون والمحلات المعتمدة</h1>
    <p style="color:var(--text2);font-size:14px">تقدر تشتري منتجاتنا الأصلية من الأماكن دي بثقة تامة، وكمان تقدر تفعّل ضمانك لو اشتريت منهم</p>
  </div>

  <?php if (!empty($governorates)): ?>
  <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;flex-wrap:wrap;justify-content:center">
    <label for="govFilter" style="font-size:13px;font-weight:700;color:var(--text2);display:flex;align-items:center;gap:6px"><?= svgIcon('map-pin', 14) ?> فلترة حسب المحافظة</label>
    <select id="govFilter" class="form-select" style="max-width:220px" onchange="filterByGovernorate(this.value)">
      <option value="">كل المحافظات (<?= count($stores) ?>)</option>
      <?php foreach ($governorates as $gov): ?>
        <option value="<?= e($gov) ?>"><?= e($gov) ?> (<?= count($byGov[$gov]) ?>)</option>
      <?php endforeach; ?>
    </select>
  </div>
  <?php endif; ?>

  <?php if (!empty($mapPoints)): ?>
  <!-- Interactive map — Leaflet (no API key needed), Notch logo as the pin icon for every store -->
  <div id="retailersMap" style="height:380px;border-radius:var(--radius);overflow:hidden;margin-bottom:36px;border:1px solid var(--border)"></div>
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <script>
  let retailersMapInstance, allMapPoints = <?= json_encode($mapPoints, JSON_UNESCAPED_UNICODE) ?>, currentMarkers = [], notchMapIcon = null;
  (function(){
    if (!allMapPoints.length || typeof L === 'undefined') return;
    retailersMapInstance = L.map('retailersMap');
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; OpenStreetMap contributors', maxZoom: 19
    }).addTo(retailersMapInstance);
    <?php if ($mapMarkerLogo): ?>
    notchMapIcon = L.icon({
      iconUrl: '<?= addslashes($mapMarkerLogo) ?>',
      iconSize: [34, 34], iconAnchor: [17, 34], popupAnchor: [0, -34],
      className: 'notch-map-pin'
    });
    <?php endif; ?>
    renderMapMarkers(allMapPoints);
  })();
  function renderMapMarkers(points){
    if (!retailersMapInstance) return;
    currentMarkers.forEach(m => retailersMapInstance.removeLayer(m));
    currentMarkers = [];
    const bounds = [];
    points.forEach(p => {
      const marker = L.marker([p.lat, p.lng], notchMapIcon ? {icon: notchMapIcon} : {}).addTo(retailersMapInstance);
      let popupHtml = '<b>' + p.name + '</b>';
      if (p.address) popupHtml += '<br><span style="font-size:12px;color:#666">' + p.address + '</span>';
      marker.bindPopup(popupHtml);
      currentMarkers.push(marker);
      bounds.push([p.lat, p.lng]);
    });
    if (bounds.length === 1) { retailersMapInstance.setView(bounds[0], 14); }
    else if (bounds.length > 1) { retailersMapInstance.fitBounds(bounds, {padding: [30, 30]}); }
  }
  function filterByGovernorate(gov){
    // Filter map markers
    const filtered = gov ? allMapPoints.filter(p => p.gov === gov) : allMapPoints;
    renderMapMarkers(filtered);
    // Filter list sections below the map
    document.querySelectorAll('.gov-section').forEach(section => {
      section.style.display = (!gov || section.dataset.gov === gov) ? '' : 'none';
    });
  }
  </script>
  <style>
  .notch-map-pin{background:#fff;border-radius:50%;padding:4px;box-shadow:0 2px 8px rgba(0,0,0,.25);border:2px solid var(--accent)}
  .leaflet-popup-content-wrapper{border-radius:10px}
  </style>
  <?php endif; ?>

  <?php if (empty($stores) && empty($onlinePartners)): ?>
    <div style="text-align:center;padding:60px 20px;color:var(--text3)">
      <div style="margin-bottom:12px;display:flex;justify-content:center"><?= svgIcon('store', 40, 1.3) ?></div>
      لسه مفيش موزعين مسجّلين
    </div>
  <?php endif; ?>

  <?php if (!empty($stores)): foreach ($byGov as $gov => $list): ?>
    <div class="gov-section" data-gov="<?= e($gov) ?>" style="margin-bottom:32px">
      <div style="font-size:13px;font-weight:800;color:var(--accent);text-transform:uppercase;letter-spacing:.05em;margin-bottom:14px;display:flex;align-items:center;gap:6px"><?= svgIcon('map-pin', 14) ?> <?= e($gov) ?></div>
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px">
        <?php foreach ($list as $r): ?>
          <div class="retailer-card">
            <div class="retailer-card-top">
              <?php if ($r['logo']): ?>
                <img src="<?= uploadUrl($r['logo']) ?>" class="retailer-logo">
              <?php else: ?>
                <div class="retailer-logo retailer-logo-ph"><?= svgIcon('store', 22) ?></div>
              <?php endif; ?>
              <div style="font-weight:800;font-size:15.5px;unicode-bidi:plaintext"><?= e($r['name']) ?></div>
            </div>
            <?php if ($r['address']): ?><div class="retailer-line"><?= svgIcon('map-pin', 13) ?> <?= e($r['address']) ?></div><?php endif; ?>
            <?php if ($r['phone']): ?><div class="retailer-line" style="direction:ltr;text-align:right"><?= svgIcon('phone', 13) ?> <?= e($r['phone']) ?></div><?php endif; ?>
            <div style="display:flex;gap:8px;margin-top:14px">
              <?php if ($r['map_link']): ?><a href="<?= e($r['map_link']) ?>" target="_blank" class="btn btn-secondary btn-sm" style="flex:1"><?= svgIcon('map-pin', 13) ?> الموقع</a><?php endif; ?>
              <?php if ($r['phone']): ?><a href="tel:<?= e($r['phone']) ?>" class="btn btn-primary btn-sm" style="flex:1"><?= svgIcon('phone', 13) ?> اتصال</a><?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endforeach; endif; ?>

  <?php if (!empty($onlinePartners)): ?>
    <div style="margin-bottom:32px">
      <div style="font-size:13px;font-weight:800;color:var(--accent);text-transform:uppercase;letter-spacing:.05em;margin-bottom:14px;display:flex;align-items:center;gap:6px"><?= svgIcon('search', 14) ?> شركاؤنا في البيع الأونلاين</div>
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:16px">
        <?php foreach ($onlinePartners as $r): ?>
          <a href="<?= e($r['website'] ?: '#') ?>" target="_blank" class="retailer-card retailer-card-online">
            <?php if ($r['logo']): ?>
              <img src="<?= uploadUrl($r['logo']) ?>" class="retailer-logo" style="margin:0 auto 12px">
            <?php else: ?>
              <div class="retailer-logo retailer-logo-ph" style="margin:0 auto 12px"><?= svgIcon('search', 22) ?></div>
            <?php endif; ?>
            <div style="font-weight:800;font-size:14.5px;unicode-bidi:plaintext"><?= e($r['name']) ?></div>
            <?php if ($r['website']): ?><div style="font-size:11.5px;color:var(--accent);margin-top:4px;direction:ltr"><?= e(parse_url($r['website'], PHP_URL_HOST) ?: $r['website']) ?></div><?php endif; ?>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

  <div style="background:var(--black);border-radius:var(--radius);padding:24px;text-align:center;margin-top:20px">
    <div style="color:#fff;font-weight:700;margin-bottom:8px">اشتريت من أحد المحلات دي؟</div>
    <a href="<?= url('warranty') ?>" class="btn btn-primary"><?= svgIcon('shield', 15) ?> فعّل ضمان منتجك ←</a>
  </div>
</div>
<style>
.retailer-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:20px;transition:border-color .2s,transform .2s}
.retailer-card:hover{border-color:var(--accent);transform:translateY(-2px)}
.retailer-card-top{display:flex;align-items:center;gap:12px;margin-bottom:14px}
.retailer-logo{width:48px;height:48px;object-fit:contain;border-radius:11px;border:1px solid var(--border);flex-shrink:0;background:#fff;padding:4px}
.retailer-logo-ph{display:flex;align-items:center;justify-content:center;color:var(--text3);background:var(--bg2)}
.retailer-line{display:flex;align-items:center;gap:6px;font-size:12.5px;color:var(--text2);margin-bottom:6px}
.retailer-card-online{display:block;text-align:center;text-decoration:none;color:inherit}
</style>
<?php
$content = ob_get_clean();
require APP_PATH . '/Views/store/layouts/app.php';
