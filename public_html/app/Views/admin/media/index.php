<?php
$pageTitle  = 'الميديا';
$breadcrumb = [['label'=>'الميديا']];
$a = ADMIN_PREFIX;
ob_start();
?>
<div class="ph">
  <div class="ph-left"><h1><span style="display:inline-flex;align-items:center;gap:9px"><?= svgIcon('image', 22) ?> الميديا</span></h1><p><?= count($files) ?> صورة</p></div>
  <div class="ph-right">
    <label class="btn btn-primary" style="cursor:pointer">
      📤 رفع صور
      <input type="file" id="uploadInput" accept="image/*,video/mp4,video/webm,video/quicktime" multiple style="display:none" onchange="uploadFiles(this)">
    </label>
  </div>
</div>

<!-- Upload progress -->
<div id="uploadProgress" style="display:none;margin-bottom:14px">
  <div style="background:var(--bg2);border:1px solid var(--bd);border-radius:var(--radius-s);padding:12px">
    <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:6px">
      <span>جاري الرفع...</span><span id="uploadPct">0%</span>
    </div>
    <div style="height:4px;background:var(--bg3);border-radius:2px">
      <div id="uploadBar" style="height:100%;background:var(--ac);border-radius:2px;width:0%;transition:width .3s"></div>
    </div>
  </div>
</div>

<!-- Filter -->
<div class="filter-bar" style="margin-bottom:16px">
  <div class="search-box">
    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
    <input type="text" class="search-input" placeholder="بحث..." oninput="filterFiles(this.value)">
  </div>
</div>

<!-- Grid -->
<div id="fileGrid" class="card" style="padding:16px">
  <?php if (empty($files)): ?>
    <div class="empty">
      <div class="empty-icon"><?= svgIcon('image', 32) ?></div>
      <div class="empty-title">لا توجد صور بعد</div>
      <div class="empty-desc">ارفع صور لاستخدامها في المنتجات والإعدادات</div>
    </div>
  <?php else: ?>
    <div class="img-grid" id="imgGrid">
      <?php foreach ($files as $f): ?>
        <div class="img-thumb" data-name="<?= e($f['name']) ?>" style="position:relative">
          <?php if (($f['type'] ?? 'image') === 'video'): ?>
            <video src="<?= e($f['url']) ?>" muted preload="metadata"></video>
            <span class="badge badge-purple" style="position:absolute;top:5px;right:5px;font-size:9px">🎬</span>
          <?php else: ?>
            <img src="<?= e($f['url']) ?>" alt="<?= e($f['name']) ?>" loading="lazy">
          <?php endif; ?>
          <div class="img-overlay" style="position:absolute;inset:0;background:rgba(0,0,0,.5);opacity:0;transition:opacity .15s;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:4px">
            <a href="<?= e($f['url']) ?>" target="_blank" class="btn btn-sm" style="background:#fff;color:#111;padding:4px 8px;font-size:11px">عرض</a>
            <button onclick="copyUrl('<?= e($f['url']) ?>')" class="btn btn-sm" style="background:var(--ac);color:#fff;padding:4px 8px;font-size:11px">نسخ الرابط</button>
            <button onclick="deleteFile('<?= e($f['name']) ?>', this.closest('.img-thumb'))" class="btn btn-sm" style="background:var(--red);color:#fff;padding:4px 8px;font-size:11px">حذف</button>
          </div>
          <div style="position:absolute;bottom:0;right:0;left:0;background:rgba(0,0,0,.6);color:#fff;font-size:9px;padding:3px 5px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= e($f['size']) ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php
$extraScript = "
document.querySelectorAll('.img-thumb').forEach(t=>{
  t.addEventListener('mouseenter',()=>t.querySelector('.img-overlay').style.opacity='1');
  t.addEventListener('mouseleave',()=>t.querySelector('.img-overlay').style.opacity='0');
});
function filterFiles(q){
  document.querySelectorAll('#imgGrid .img-thumb').forEach(t=>{
    t.style.display=t.dataset.name.toLowerCase().includes(q.toLowerCase())?'':'none';
  });
}
function copyUrl(url){
  navigator.clipboard.writeText(url).then(()=>{
    const el=document.createElement('div');
    el.style.cssText='position:fixed;bottom:20px;left:50%;transform:translateX(-50%);background:var(--ac);color:#fff;padding:8px 16px;border-radius:8px;font-size:13px;z-index:9999;animation:slideDown .2s ease';
    el.textContent='تم نسخ الرابط ✓';
    document.body.appendChild(el);
    setTimeout(()=>el.remove(),2000);
  });
}
async function deleteFile(name, el){
  if(!confirm('حذف هذه الصورة؟')) return;
  const fd=new FormData();
  fd.append('name',name);
  fd.append('_csrf','<?= csrf_token() ?>');
  await fetch('/$a/media/delete',{method:'POST',body:fd});
  el.remove();
}
async function uploadFiles(input){
  if (!input.files || !input.files.length) return;
  const fd=new FormData();
  for(const f of input.files)fd.append('images[]',f);
  fd.append('_csrf','<?= csrf_token() ?>');
  document.getElementById('uploadProgress').style.display='block';
  document.getElementById('uploadBar').style.width='0%';
  document.getElementById('uploadPct').textContent='0%';
  const xhr=new XMLHttpRequest();
  xhr.open('POST','/$a/media/upload');
  xhr.timeout = 120000; // 2 minutes — enough for a large video on a slow connection
  xhr.upload.onprogress=e=>{
    if(e.lengthComputable){
      const pct=Math.round(e.loaded/e.total*100);
      document.getElementById('uploadBar').style.width=pct+'%';
      document.getElementById('uploadPct').textContent=pct+'%';
    }
  };
  function finish(msg, isError){
    document.getElementById('uploadProgress').style.display='none';
    input.value='';
    if (isError) { alert(msg); } else { location.reload(); }
  }
  xhr.onload=()=>{
    if (xhr.status < 200 || xhr.status >= 300) {
      finish('فشل الرفع (خطأ من السيرفر: '+xhr.status+') — الملف على الأغلب أكبر من الحد المسموح به', true);
      return;
    }
    let data;
    try { data = JSON.parse(xhr.responseText); }
    catch(e) { finish('فشل الرفع — رد غير متوقع من السيرفر', true); return; }
    if (!data.ok) {
      const errMsg = (data.errors && data.errors.length) ? data.errors.join('\\n') : (data.msg || 'فشل الرفع لسبب غير معروف');
      finish(errMsg, true);
      return;
    }
    if (data.errors && data.errors.length) {
      alert('بعض الملفات فشلت:\\n' + data.errors.join('\\n'));
    }
    finish('', false);
  };
  xhr.onerror = () => finish('فشل الاتصال بالسيرفر — تأكد من اتصال الإنترنت وحاول تاني', true);
  xhr.ontimeout = () => finish('استغرق الرفع وقتاً طويلاً جداً — جرب ملف أصغر أو اتصال أسرع', true);
  xhr.send(fd);
}
";
$content = ob_get_clean();
require APP_PATH . '/Views/admin/layouts/app.php';
