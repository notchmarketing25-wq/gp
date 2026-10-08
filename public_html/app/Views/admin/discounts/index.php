<?php
$pageTitle  = 'الخصومات';
$breadcrumb = [['label' => 'الخصومات']];
$a = ADMIN_PREFIX;
$data = $paginator['data'] ?? $discounts ?? [];
ob_start();
?>
<div class="ph"><div class="ph-left"><h1>كودات الخصم</h1></div></div>
<div style="display:grid;grid-template-columns:1fr 320px;gap:16px;align-items:start">
  <div class="card">
    <div class="table-wrap"><table>
      <thead><tr><th>الكود</th><th>النوع</th><th>القيمة</th><th>الاستخدام</th><th>الانتهاء</th><th>الحالة</th><th></th></tr></thead>
      <tbody>
        <?php if (empty($data)): ?>
          <tr><td colspan="7"><div class="empty"><div class="empty-icon"><?= svgIcon('tag', 32) ?></div><div class="empty-title">لا توجد كودات خصم</div></div></td></tr>
        <?php else: foreach ($data as $d): ?>
          <tr>
            <td><span style="font-family:monospace;font-weight:700;font-size:13px;color:var(--ac)"><?= e($d['code']) ?></span></td>
            <td class="td-dim"><?= $d['type']==='percentage'?'نسبة':'مبلغ ثابت' ?></td>
            <td><span class="badge badge-purple"><?= $d['type']==='percentage'?$d['value'].'%':money($d['value']) ?></span></td>
            <td class="td-dim"><?= $d['used_count'] ?><?= $d['max_uses']?' / '.$d['max_uses']:'' ?></td>
            <td class="td-dim"><?= $d['expires_at']?formatDate($d['expires_at']):'بلا حد' ?></td>
            <td><span class="badge <?= $d['is_active']?'badge-ok':'badge-dim' ?>"><?= $d['is_active']?'نشط':'موقوف' ?></span></td>
            <td><button class="btn btn-danger btn-sm" onclick="confirmDelete('/<?= $a ?>/discounts/<?= $d['id'] ?>/delete','حذف الكود؟','')">حذف</button></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table></div>
  </div>
  <div class="card">
    <div class="card-header"><span class="card-title">إضافة كود جديد</span></div>
    <div class="card-body">
      <form method="POST" action="/<?= $a ?>/discounts/create">
        <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
        <div class="form-group" style="margin-bottom:10px">
          <label class="form-label">الكود <span style="color:var(--red)">*</span></label>
          <div style="display:flex;gap:6px">
            <input type="text" name="code" id="dc" class="form-input" placeholder="SALE20" style="text-transform:uppercase" required>
            <button type="button" class="btn btn-secondary btn-sm" title="توليد عشوائي" onclick="document.getElementById('dc').value='NT'+Math.random().toString(36).substr(2,6).toUpperCase()">🎲</button>
          </div>
        </div>
        <div class="form-group" style="margin-bottom:10px"><label class="form-label">النوع</label><select name="type" class="form-select"><option value="percentage">نسبة مئوية %</option><option value="fixed">مبلغ ثابت (ج.م)</option></select></div>
        <div class="form-group" style="margin-bottom:10px"><label class="form-label">القيمة <span style="color:var(--red)">*</span></label><input type="number" name="value" class="form-input" step="0.01" min="0" required></div>
        <div class="form-group" style="margin-bottom:10px"><label class="form-label">الحد الأدنى للطلب</label><input type="number" name="min_order_amount" class="form-input" step="0.01" min="0" placeholder="0"></div>
        <div class="form-group" style="margin-bottom:10px"><label class="form-label">أقصى استخدام</label><input type="number" name="max_uses" class="form-input" min="1" placeholder="لا نهاية"></div>
        <div class="form-group" style="margin-bottom:14px"><label class="form-label">تاريخ الانتهاء</label><input type="datetime-local" name="expires_at" class="form-input"></div>
        <button type="submit" class="btn btn-primary btn-full">+ إضافة الكود</button>
      </form>
    </div>
  </div>
</div>
<?php $content = ob_get_clean(); require APP_PATH . '/Views/admin/layouts/app.php';
