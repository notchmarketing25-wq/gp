<?php
$isEdit     = isset($product);
$pageTitle  = $isEdit ? 'تعديل المنتج' : 'إضافة منتج جديد';
$breadcrumb = [['label' => 'المنتجات', 'url' => adminUrl('products')], ['label' => $pageTitle]];
$v          = $product ?? [];

$extraScript = <<<'JSEOF'
function previewThumb(input){
  if(input.files&&input.files[0]){
    const reader=new FileReader();
    reader.onload=e=>{
      const prev=document.getElementById('thumbPreview');
      prev.innerHTML='<img src=\'' + e.target.result + '\' style=\'width:100%;height:100%;object-fit:cover\'>';
    };
    reader.readAsDataURL(input.files[0]);
  }
}
function setThumbFromMedia(url){
  document.getElementById('thumbUrl').value=url;
  const prev=document.getElementById('thumbPreview');
  prev.innerHTML='<img src=\'' + url + '\' style=\'width:100%;height:100%;object-fit:cover\'>';
}
function addExtraFromMedia(url,name){
  const div=document.createElement('div');
  div.style.cssText='position:relative;width:64px;height:64px;border-radius:6px;overflow:hidden;border:1px solid var(--bd)';
  div.innerHTML='<img src=\'' + url + '\' style=\'width:100%;height:100%;object-fit:cover\'>'
    + '<input type=\'hidden\' name=\'media_images[]\' value=\'' + url + '\'>'
    + '<button type=\'button\' onclick=\'this.parentElement.remove()\' style=\'position:absolute;top:-4px;left:-4px;width:18px;height:18px;background:var(--red);border:none;border-radius:50%;color:#fff;font-size:11px;cursor:pointer\'>×</button>';
  document.getElementById('extraFromMedia').appendChild(div);
}
document.getElementById('nameInput')?.addEventListener('input', function() {
  const slugEl = document.getElementById('slugInput');
  if (!slugEl.dataset.manual) slugEl.value = mkSlug(this.value);
});
document.getElementById('slugInput')?.addEventListener('input', function() {
  this.dataset.manual = '1';
});
function addTierRow(){
  const wrap = document.getElementById('tiersWrap');
  const row = document.createElement('div');
  row.className = 'tier-row';
  row.style.cssText = 'display:grid;grid-template-columns:1fr 1fr auto;gap:8px;align-items:center';
  row.innerHTML = '<input type=\'number\' name=\'tier_qty[]\' class=\'form-input\' placeholder=\'الكمية من\' min=\'1\'>'
    + '<input type=\'number\' name=\'tier_price[]\' class=\'form-input\' placeholder=\'السعر للقطعة\' step=\'0.01\' min=\'0\'>'
    + '<button type=\'button\' class=\'btn btn-danger btn-sm\' onclick=\'this.closest(".tier-row").remove()\'>&#10005;</button>';
  wrap.appendChild(row);
}
JSEOF;


ob_start(); ?>

<div class="page-header">
  <div class="page-header-left">
    <h1><?= $pageTitle ?></h1>
    <?php if ($isEdit): ?>
      <p>آخر تحديث: <?= formatDateTime($product['updated_at']) ?></p>
    <?php endif; ?>
  </div>
  <div class="page-header-actions">
    <a href="<?= adminUrl('products') ?>" class="btn btn-secondary">← رجوع</a>
    <?php if ($isEdit): ?>
      <a href="<?= url('products/' . $product['slug']) ?>" target="_blank" class="btn btn-secondary">عرض في المتجر</a>
    <?php endif; ?>
  </div>
</div>

<form method="POST" enctype="multipart/form-data"
  action="<?= $isEdit ? adminUrl('products/' . $product['id'] . '/edit') : adminUrl('products/create') ?>">
  <?= csrf_field() ?>

  <div style="display:grid;grid-template-columns:1fr 340px;gap:20px;align-items:start">

    <!-- Right: Main info -->
    <div style="display:flex;flex-direction:column;gap:18px">

      <!-- Basic Info -->
      <div class="card">
        <div class="card-header"><span class="card-title">معلومات المنتج</span></div>
        <div class="card-body">
          <div class="form-grid" style="gap:16px">
            <div class="form-group">
              <label class="form-label">اسم المنتج <span>*</span></label>
              <input type="text" id="nameInput" name="name" class="form-input" value="<?= e($v['name'] ?? '') ?>" required>
            </div>
            <div class="form-group">
              <label class="form-label">Slug (رابط المنتج)</label>
              <input type="text" id="slugInput" name="slug" class="form-input" value="<?= e($v['slug'] ?? '') ?>" placeholder="auto-generated">
              <span class="form-hint">يُستخدم في الرابط: <?= url('products/') ?><strong id="slugPreview"><?= e($v['slug'] ?? '') ?></strong></span>
            </div>
            <div class="form-group">
              <label class="form-label">وصف مختصر</label>
              <textarea name="short_desc" class="form-textarea" rows="2" style="min-height:70px"><?= e($v['short_desc'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
              <label class="form-label">الوصف الكامل</label>
              <textarea name="description" class="form-textarea" rows="8"><?= e($v['description'] ?? '') ?></textarea>
            </div>
          </div>
        </div>
      </div>

      <!-- Images -->
      <div class="card">
        <div class="card-header"><span class="card-title">الصور</span></div>
        <div class="card-body">
          <!-- Existing images -->
          <?php if (!empty($product['images'])): ?>
          <div id="existingImages" style="display:flex;flex-wrap:wrap;gap:10px;margin-bottom:16px">
            <?php foreach ($product['images'] as $img): ?>
            <div style="position:relative;width:90px;height:90px" id="img-<?= $img['id'] ?>">
              <img src="<?= uploadUrl($img['image']) ?>" style="width:90px;height:90px;object-fit:cover;border-radius:8px;border:1px solid var(--bd)">
              <button type="button"
                onclick="deleteImage(<?= $img['id'] ?>, <?= $product['id'] ?>)"
                style="position:absolute;top:-6px;left:-6px;width:20px;height:20px;background:var(--red);border:none;border-radius:50%;color:#fff;font-size:12px;cursor:pointer;display:flex;align-items:center;justify-content:center">×</button>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>

          <!-- Thumbnail -->
          <div class="form-group" style="margin-bottom:14px">
            <label class="form-label">الصورة الرئيسية</label>
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px">
              <div id="thumbPreview" style="width:64px;height:64px;border-radius:8px;overflow:hidden;border:1px solid var(--bd);background:var(--bg3);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                <?php if (!empty($product['thumbnail'])): ?>
                  <img src="<?= uploadUrl($product['thumbnail']) ?>" style="width:100%;height:100%;object-fit:cover" id="thumbImg">
                <?php else: ?><span style="font-size:24px">🖼️</span><?php endif; ?>
              </div>
              <div style="display:flex;flex-direction:column;gap:6px;flex:1">
                <label class="btn btn-secondary btn-sm" style="cursor:pointer;justify-content:center">
                  📤 رفع صورة
                  <input type="file" name="thumbnail" class="form-input" accept="image/*" style="display:none" onchange="previewThumb(this)">
                </label>
                <button type="button" class="btn btn-secondary btn-sm" onclick="openMedia(function(f){setThumbFromMedia(f.url);})">
                  🖼️ من مكتبة الصور
                </button>
              </div>
            </div>
            <input type="hidden" name="thumbnail_url" id="thumbUrl">
          </div>

          <!-- Extra images -->
          <div class="form-group">
            <label class="form-label">صور إضافية</label>
            <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:8px">
              <label class="btn btn-secondary btn-sm" style="cursor:pointer">
                📤 رفع صور
                <input type="file" name="images[]" class="form-input" accept="image/*" multiple style="display:none">
              </label>
              <button type="button" class="btn btn-secondary btn-sm" onclick="openMedia(function(f){addExtraFromMedia(f.url,f.name);})">
                🖼️ من المكتبة
              </button>
            </div>
            <span class="form-hint">يمكنك رفع عدة صور مرة واحدة</span>
            <div id="extraFromMedia" style="display:flex;gap:6px;flex-wrap:wrap;margin-top:8px"></div>
          </div>
        </div>
      </div>

      <!-- Variants (color, cable type, etc.) -->
      <div class="card">
        <div class="card-header">
          <span class="card-title"><?= svgIcon('tag', 15) ?> المتغيرات <?= $isEdit ? '('.count($product['variants']??[]).')' : '' ?></span>
          <?php if ($isEdit): ?><button type="button" class="btn btn-primary btn-sm" onclick="openAddVariantInline()">+ إضافة متغير</button><?php endif; ?>
        </div>
        <div class="card-body">
          <?php if (!$isEdit): ?>
            <p style="font-size:12.5px;color:var(--t3);text-align:center;padding:10px 0">احفظ المنتج أولاً، وبعدها هتقدر تضيف متغيرات زي اللون أو النوع من نفس صفحة التعديل</p>
          <?php elseif (empty($product['variants'])): ?>
            <p style="font-size:12.5px;color:var(--t3);text-align:center;padding:10px 0">لا توجد متغيرات — دوس "إضافة متغير" لو المنتج عنده اختيارات زي اللون أو النوع</p>
          <?php else: ?>
            <div class="table-wrap">
              <table>
                <thead><tr><th>الصورة</th><th>المتغير</th><th>SKU</th><th>السعر</th><th>المخزون</th><th></th></tr></thead>
                <tbody>
                  <?php foreach ($product['variants'] as $v): $vs=(int)($v['stock']??0); ?>
                    <tr>
                      <td><?php if($v['image']): ?><img src="<?= uploadUrl($v['image']) ?>" style="width:32px;height:32px;object-fit:cover;border-radius:6px"><?php else: ?><span class="td-dim">—</span><?php endif; ?></td>
                      <td style="font-weight:600"><?= e($v['title']) ?></td>
                      <td class="td-mono td-dim"><?= e($v['sku']??'—') ?></td>
                      <td><?= money($v['price']) ?></td>
                      <td><span style="font-weight:700;color:<?=$vs===0?'var(--red)':($vs<=5?'var(--yellow)':'var(--green)') ?>"><?=$vs===0?'نفذ':$vs?></span></td>
                      <td>
                        <div style="display:flex;gap:5px;justify-content:flex-end">
                          <button type="button" class="btn btn-secondary btn-sm" onclick='openEditVariantInline(<?= htmlspecialchars(json_encode($v),ENT_QUOTES) ?>)'>تعديل</button>
                          <button type="button" class="btn btn-danger btn-sm" onclick="deleteVariantInline(<?= (int)$v['id'] ?>)">حذف</button>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <?php /* Variant add/edit modal + delete form moved below, outside the main <form>,
               since nested <form> elements are invalid HTML — the variant modal's own
               "stock"/"price"/etc. fields were colliding with the main product form's
               identically-named fields, and the browser was silently letting whichever
               field came last in the DOM win, which is what caused stock to save wrong. */ ?>

      <!-- SEO -->
      <div class="card">
        <div class="card-header"><span class="card-title">SEO</span></div>
        <div class="card-body">
          <div class="form-grid" style="gap:14px">
            <div class="form-group">
              <label class="form-label">عنوان الصفحة (Meta Title)</label>
              <input type="text" name="meta_title" class="form-input" value="<?= e($v['meta_title'] ?? '') ?>">
            </div>
            <div class="form-group">
              <label class="form-label">وصف الصفحة (Meta Description)</label>
              <textarea name="meta_desc" class="form-textarea" rows="2" style="min-height:70px"><?= e($v['meta_desc'] ?? '') ?></textarea>
            </div>
          </div>
        </div>
      </div>

      <!-- Sales channel -->
      <div class="card">
        <div class="card-header"><span class="card-title">🎯 قناة البيع</span></div>
        <div class="card-body">
          <select name="channel" class="form-select">
            <option value="both" <?= (($v['channel']??'both')==='both') ? 'selected' : '' ?>>🛍️🏬 الاثنين — يظهر في المتجر العادي وفي Business</option>
            <option value="retail_only" <?= (($v['channel']??'')==='retail_only') ? 'selected' : '' ?>>🛍️ قطاعي فقط — يظهر في المتجر العادي بس</option>
            <option value="wholesale_only" <?= (($v['channel']??'')==='wholesale_only') ? 'selected' : '' ?>>🏬 جملة فقط — يظهر في Business/الجملة بس، مخفي من المتجر العادي</option>
          </select>
          <span class="form-hint">"جملة فقط" بتخفي المنتج تماماً من الصفحة الرئيسية والبحث والتصنيفات في المتجر العادي</span>
        </div>
      </div>

      <!-- Wholesale pricing -->
      <div class="card">
        <div class="card-header">
          <span class="card-title"><?= svgIcon('package', 15) ?> أسعار الجملة</span>
          <div class="toggle-wrap">
            <label class="toggle"><input type="checkbox" name="wholesale_enabled" id="wsToggle" value="1" <?= !empty($v['wholesale_enabled']) ? 'checked' : '' ?> onchange="document.getElementById('wsPanel').style.display=this.checked?'block':'none'"><span class="toggle-slider"></span></label>
            <span style="font-size:12px;color:var(--t2)">تفعيل</span>
          </div>
        </div>
        <div class="card-body" id="wsPanel" style="display:<?= !empty($v['wholesale_enabled']) ? 'block' : 'none' ?>">
          <div style="background:var(--acb2);border:1px solid rgba(var(--acr),.25);border-radius:10px;padding:12px 14px;margin-bottom:16px;font-size:12px;color:var(--t2);line-height:1.7">
            <b style="color:var(--t1)">إزاي السعر بيتحسب؟</b>
            لو حددت شرائح أسعار هنا (بالكمية)، هي اللي هتتطبق لكل عملاء الجملة، **بغض النظر عن شريحة العميل الخاصة به** (أساسية/فضية/ذهبية).
            لو سبت القسم ده فاضي، السعر هيتحسب تلقائي من خصم شريحة العميل نفسه (<a href="/<?= ADMIN_PREFIX ?>/business" style="color:var(--ac);font-weight:600">راجع شرائح العملاء هنا</a>) — يعني نفس المنتج ممكن يتباع بسعر مختلف شوية لكل عميل حسب شريحته.
          </div>
          <div class="form-group" style="margin-bottom:14px">
            <label class="form-label">أقل كمية لتفعيل سعر الجملة</label>
            <input type="number" name="wholesale_min_qty" class="form-input" value="<?= e($v['wholesale_min_qty'] ?? 10) ?>" min="1" style="max-width:160px">
            <span class="form-hint">تحت هذه الكمية يُستخدم السعر العادي</span>
          </div>

          <label class="form-label" style="margin-bottom:8px;display:block">شرائح الأسعار حسب الكمية (اختياري — أولوية على خصم شريحة العميل)</label>
          <div id="tiersWrap" style="display:flex;flex-direction:column;gap:8px;margin-bottom:10px">
            <?php
            $tiers = $product['price_tiers'] ?? [];
            if (empty($tiers)) $tiers = [['min_qty'=>10,'price'=>''],['min_qty'=>50,'price'=>'']];
            foreach ($tiers as $t): ?>
              <div class="tier-row" style="display:grid;grid-template-columns:1fr 1fr auto;gap:8px;align-items:center">
                <input type="number" name="tier_qty[]" class="form-input" placeholder="الكمية من" value="<?= e($t['min_qty']) ?>" min="1">
                <input type="number" name="tier_price[]" class="form-input" placeholder="السعر للقطعة" value="<?= e($t['price']) ?>" step="0.01" min="0">
                <button type="button" class="btn btn-danger btn-sm" onclick="this.closest('.tier-row').remove()">&#10005;</button>
              </div>
            <?php endforeach; ?>
          </div>
          <button type="button" class="btn btn-secondary btn-sm" onclick="addTierRow()">+ إضافة شريحة سعر</button>
          <span class="form-hint" style="display:block;margin-top:8px">مثال: 10 قطع = 90 ج.م، 50 قطعة = 75 ج.م، 100 قطعة = 65 ج.م</span>
        </div>
      </div>

    </div>

    <!-- Left: Sidebar panels -->
    <div style="display:flex;flex-direction:column;gap:16px">

      <!-- Status & Visibility -->
      <div class="card">
        <div class="card-header"><span class="card-title">النشر</span></div>
        <div class="card-body" style="display:flex;flex-direction:column;gap:14px">
          <div class="form-group">
            <label class="form-label">الحالة</label>
            <select name="status" class="form-select">
              <option value="active"   <?= ($v['status']??'draft')==='active'  ?'selected':'' ?>>✅ نشط</option>
              <option value="draft"    <?= ($v['status']??'draft')==='draft'   ?'selected':'' ?>>📝 مسودة</option>
              <option value="archived" <?= ($v['status']??'')==='archived'     ?'selected':'' ?>>📦 مؤرشف</option>
            </select>
          </div>
          <div class="toggle-wrap">
            <label class="toggle">
              <input type="checkbox" name="is_featured" value="1" <?= !empty($v['is_featured']) ? 'checked' : '' ?>>
              <span class="toggle-slider"></span>
            </label>
            <span style="font-size:13px">منتج مميز (Featured)</span>
          </div>
          <div class="form-group">
            <label class="form-label">ترتيب العرض</label>
            <input type="number" name="sort_order" class="form-input" value="<?= e($v['sort_order'] ?? 0) ?>" min="0">
          </div>
          <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center">
            <?= $isEdit ? '💾 حفظ التعديلات' : '+ إضافة المنتج' ?>
          </button>
        </div>
      </div>

      <!-- Pricing -->
      <div class="card">
        <div class="card-header"><span class="card-title">الأسعار</span></div>
        <div class="card-body" style="display:flex;flex-direction:column;gap:12px">
          <div class="form-group">
            <label class="form-label">السعر (<?= APP_CURRENCY_SYMBOL ?>) <span>*</span></label>
            <input type="number" name="price" class="form-input" value="<?= e($v['price'] ?? '') ?>" step="0.01" min="0" required>
          </div>
          <div class="form-group">
            <label class="form-label">السعر قبل الخصم</label>
            <input type="number" name="compare_price" class="form-input" value="<?= e($v['compare_price'] ?? '') ?>" step="0.01" min="0">
            <span class="form-hint">أعلى من السعر الحالي لإظهار خصم</span>
          </div>
          <div class="form-group">
            <label class="form-label">سعر التكلفة</label>
            <input type="number" name="cost_price" class="form-input" value="<?= e($v['cost_price'] ?? '') ?>" step="0.01" min="0">
            <span class="form-hint">لحساب الربح (لا يُعرض للعميل)</span>
          </div>
        </div>
      </div>

      <!-- Inventory -->
      <div class="card">
        <div class="card-header"><span class="card-title">المخزون</span></div>
        <div class="card-body" style="display:flex;flex-direction:column;gap:12px">
          <?php $hasVariants = $isEdit && !empty($product['variants']); ?>
          <?php if ($hasVariants): ?>
            <div style="background:var(--acb2);border:1px solid rgba(var(--acr),.25);border-radius:10px;padding:10px 14px;font-size:12px;color:var(--t2);line-height:1.7">
              المنتج ده عنده متغيرات — المخزون بقى بيتحسب من مخزون كل متغير لوحده، مش من الرقم هنا. عدّل مخزون كل اختيار من قسم "المتغيرات" فوق.
            </div>
          <?php endif; ?>
          <div class="toggle-wrap">
            <label class="toggle">
              <input type="checkbox" name="track_stock" value="1" <?= ($v['track_stock']??1) ? 'checked' : '' ?> id="trackStock">
              <span class="toggle-slider"></span>
            </label>
            <span style="font-size:13px">تتبع المخزون</span>
          </div>
          <div class="form-group">
            <label class="form-label">الكمية <?php if ($hasVariants): ?><span style="color:var(--t3);font-weight:400">(غير مستخدمة — راجع المتغيرات)</span><?php endif; ?></label>
            <input type="number" name="stock" class="form-input" value="<?= e($v['stock'] ?? 0) ?>" min="0" <?= $hasVariants ? 'disabled' : '' ?>>
            <?php if ($hasVariants): ?><input type="hidden" name="stock" value="<?= e($v['stock'] ?? 0) ?>"><?php endif; ?>
          </div>
          <div class="form-group">
            <label class="form-label">SKU</label>
            <input type="text" name="sku" class="form-input" value="<?= e($v['sku'] ?? '') ?>" placeholder="اختياري">
          </div>
          <div class="toggle-wrap">
            <label class="toggle">
              <input type="checkbox" name="allow_backorder" value="1" <?= !empty($v['allow_backorder']) ? 'checked' : '' ?>>
              <span class="toggle-slider"></span>
            </label>
            <span style="font-size:13px">السماح بالطلب عند نفاد المخزون</span>
          </div>
          <div class="form-group">
            <label class="form-label">الوزن (كجم)</label>
            <input type="number" name="weight" class="form-input" value="<?= e($v['weight'] ?? '') ?>" step="0.001" min="0">
          </div>
        </div>
      </div>

      <!-- Classification -->
      <div class="card">
        <div class="card-header"><span class="card-title">التصنيف</span></div>
        <div class="card-body" style="display:flex;flex-direction:column;gap:12px">
          <div class="form-group">
            <label class="form-label">التصنيف</label>
            <select name="collection_id" class="form-select">
              <option value="">بدون تصنيف</option>
              <?php foreach ($collections as $c): ?>
                <option value="<?= $c['id'] ?>" <?= ($v['collection_id']??'')==$c['id'] ?'selected':'' ?>><?= e($c['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">الماركة</label>
            <select name="brand_id" class="form-select">
              <option value="">بدون ماركة</option>
              <?php foreach ($brands as $b): ?>
                <option value="<?= $b['id'] ?>" <?= ($v['brand_id']??'')==$b['id'] ?'selected':'' ?>><?= e($b['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>

    </div><!-- /sidebar -->
  </div>
</form>

<?php if ($isEdit): ?>
<!-- Variant add/edit modal — an independent <form>, kept outside the main product <form>
     above. It used to be nested inside it, which is invalid HTML: the browser was merging
     its "stock"/"price"/etc. fields into the outer form, and whichever field happened to
     come last in the DOM silently won when the product form was submitted — that's what
     was corrupting the saved stock value. -->
<div class="modal-overlay" id="variantModalInline">
  <div class="modal">
    <div class="modal-head"><h3 id="vmiTitle">+ إضافة متغير</h3><button type="button" class="btn btn-ghost btn-icon" onclick="document.getElementById('variantModalInline').classList.remove('open')" style="font-size:18px">×</button></div>
    <form method="POST" id="variantFormInline" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <div class="form-group" style="margin-bottom:12px">
        <label class="form-label">اسم المتغير <span class="req">*</span></label>
        <input type="text" name="title" id="vmiTitleInput" class="form-input" required placeholder="مثال: أحمر - USB-C">
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:12px">
        <div class="form-group" style="margin:0">
          <label class="form-label">السعر <span class="req">*</span></label>
          <input type="number" name="price" id="vmiPrice" class="form-input" step="0.01" required value="<?= e($product['price']) ?>">
        </div>
        <div class="form-group" style="margin:0">
          <label class="form-label">المخزون</label>
          <input type="number" name="stock" id="vmiStock" class="form-input" value="0">
        </div>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:12px">
        <div class="form-group" style="margin:0">
          <label class="form-label">SKU (اختياري)</label>
          <input type="text" name="sku" id="vmiSku" class="form-input">
        </div>
        <div class="form-group" style="margin:0">
          <label class="form-label">سعر قبل الخصم (اختياري)</label>
          <input type="number" name="compare_price" id="vmiComparePrice" class="form-input" step="0.01">
        </div>
      </div>
      <div class="form-group" style="margin-bottom:16px">
        <label class="form-label">صورة المتغير (اختياري)</label>
        <input type="file" name="image" class="form-input" accept="image/*" style="padding:8px">
      </div>
      <div class="modal-actions">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('variantModalInline').classList.remove('open')">إلغاء</button>
        <button type="submit" class="btn btn-primary">حفظ</button>
      </div>
    </form>
  </div>
</div>

<!-- Shared delete form for variant rows — one independent form, its action set by JS before
     submitting, instead of one nested <form> per row. -->
<form method="POST" id="variantDeleteForm" style="display:none">
  <?= csrf_field() ?>
</form>

<script>
function openAddVariantInline(){
  document.getElementById('vmiTitle').textContent='+ إضافة متغير';
  document.getElementById('variantFormInline').reset();
  document.getElementById('variantFormInline').action='<?= adminUrl('products/'.$product['id'].'/variants/create') ?>';
  document.getElementById('vmiPrice').value='<?= e($product['price']) ?>';
  document.getElementById('variantModalInline').classList.add('open');
}
function openEditVariantInline(v){
  document.getElementById('vmiTitle').textContent='تعديل المتغير';
  document.getElementById('variantFormInline').action='<?= adminUrl('products/'.$product['id'].'/variants/') ?>'+v.id;
  document.getElementById('vmiTitleInput').value=v.title||'';
  document.getElementById('vmiPrice').value=v.price||0;
  document.getElementById('vmiStock').value=v.stock||0;
  document.getElementById('vmiSku').value=v.sku||'';
  document.getElementById('vmiComparePrice').value=v.compare_price||'';
  document.getElementById('variantModalInline').classList.add('open');
}
function deleteVariantInline(variantId){
  if (!confirm('حذف المتغير؟')) return;
  const form = document.getElementById('variantDeleteForm');
  form.action = '<?= adminUrl('products/'.$product['id'].'/variants/') ?>' + variantId + '/delete';
  form.submit();
}
</script>
<?php endif; ?>

<script>
// Update slug preview
document.getElementById('slugInput')?.addEventListener('input', function() {
  document.getElementById('slugPreview').textContent = this.value;
});

// Delete image via AJAX
function deleteImage(imageId, productId) {
  if (!confirm('حذف هذه الصورة؟')) return;
  const form = new FormData();
  form.append('image_id', imageId);
  form.append('_csrf', '<?= csrf_token() ?>');
  fetch('<?= adminUrl('products') ?>/' + productId + '/image-delete', { method:'POST', body: form })
    .then(r => r.json())
    .then(d => { if (d.success) document.getElementById('img-' + imageId)?.remove(); });
}
</script>

<?php
$content = ob_get_clean();
require APP_PATH . '/Views/admin/layouts/app.php';
