<?php
// Partial: product card
// Variable: $p (product array)
$discount = 0;
if (!empty($p['compare_price']) && $p['compare_price'] > $p['price']) {
    $discount = round((1 - $p['price'] / $p['compare_price']) * 100);
}
$isNew = !empty($p['created_at']) && (strtotime($p['created_at']) > strtotime('-14 days'));
?>
<div class="product-card">
  <a href="<?= url('products/' . $p['slug']) ?>">
    <div class="product-card-img">
      <?php if (!empty($p['thumbnail'])): ?>
        <img src="<?= uploadUrl($p['thumbnail']) ?>" alt="<?= e($p['name']) ?>" loading="lazy">
      <?php else: ?>
        <div class="product-card-img-placeholder">📦</div>
      <?php endif; ?>
      <?php if ($discount > 0): ?>
        <span class="product-card-badge">-<?= $discount ?>%</span>
      <?php elseif ($p['is_featured'] ?? 0): ?>
        <span class="product-card-badge" style="background:var(--gold);color:#000">⭐ مميز</span>
      <?php elseif ($isNew): ?>
        <span class="product-card-badge" style="background:var(--green,#16a34a)">جديد</span>
      <?php endif; ?>
      <button class="product-card-wishlist" onclick="event.preventDefault();toggleWishlist(<?= $p['id'] ?>,this)" title="أضف للمفضلة"><?= svgIcon('heart', 15) ?></button>
    </div>
  </a>
  <div class="product-card-body">
    <?php if (!empty($p['brand_name'])): ?>
      <div class="product-card-brand"><?= e($p['brand_name']) ?></div>
    <?php endif; ?>
    <a href="<?= url('products/' . $p['slug']) ?>">
      <div class="product-card-name"><?= e($p['name']) ?></div>
    </a>
    <?php if (!empty($p['rating']['count']) && $p['rating']['count'] > 0): ?>
      <div class="product-card-rating">
        <?= str_repeat('★', min(5, round($p['rating']['avg'] ?? 0))) ?><?= str_repeat('☆', 5 - min(5, round($p['rating']['avg'] ?? 0))) ?>
        <span>(<?= $p['rating']['count'] ?>)</span>
      </div>
    <?php endif; ?>
    <div class="product-card-footer">
      <div>
        <div class="product-card-price"><?= money($p['price']) ?></div>
        <?php if ($discount > 0): ?>
          <div class="product-card-old-price"><?= money($p['compare_price']) ?></div>
        <?php endif; ?>
      </div>
      <?php if (($p['stock'] ?? 1) > 0 || !($p['track_stock'] ?? 1)): ?>
        <button class="product-card-add" onclick="addToCart(<?= $p['id'] ?>)" title="أضف للسلة"><?= svgIcon('plus', 17, 2.5) ?></button>
      <?php else: ?>
        <span style="font-size:11px;color:var(--text3)">نفذ</span>
      <?php endif; ?>
    </div>
  </div>
</div>
