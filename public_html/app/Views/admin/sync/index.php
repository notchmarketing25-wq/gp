<?php
$pageTitle  = 'مزامنة Supabase';
$breadcrumb = [['label'=>'مزامنة Supabase']];
$a = ADMIN_PREFIX;
ob_start();
?>
<div class="ph">
  <div class="ph-left"><h1><span style="display:inline-flex;align-items:center;gap:9px"><?= svgIcon('refresh', 22) ?> مزامنة من Supabase</span></h1><p>انقل بياناتك القديمة بدون تكرار — بيتأكد من الموجود ويجيب الباقي بس</p></div>
</div>

<div style="display:grid;grid-template-columns:1fr 340px;gap:16px;align-items:start;margin-bottom:20px">
  <div class="card">
    <div class="card-header">
      <span class="card-title"><?= svgIcon('settings', 15) ?> بيانات الاتصال</span>
      <span class="badge <?= $configured?'badge-ok':'badge-warn' ?>"><?= $configured?'✅ متصل':'⏳ لسه محتاج إعداد' ?></span>
    </div>
    <div class="card-body">
      <form method="POST" action="<?= adminUrl('sync/supabase/config') ?>" id="configForm">
        <?= csrf_field() ?>
        <div class="form-group" style="margin-bottom:14px">
          <label class="form-label">Supabase Project URL</label>
          <input type="text" name="supabase_url" class="form-input" value="<?= e($s['supabase_url']) ?>" placeholder="https://xxxxx.supabase.co">
        </div>
        <div class="form-group" style="margin-bottom:16px">
          <label class="form-label">API Key (anon أو service_role)</label>
          <input type="password" name="supabase_api_key" class="form-input" value="<?= e($s['supabase_api_key']) ?>" placeholder="eyJhbGciOi...">
          <span class="form-hint">تلاقيها في Supabase → Project Settings → API</span>
        </div>
        <div style="display:flex;gap:8px">
          <button type="submit" class="btn btn-primary"><?= svgIcon('check', 15) ?> حفظ</button>
          <button type="button" class="btn btn-secondary" onclick="testConn()">🔌 اختبار الاتصال</button>
        </div>
        <div id="testResult" style="margin-top:12px;font-size:12.5px"></div>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><span class="card-title">ℹ️ كيف تعمل المزامنة</span></div>
    <div class="card-body" style="font-size:12px;color:var(--t2);line-height:1.9">
      1. اربط بيانات Supabase<br>
      2. اختر نوع البيانات (منتجات، عملاء...)<br>
      3. حدد اسم الجدول في Supabase وطابق الأعمدة<br>
      4. هيتم فحص كل صف — لو موجود بالفعل (بالإيميل أو الـ SKU مثلاً) هيتخطاه، ولو جديد هيضيفه فقط<br>
      5. معاينة كاملة قبل أي حفظ نهائي
    </div>
  </div>
</div>

<div class="card" style="margin-bottom:20px">
  <div class="card-header"><span class="card-title"><?= svgIcon('package', 15) ?> اختر نوع البيانات للمزامنة</span></div>
  <div class="card-body" style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px">
    <?php $icons = ['products'=>'📦','collections'=>'🗂️','brands'=>'🏷️','customers'=>'👥']; ?>
    <?php foreach ($entities as $key => $ent): ?>
      <a href="<?= adminUrl('sync/supabase/'.$key) ?>" style="display:block;background:var(--bg3);border:1px solid var(--bd);border-radius:12px;padding:20px;text-align:center;transition:all .15s" onmouseover="this.style.borderColor='var(--ac)'" onmouseout="this.style.borderColor='var(--bd)'">
        <div style="font-size:28px;margin-bottom:8px"><?= $icons[$key] ?></div>
        <div style="font-weight:700;font-size:13px"><?= e($ent['label']) ?></div>
      </a>
    <?php endforeach; ?>
  </div>
</div>

<div class="card">
  <div class="card-header"><span class="card-title">📜 سجل عمليات المزامنة</span></div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>النوع</th><th>جدول Supabase</th><th>تم جلبه</th><th>جديد</th><th>متخطى</th><th>أخطاء</th><th>التاريخ</th></tr></thead>
      <tbody>
        <?php if (empty($logs)): ?>
          <tr><td colspan="7" style="text-align:center;color:var(--t3);padding:24px">لا توجد عمليات مزامنة بعد</td></tr>
        <?php else: foreach ($logs as $l): ?>
          <tr>
            <td style="font-weight:600"><?= $entities[$l['entity']]['label'] ?? e($l['entity']) ?></td>
            <td class="td-mono td-dim"><?= e($l['supabase_table']) ?></td>
            <td><?= $l['fetched_count'] ?></td>
            <td style="color:var(--green);font-weight:700"><?= $l['new_count'] ?></td>
            <td style="color:var(--t3)"><?= $l['skipped_count'] ?></td>
            <td style="color:<?= $l['error_count']?'var(--red)':'var(--t3)' ?>"><?= $l['error_count'] ?></td>
            <td class="td-dim" style="font-size:11px"><?= formatDateTime($l['created_at']) ?></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php
$extraScript = "
async function testConn(){
  const el = document.getElementById('testResult');
  el.innerHTML = '⏳ جاري الاختبار...';
  try {
    const r = await fetch('/<?= $a ?>/sync/supabase/test/' + Date.now() + Math.random().toString(36).slice(2), { cache: 'no-store', headers: { 'Cache-Control': 'no-cache' } });
    const raw = await r.text();
    let d;
    try { d = JSON.parse(raw); }
    catch(parseErr) {
      el.innerHTML = '<span style=\"color:var(--red)\">❌ السيرفر رجّع رد غير متوقع (HTTP ' + r.status + '):</span><pre style=\"background:var(--bg3);padding:10px;border-radius:8px;margin-top:8px;font-size:11px;white-space:pre-wrap;max-height:200px;overflow:auto\">' + raw.replace(/</g,'&lt;').slice(0,1500) + '</pre>';
      return;
    }
    if (d.ok) { el.innerHTML = '<span style=\"color:var(--green)\">✅ الاتصال ناجح — ' + d.tables.length + ' جدول متاح</span>'; }
    else { el.innerHTML = '<span style=\"color:var(--red)\">❌ ' + d.error + '</span>'; }
  } catch(e) { el.innerHTML = '<span style=\"color:var(--red)\">❌ فشل الطلب نفسه: ' + e.message + '</span>'; }
}
";
$content = ob_get_clean();
require APP_PATH . '/Views/admin/layouts/app.php';
