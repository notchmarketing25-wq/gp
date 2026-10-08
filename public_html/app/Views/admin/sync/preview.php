<?php
$pageTitle  = 'معاينة مزامنة ' . $config['label'];
$breadcrumb = [['label'=>'مزامنة Supabase','url'=>adminUrl('sync/supabase')],['label'=>'معاينة']];
$a = ADMIN_PREFIX;

$newCount = count(array_filter($items, fn($i)=>!$i['_exists']));
$existCount = count(array_filter($items, fn($i)=>$i['_exists']));
ob_start();
?>
<div class="ph">
  <div class="ph-left"><h1><span style="display:inline-flex;align-items:center;gap:9px"><?= svgIcon('search', 22) ?> معاينة مزامنة <?= e($config['label']) ?></span></h1><p>جدول Supabase: <code style="background:var(--bg3);padding:1px 6px;border-radius:4px"><?= e($supaTable) ?></code></p></div>
  <div class="ph-right"><a href="<?= adminUrl('sync/supabase/'.$entity) ?>" class="btn btn-secondary">← تعديل المطابقة</a></div>
</div>

<div class="stats" style="grid-template-columns:repeat(3,1fr);margin-bottom:20px">
  <div class="stat"><div class="stat-icon" style="background:#E8F0FF;color:var(--blue)"><?= svgIcon('upload', 20) ?></div><div class="stat-value"><?= $totalFetched ?></div><div class="stat-label">تم جلبه من Supabase</div></div>
  <div class="stat"><div class="stat-icon" style="background:#E8FBF4;color:var(--green)"><?= svgIcon('star', 20) ?></div><div class="stat-value"><?= $newCount ?></div><div class="stat-label">سجلات جديدة هتتضاف</div></div>
  <div class="stat"><div class="stat-icon" style="background:#FFF8E8;color:var(--yellow)"><?= svgIcon('refresh', 20) ?></div><div class="stat-value"><?= $existCount ?></div><div class="stat-label">موجودة بالفعل — هتتخطى</div></div>
</div>

<div class="card" style="margin-bottom:20px">
  <div class="table-wrap" style="max-height:500px;overflow-y:auto">
    <table>
      <thead>
        <tr>
          <th>الحالة</th>
          <?php foreach ($config['fields'] as $f): ?><th><?= $f ?></th><?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach (array_slice($items, 0, 500) as $item): ?>
          <tr style="<?= $item['_exists'] ? 'opacity:.5' : '' ?>">
            <td><span class="badge <?= $item['_exists']?'badge-dim':'badge-ok' ?>"><?= $item['_exists']?'⏭️ موجود':'🆕 جديد' ?></span></td>
            <?php foreach ($config['fields'] as $f): ?>
              <td style="font-size:12px;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= e($item[$f] ?? '—') ?></td>
            <?php endforeach; ?>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php if (count($items) > 500): ?>
      <div style="padding:14px;text-align:center;color:var(--t3);font-size:12px">... وعدد <?= count($items)-500 ?> سجل إضافي مش ظاهرين في المعاينة (هيتم معالجتهم برضو عند التأكيد)</div>
    <?php endif; ?>
  </div>
</div>

<form method="POST" action="<?= adminUrl('sync/supabase/'.$entity.'/confirm') ?>" style="position:sticky;bottom:16px">
  <?= csrf_field() ?>
  <input type="hidden" name="batch_id" value="<?= e($batchId) ?>">
  <div class="card" style="padding:16px 20px;display:flex;align-items:center;justify-content:space-between;box-shadow:var(--shadow-lg);flex-wrap:wrap;gap:10px">
    <div style="font-size:13px;color:var(--t2)">هيتم إضافة <strong style="color:var(--t1)"><?= $newCount ?></strong> سجل جديد فقط — الموجود مسبقاً مش هيتكرر</div>
    <button type="submit" class="btn btn-primary btn-lg" onclick="return confirm('تأكيد مزامنة <?= $newCount ?> سجل جديد؟')" <?= $newCount===0?'disabled':'' ?>><?= svgIcon('check', 15) ?> تأكيد المزامنة</button>
  </div>
</form>

<?php $content = ob_get_clean(); require APP_PATH . '/Views/admin/layouts/app.php';
