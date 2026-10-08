<?php
$pageTitle  = 'إعدادات API';
$breadcrumb = [['label'=>'الإعدادات','url'=>adminUrl('settings')], ['label'=>'API']];
$newKey = Session::get('_new_api_key');
if ($newKey) Session::remove('_new_api_key');
ob_start();
?>
<div class="ph">
  <div class="ph-left"><h1><span style="display:inline-flex;align-items:center;gap:9px"><?= svgIcon('settings', 22) ?> إعدادات API</span></h1><p>اسمح لأنظمة خارجية (محاسبة، تكاملات) تقرأ بيانات حسابك بأمان</p></div>
</div>

<?php if ($newKey): ?>
<div style="background:var(--acb2);border:1px solid rgba(var(--acr),.3);border-radius:12px;padding:16px 20px;margin-bottom:20px">
  <div style="font-weight:700;font-size:13px;margin-bottom:8px">✅ المفتاح الجديد — انسخه دلوقتي، مش هيظهر تاني كامل بعد الصفحة دي</div>
  <div style="display:flex;gap:8px;align-items:center">
    <code id="newKeyText" style="background:var(--bg3);padding:8px 12px;border-radius:8px;font-size:12.5px;flex:1;overflow-x:auto;white-space:nowrap"><?= e($newKey) ?></code>
    <button class="btn btn-secondary btn-sm" onclick="navigator.clipboard.writeText('<?= e($newKey) ?>');this.textContent='تم النسخ ✅'">نسخ</button>
  </div>
</div>
<?php endif; ?>

<div class="card" style="margin-bottom:20px">
  <div class="card-header"><span class="card-title">+ إنشاء مفتاح جديد</span></div>
  <div class="card-body">
    <form method="POST" action="<?= adminUrl('settings/api/create') ?>" style="display:flex;gap:8px">
      <?= csrf_field() ?>
      <input type="text" name="label" class="form-input" placeholder="اسم/وصف المفتاح (مثال: نظام المحاسبة)" required style="flex:1">
      <button type="submit" class="btn btn-primary">+ إنشاء</button>
    </form>
  </div>
</div>

<div class="card" style="margin-bottom:20px">
  <div class="table-wrap">
    <table>
      <thead><tr><th>الاسم</th><th>المفتاح</th><th>آخر استخدام</th><th>الحالة</th><th></th></tr></thead>
      <tbody>
        <?php if (empty($keys)): ?>
          <tr><td colspan="5"><div class="empty"><div class="empty-icon"><?= svgIcon('settings', 32) ?></div><div class="empty-title">لا توجد مفاتيح API بعد</div></div></td></tr>
        <?php else: foreach ($keys as $k): ?>
          <tr>
            <td style="font-weight:600"><?= e($k['label']) ?></td>
            <td class="td-mono td-dim"><?= e(substr($k['api_key'],0,10)) ?>••••••••</td>
            <td class="td-dim" style="font-size:12px"><?= $k['last_used_at'] ? timeAgo($k['last_used_at']) : 'لم يُستخدم بعد' ?></td>
            <td>
              <form method="POST" action="<?= adminUrl('settings/api/'.$k['id'].'/toggle') ?>" style="display:inline">
                <?= csrf_field() ?>
                <button class="badge <?= $k['is_active']?'badge-ok':'badge-dim' ?>" style="border:none;cursor:pointer"><?= $k['is_active']?'نشط':'موقوف' ?></button>
              </form>
            </td>
            <td>
              <button class="btn btn-danger btn-sm" onclick="confirmDelete('<?= adminUrl('settings/api/'.$k['id'].'/delete') ?>','حذف المفتاح؟','أي نظام بيستخدمه هيتوقف عن الشغل فوراً')">حذف</button>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="card">
  <div class="card-header"><span class="card-title">📖 طريقة الاستخدام</span></div>
  <div class="card-body" style="font-size:13px;color:var(--t2);line-height:1.9">
    <p style="margin-bottom:10px">أرسل المفتاح في الـ header بالشكل ده:</p>
    <code style="display:block;background:var(--bg3);padding:12px;border-radius:8px;font-size:12px;margin-bottom:14px" dir="ltr">Authorization: Bearer YOUR_API_KEY</code>
    <p style="margin-bottom:8px;font-weight:700">النقاط المتاحة:</p>
    <ul style="padding-right:20px;display:flex;flex-direction:column;gap:6px">
      <li><code style="font-size:12px" dir="ltr"><?= APP_URL ?>/api/v1/summary</code> — نظرة عامة: الحسابات، المالية، الطلبات، الشحنات، المنتجات</li>
      <li><code style="font-size:12px" dir="ltr"><?= APP_URL ?>/api/v1/orders?page=1&per_page=20</code> — قائمة الطلبات (مقسّمة صفحات)</li>
      <li><code style="font-size:12px" dir="ltr"><?= APP_URL ?>/api/v1/shipments?page=1&per_page=20</code> — قائمة الشحنات (مقسّمة صفحات)</li>
    </ul>
    <p style="margin-top:12px;color:var(--t3);font-size:12px">النقاط دي للقراءة فقط — مفيش أي منها بيقدر يعدّل أو يحذف أي بيانات، حتى لو المفتاح اتسرّب.</p>
  </div>
</div>
<?php
$content = ob_get_clean();
require APP_PATH . '/Views/admin/layouts/app.php';
