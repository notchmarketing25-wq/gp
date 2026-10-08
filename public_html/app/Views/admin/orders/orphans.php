<?php
$pageTitle  = 'منتجات غير مرتبطة';
$breadcrumb = [['label' => 'الطلبات', 'url' => adminUrl('orders')], ['label' => 'منتجات غير مرتبطة']];
ob_start();
?>
<div class="ph">
  <div class="ph-left">
    <h1>منتجات غير مرتبطة</h1>
    <p>عناصر طلبات قديمة كان منتجها محذوفًا — اربطها باسمها بمنتج حالي (مفيد لو مش عارف SKU المنتج القديم)</p>
  </div>
  <div class="ph-right">
    <a href="<?= adminUrl('orders') ?>" class="btn btn-secondary">← رجوع للطلبات</a>
  </div>
</div>

<?php if (empty($groups)): ?>
  <div class="card" style="padding:48px;text-align:center;color:var(--t3)">
    <div style="font-size:40px;margin-bottom:10px">🎉</div>
    كل عناصر الطلبات مرتبطة بمنتجات حالية — لا يوجد شيء يحتاج ربط.
  </div>
<?php else: ?>
  <div class="card">
    <div class="card-header">
      <span class="card-title">مجموعات غير مرتبطة (<?= count($groups) ?>)</span>
      <input type="text" id="orphanSearch" class="search-input" placeholder="فلتر بالاسم..." style="width:220px" oninput="filterOrphans(this.value)">
    </div>
    <div class="table-wrap">
      <table id="orphansTable">
        <thead>
          <tr>
            <th style="width:32%">اسم المنتج (كما حُفظ في الطلب)</th>
            <th>SKU القديم</th>
            <th>عدد الطلبات</th>
            <th>عدد العناصر</th>
            <th>إجمالي الكمية</th>
            <th style="width:30%">اربط بمنتج</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($groups as $g): ?>
            <tr data-name="<?= strtolower(e($g['name'])) ?>">
              <td>
                <div class="prod-cell">
                  <?php if ($g['image']): ?>
                    <img src="<?= uploadUrl($g['image']) ?>" class="prod-img" alt="">
                  <?php else: ?>
                    <div class="prod-img-ph">📦</div>
                  <?php endif; ?>
                  <div class="prod-name"><?= e($g['name']) ?></div>
                </div>
              </td>
              <td class="td-mono td-dim"><?= e($g['sku'] ?: '—') ?></td>
              <td><?= number_format($g['orders_count']) ?></td>
              <td><?= number_format($g['items']) ?></td>
              <td><?= number_format($g['total_qty']) ?></td>
              <td>
                <form method="POST" action="<?= adminUrl('orders/orphans/link') ?>"
                      class="orphan-link-form"
                      onsubmit="return this.product_id.value ? confirm('هيتم ربط كل طلبات &quot;<?= e(addslashes($g['name'])) ?>&quot; بالمنتج المختار. تأكيد؟') : (alert('اختر منتج من القائمة أولاً'), false)"
                      style="display:flex;gap:6px">
                  <?= csrf_field() ?>
                  <input type="hidden" name="name" value="<?= e($g['name']) ?>">
                  <div class="product-combo" style="position:relative;flex:1;min-width:0">
                    <input type="text" class="form-input combo-input" placeholder="🔍 اكتب اسم المنتج..." autocomplete="off">
                    <input type="hidden" name="product_id" class="combo-hidden-id">
                    <div class="combo-panel" style="display:none;position:absolute;z-index:20;top:100%;right:0;left:0;background:var(--bg2);border:1px solid var(--bd);border-radius:8px;max-height:240px;overflow-y:auto;margin-top:4px"></div>
                  </div>
                  <button type="submit" class="btn btn-primary btn-sm">ربط</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

<!-- Product catalog as inert JSON data (type=application/json never executes as script), read via
     JSON.parse below. Every product name reaches the page this way or through .textContent further
     down — never spliced into a JS string or HTML attribute — so a name containing a quote, ™, or
     any other character can't break anything, unlike the earlier AJAX-built picker. -->
<script type="application/json" id="productsData"><?= json_encode($products, JSON_HEX_TAG | JSON_UNESCAPED_UNICODE) ?></script>

<script>
// Pure cosmetic filter over the already-rendered rows — the link action itself is a plain form
// submit handled entirely server-side, so nothing here can break the actual linking.
function filterOrphans(q) {
  q = q.toLowerCase();
  document.querySelectorAll('#orphansTable tbody tr').forEach(r => {
    r.style.display = r.dataset.name?.includes(q) ? '' : 'none';
  });
}

// Type-to-search product combobox. Every option is built with createElement + textContent (never
// innerHTML with an interpolated name), so this cannot break no matter what characters a product
// name contains.
const ORPHAN_PRODUCTS = JSON.parse(document.getElementById('productsData').textContent);

function setupProductCombo(root) {
  const input  = root.querySelector('.combo-input');
  const hidden = root.querySelector('.combo-hidden-id');
  const panel  = root.querySelector('.combo-panel');

  function renderList(filterText) {
    panel.innerHTML = '';
    const q = filterText.trim().toLowerCase();
    const matches = ORPHAN_PRODUCTS
      .filter(p => !q || p.name.toLowerCase().includes(q) || (p.sku || '').toLowerCase().includes(q))
      .slice(0, 50);

    if (!matches.length) {
      const empty = document.createElement('div');
      empty.style.cssText = 'padding:10px;color:var(--t3);font-size:12px';
      empty.textContent = 'لا توجد نتائج';
      panel.appendChild(empty);
    } else {
      matches.forEach(p => {
        const row = document.createElement('div');
        row.style.cssText = 'padding:8px 10px;cursor:pointer;border-bottom:1px solid var(--bd);font-size:13px';
        row.textContent = p.name + (p.sku ? ' — ' + p.sku : '');
        row.addEventListener('mousedown', (e) => {
          // mousedown (not click) fires before the input's blur, so the panel doesn't close first.
          e.preventDefault();
          hidden.value = p.id;
          input.value = p.name;
          panel.style.display = 'none';
        });
        panel.appendChild(row);
      });
    }
    panel.style.display = 'block';
  }

  input.addEventListener('focus', () => renderList(input.value));
  input.addEventListener('input', () => { hidden.value = ''; renderList(input.value); });
  input.addEventListener('blur', () => setTimeout(() => { panel.style.display = 'none'; }, 150));
}

document.querySelectorAll('.product-combo').forEach(setupProductCombo);
</script>

<?php
$content = ob_get_clean();
require APP_PATH . '/Views/admin/layouts/app.php';
