<?php
$pageTitle  = 'المنتجات';
$breadcrumb = [['label' => 'المنتجات']];
$a = ADMIN_PREFIX;
// Controller passes $paginator
$data      = $paginator['data']         ?? [];
$total     = $paginator['total']        ?? 0;
$curPage   = $paginator['current_page'] ?? 1;
$lastPage  = $paginator['last_page']    ?? 1;
ob_start();
?>
<div class="ph">
  <div class="ph-left"><h1>المنتجات</h1><p><?= number_format($total) ?> منتج</p></div>
  <div class="ph-right"><a href="<?= adminUrl('import/shopify') ?>" class="btn btn-secondary">🛍️ استيراد من Shopify</a><a href="/<?= $a ?>/products/create" class="btn btn-primary">+ إضافة منتج</a></div>
</div>
<div class="card">
  <!-- Channel tabs — قطاعي/جملة/الكل. Polaris-style: neutral by default, color used only on the
       active tab's underline to signal state, not as decoration. -->
  <div class="chan-tabs">
    <?php foreach ([
      ''                => ['الكل', $channelCounts['all'] ?? 0],
      'both'            => ['قطاعي وجملة', $channelCounts['both'] ?? 0],
      'retail_only'     => ['قطاعي فقط', $channelCounts['retail_only'] ?? 0],
      'wholesale_only'  => ['جملة فقط', $channelCounts['wholesale_only'] ?? 0],
    ] as $chVal => [$chLabel, $chCount]): ?>
      <a href="?channel=<?= urlencode($chVal) ?>&status=<?= urlencode($status??'') ?>&search=<?= urlencode($search??'') ?>"
         class="chan-tab <?= ($channel??'')===$chVal ? 'active' : '' ?>">
        <?= $chLabel ?> <span class="chan-tab-count"><?= number_format($chCount) ?></span>
      </a>
    <?php endforeach; ?>
  </div>
  <!-- Status tabs — "مؤرشف" is its own explicit tab: archived products are excluded from the
       default/all view (see adminList()) so they never mix in silently; this tab is the only
       way to see them, on purpose. -->
  <div class="chan-tabs" style="border-top:1px solid var(--bd)">
    <?php foreach ([
      ''         => ['الكل (نشط + مسودة)', $statusCounts['all'] ?? 0, 'grid'],
      'active'   => ['نشط', $statusCounts['active'] ?? 0, 'check'],
      'draft'    => ['مسودة', $statusCounts['draft'] ?? 0, 'edit'],
      'archived' => ['مؤرشف', $statusCounts['archived'] ?? 0, 'package'],
    ] as $stVal => [$stLabel, $stCount, $stIcon]): ?>
      <a href="?status=<?= urlencode($stVal) ?>&channel=<?= urlencode($channel??'') ?>&search=<?= urlencode($search??'') ?>"
         class="chan-tab <?= ($status??'')===$stVal ? 'active' : '' ?>">
        <?= svgIcon($stIcon, 13) ?> <?= $stLabel ?> <span class="chan-tab-count"><?= number_format($stCount) ?></span>
      </a>
    <?php endforeach; ?>
  </div>
  <div style="padding:12px 14px;border-bottom:1px solid var(--bd)" id="bulkBarWrap">
    <form method="GET" class="filter-bar" id="filterForm">
      <input type="hidden" name="channel" value="<?= e($channel??'') ?>">
      <div class="search-box">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
        <input type="text" name="search" class="search-input" placeholder="بحث بالاسم أو SKU..." value="<?= e($search ?? '') ?>">
      </div>
      <select name="status" class="filter-select" onchange="this.form.submit()">
        <option value="">كل الحالات</option>
        <option value="active"   <?= ($status??'')==='active'  ?'selected':'' ?>>✅ نشط</option>
        <option value="draft"    <?= ($status??'')==='draft'   ?'selected':'' ?>>📝 مسودة</option>
        <option value="archived" <?= ($status??'')==='archived'?'selected':'' ?>>📦 مؤرشف</option>
      </select>
      <button type="submit" class="btn btn-secondary">بحث</button>
      <?php if (!empty($search) || !empty($status)): ?>
        <a href="/<?= $a ?>/products" class="btn btn-secondary">× مسح</a>
      <?php endif; ?>
    </form>
    <!-- Bulk action bar — appears once at least one product row is checked -->
    <form method="POST" action="/<?= $a ?>/products/bulk-update" id="bulkForm" style="display:none;align-items:center;gap:10px;margin-top:10px;padding-top:10px;border-top:1px solid var(--bd);flex-wrap:wrap" onsubmit="return confirmBulk(event)">
      <?= csrf_field() ?>
      <span id="bulkCount" style="font-size:12.5px;font-weight:700;color:var(--ac)"></span>
      <select name="channel" class="filter-select" onchange="if(this.value){document.getElementById('bulkAction').value='channel';document.getElementById('bulkForm').requestSubmit();}">
        <option value="">تغيير مكان البيع لـ...</option>
        <option value="both">قطاعي وجملة</option>
        <option value="retail_only">قطاعي فقط</option>
        <option value="wholesale_only">جملة فقط</option>
      </select>
      <select name="status" class="filter-select" onchange="if(this.value){document.getElementById('bulkAction').value='status';document.getElementById('bulkForm').requestSubmit();}">
        <option value="">تغيير الحالة لـ...</option>
        <option value="active">نشط</option>
        <option value="draft">مسودة</option>
        <option value="archived">مؤرشف</option>
      </select>
      <button type="button" class="btn btn-danger btn-sm" onclick="document.getElementById('bulkAction').value='delete';document.getElementById('bulkForm').requestSubmit()">حذف المحدد</button>
      <input type="hidden" name="bulk_action" id="bulkAction" value="">
      <span id="bulkIdsWrap"></span>
    </form>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th style="width:34px"><input type="checkbox" id="checkAll" onchange="toggleAll(this)"></th><th>المنتج</th><th>الحالة</th><th>السعر (قطاعي)</th><th>سعر الجملة</th><th>نوع البيع</th><th>المخزون</th><th>التصنيف</th><th>الماركة</th><th></th></tr></thead>
      <tbody>
        <?php if (empty($data)): ?>
          <tr><td colspan="10">
            <div class="empty">
              <div class="empty-icon"><?= svgIcon('package', 32) ?></div>
              <div class="empty-title">لا توجد منتجات</div>
              <div class="empty-desc">ابدأ بإضافة أول منتج في متجرك</div>
              <a href="/<?= $a ?>/products/create" class="btn btn-primary">+ إضافة منتج</a>
            </div>
          </td></tr>
        <?php else: foreach ($data as $p): ?>
          <tr>
            <td><input type="checkbox" class="row-check" value="<?= $p['id'] ?>" onchange="updateBulkBar()"></td>
            <td>
              <div class="prod-cell">
                <?php if ($p['thumbnail']??null): ?>
                  <img src="<?= uploadUrl($p['thumbnail']) ?>" class="prod-img" alt="">
                <?php else: ?>
                  <div class="prod-img-ph">📦</div>
                <?php endif; ?>
                <div>
                  <a href="/<?= $a ?>/products/<?= $p['id'] ?>" class="prod-name" style="color:var(--t1)"><?= e($p['name']) ?></a>
                  <?php if ($p['sku']??null): ?><div class="prod-sku">SKU: <?= e($p['sku']) ?></div><?php endif; ?>
                </div>
              </div>
            </td>
            <td>
              <form method="POST" action="/<?= $a ?>/products/<?= $p['id'] ?>/quick-status" onsubmit="return true">
                <?= csrf_field() ?>
                <select name="status" class="quick-status-select badge-<?= match($p['status']??'draft'){'active'=>'ok','draft'=>'warn',default=>'dim'} ?>" onchange="this.form.requestSubmit()">
                  <option value="active"   <?= ($p['status']??'')==='active'  ?'selected':'' ?>>✅ نشط</option>
                  <option value="draft"    <?= ($p['status']??'')==='draft'   ?'selected':'' ?>>📝 مسودة</option>
                  <option value="archived" <?= ($p['status']??'')==='archived'?'selected':'' ?>>📦 مؤرشف</option>
                </select>
              </form>
            </td>
            <td>
              <input type="number" class="quick-price-input" data-id="<?= $p['id'] ?>" value="<?= e($p['price']) ?>" step="0.01" min="0" onchange="saveQuickPrice(this)">
              <?php if ($p['compare_price']??null): ?><div style="font-size:11px;color:var(--t3);text-decoration:line-through"><?= money($p['compare_price']) ?></div><?php endif; ?>
            </td>
            <td>
              <?php if (($p['channel']??'both') === 'retail_only'): ?>
                <span class="td-dim">— غير متاح للجملة</span>
              <?php elseif (!empty($p['wholesale_enabled']) && (int)$p['wholesale_tier_count'] > 0): ?>
                <span style="font-weight:700;color:var(--ac)">من <?= money($p['wholesale_from_price']) ?></span>
                <div style="font-size:10.5px;color:var(--t3)"><?= (int)$p['wholesale_tier_count'] ?> شرائح كمية</div>
              <?php elseif (!empty($p['wholesale_enabled'])): ?>
                <span class="badge badge-dim">حسب شريحة العميل</span>
              <?php else: ?>
                <span class="badge badge-warn">⚠ جملة مفعّلة، غير مسعّرة</span>
              <?php endif; ?>
            </td>
            <td>
              <select class="quick-status-select badge-dim" data-id="<?= $p['id'] ?>" onchange="saveQuickChannel(this)">
                <option value="both" <?= ($p['channel']??'both')==='both'?'selected':'' ?>>قطاعي وجملة</option>
                <option value="retail_only" <?= ($p['channel']??'')==='retail_only'?'selected':'' ?>>قطاعي فقط</option>
                <option value="wholesale_only" <?= ($p['channel']??'')==='wholesale_only'?'selected':'' ?>>جملة فقط</option>
              </select>
            </td>
            <td>
              <?php if ((int)($p['variant_count']??0) > 0):
                $vStock = (int)$p['variant_stock_total'];
              ?>
                <?php if (!$p['track_stock']): ?>
                  <span class="td-dim">—</span>
                <?php elseif ($vStock == 0): ?>
                  <span class="badge badge-err">نفذ</span>
                <?php elseif ($vStock <= 5): ?>
                  <span class="badge badge-warn"><?= $vStock ?></span>
                <?php else: ?>
                  <span style="font-weight:600"><?= number_format($vStock) ?></span>
                <?php endif; ?>
                <div style="font-size:10px;color:var(--t3)"><?= (int)$p['variant_count'] ?> متغيرات</div>
              <?php elseif (!($p['track_stock']??0)): ?>
                <span class="td-dim">—</span>
              <?php elseif (($p['stock']??0) == 0): ?>
                <span class="badge badge-err">نفذ</span>
              <?php elseif (($p['stock']??0) <= 5): ?>
                <span class="badge badge-warn"><?= $p['stock'] ?></span>
              <?php else: ?>
                <span style="font-weight:600"><?= number_format($p['stock']) ?></span>
              <?php endif; ?>
            </td>
            <td class="td-dim"><?= e($p['collection_name'] ?? '—') ?></td>
            <td class="td-dim"><?= e($p['brand_name'] ?? '—') ?></td>
            <td>
              <div style="display:flex;gap:5px;justify-content:flex-end">
                <a href="/<?= $a ?>/products/<?= $p['id'] ?>/edit" class="btn btn-secondary btn-sm">تعديل</a>
                <button class="btn btn-secondary btn-sm" onclick="openDuplicateModal(<?= $p['id'] ?>, <?= htmlspecialchars(json_encode($p['name']), ENT_QUOTES) ?>)" title="نسخ المنتج كمنتج مستقل بمخزون منفصل"><?= svgIcon('grid', 13) ?></button>
                <button class="btn btn-danger btn-sm" onclick="confirmDelete('/<?= $a ?>/products/<?= $p['id'] ?>/delete','حذف المنتج؟','سيتم حذف المنتج وصوره نهائياً.')">حذف</button>
              </div>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Duplicate product modal — copies the product into a fully independent row with its own
       stock, letting the admin split a single "both" product into separate retail/wholesale ones. -->
  <div class="modal-overlay" id="duplicateModal">
    <div class="modal">
      <div class="modal-head"><h3>نسخ المنتج</h3><button type="button" class="btn btn-ghost btn-icon" onclick="document.getElementById('duplicateModal').classList.remove('open')" style="font-size:18px">×</button></div>
      <p style="font-size:12.5px;color:var(--t3);margin-bottom:14px" id="dupProductName"></p>
      <p style="font-size:12px;color:var(--t2);margin-bottom:16px;background:var(--bg3);padding:10px 12px;border-radius:8px">هيتعمل منتج جديد مستقل تماماً — سعره ومخزونه ومتغيراته منفصلين خالص عن الأصلي، وحالته هتبقى "مسودة" لحد ما تراجعه.</p>
      <form method="POST" id="duplicateForm">
        <?= csrf_field() ?>
        <div class="form-group" style="margin-bottom:16px">
          <label class="form-label">النسخة الجديدة تبقى نوعها</label>
          <select name="channel" class="form-select">
            <option value="retail_only">قطاعي فقط</option>
            <option value="wholesale_only">جملة فقط</option>
            <option value="both">قطاعي وجملة (زي الأصلي)</option>
          </select>
        </div>
        <div class="modal-actions">
          <button type="button" class="btn btn-secondary" onclick="document.getElementById('duplicateModal').classList.remove('open')">إلغاء</button>
          <button type="submit" class="btn btn-primary">انسخ المنتج</button>
        </div>
      </form>
    </div>
  </div>
  <?php if ($lastPage > 1): ?>
    <div class="pagination">
      <?php for ($i=1;$i<=$lastPage;$i++): ?>
        <a href="?page=<?=$i?>&search=<?=urlencode($search??'')?>&status=<?=urlencode($status??'')?>" class="page-btn <?=$i===$curPage?'active':''?>"><?=$i?></a>
      <?php endfor; ?>
    </div>
  <?php endif; ?>
</div>
<style>
.quick-status-select{border:none;border-radius:100px;padding:4px 10px;font-size:11.5px;font-weight:700;cursor:pointer;appearance:none;-webkit-appearance:none}
.quick-status-select.badge-ok{background:rgba(34,197,94,.12);color:var(--green,#16a34a)}
.quick-status-select.badge-warn{background:rgba(245,158,11,.12);color:#b45309}
.quick-status-select.badge-dim{background:var(--bg3);color:var(--t3)}
.quick-price-input{width:90px;border:1px solid transparent;background:none;font-weight:700;font-size:13px;color:var(--t1);border-radius:6px;padding:4px 6px;transition:border-color .15s,background .15s}
.quick-price-input:hover{border-color:var(--bd)}
.quick-price-input:focus{border-color:var(--ac);background:var(--bg1);outline:none}
.quick-price-input.saved{background:rgba(34,197,94,.12) !important}
</style>
<script>
function toggleAll(cb){
  document.querySelectorAll('.row-check').forEach(r => r.checked = cb.checked);
  updateBulkBar();
}
function updateBulkBar(){
  const checked = document.querySelectorAll('.row-check:checked');
  const bar = document.getElementById('bulkForm');
  if (checked.length > 0) {
    bar.style.display = 'flex';
    document.getElementById('bulkCount').textContent = checked.length + ' منتج محدد';
    let idsHtml = '';
    checked.forEach(c => idsHtml += '<input type="hidden" name="ids[]" value="'+c.value+'">');
    document.getElementById('bulkIdsWrap').innerHTML = idsHtml;
  } else {
    bar.style.display = 'none';
  }
}
function confirmBulk(e){
  const action = document.getElementById('bulkAction').value;
  if (action === 'delete') {
    return confirm('متأكد إنك عايز تحذف المنتجات المحددة؟ الإجراء ده نهائي.');
  }
  return true;
}

const _csrfToken = '<?= csrf_token() ?>';

function saveQuickPrice(input){
  const id = input.dataset.id;
  const price = input.value;
  const fd = new FormData();
  fd.append('price', price);
  fd.append('_csrf', _csrfToken);
  fetch('/<?= $a ?>/products/'+id+'/quick-price', {method:'POST', body: fd})
    .then(r => r.json())
    .then(d => {
      if (d.ok) {
        input.classList.add('saved');
        setTimeout(() => input.classList.remove('saved'), 900);
      } else {
        alert(d.msg || 'فشل تحديث السعر');
      }
    })
    .catch(() => alert('حدث خطأ، حاول تاني'));
}

function saveQuickChannel(select){
  const id = select.dataset.id;
  const channel = select.value;
  const fd = new FormData();
  fd.append('channel', channel);
  fd.append('_csrf', _csrfToken);
  fetch('/<?= $a ?>/products/'+id+'/quick-channel', {method:'POST', body: fd})
    .then(r => r.json())
    .then(d => { if (!d.ok) alert(d.msg || 'فشل التحديث'); else location.reload(); })
    .catch(() => alert('حدث خطأ، حاول تاني'));
}

function openDuplicateModal(productId, productName){
  document.getElementById('dupProductName').textContent = 'نسخ: ' + productName;
  document.getElementById('duplicateForm').action = '/<?= $a ?>/products/' + productId + '/duplicate';
  document.getElementById('duplicateModal').classList.add('open');
}
</script>
<?php $content = ob_get_clean(); require APP_PATH . '/Views/admin/layouts/app.php';
