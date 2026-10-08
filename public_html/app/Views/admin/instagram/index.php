<?php
$pageTitle  = 'فيديوهات انستجرام';
$breadcrumb = [['label'=>'فيديوهات انستجرام']];
ob_start();
?>
<div class="ph">
  <div class="ph-left"><h1><span style="display:inline-flex;align-items:center;gap:9px"><?= svgIcon('image', 22) ?> فيديوهات انستجرام</span></h1><p>اعرض ريلز أو بوستات انستجرام في الصفحة الرئيسية — زي "What the Pros Are Saying"</p></div>
</div>

<div class="card" style="margin-bottom:20px">
  <div class="card-header"><span class="card-title">+ إضافة فيديو</span></div>
  <div class="card-body">
    <p style="font-size:12.5px;color:var(--t3);margin-bottom:14px">
      انسخ رابط أي بوست أو ريل عام (Public) من انستجرام (مثال: <code>https://www.instagram.com/reel/xxxxx/</code>) والصقه هنا.
      النظام هيعرضه تلقائي باستخدام تضمين انستجرام الرسمي — مش محتاج توكن أو حساب API.
    </p>
    <form method="POST" action="<?= adminUrl('instagram/create') ?>" style="display:flex;gap:8px;flex-wrap:wrap">
      <?= csrf_field() ?>
      <input type="url" name="url" class="form-input" required placeholder="https://www.instagram.com/reel/..." style="flex:2;min-width:240px">
      <input type="text" name="caption" class="form-input" placeholder="تعليق مختصر (اختياري)" style="flex:1;min-width:160px">
      <button type="submit" class="btn btn-primary">+ إضافة</button>
    </form>
  </div>
</div>

<div class="card">
  <div class="table-wrap">
    <table>
      <thead><tr><th>الرابط</th><th>التعليق</th><th>الحالة</th><th></th></tr></thead>
      <tbody>
        <?php if (empty($reels)): ?>
          <tr><td colspan="4"><div class="empty"><div class="empty-icon"><?= svgIcon('image', 32) ?></div><div class="empty-title">لا توجد فيديوهات مضافة بعد</div></div></td></tr>
        <?php else: foreach ($reels as $r): ?>
          <tr>
            <td><a href="<?= e($r['url']) ?>" target="_blank" style="font-size:12px;color:var(--ac)"><?= e(mb_substr($r['url'],0,50)) ?>…</a></td>
            <td class="td-dim" style="font-size:12.5px"><?= e($r['caption'] ?: '—') ?></td>
            <td>
              <form method="POST" action="<?= adminUrl('instagram/'.$r['id'].'/toggle') ?>" style="display:inline">
                <?= csrf_field() ?>
                <button class="badge <?= $r['is_active']?'badge-ok':'badge-dim' ?>" style="border:none;cursor:pointer"><?= $r['is_active']?'ظاهر':'مخفي' ?></button>
              </form>
            </td>
            <td>
              <div style="display:flex;gap:4px;justify-content:flex-end">
                <form method="POST" action="<?= adminUrl('instagram/'.$r['id'].'/reorder') ?>" style="display:inline"><?= csrf_field() ?><input type="hidden" name="direction" value="up"><button class="btn btn-secondary btn-sm">↑</button></form>
                <form method="POST" action="<?= adminUrl('instagram/'.$r['id'].'/reorder') ?>" style="display:inline"><?= csrf_field() ?><input type="hidden" name="direction" value="down"><button class="btn btn-secondary btn-sm">↓</button></form>
                <button class="btn btn-danger btn-sm" onclick="confirmDelete('<?= adminUrl('instagram/'.$r['id'].'/delete') ?>','حذف الفيديو؟','')">حذف</button>
              </div>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php
$content = ob_get_clean();
require APP_PATH . '/Views/admin/layouts/app.php';
