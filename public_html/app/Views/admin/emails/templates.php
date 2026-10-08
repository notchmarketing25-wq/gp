<?php
$pageTitle  = 'قوالب الإيميلات';
$breadcrumb = [['label'=>'الإيميلات']];
$a = ADMIN_PREFIX;
ob_start();
?>
<div class="ph">
  <div class="ph-left"><h1><span style="display:inline-flex;align-items:center;gap:9px"><?= svgIcon('mail', 22) ?> قوالب الإيميلات</span></h1><p>تصميم ذكي يُرسل تلقائياً عند كل حدث</p></div>
  <div class="ph-right">
    <a href="<?= adminUrl('emails/logs') ?>" class="btn btn-secondary">📊 سجل الإرسال</a>
    <a href="<?= adminUrl('emails/settings') ?>" class="btn btn-secondary">⚙️ إعدادات SMTP</a>
  </div>
</div>

<div class="card">
  <div class="table-wrap">
    <table>
      <thead><tr><th>القالب</th><th>الموضوع</th><th>يُرسل عند</th><th>الحالة</th><th></th></tr></thead>
      <tbody>
        <?php
        $triggerMap = [
          'welcome_customer'=>'تسجيل عميل جديد','order_confirmation'=>'تأكيد طلب جديد',
          'order_shipped'=>'تغيير حالة الطلب لـ "تم الشحن"','order_delivered'=>'تغيير حالة الطلب لـ "تم التسليم"',
          'business_registration_received'=>'تسجيل حساب Business جديد','business_approved'=>'اعتماد حساب Business',
          'credit_approved'=>'اعتماد طلب N&Credit','wholesale_approved'=>'اعتماد حساب جملة',
        ];
        foreach ($templates as $t): ?>
          <tr>
            <td style="font-weight:600"><?= e($t['name']) ?></td>
            <td class="td-dim" style="font-size:12px"><?= e($t['subject']) ?></td>
            <td class="td-dim" style="font-size:12px"><?= $triggerMap[$t['key_name']] ?? $t['key_name'] ?></td>
            <td><span class="badge <?= $t['is_active']?'badge-ok':'badge-dim' ?>"><?= $t['is_active']?'نشط':'معطّل' ?></span></td>
            <td><a href="<?= adminUrl('emails/templates/'.$t['id'].'/edit') ?>" class="btn btn-secondary btn-sm">تعديل ومعاينة</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php $content = ob_get_clean(); require APP_PATH . '/Views/admin/layouts/app.php';
