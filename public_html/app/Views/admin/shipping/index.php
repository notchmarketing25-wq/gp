<?php
$pageTitle  = 'الشحنات';
$breadcrumb = [['label'=>'الشحنات']];
$a = ADMIN_PREFIX;
$statusLabels = ['pending'=>'⏳ قيد الانتظار','picked_up'=>'📦 تم الاستلام','in_transit'=>'🚚 في الطريق','out_for_delivery'=>'🏍️ خارج للتوصيل','delivered'=>'✅ تم التسليم','returned'=>'↩️ مرتجع','failed'=>'❌ فشل التوصيل'];
$statusColors = ['pending'=>'badge-warn','picked_up'=>'badge-info','in_transit'=>'badge-info','out_for_delivery'=>'badge-purple','delivered'=>'badge-ok','returned'=>'badge-err','failed'=>'badge-err'];
ob_start();
?>
<div class="ph">
  <div class="ph-left"><h1><span style="display:inline-flex;align-items:center;gap:9px"><?= svgIcon('truck', 22) ?> الشحنات</span></h1><p>إدارة كل الشحنات وحالات التحصيل</p></div>
  <div class="ph-right">
    <a href="<?= adminUrl('settings/shipping') ?>" class="btn btn-secondary">🗺️ مناطق الشحن</a>
    <a href="<?= adminUrl('shipping/carriers') ?>" class="btn btn-secondary">🏢 شركات الشحن</a>
    <a href="<?= adminUrl('shipping/import') ?>" class="btn btn-secondary">🛍️ استيراد من Shopify</a>
    <button class="btn btn-primary" onclick="document.getElementById('addModal').classList.add('open')">+ شحنة جديدة</button>
  </div>
</div>

<!-- Stats -->
<div class="stats">
  <div class="stat"><div class="stat-icon" style="background:#E8F0FF;color:var(--blue)"><?= svgIcon('package', 20) ?></div><div class="stat-value"><?= number_format($stats['total']) ?></div><div class="stat-label">إجمالي الشحنات</div></div>
  <div class="stat"><div class="stat-icon" style="background:#FFF8E8;color:var(--yellow)"><?= svgIcon('truck', 20) ?></div><div class="stat-value"><?= number_format($stats['in_transit']) ?></div><div class="stat-label">قيد التوصيل</div></div>
  <div class="stat"><div class="stat-icon" style="background:#E8FBF4;color:var(--green)"><?= svgIcon('check', 20) ?></div><div class="stat-value"><?= number_format($stats['delivered']) ?></div><div class="stat-label">تم التسليم</div></div>
  <div class="stat"><div class="stat-icon" style="background:#FFF0F0;color:var(--red)"><?= svgIcon('store', 20) ?></div><div class="stat-value" style="font-size:18px"><?= money($stats['cod_pending']) ?></div><div class="stat-label">تحصيل معلّق (COD)</div></div>
</div>

<!-- Filters -->
<div class="card" style="margin-bottom:14px">
  <div class="card-body">
    <form method="GET" style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr auto;gap:10px;align-items:end">
      <div class="form-group"><label class="form-label">بحث</label><input type="text" name="search" class="form-input" value="<?= e($search??'') ?>" placeholder="رقم التتبع، الاسم، الهاتف..."></div>
      <div class="form-group"><label class="form-label">الحالة</label>
        <select name="status" class="form-select">
          <option value="">الكل</option>
          <?php foreach($statusLabels as $k=>$l): ?><option value="<?=$k?>" <?= ($status??'')===$k?'selected':'' ?>><?=$l?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="form-group"><label class="form-label">شركة الشحن</label>
        <select name="carrier" class="form-select">
          <option value="">الكل</option>
          <?php foreach($carriers as $c): ?><option value="<?=$c['id']?>" <?= ($carrier??'')==$c['id']?'selected':'' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="form-group"><label class="form-label">التحصيل</label>
        <select name="cod" class="form-select">
          <option value="">الكل</option>
          <option value="1" <?= ($cod??'')==='1'?'selected':'' ?>>✅ محصّل</option>
          <option value="0" <?= ($cod??'')==='0'?'selected':'' ?>>⏳ لم يُحصَّل</option>
        </select>
      </div>
      <button type="submit" class="btn btn-primary">بحث</button>
    </form>
  </div>
</div>

<div class="card">
  <div class="table-wrap">
    <table>
      <thead><tr><th>رقم التتبع</th><th>الطلب</th><th>العميل</th><th>شركة الشحن</th><th>مبلغ COD</th><th>التحصيل</th><th>الحالة</th><th>التاريخ</th><th></th></tr></thead>
      <tbody>
        <?php if (empty($shipments)): ?>
          <tr><td colspan="9"><div class="empty"><div class="empty-icon"><?= svgIcon('truck', 32) ?></div><div class="empty-title">لا توجد شحنات</div><div class="empty-desc">أضف شحنة يدوياً أو استورد من Shopify</div></div></td></tr>
        <?php else: foreach ($shipments as $s): ?>
          <tr>
            <td class="td-mono" style="font-weight:600"><?= e($s['tracking_number'] ?: '—') ?></td>
            <td><?php if($s['order_number']): ?><a href="<?= adminUrl('orders/'.$s['order_id']) ?>" style="color:var(--ac);font-weight:600;font-family:monospace"><?= e($s['order_number']) ?></a><?php else: ?><span class="td-dim">—</span><?php endif; ?></td>
            <td><div style="font-weight:500;font-size:13px"><?= e($s['customer_name']) ?></div><div style="font-size:11px;color:var(--t3)"><?= e($s['customer_phone']) ?></div></td>
            <td class="td-dim"><?= e($s['carrier_name'] ?? '—') ?></td>
            <td style="font-weight:700"><?= money($s['cod_amount']) ?></td>
            <td><span class="badge <?= $s['cod_collected']?'badge-ok':'badge-warn' ?>"><?= $s['cod_collected']?'✅ محصّل':'⏳ لم يُحصَّل' ?></span></td>
            <td><span class="badge <?= $statusColors[$s['status']] ?? 'badge-dim' ?>"><?= $statusLabels[$s['status']] ?? $s['status'] ?></span></td>
            <td class="td-dim" style="font-size:11px"><?= formatDate($s['created_at']) ?></td>
            <td>
              <div style="display:flex;gap:5px">
                <button class="btn btn-secondary btn-sm" onclick='editShipment(<?= htmlspecialchars(json_encode($s),ENT_QUOTES) ?>)'>تعديل</button>
                <button class="btn btn-danger btn-sm" onclick="confirmDelete('<?= adminUrl('shipping/'.$s['id'].'/delete') ?>','حذف الشحنة؟','')">حذف</button>
              </div>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
  <?php if ($lastPage > 1): ?>
    <div class="pagination" style="padding:14px">
      <?php for ($i=1;$i<=$lastPage;$i++): ?>
        <a href="?page=<?=$i?>&status=<?=urlencode($status)?>&carrier=<?=urlencode($carrier)?>&search=<?=urlencode($search)?>" class="page-btn <?=$i===$page?'active':''?>"><?=$i?></a>
      <?php endfor; ?>
    </div>
  <?php endif; ?>
</div>

<!-- Add shipment modal -->
<div class="modal-overlay" id="addModal">
  <div class="modal modal-lg">
    <div class="modal-head"><h3>+ شحنة جديدة</h3><button class="btn btn-ghost btn-icon" onclick="document.getElementById('addModal').classList.remove('open')" style="font-size:18px">×</button></div>
    <form method="POST" action="<?= adminUrl('shipping/create') ?>">
      <?= csrf_field() ?>
      <div class="form-grid form-row-2">
        <div class="form-group"><label class="form-label">رقم الطلب (اختياري)</label><input type="number" name="order_id" class="form-input" placeholder="ID الطلب"></div>
        <div class="form-group"><label class="form-label">شركة الشحن</label>
          <select name="carrier_id" class="form-select">
            <?php foreach($carriers as $c): ?><option value="<?=$c['id']?>"><?= e($c['name']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="form-group"><label class="form-label">اسم العميل</label><input type="text" name="customer_name" class="form-input" required></div>
        <div class="form-group"><label class="form-label">الهاتف</label><input type="text" name="customer_phone" class="form-input" required></div>
        <div class="form-group" style="grid-column:span 2"><label class="form-label">العنوان</label><input type="text" name="address" class="form-input"></div>
        <div class="form-group"><label class="form-label">المدينة</label><input type="text" name="city" class="form-input"></div>
        <div class="form-group"><label class="form-label">المحافظة</label><input type="text" name="governorate" class="form-input"></div>
        <div class="form-group"><label class="form-label">رقم التتبع</label><input type="text" name="tracking_number" class="form-input"></div>
        <div class="form-group"><label class="form-label">مبلغ COD</label><input type="number" name="cod_amount" class="form-input" step="0.01" min="0"></div>
        <div class="form-group" style="grid-column:span 2"><label class="form-label">ملاحظات</label><input type="text" name="notes" class="form-input"></div>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('addModal').classList.remove('open')">إلغاء</button>
        <button type="submit" class="btn btn-primary"><?= svgIcon('check', 15) ?> إنشاء الشحنة</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit shipment modal -->
<div class="modal-overlay" id="editModal">
  <div class="modal">
    <div class="modal-head"><h3>✏️ تعديل الشحنة</h3><button class="btn btn-ghost btn-icon" onclick="document.getElementById('editModal').classList.remove('open')" style="font-size:18px">×</button></div>
    <form method="POST" id="editForm">
      <?= csrf_field() ?>
      <div class="form-group" style="margin-bottom:12px"><label class="form-label">شركة الشحن</label>
        <select name="carrier_id" id="ecCarrier" class="form-select">
          <?php foreach($carriers as $c): ?><option value="<?=$c['id']?>"><?= e($c['name']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="form-group" style="margin-bottom:12px"><label class="form-label">رقم التتبع</label><input type="text" name="tracking_number" id="ecTracking" class="form-input"></div>
      <div class="form-group" style="margin-bottom:12px"><label class="form-label">الحالة</label>
        <select name="status" id="ecStatus" class="form-select">
          <?php foreach($statusLabels as $k=>$l): ?><option value="<?=$k?>"><?=$l?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="form-group" style="margin-bottom:12px"><label class="form-label">مبلغ COD</label><input type="number" name="cod_amount" id="ecCod" class="form-input" step="0.01" min="0"></div>
      <div class="toggle-wrap" style="margin-bottom:12px"><label class="toggle"><input type="checkbox" name="cod_collected" id="ecCollected" value="1"><span class="toggle-slider"></span></label><span style="font-size:13px">تم تحصيل المبلغ ✅</span></div>
      <div class="form-group" style="margin-bottom:16px"><label class="form-label">ملاحظات</label><textarea name="notes" id="ecNotes" class="form-textarea" rows="2"></textarea></div>
      <div class="modal-actions">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('editModal').classList.remove('open')">إلغاء</button>
        <button type="submit" class="btn btn-primary"><?= svgIcon('check', 15) ?> حفظ</button>
      </div>
    </form>
  </div>
</div>

<?php
$extraScript = "
function editShipment(s){
  document.getElementById('ecCarrier').value = s.carrier_id || '';
  document.getElementById('ecTracking').value = s.tracking_number || '';
  document.getElementById('ecStatus').value = s.status;
  document.getElementById('ecCod').value = s.cod_amount;
  document.getElementById('ecCollected').checked = s.cod_collected == 1;
  document.getElementById('ecNotes').value = s.notes || '';
  document.getElementById('editForm').action = '/$a/shipping/'+s.id;
  document.getElementById('editModal').classList.add('open');
}
";
$content = ob_get_clean();
require APP_PATH . '/Views/admin/layouts/app.php';
