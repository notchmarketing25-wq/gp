<?php
$pageTitle  = 'تحديثات النظام';
$breadcrumb = [['label'=>'الإعدادات','url'=>adminUrl('settings')],['label'=>'التحديثات']];
$a = ADMIN_PREFIX;
ob_start();
?>
<div class="ph">
  <div class="ph-left">
    <h1>تحديثات النظام</h1>
    <p>الإصدار الحالي: <strong style="color:var(--ac)"><?= VERSION ?></strong></p>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px">

  <!-- Upload package -->
  <div class="card">
    <div class="card-header">
      <span class="card-title"><?= svgIcon('package', 15) ?> رفع حزمة تحديث</span>
    </div>
    <div class="card-body">
      <p style="font-size:13px;color:var(--t2);margin-bottom:16px;line-height:1.7">
        ارفع ملف ZIP يحتوي على ملفات التحديث. سيتم تطبيقه تلقائياً وحفظ نسخة احتياطية من الإعدادات.
      </p>
      <form method="POST" action="<?= adminUrl('updater/upload') ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="form-group" style="margin-bottom:14px">
          <label class="form-label">ملف التحديث (.zip)</label>
          <input type="file" name="package" class="form-input" accept=".zip" required style="padding:8px">
          <span class="form-hint">حجم أقصى: 50MB — صيغة ZIP فقط</span>
        </div>
        <button type="submit" class="btn btn-primary btn-full">⬆️ رفع الحزمة</button>
      </form>
    </div>
  </div>

  <!-- Info -->
  <div class="card">
    <div class="card-header"><span class="card-title">ℹ️ معلومات النظام</span></div>
    <div class="card-body">
      <div style="display:flex;flex-direction:column;gap:10px">
        <?php foreach ([
          ['الإصدار', VERSION],
          ['PHP', PHP_VERSION],
          ['قاعدة البيانات', DB_NAME],
          ['السيرفر', $_SERVER['SERVER_SOFTWARE']??'N/A'],
          ['المسار', ROOT_PATH],
          ['مساحة متاحة', round(disk_free_space(ROOT_PATH)/1024/1024).' MB'],
        ] as [$k,$v]): ?>
          <div style="display:flex;justify-content:space-between;font-size:12px;padding:7px 0;border-bottom:1px solid var(--bd)">
            <span style="color:var(--t2)"><?= $k ?></span>
            <span style="font-weight:500;font-family:monospace"><?= e($v) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

</div>

<!-- Available packages -->
<div class="card" style="margin-bottom:16px">
  <div class="card-header"><span class="card-title">📋 الحزم المتاحة</span></div>
  <?php if (empty($packages)): ?>
    <div style="padding:40px;text-align:center;color:var(--t3)">
      <div style="font-size:36px;margin-bottom:10px">📭</div>
      <div style="font-size:14px">لا توجد حزم تحديث مرفوعة</div>
      <div style="font-size:12px;margin-top:4px">ارفع ملف ZIP من الأعلى لتطبيق تحديث</div>
    </div>
  <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead><tr><th>الحزمة</th><th>الحجم</th><th>تاريخ الرفع</th><th>نسخة احتياطية</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($packages as $p): ?>
            <tr>
              <td>
                <div style="font-family:monospace;font-weight:700;color:var(--ac)"><?= e($p['name']) ?></div>
                <div style="font-size:11px;color:var(--t3)"><?= e($p['file']) ?></div>
              </td>
              <td class="td-dim"><?= e($p['size']) ?></td>
              <td class="td-dim"><?= e($p['modified']) ?></td>
              <td>
                <span class="badge <?= $p['hasBackup']?'badge-ok':'badge-dim' ?>">
                  <?= $p['hasBackup']?'✓ موجودة':'لا يوجد' ?>
                </span>
              </td>
              <td>
                <div style="display:flex;gap:6px;justify-content:flex-end">
                  <form method="POST" action="<?= adminUrl('updater/apply/'.$p['name']) ?>" onsubmit="return confirm('تطبيق تحديث <?= e($p['name']) ?>؟ تأكد من عمل نسخة احتياطية.')">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-primary btn-sm"><?= svgIcon('zap', 15) ?> تطبيق</button>
                  </form>
                  <?php if ($p['hasBackup']): ?>
                    <form method="POST" action="<?= adminUrl('updater/rollback/'.$p['name']) ?>" onsubmit="return confirm('استعادة النسخة السابقة؟')">
                      <?= csrf_field() ?>
                      <button type="submit" class="btn btn-secondary btn-sm">↩️ استعادة</button>
                    </form>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<!-- Update history -->
<div class="card">
  <div class="card-header"><span class="card-title">📜 سجل التحديثات</span></div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>الإصدار</th><th>الوصف</th><th>التاريخ</th></tr></thead>
      <tbody>
        <?php if (empty($history)): ?>
          <tr><td colspan="3" style="text-align:center;color:var(--t3);padding:24px">لا يوجد سجل</td></tr>
        <?php else: foreach ($history as $h): ?>
          <tr>
            <td><span class="badge badge-purple td-mono"><?= e($h['version']) ?></span></td>
            <td class="td-dim"><?= e($h['description']??'') ?></td>
            <td class="td-dim"><?= formatDateTime($h['applied_at']) ?></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php $content = ob_get_clean(); require APP_PATH . '/Views/admin/layouts/app.php';
