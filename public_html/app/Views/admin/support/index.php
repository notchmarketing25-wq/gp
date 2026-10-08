<?php
$pageTitle  = 'دعم العملاء';
$breadcrumb = [['label'=>'دعم العملاء']];
ob_start();
?>
<div class="ph">
  <div class="ph-left"><h1><span style="display:inline-flex;align-items:center;gap:9px"><?= svgIcon('message', 22) ?> دعم العملاء</span></h1><p><?= (int)($counts['total_c']??0) ?> تذكرة إجمالاً</p></div>
</div>

<div class="stats" style="margin-bottom:20px">
  <div class="stat"><div class="stat-icon" style="background:rgba(0,87,255,.1)"><?= svgIcon('message', 20) ?></div><div class="stat-label">مفتوحة</div><div class="stat-value"><?= (int)($counts['open_c']??0) ?></div></div>
  <div class="stat"><div class="stat-icon" style="background:rgba(245,158,11,.1)"><?= svgIcon('clock', 20) ?></div><div class="stat-label">قيد المعالجة</div><div class="stat-value"><?= (int)($counts['progress_c']??0) ?></div></div>
  <div class="stat"><div class="stat-icon" style="background:rgba(34,197,94,.1)"><?= svgIcon('check', 20) ?></div><div class="stat-label">تم الحل</div><div class="stat-value"><?= (int)($counts['resolved_c']??0) ?></div></div>
</div>

<div class="card" style="margin-bottom:16px">
  <div class="card-body">
    <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap">
      <input type="text" name="search" class="form-input" placeholder="بحث برقم التذكرة أو الاسم أو الموضوع..." value="<?= e($search) ?>" style="flex:1;min-width:200px">
      <select name="status" class="form-select" style="width:auto" onchange="this.form.submit()">
        <option value="">كل الحالات</option>
        <option value="open" <?= $status==='open'?'selected':'' ?>>مفتوحة</option>
        <option value="in_progress" <?= $status==='in_progress'?'selected':'' ?>>قيد المعالجة</option>
        <option value="resolved" <?= $status==='resolved'?'selected':'' ?>>تم الحل</option>
        <option value="closed" <?= $status==='closed'?'selected':'' ?>>مغلقة</option>
      </select>
      <button class="btn btn-primary">بحث</button>
    </form>
  </div>
</div>

<div class="card">
  <div class="table-wrap">
    <table>
      <thead><tr><th>التذكرة</th><th>العميل</th><th>الموضوع</th><th>النوع</th><th>مسندة لـ</th><th>الحالة</th><th>التاريخ</th></tr></thead>
      <tbody>
        <?php if (empty($tickets)): ?>
          <tr><td colspan="7"><div class="empty"><div class="empty-icon"><?= svgIcon('message', 32) ?></div><div class="empty-title">لا توجد تذاكر</div></div></td></tr>
        <?php else: foreach ($tickets as $t):
          $statusMap = ['open'=>['مفتوحة','badge-ok'],'in_progress'=>['قيد المعالجة','badge-warn'],'resolved'=>['تم الحل','badge-purple'],'closed'=>['مغلقة','badge-dim']];
          [$sLabel,$sBadge] = $statusMap[$t['status']] ?? [$t['status'],'badge-dim'];
        ?>
          <tr onclick="location.href='<?= adminUrl('support/'.$t['id']) ?>'" style="cursor:pointer">
            <td><code style="font-size:12px;color:var(--ac);font-weight:600"><?= e($t['ticket_number']) ?></code></td>
            <td><div style="font-weight:500;font-size:13px"><?= e($t['name']) ?></div><div style="font-size:11px;color:var(--t3)"><?= e($t['email']) ?></div></td>
            <td style="font-size:12.5px"><?= e($t['subject']) ?></td>
            <td class="td-dim" style="font-size:12px"><?= e($t['category']) ?></td>
            <td class="td-dim" style="font-size:12px"><?= e($t['assigned_name'] ?? '—') ?></td>
            <td><span class="badge <?= $sBadge ?>"><?= $sLabel ?></span></td>
            <td class="td-dim" style="font-size:11.5px"><?= timeAgo($t['created_at']) ?></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php
$content = ob_get_clean();
require APP_PATH . '/Views/admin/layouts/app.php';
