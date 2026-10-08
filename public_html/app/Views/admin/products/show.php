<?php
$pageTitle  = e($product['name']);
$breadcrumb = [
    ['label'=>'المنتجات','url'=>adminUrl('products')],
    ['label'=>$product['name']]
];
$a = ADMIN_PREFIX;

// Generate barcode value
$barcodeVal = $product['barcode'] ?? '';
if (!$barcodeVal) {
    $barcodeVal = $product['sku'] ? 'NT-'.$product['sku'] : 'NT'.str_pad($product['id'],8,'0',STR_PAD_LEFT);
}

ob_start();
?>

<div class="ph">
  <div class="ph-left">
    <h1><?= e($product['name']) ?></h1>
    <p style="display:flex;align-items:center;gap:8px;margin-top:5px">
      <span class="badge <?= match($product['status']??'draft'){'active'=>'badge-ok','draft'=>'badge-warn',default=>'badge-dim'} ?>">
        <?= match($product['status']??'draft'){'active'=>'نشط','draft'=>'مسودة','archived'=>'مؤرشف',default=>($product['status']??'')} ?>
      </span>
      <?php if ($product['sku']??null): ?>
        <code style="font-family:monospace;font-size:12px;color:var(--t2)">SKU: <?= e($product['sku']) ?></code>
      <?php endif; ?>
    </p>
  </div>
  <div class="ph-right">
    <a href="<?= url('products/'.$product['slug']) ?>" target="_blank" class="btn btn-secondary">👁️ عرض</a>
    <button class="btn btn-secondary" onclick="document.getElementById('mergeModal').classList.add('open')">🔀 دمج مع منتج آخر</button>
    <a href="<?= adminUrl('products/'.$product['id'].'/edit') ?>" class="btn btn-primary">✏️ تعديل</a>
  </div>
</div>

<!-- Merge into another product — moves this product's order history/reviews/addons/wishlists
     onto the chosen target, then archives this one. Used to fix reports split across a
     re-created duplicate and its old, now-orphaned order history. -->
<div class="modal-overlay" id="mergeModal">
  <div class="modal">
    <div class="modal-head"><h3>🔀 دمج هذا المنتج مع منتج آخر</h3><button class="btn btn-ghost btn-icon" onclick="document.getElementById('mergeModal').classList.remove('open')" style="font-size:18px">×</button></div>
    <form method="POST" action="<?= adminUrl('products/'.$product['id'].'/merge') ?>" onsubmit="return confirm('سيتم نقل كل طلبات وتقييمات \'<?= e(addslashes($product['name'])) ?>\' إلى المنتج المختار، وأرشفة هذا المنتج. متأكد؟')">
      <?= csrf_field() ?>
      <p style="color:var(--t2);font-size:13px;margin-bottom:14px">
        كل الطلبات والتقييمات والإضافات المرتبطة بـ"<b><?= e($product['name']) ?></b>" ستنتقل للمنتج اللي تختاره، وبعدين هذا المنتج يُؤرشف تلقائيًا (مش حذف نهائي).
      </p>
      <div class="form-group" style="margin-bottom:16px;position:relative">
        <label class="form-label">دمج في المنتج <span class="req">*</span></label>
        <input type="text" id="mergeSearchInput" class="form-input" placeholder="اكتب اسم المنتج أو SKU..." autocomplete="off" oninput="mergeSearch(this.value)">
        <input type="hidden" name="target_id" id="mergeTargetId" required>
        <div id="mergeSearchResults" style="position:absolute;z-index:10;top:100%;right:0;left:0;background:var(--bg2);border:1px solid var(--bd);border-radius:8px;max-height:220px;overflow-y:auto;display:none;margin-top:4px"></div>
      </div>
      <div id="mergeSelectedPreview" style="display:none;align-items:center;gap:8px;background:var(--bg3);border-radius:8px;padding:8px;margin-bottom:16px">
        <img id="mergeSelectedImg" style="width:32px;height:32px;object-fit:cover;border-radius:6px">
        <span id="mergeSelectedName" style="font-weight:600;font-size:13px"></span>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('mergeModal').classList.remove('open')">إلغاء</button>
        <button type="submit" class="btn btn-danger" id="mergeSubmitBtn" disabled>تأكيد الدمج</button>
      </div>
    </form>
  </div>
</div>
<script>
let _mergeSearchTimer = null;
function mergeSearch(q) {
  clearTimeout(_mergeSearchTimer);
  const box = document.getElementById('mergeSearchResults');
  if (q.trim().length < 2) { box.style.display = 'none'; return; }
  _mergeSearchTimer = setTimeout(async () => {
    const r = await fetch('<?= adminUrl('products/search-json') ?>?q=' + encodeURIComponent(q));
    const items = await r.json();
    box.innerHTML = items.filter(p => p.id != <?= (int)$product['id'] ?>).map(p =>
      `<div onclick='mergePick(${p.id}, ${JSON.stringify(p.name)}, ${JSON.stringify(p.thumbnail_url||"")})' style="display:flex;align-items:center;gap:8px;padding:8px;cursor:pointer;border-bottom:1px solid var(--bd)">
         ${p.thumbnail_url ? `<img src="${p.thumbnail_url}" style="width:28px;height:28px;object-fit:cover;border-radius:5px">` : ''}
         <span style="font-size:13px">${p.name}</span>
       </div>`
    ).join('') || '<div style="padding:10px;color:var(--t3);font-size:12px">لا نتائج</div>';
    box.style.display = 'block';
  }, 250);
}
function mergePick(id, name, thumb) {
  document.getElementById('mergeTargetId').value = id;
  document.getElementById('mergeSearchInput').value = name;
  document.getElementById('mergeSearchResults').style.display = 'none';
  document.getElementById('mergeSelectedPreview').style.display = 'flex';
  document.getElementById('mergeSelectedName').textContent = name;
  document.getElementById('mergeSelectedImg').src = thumb || '';
  document.getElementById('mergeSelectedImg').style.visibility = thumb ? 'visible' : 'hidden';
  document.getElementById('mergeSubmitBtn').disabled = false;
}
</script>

<div style="display:grid;grid-template-columns:1fr 280px;gap:16px;align-items:start">

  <!-- ── LEFT ───────────────────────────────────── -->
  <div style="display:flex;flex-direction:column;gap:14px">

    <!-- Images -->
    <div class="card">
      <div class="card-header">
        <span class="card-title">📸 صور المنتج (<?= count($images) ?>)</span>
        <a href="<?= adminUrl('products/'.$product['id'].'/edit') ?>" class="btn btn-secondary btn-sm">إدارة الصور</a>
      </div>
      <div class="card-body">
        <?php if (empty($images)): ?>
          <div style="text-align:center;padding:30px;color:var(--t3);font-size:13px">
            <div style="font-size:40px;margin-bottom:10px">🖼️</div>
            لا توجد صور — <a href="<?= adminUrl('products/'.$product['id'].'/edit') ?>" style="color:var(--ac)">أضف صور</a>
          </div>
        <?php else: ?>
          <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(110px,1fr));gap:10px">
            <?php foreach ($images as $img): ?>
              <div style="aspect-ratio:1;border-radius:var(--radius-xs);overflow:hidden;border:1px solid var(--bd);background:var(--bg3)">
                <img src="<?= uploadUrl($img['image']) ?>" style="width:100%;height:100%;object-fit:cover" alt="<?= e($img['alt']??'') ?>">
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Description -->
    <?php if ($product['description']??null): ?>
      <div class="card">
        <div class="card-header"><span class="card-title">الوصف</span></div>
        <div class="card-body">
          <p style="font-size:13px;color:var(--t2);line-height:1.9"><?= nl2br(e($product['description'])) ?></p>
        </div>
      </div>
    <?php endif; ?>

    <!-- Variants -->
    <div class="card">
      <div class="card-header">
        <span class="card-title"><?= svgIcon('tag', 15) ?> المتغيرات (<?= count($variants) ?>)</span>
        <button class="btn btn-primary btn-sm" onclick="openAddVariant()">+ إضافة متغير</button>
      </div>
      <?php if (!empty($variants)): ?>
        <div class="table-wrap">
          <table>
            <thead><tr><th>الصورة</th><th>المتغير</th><th>SKU</th><th>السعر</th><th>المخزون</th><th></th></tr></thead>
            <tbody>
              <?php foreach ($variants as $v): $vs=(int)($v['stock']??0); ?>
                <tr>
                  <td><?php if($v['image']): ?><img src="<?= uploadUrl($v['image']) ?>" style="width:36px;height:36px;object-fit:cover;border-radius:7px"><?php else: ?><span class="td-dim">—</span><?php endif; ?></td>
                  <td style="font-weight:600"><?= e($v['title']??'') ?></td>
                  <td class="td-mono td-dim"><?= e($v['sku']??'—') ?></td>
                  <td><?= money($v['price']??$product['price']) ?></td>
                  <td><span style="font-weight:700;color:<?=$vs===0?'var(--red)':($vs<=5?'var(--yellow)':'var(--green)') ?>"><?=$vs===0?'نفذ':$vs?></span></td>
                  <td>
                    <div style="display:flex;gap:5px;justify-content:flex-end">
                      <button class="btn btn-secondary btn-sm" onclick='openEditVariant(<?= htmlspecialchars(json_encode($v),ENT_QUOTES) ?>)'>تعديل</button>
                      <form method="POST" action="<?= adminUrl('products/'.$product['id'].'/variants/'.$v['id'].'/delete') ?>" style="display:inline" onsubmit="return confirm('حذف المتغير؟')">
                        <?= csrf_field() ?>
                        <button class="btn btn-danger btn-sm">حذف</button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="empty"><div class="empty-icon"><?= svgIcon('tag', 32) ?></div><div class="empty-title">لا توجد متغيرات — أضف واحد لو المنتج عنده اختيارات زي اللون أو النوع</div></div>
      <?php endif; ?>
    </div>

    <div class="modal-overlay" id="variantModal">
      <div class="modal">
        <div class="modal-head"><h3 id="vmTitle">+ إضافة متغير</h3><button class="btn btn-ghost btn-icon" onclick="document.getElementById('variantModal').classList.remove('open')" style="font-size:18px">×</button></div>
        <form method="POST" id="variantForm" enctype="multipart/form-data">
          <?= csrf_field() ?>
          <div class="form-group" style="margin-bottom:12px">
            <label class="form-label">اسم المتغير <span class="req">*</span></label>
            <input type="text" name="title" id="vmTitleInput" class="form-input" required placeholder="مثال: أحمر - USB-C">
          </div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:12px">
            <div class="form-group" style="margin:0">
              <label class="form-label">السعر <span class="req">*</span></label>
              <input type="number" name="price" id="vmPrice" class="form-input" step="0.01" required value="<?= e($product['price']) ?>">
            </div>
            <div class="form-group" style="margin:0">
              <label class="form-label">المخزون</label>
              <input type="number" name="stock" id="vmStock" class="form-input" value="0">
            </div>
          </div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:12px">
            <div class="form-group" style="margin:0">
              <label class="form-label">SKU (اختياري)</label>
              <input type="text" name="sku" id="vmSku" class="form-input">
            </div>
            <div class="form-group" style="margin:0">
              <label class="form-label">سعر قبل الخصم (اختياري)</label>
              <input type="number" name="compare_price" id="vmComparePrice" class="form-input" step="0.01">
            </div>
          </div>
          <div class="form-group" style="margin-bottom:16px">
            <label class="form-label">صورة المتغير (اختياري)</label>
            <input type="file" name="image" class="form-input" accept="image/*" style="padding:8px">
          </div>
          <div class="modal-actions">
            <button type="button" class="btn btn-secondary" onclick="document.getElementById('variantModal').classList.remove('open')">إلغاء</button>
            <button type="submit" class="btn btn-primary">حفظ</button>
          </div>
        </form>
      </div>
    </div>
    <script>
    function openAddVariant(){
      document.getElementById('vmTitle').textContent='+ إضافة متغير';
      document.getElementById('variantForm').reset();
      document.getElementById('variantForm').action='<?= adminUrl('products/'.$product['id'].'/variants/create') ?>';
      document.getElementById('vmPrice').value='<?= e($product['price']) ?>';
      document.getElementById('variantModal').classList.add('open');
    }
    function openEditVariant(v){
      document.getElementById('vmTitle').textContent='تعديل المتغير';
      document.getElementById('variantForm').action='<?= adminUrl('products/'.$product['id'].'/variants/') ?>'+v.id;
      document.getElementById('vmTitleInput').value=v.title||'';
      document.getElementById('vmPrice').value=v.price||0;
      document.getElementById('vmStock').value=v.stock||0;
      document.getElementById('vmSku').value=v.sku||'';
      document.getElementById('vmComparePrice').value=v.compare_price||'';
      document.getElementById('variantModal').classList.add('open');
    }
    </script>

    <!-- Add-ons / Bundles ("خد الشاحن مع الكابل بسعر خاص") -->
    <div class="card">
      <div class="card-header">
        <span class="card-title"><?= svgIcon('tag', 15) ?> إضافات مقترحة (<?= count($addons) ?>)</span>
        <button class="btn btn-primary btn-sm" onclick="document.getElementById('addonModal').classList.add('open')">+ إضافة منتج مكمّل</button>
      </div>
      <?php if (!empty($addons)): ?>
        <div class="table-wrap">
          <table>
            <thead><tr><th>المنتج</th><th>السعر الأصلي</th><th>الخصم</th><th>متحدد تلقائياً</th><th></th></tr></thead>
            <tbody>
              <?php foreach ($addons as $ad): ?>
                <tr>
                  <td style="display:flex;align-items:center;gap:8px">
                    <?php if ($ad['addon_thumbnail']): ?>
                      <img src="<?= uploadUrl($ad['addon_thumbnail']) ?>" style="width:32px;height:32px;object-fit:cover;border-radius:6px">
                    <?php endif; ?>
                    <span style="font-weight:600"><?= e($ad['addon_name']) ?></span>
                  </td>
                  <td><?= money($ad['addon_price']) ?></td>
                  <td>
                    <?php if ($ad['discount_type'] === 'none'): ?>
                      <span class="td-dim">بدون خصم</span>
                    <?php elseif ($ad['discount_type'] === 'percent'): ?>
                      <span class="badge badge-ok"><?= (float)$ad['discount_value'] ?>%</span>
                    <?php else: ?>
                      <span class="badge badge-ok">-<?= money($ad['discount_value']) ?></span>
                    <?php endif; ?>
                  </td>
                  <td><?= $ad['is_checked_default'] ? '✅' : '—' ?></td>
                  <td>
                    <form method="POST" action="<?= adminUrl('products/'.$product['id'].'/addons/'.$ad['id'].'/delete') ?>" style="display:inline" onsubmit="return confirm('حذف الإضافة؟')">
                      <?= csrf_field() ?>
                      <button class="btn btn-danger btn-sm">حذف</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="empty"><div class="empty-icon"><?= svgIcon('tag', 32) ?></div><div class="empty-title">لا توجد إضافات مقترحة — أضف منتج مكمّل زي "الشاحن" على منتج "الكابل" بسعر باقة خاص</div></div>
      <?php endif; ?>
    </div>

    <div class="modal-overlay" id="addonModal">
      <div class="modal">
        <div class="modal-head"><h3>+ إضافة منتج مكمّل</h3><button class="btn btn-ghost btn-icon" onclick="document.getElementById('addonModal').classList.remove('open')" style="font-size:18px">×</button></div>
        <form method="POST" action="<?= adminUrl('products/'.$product['id'].'/addons/create') ?>">
          <?= csrf_field() ?>
          <div class="form-group" style="margin-bottom:12px;position:relative">
            <label class="form-label">ابحث عن المنتج <span class="req">*</span></label>
            <input type="text" id="addonSearchInput" class="form-input" placeholder="اكتب اسم المنتج أو SKU..." autocomplete="off" oninput="addonSearch(this.value)">
            <input type="hidden" name="addon_product_id" id="addonProductId" required>
            <div id="addonSearchResults" style="position:absolute;z-index:10;top:100%;right:0;left:0;background:var(--bg2);border:1px solid var(--bd);border-radius:8px;max-height:220px;overflow-y:auto;display:none;margin-top:4px"></div>
          </div>
          <div id="addonSelectedPreview" style="display:none;align-items:center;gap:8px;background:var(--bg3);border-radius:8px;padding:8px;margin-bottom:12px">
            <img id="addonSelectedImg" style="width:32px;height:32px;object-fit:cover;border-radius:6px">
            <span id="addonSelectedName" style="font-weight:600;font-size:13px"></span>
          </div>
          <div class="form-group" style="margin-bottom:12px">
            <label class="form-label">نوع الخصم على سعر الباقة</label>
            <select name="discount_type" class="form-select">
              <option value="none">بدون خصم (السعر الأصلي)</option>
              <option value="percent">نسبة مئوية %</option>
              <option value="fixed">مبلغ ثابت</option>
            </select>
          </div>
          <div class="form-group" style="margin-bottom:12px">
            <label class="form-label">قيمة الخصم</label>
            <input type="number" name="discount_value" class="form-input" step="0.01" value="0">
          </div>
          <div class="form-group" style="margin-bottom:16px;display:flex;align-items:center;gap:8px">
            <input type="checkbox" name="is_checked_default" id="addonCheckedDefault" checked style="width:16px;height:16px">
            <label for="addonCheckedDefault" style="font-size:13px;margin:0">يبان متحدد تلقائياً في صفحة المنتج</label>
          </div>
          <div class="modal-actions">
            <button type="button" class="btn btn-secondary" onclick="document.getElementById('addonModal').classList.remove('open')">إلغاء</button>
            <button type="submit" class="btn btn-primary">حفظ</button>
          </div>
        </form>
      </div>
    </div>
    <script>
    let _addonSearchTimer = null;
    function addonSearch(q) {
      clearTimeout(_addonSearchTimer);
      const box = document.getElementById('addonSearchResults');
      if (q.trim().length < 2) { box.style.display = 'none'; return; }
      _addonSearchTimer = setTimeout(async () => {
        const r = await fetch('<?= adminUrl('products/search-json') ?>?q=' + encodeURIComponent(q));
        const items = await r.json();
        box.innerHTML = items.filter(p => p.id != <?= (int)$product['id'] ?>).map(p =>
          `<div onclick='addonPick(${p.id}, ${JSON.stringify(p.name)}, ${JSON.stringify(p.thumbnail_url||"")})' style="display:flex;align-items:center;gap:8px;padding:8px;cursor:pointer;border-bottom:1px solid var(--bd)">
             ${p.thumbnail_url ? `<img src="${p.thumbnail_url}" style="width:28px;height:28px;object-fit:cover;border-radius:5px">` : ''}
             <span style="font-size:13px">${p.name}</span>
           </div>`
        ).join('') || '<div style="padding:10px;color:var(--t3);font-size:12px">لا نتائج</div>';
        box.style.display = 'block';
      }, 250);
    }
    function addonPick(id, name, thumb) {
      document.getElementById('addonProductId').value = id;
      document.getElementById('addonSearchInput').value = name;
      document.getElementById('addonSearchResults').style.display = 'none';
      document.getElementById('addonSelectedPreview').style.display = 'flex';
      document.getElementById('addonSelectedName').textContent = name;
      document.getElementById('addonSelectedImg').src = thumb || '';
      document.getElementById('addonSelectedImg').style.visibility = thumb ? 'visible' : 'hidden';
    }
    </script>

    <!-- Stock movements -->
    <div class="card">
      <div class="card-header">
        <span class="card-title"><?= svgIcon('package', 15) ?> حركات المخزون</span>
        <a href="<?= adminUrl('warehouse') ?>" class="btn btn-secondary btn-sm">المخزن ←</a>
      </div>
      <?php if (empty($movements)): ?>
        <div style="padding:28px;text-align:center;color:var(--t3);font-size:13px">لا توجد حركات بعد</div>
      <?php else: ?>
        <div class="table-wrap">
          <table>
            <thead><tr><th>النوع</th><th>الكمية</th><th>قبل</th><th>بعد</th><th>السبب</th><th>التاريخ</th></tr></thead>
            <tbody>
              <?php foreach ($movements as $m): ?>
                <tr>
                  <td><span class="badge <?= match($m['type']){'in'=>'badge-ok','out'=>'badge-err','adjustment'=>'badge-warn',default=>'badge-info'} ?>"><?= match($m['type']){'in'=>'⬆️ وارد','out'=>'⬇️ صادر','adjustment'=>'🔄 تسوية',default=>$m['type']} ?></span></td>
                  <td style="font-weight:700;color:<?=$m['type']==='in'?'var(--green)':'var(--red)' ?>"><?=($m['type']==='in'?'+':'-').abs($m['qty'])?></td>
                  <td class="td-dim"><?=$m['qty_before']?></td>
                  <td style="font-weight:600"><?=$m['qty_after']?></td>
                  <td class="td-dim" style="font-size:11px"><?= e($m['reason']??'—') ?></td>
                  <td class="td-dim" style="font-size:11px"><?= formatDateTime($m['created_at']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

  </div>

  <!-- ── RIGHT ──────────────────────────────────── -->
  <div style="display:flex;flex-direction:column;gap:12px">

    <!-- Price -->
    <div class="card">
      <div class="card-header"><span class="card-title">💰 التسعير</span></div>
      <div class="card-body">
        <div style="font-size:30px;font-weight:900;letter-spacing:-1px;color:var(--t1)"><?= money($product['price']) ?></div>
        <?php if ($product['compare_price']??null): ?>
          <div style="font-size:13px;color:var(--t3);text-decoration:line-through;margin-top:4px"><?= money($product['compare_price']) ?></div>
          <?php $disc = round((1-$product['price']/$product['compare_price'])*100); ?>
          <span class="badge badge-err" style="margin-top:8px">خصم <?= $disc ?>%</span>
        <?php endif; ?>
        <?php if ($product['cost_price']??null): ?>
          <div style="margin-top:12px;padding-top:12px;border-top:1px solid var(--bd)">
            <div style="font-size:11px;color:var(--t3)">التكلفة</div>
            <div style="font-size:14px;font-weight:600"><?= money($product['cost_price']) ?></div>
            <div style="font-size:11px;color:var(--green);margin-top:2px">هامش: <?= money($product['price'] - $product['cost_price']) ?></div>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Stock -->
    <div class="card">
      <div class="card-header">
        <span class="card-title"><?= svgIcon('package', 15) ?> المخزون</span>
        <button class="btn btn-secondary btn-sm" onclick="openAdjustFromShow(<?= $product['id'] ?>,'<?= e(addslashes($product['name'])) ?>')">تعديل</button>
      </div>
      <div class="card-body">
        <?php if (!($product['track_stock']??0)): ?>
          <div style="color:var(--t3);font-size:13px">غير محدود</div>
        <?php else:
          $stock = (int)$product['stock'];
          $color = $stock===0?'var(--red)':($stock<=5?'var(--yellow)':'var(--green)');
        ?>
          <div style="font-size:40px;font-weight:900;color:<?=$color?>;letter-spacing:-2px"><?= number_format($stock) ?></div>
          <div style="font-size:12px;color:var(--t3)">وحدة متاحة</div>
          <?php if ($stock===0): ?><span class="badge badge-err" style="margin-top:8px">نفذ من المخزون</span><?php endif; ?>
          <?php if ($stock>0&&$stock<=5): ?><span class="badge badge-warn" style="margin-top:8px">مخزون منخفض</span><?php endif; ?>
        <?php endif; ?>
      </div>
    </div>

    <!-- Barcode -->
    <div class="card">
      <div class="card-header"><span class="card-title">🔖 الباركود</span></div>
      <div class="card-body">
        <div id="bcWrapper" style="text-align:center;background:var(--bg3);border-radius:8px;padding:14px;margin-bottom:12px">
          <svg id="bcSvg"></svg>
          <div style="font-family:monospace;font-size:12px;font-weight:700;margin-top:6px;letter-spacing:1px"><?= e($barcodeVal) ?></div>
        </div>

        <?php if (!($product['barcode']??null)): ?>
          <!-- Save barcode to DB -->
          <form method="POST" action="<?= adminUrl('products/'.$product['id'].'/barcode') ?>" style="margin-bottom:8px">
            <?= csrf_field() ?>
            <input type="hidden" name="barcode" id="barcodeInput" value="<?= e($barcodeVal) ?>">
            <div style="display:flex;gap:6px;margin-bottom:8px">
              <input type="text" id="barcodeEdit" class="form-input" value="<?= e($barcodeVal) ?>" placeholder="أدخل باركود" oninput="updateBarcode(this.value)">
              <button type="button" class="btn btn-secondary btn-sm" onclick="genBarcode()" title="توليد عشوائي">🎲</button>
            </div>
            <button type="submit" class="btn btn-primary btn-full btn-sm"><?= svgIcon('check', 15) ?> حفظ الباركود</button>
          </form>
        <?php else: ?>
          <div style="display:flex;gap:6px;margin-bottom:8px">
            <input type="text" class="form-input" value="<?= e($barcodeVal) ?>" readonly style="font-family:monospace">
          </div>
        <?php endif; ?>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px">
          <button class="btn btn-secondary btn-sm" onclick="printBarcode()">🖨️ طباعة</button>
          <button class="btn btn-secondary btn-sm" onclick="dlBarcode()">⬇️ تحميل</button>
        </div>
      </div>
    </div>

    <!-- Details -->
    <div class="card">
      <div class="card-header"><span class="card-title">تفاصيل</span></div>
      <div class="card-body">
        <?php foreach([
          ['التصنيف',  $product['collection_name']??'—'],
          ['الماركة',  $product['brand_name']??'—'],
          ['الوزن',    ($product['weight']??null) ? $product['weight'].' كجم' : '—'],
          ['الضمان',   $product['warranty_period']??'—'],
          ['الباركود', $product['barcode']??'—'],
          ['التاريخ',  formatDate($product['created_at'])],
        ] as [$k,$v]): ?>
          <div style="display:flex;justify-content:space-between;align-items:center;padding:7px 0;border-bottom:1px solid var(--bd);font-size:12px">
            <span style="color:var(--t3)"><?= $k ?></span>
            <span style="font-weight:500;font-family:<?= $k==='الباركود'?'monospace':'inherit' ?>"><?= e($v) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

  </div>
</div>

<!-- Inline adjust modal (reuse warehouse modal) -->
<div class="modal-overlay" id="showAdjustModal">
  <div class="modal">
    <div class="modal-head">
      <h3>تعديل المخزون</h3>
      <button class="btn btn-ghost btn-icon" onclick="this.closest('.modal-overlay').classList.remove('open')" style="font-size:18px">×</button>
    </div>
    <input type="hidden" id="showAdjPid" value="<?= $product['id'] ?>">
    <div class="form-group" style="margin-bottom:12px">
      <label class="form-label">نوع التعديل</label>
      <select id="showAdjType" class="form-select">
        <option value="in">➕ إضافة (وارد)</option>
        <option value="out">➖ خصم (صادر)</option>
        <option value="adjustment">🔄 ضبط الكمية مباشرة</option>
      </select>
    </div>
    <div class="form-group" style="margin-bottom:12px">
      <label class="form-label">الكمية</label>
      <input type="number" id="showAdjQty" class="form-input" min="0" placeholder="0">
    </div>
    <div class="form-group" style="margin-bottom:16px">
      <label class="form-label">السبب</label>
      <select id="showAdjReason" class="form-select">
        <option value="شراء بضاعة">شراء بضاعة</option>
        <option value="مرتجع عميل">مرتجع عميل</option>
        <option value="تلف/فقدان">تلف/فقدان</option>
        <option value="جرد">جرد</option>
        <option value="تصحيح خطأ">تصحيح خطأ</option>
      </select>
    </div>
    <div class="modal-actions">
      <button class="btn btn-secondary" onclick="document.getElementById('showAdjustModal').classList.remove('open')">إلغاء</button>
      <button class="btn btn-primary" onclick="submitShowAdj()"><?= svgIcon('check', 15) ?> تأكيد التعديل</button>
    </div>
  </div>
</div>

<?php
$csrf = Session::csrf();
$adjUrl = adminUrl('warehouse/adjust');
$extraHead = '<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>';
$extraScript = "
var _bcVal = '".addslashes($barcodeVal)."';

function renderBarcode(val){
  try {
    JsBarcode('#bcSvg', val||_bcVal, {
      format:'CODE128', width:2, height:55, displayValue:false,
      lineColor: document.documentElement.dataset.theme==='light'?'#111':'#eee',
      background:'transparent'
    });
  } catch(e){ document.getElementById('bcSvg').innerHTML='<text style=\"font-size:11px;fill:var(--t3)\">خطأ في الباركود</text>'; }
}

window.addEventListener('DOMContentLoaded', function(){ renderBarcode(_bcVal); });

function updateBarcode(v){
  document.getElementById('barcodeInput').value=v;
  if(v.length>=4) renderBarcode(v);
}

function genBarcode(){
  var b='NT-'.concat(Math.random().toString(36).substr(2,8).toUpperCase());
  document.getElementById('barcodeEdit').value=b;
  updateBarcode(b);
}

function printBarcode(){
  var lc=document.documentElement.dataset.theme==='light'?'#111':'#000';
  var svg=document.getElementById('bcSvg').cloneNode(true);
  var w=window.open('','_blank','width=400,height=300');
  w.document.write('<html><body style=\"text-align:center;padding:30px;background:#fff;font-family:monospace\">'
    +new XMLSerializer().serializeToString(svg)
    +'<br><b style=\"font-size:15px;letter-spacing:2px\">'+'".addslashes($barcodeVal)."'+'</b>'
    +'<br><small style=\"font-size:11px;color:#888\">'+'".e($product['name'])."'+'</small></body></html>');
  w.document.close(); w.print(); w.close();
}

function dlBarcode(){
  var data=new XMLSerializer().serializeToString(document.getElementById('bcSvg'));
  var blob=new Blob([data],{type:'image/svg+xml'});
  var a=document.createElement('a');
  a.href=URL.createObjectURL(blob);
  a.download='barcode_".$barcodeVal.".svg';
  a.click();
}

function openAdjustFromShow(pid, name){
  document.getElementById('showAdjustModal').classList.add('open');
}

async function submitShowAdj(){
  var type=document.getElementById('showAdjType').value;
  var qty=document.getElementById('showAdjQty').value;
  var reason=document.getElementById('showAdjReason').value;
  if(!qty){ alert('أدخل الكمية'); return; }
  var fd=new FormData();
  fd.append('product_id','".addslashes($product['id'])."');
  fd.append('type',type);
  fd.append('qty',qty);
  fd.append('reason',reason);
  fd.append('_csrf','".addslashes($csrf)."');
  var r=await fetch('".addslashes($adjUrl)."',{method:'POST',body:fd});
  var d=await r.json();
  if(d.ok){
    document.getElementById('showAdjustModal').classList.remove('open');
    location.reload();
  } else { alert(d.msg||'خطأ'); }
}
";
$content = ob_get_clean();
require APP_PATH . '/Views/admin/layouts/app.php';
