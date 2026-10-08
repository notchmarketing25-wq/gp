<?php
$pageTitle  = 'البانرات';
$breadcrumb = [['label'=>'البانرات']];
$a = ADMIN_PREFIX;
ob_start();

function _bannerCard($b, $a) { ?>
  <div class="card" style="overflow:visible">
    <div style="aspect-ratio:<?= $b['type']==='slider'?'21/9':($b['type']==='medium'?'16/9':($b['type']==='middle'?'21/6':'1/1')) ?>;background:var(--bg3);position:relative;border-radius:var(--radius) var(--radius) 0 0;overflow:hidden">
      <?php if (($b['media_type'] ?? 'image') === 'video'): ?>
        <video src="<?= uploadUrl($b['image']) ?>" style="width:100%;height:100%;object-fit:cover" muted loop playsinline preload="metadata"></video>
        <span class="badge badge-purple" style="position:absolute;top:8px;right:8px">🎬 فيديو</span>
      <?php else: ?>
        <img src="<?= uploadUrl($b['image']) ?>" style="width:100%;height:100%;object-fit:cover" loading="lazy">
      <?php endif; ?>
      <span class="badge <?= $b['is_active']?'badge-ok':'badge-dim' ?>" style="position:absolute;top:8px;left:8px"><?= $b['is_active']?'نشط':'موقوف' ?></span>
    </div>
    <div style="padding:12px">
      <div style="font-size:13px;font-weight:600;margin-bottom:2px"><?= e($b['title']?:'بدون عنوان') ?></div>
      <div style="font-size:11px;color:var(--t3);margin-bottom:10px"><?= e($b['link']?:'—') ?></div>
      <div style="display:flex;gap:6px">
        <button class="btn btn-secondary btn-sm" style="flex:1" onclick='editBanner(<?= htmlspecialchars(json_encode($b),ENT_QUOTES) ?>)'>تعديل</button>
        <button class="btn btn-danger btn-sm" onclick="confirmDelete('<?= adminUrl('banners/'.$b['id'].'/delete') ?>','حذف البانر؟','')">حذف</button>
      </div>
    </div>
  </div>
<?php }

// Recommended pixel dimensions per banner type — shown in the upload modal
$sizeHints = [
    'slider' => '1600 × 686 بكسل (نسبة 21:9)',
    'medium' => '800 × 450 بكسل (نسبة 16:9)',
    'small'  => '400 × 400 بكسل (مربعة 1:1)',
    'middle' => '1600 × 457 بكسل (نسبة 21:6) — بانر إعلاني عريض وسط الصفحة',
];
?>

<div class="ph">
  <div class="ph-left"><h1><span style="display:inline-flex;align-items:center;gap:9px"><?= svgIcon('image', 22) ?> البانرات والسلايدر</span></h1><p>إدارة بانرات الصفحة الرئيسية</p></div>
  <div class="ph-right"><button class="btn btn-primary" onclick="openAdd('slider')">+ إضافة بانر</button></div>
</div>

<!-- Slider banners -->
<div style="margin-bottom:28px">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
    <h3 style="font-size:14px;font-weight:700;display:flex;align-items:center;gap:6px"><?= svgIcon('image', 15) ?> السلايدر الرئيسي <span style="font-weight:400;color:var(--t3);font-size:12px">(21:9 — يظهر أعلى الصفحة — صورة أو فيديو)</span></h3>
    <button class="btn btn-secondary btn-sm" onclick="openAdd('slider')">+ إضافة</button>
  </div>
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:14px">
    <?php if (empty($sliders)): ?><div class="empty" style="grid-column:1/-1"><div class="empty-icon"><?= svgIcon('image', 32) ?></div><div class="empty-title">لا توجد سلايدات</div></div>
    <?php else: foreach ($sliders as $b) _bannerCard($b,$a); endif; ?>
  </div>
</div>

<!-- Medium banners -->
<div style="margin-bottom:28px">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
    <h3 style="font-size:14px;font-weight:700;display:flex;align-items:center;gap:6px"><?= svgIcon('image', 15) ?> بانرات متوسطة <span style="font-weight:400;color:var(--t3);font-size:12px">(16:9 — صفين جنب بعض — صورة أو فيديو)</span></h3>
    <button class="btn btn-secondary btn-sm" onclick="openAdd('medium')">+ إضافة</button>
  </div>
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:14px">
    <?php if (empty($mediums)): ?><div class="empty" style="grid-column:1/-1"><div class="empty-icon"><?= svgIcon('image', 32) ?></div><div class="empty-title">لا توجد بانرات متوسطة</div></div>
    <?php else: foreach ($mediums as $b) _bannerCard($b,$a); endif; ?>
  </div>
</div>

<!-- Small banners -->
<div style="margin-bottom:28px">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
    <h3 style="font-size:14px;font-weight:700;display:flex;align-items:center;gap:6px"><?= svgIcon('grid', 15) ?> بانرات صغيرة <span style="font-weight:400;color:var(--t3);font-size:12px">(مربعة — شريط أفقي)</span></h3>
    <button class="btn btn-secondary btn-sm" onclick="openAdd('small')">+ إضافة</button>
  </div>
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:14px">
    <?php if (empty($smalls)): ?><div class="empty" style="grid-column:1/-1"><div class="empty-icon"><?= svgIcon('grid', 32) ?></div><div class="empty-title">لا توجد بانرات صغيرة</div></div>
    <?php else: foreach ($smalls as $b) _bannerCard($b,$a); endif; ?>
  </div>
</div>

<!-- Middle ad banners -->
<div>
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
    <h3 style="font-size:14px;font-weight:700;display:flex;align-items:center;gap:6px"><?= svgIcon('message', 15) ?> بانر إعلاني وسط الصفحة <span style="font-weight:400;color:var(--t3);font-size:12px">(عريض — يظهر بين أقسام الصفحة الرئيسية)</span></h3>
    <button class="btn btn-secondary btn-sm" onclick="openAdd('middle')">+ إضافة</button>
  </div>
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:14px">
    <?php if (empty($middles)): ?><div class="empty" style="grid-column:1/-1"><div class="empty-icon"><?= svgIcon('message', 32) ?></div><div class="empty-title">لا توجد بانرات إعلانية</div><div class="empty-desc">لو أضفت أكتر من واحد، هيظهر واحد عشوائي في كل زيارة</div></div>
    <?php else: foreach ($middles as $b) _bannerCard($b,$a); endif; ?>
  </div>
</div>

<!-- Add/Edit modal -->
<div class="modal-overlay" id="bannerModal">
  <div class="modal modal-lg">
    <div class="modal-head">
      <h3 id="bmTitle">+ إضافة بانر</h3>
      <button class="btn btn-ghost btn-icon" onclick="document.getElementById('bannerModal').classList.remove('open')" style="font-size:18px">×</button>
    </div>
    <form method="POST" id="bannerForm" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <input type="hidden" name="type" id="bmType" value="slider">
      <div id="bmSizeHint" style="display:flex;align-items:center;gap:8px;background:var(--acb2);border:1px solid rgba(var(--acr),.25);border-radius:10px;padding:10px 14px;margin-bottom:14px;font-size:12px;color:var(--ac);font-weight:600">
        <?= svgIcon('image', 15) ?> المقاس المفضّل: <span id="bmSizeHintText">1600 × 686 بكسل</span>
      </div>
      <div id="bmMediaTypeWrap" style="display:none;margin-bottom:16px">
        <label class="form-label" style="margin-bottom:6px;display:block">نوع المحتوى</label>
        <div style="display:flex;gap:8px">
          <label style="flex:1;display:flex;align-items:center;justify-content:center;gap:8px;background:var(--bg3);border:2px solid var(--bd);border-radius:10px;padding:11px;cursor:pointer;transition:border-color .15s" id="bmMediaImageLabel">
            <input type="radio" name="media_type" value="image" id="bmMediaImage" checked onchange="bmToggleMediaType()" style="accent-color:var(--ac)"> <?= svgIcon('image', 15) ?> صورة
          </label>
          <label style="flex:1;display:flex;align-items:center;justify-content:center;gap:8px;background:var(--bg3);border:2px solid var(--bd);border-radius:10px;padding:11px;cursor:pointer;transition:border-color .15s" id="bmMediaVideoLabel">
            <input type="radio" name="media_type" value="video" id="bmMediaVideo" onchange="bmToggleMediaType()" style="accent-color:var(--ac)"> 🎬 فيديو
          </label>
        </div>
      </div>
      <div style="display:grid;grid-template-columns:200px 1fr;gap:20px">
        <div>
          <label class="form-label" style="margin-bottom:6px;display:block">المحتوى</label>
          <div id="bmPreview" class="bm-dropzone" ondragover="event.preventDefault();this.classList.add('drag')" ondragleave="this.classList.remove('drag')" ondrop="bmHandleDrop(event)" style="aspect-ratio:16/9;background:var(--bg3);border:2px dashed var(--bd);border-radius:14px;display:flex;align-items:center;justify-content:center;overflow:hidden;margin-bottom:10px;position:relative;flex-direction:column;gap:6px;cursor:pointer;transition:border-color .15s,background .15s" onclick="document.getElementById('bmFileInput').click()">
            <span style="font-size:32px">🖼️</span>
            <span style="font-size:11px;color:var(--t3)">اسحب صورة هنا أو اضغط للرفع</span>
          </div>
          <input type="hidden" name="image_url" id="bmImageUrl">
          <label class="btn btn-secondary btn-sm btn-full" style="cursor:pointer;margin-bottom:6px" id="bmUploadLabel">
            📤 رفع
            <input type="file" name="image" id="bmFileInput" accept="image/*" style="display:none" onchange="previewBanner(this)">
          </label>
          <input type="file" name="video" id="bmVideoInput" accept="video/mp4,video/webm,video/quicktime" style="display:none" onchange="previewBannerVideo(this)">
          <button type="button" class="btn btn-secondary btn-sm btn-full" id="bmLibraryBtn" onclick="openMedia(function(f){document.getElementById('bmImageUrl').value=f.url;document.getElementById('bmPreview').innerHTML='<img src=\"'+f.url+'\" style=\"width:100%;height:100%;object-fit:cover\">';})"><?= svgIcon('image', 15) ?> من المكتبة</button>
          <p style="font-size:10.5px;color:var(--t3);margin-top:8px;line-height:1.6">الفيديو: MP4 أو WebM، أقل من 40 ميجا — خليه قصير (10-20 ثانية) عشان سرعة التحميل</p>
        </div>
        <div class="form-grid" style="background:var(--bg3);border-radius:14px;padding:16px;align-content:start">
          <div class="form-group"><label class="form-label">العنوان</label><input type="text" name="title" id="bmTitleInput" class="form-input" placeholder="اختياري"></div>
          <p style="font-size:10.5px;color:var(--t3);margin:-8px 0 4px;line-height:1.6">لو سبته فاضي، الصورة أو الفيديو هيظهر لوحده من غير أي نص فوقه</p>
          <div class="form-group"><label class="form-label">العنوان الفرعي</label><input type="text" name="subtitle" id="bmSubtitle" class="form-input" placeholder="اختياري"></div>
          <div class="form-row-2 form-grid" style="margin:0">
            <div class="form-group"><label class="form-label">نص الزر</label><input type="text" name="btn_text" id="bmBtnText" class="form-input" placeholder="تسوق الآن"></div>
            <div class="form-group"><label class="form-label">الرابط</label><input type="text" name="link" id="bmLink" class="form-input" placeholder="products أو رابط كامل"></div>
          </div>
          <div class="form-row-2 form-grid" style="margin:0">
            <div class="form-group"><label class="form-label">الترتيب</label><input type="number" name="sort_order" id="bmSort" class="form-input" value="0"></div>
            <div class="form-group">
              <label class="form-label">الحالة</label>
              <div class="toggle-wrap" style="margin-top:8px"><label class="toggle"><input type="checkbox" name="is_active" id="bmActive" value="1" checked><span class="toggle-slider"></span></label><span style="font-size:13px">نشط</span></div>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('bannerModal').classList.remove('open')">إلغاء</button>
        <button type="submit" class="btn btn-primary"><?= svgIcon('check', 15) ?> حفظ</button>
      </div>
    </form>
  </div>
</div>

<?php
$extraScript = "
const bmHints = {
  slider: ['1600 × 686 بكسل (21:9)', '21/9'],
  medium: ['800 × 450 بكسل (16:9)', '16/9'],
  small:  ['400 × 400 بكسل (1:1)', '1/1'],
  middle: ['1600 × 457 بكسل (21:6)', '21/6']
};
function bmApplyHint(type){
  const h = bmHints[type] || bmHints.slider;
  document.getElementById('bmSizeHintText').textContent = h[0];
  document.getElementById('bmPreview').style.aspectRatio = h[1];
  document.getElementById('bmMediaTypeWrap').style.display = (type === 'slider' || type === 'medium') ? 'block' : 'none';
  if (type !== 'slider' && type !== 'medium') { document.getElementById('bmMediaImage').checked = true; bmToggleMediaType(); }
}
function bmToggleMediaType(){
  const isVideo = document.getElementById('bmMediaVideo').checked;
  document.getElementById('bmUploadLabel').style.display = isVideo ? 'none' : 'block';
  document.getElementById('bmLibraryBtn').style.display = isVideo ? 'none' : 'block';
  if (isVideo) {
    document.getElementById('bmPreview').innerHTML = '<span style=\"font-size:32px\">🎬</span><span style=\"font-size:11px;color:var(--t3)\">اضغط لاختيار فيديو</span>';
    document.getElementById('bmPreview').onclick = function(){ document.getElementById('bmVideoInput').click(); };
  } else {
    document.getElementById('bmPreview').innerHTML = '<span style=\"font-size:32px\">🖼️</span><span style=\"font-size:11px;color:var(--t3)\">اسحب صورة هنا أو اضغط للرفع</span>';
    document.getElementById('bmPreview').onclick = function(){ document.getElementById('bmFileInput').click(); };
  }
}
function previewBannerVideo(input){
  if(input.files&&input.files[0]){
    const url = URL.createObjectURL(input.files[0]);
    document.getElementById('bmPreview').innerHTML='<video src=\"'+url+'\" style=\"width:100%;height:100%;object-fit:cover\" muted autoplay loop playsinline></video>';
  }
}
function openAdd(type){
  document.getElementById('bmTitle').textContent='+ إضافة بانر';
  document.getElementById('bannerForm').reset();
  document.getElementById('bannerForm').action='/$a/banners/create';
  document.getElementById('bmType').value=type;
  document.getElementById('bmImageUrl').value='';
  document.getElementById('bmMediaImage').checked = true;
  document.getElementById('bmPreview').innerHTML='<span style=\"font-size:32px\">🖼️</span><span style=\"font-size:11px;color:var(--t3)\">اسحب صورة هنا أو اضغط للرفع</span>';
  bmApplyHint(type);
  document.getElementById('bannerModal').classList.add('open');
}
function editBanner(b){
  document.getElementById('bmTitle').textContent='✏️ تعديل البانر';
  document.getElementById('bannerForm').action='/$a/banners/'+b.id;
  document.getElementById('bmType').value=b.type;
  document.getElementById('bmTitleInput').value=b.title||'';
  document.getElementById('bmSubtitle').value=b.subtitle||'';
  document.getElementById('bmBtnText').value=b.btn_text||'';
  document.getElementById('bmLink').value=b.link||'';
  document.getElementById('bmSort').value=b.sort_order||0;
  document.getElementById('bmActive').checked=b.is_active==1;
  document.getElementById('bmImageUrl').value='';
  var isVideo = b.media_type === 'video';
  document.getElementById('bmMediaImage').checked = !isVideo;
  document.getElementById('bmMediaVideo').checked = isVideo;
  bmApplyHint(b.type);
  if (isVideo) {
    document.getElementById('bmPreview').innerHTML='<video src=\"".addslashes(APP_URL)."/uploads/'+b.image+'\" style=\"width:100%;height:100%;object-fit:cover\" muted autoplay loop playsinline></video>';
  } else {
    document.getElementById('bmPreview').innerHTML='<img src=\"".addslashes(APP_URL)."/uploads/'+b.image+'\" style=\"width:100%;height:100%;object-fit:cover\">';
  }
  document.getElementById('bannerModal').classList.add('open');
}
function previewBanner(input){
  if(input.files&&input.files[0]){
    showBmLoading();
    const r=new FileReader();
    r.onload=e=>{document.getElementById('bmPreview').innerHTML='<img src=\"'+e.target.result+'\" style=\"width:100%;height:100%;object-fit:cover\">';};
    r.readAsDataURL(input.files[0]);
  }
}
function showBmLoading(){
  document.getElementById('bmPreview').innerHTML='<span style=\"font-size:12px;color:var(--t2)\">⏳ جاري التحميل...</span>';
}
function bmHandleDrop(ev){
  ev.preventDefault();
  ev.currentTarget.style.borderColor='var(--bd)';
  const files = ev.dataTransfer.files;
  if (files && files[0]) {
    document.getElementById('bmFileInput').files = files;
    previewBanner(document.getElementById('bmFileInput'));
  }
}
";
$content = ob_get_clean();
require APP_PATH . '/Views/admin/layouts/app.php';
