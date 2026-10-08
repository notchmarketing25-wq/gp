<?php
$pageTitle  = 'مزامنة ' . $config['label'];
$breadcrumb = [['label'=>'مزامنة Supabase','url'=>adminUrl('sync/supabase')],['label'=>$config['label']]];
$a = ADMIN_PREFIX;
ob_start();
?>
<div class="ph">
  <div class="ph-left"><h1><span style="display:inline-flex;align-items:center;gap:9px"><?= svgIcon('refresh', 22) ?> مزامنة <?= e($config['label']) ?></span></h1><p>حدد جدول Supabase وطابق الأعمدة</p></div>
  <div class="ph-right"><a href="<?= adminUrl('sync/supabase') ?>" class="btn btn-secondary">← رجوع</a></div>
</div>

<?php if (empty($tables)): ?>
  <div class="card">
    <div class="card-body" style="text-align:center;padding:40px">
      <div style="font-size:40px;margin-bottom:12px">⚠️</div>
      <div style="font-weight:700;margin-bottom:8px">لسه مربوطش Supabase</div>
      <a href="<?= adminUrl('sync/supabase') ?>" class="btn btn-primary">إعداد الاتصال</a>
    </div>
  </div>
<?php else: ?>

<form method="POST" action="<?= adminUrl('sync/supabase/'.$entity.'/preview') ?>" id="mapForm">
  <?= csrf_field() ?>

  <div class="card" style="margin-bottom:16px">
    <div class="card-header"><span class="card-title">1️⃣ اختر جدول Supabase</span></div>
    <div class="card-body">
      <select name="supabase_table" id="tableSelect" class="form-select" onchange="loadColumns(this.value)" required>
        <option value="">اختر جدول...</option>
        <?php foreach ($tables as $t): ?><option value="<?= e($t) ?>"><?= e($t) ?></option><?php endforeach; ?>
      </select>
    </div>
  </div>

  <div class="card" style="margin-bottom:16px" id="mappingCard">
    <div class="card-header"><span class="card-title">2️⃣ طابق الأعمدة</span></div>
    <div class="card-body">
      <div class="form-group" style="margin-bottom:16px">
        <label class="form-label">المفتاح الفريد (لمنع التكرار) <span class="req">*</span></label>
        <select name="unique_key" class="form-select">
          <?php foreach ($config['unique_keys'] as $k): ?><option value="<?= $k ?>"><?= $k ?></option><?php endforeach; ?>
        </select>
        <span class="form-hint">لو القيمة دي موجودة بالفعل في النظام، هيتم تخطي الصف تلقائياً</span>
      </div>
      <div id="mappingRows" style="display:flex;flex-direction:column;gap:10px">
        <div style="text-align:center;color:var(--t3);padding:20px" id="mappingPlaceholder">اختر جدول Supabase الأول عشان نجيب أسماء الأعمدة</div>
      </div>
    </div>
  </div>

  <button type="submit" class="btn btn-primary" id="previewBtn" disabled><?= svgIcon('search', 15) ?> معاينة البيانات</button>
</form>

<?php endif; ?>

<?php
$fields = json_encode($config['fields']);
$extraScript = "
const localFields = {$fields};
async function loadColumns(table){
  document.getElementById('mappingRows').innerHTML = '<div style=\"text-align:center;color:var(--t3);padding:20px\">⏳ جاري التحميل...</div>';
  document.getElementById('previewBtn').disabled = true;
  if (!table) return;
  try {
    const r = await fetch('/<?= $a ?>/sync/supabase/columns/' + Date.now() + Math.random().toString(36).slice(2) + '?table=' + encodeURIComponent(table), { cache: 'no-store' });
    const d = await r.json();
    if (!d.ok) { document.getElementById('mappingRows').innerHTML = '<div style=\"color:var(--red);text-align:center;padding:20px\">' + d.error + '</div>'; return; }
    renderMapping(d.columns);
    document.getElementById('previewBtn').disabled = false;
  } catch(e) {
    document.getElementById('mappingRows').innerHTML = '<div style=\"color:var(--red);text-align:center;padding:20px\">فشل الاتصال</div>';
  }
}
function renderMapping(columns){
  let html = '';
  localFields.forEach(f => {
    const guess = columns.find(c => c.toLowerCase() === f.toLowerCase()) || '';
    html += '<div style=\"display:grid;grid-template-columns:140px 1fr;gap:10px;align-items:center\">';
    html += '<label style=\"font-size:12.5px;font-weight:600\">' + f + '</label>';
    html += '<select name=\"map[' + f + ']\" class=\"form-select\">';
    html += '<option value=\"\">— تجاهل —</option>';
    columns.forEach(c => { html += '<option value=\"' + c + '\"' + (c === guess ? ' selected' : '') + '>' + c + '</option>'; });
    html += '</select></div>';
  });
  document.getElementById('mappingRows').innerHTML = html;
}
";
$content = ob_get_clean();
require APP_PATH . '/Views/admin/layouts/app.php';
