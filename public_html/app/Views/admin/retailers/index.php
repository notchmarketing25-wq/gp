<?php
$pageTitle  = 'الموزعون المعتمدون';
$breadcrumb = [['label'=>'الموزعون المعتمدون']];
$a = ADMIN_PREFIX;
ob_start();
?>
<div class="ph">
  <div class="ph-left"><h1><span style="display:inline-flex;align-items:center;gap:9px"><?= svgIcon('store', 22) ?> الموزعون المعتمدون (Retailers)</span></h1><p>الأماكن المعتمدة لبيع منتجاتك — تظهر في صفحة عامة وفي فورم تفعيل الضمان</p></div>
  <div class="ph-right">
    <a href="<?= url('retailers') ?>" target="_blank" class="btn btn-secondary">👁️ عرض الصفحة العامة</a>
    <button class="btn btn-primary" onclick="openRetailerModal()">+ إضافة متجر</button>
  </div>
</div>

<div class="card">
  <div class="table-wrap">
    <table>
      <thead><tr><th>الشعار</th><th>الاسم</th><th>النوع</th><th>الهاتف</th><th>المحافظة</th><th>الحالة</th><th></th></tr></thead>
      <tbody>
        <?php if (empty($retailers)): ?>
          <tr><td colspan="7"><div class="empty"><div class="empty-icon"><?= svgIcon('store', 32) ?></div><div class="empty-title">لا يوجد موزعون بعد</div></div></td></tr>
        <?php else: foreach ($retailers as $r): ?>
          <tr>
            <td><?php if($r['logo']): ?><img src="<?= uploadUrl($r['logo']) ?>" style="width:36px;height:36px;object-fit:contain;border-radius:8px;border:1px solid var(--bd)"><?php else: ?><?= svgIcon('store', 20) ?><?php endif; ?></td>
            <td style="font-weight:600"><?= e($r['name']) ?></td>
            <td><span class="badge <?= ($r['type']??'store')==='online'?'badge-purple':'badge-dim' ?>"><?= ($r['type']??'store')==='online' ? 'شريك أونلاين' : 'محل فعلي' ?></span></td>
            <td class="td-dim"><?= e($r['phone']?:'—') ?></td>
            <td class="td-dim"><?= e($r['governorate']?:'—') ?></td>
            <td><span class="badge <?= $r['is_active']?'badge-ok':'badge-dim' ?>"><?= $r['is_active']?'نشط':'مخفي' ?></span></td>
            <td>
              <div style="display:flex;gap:5px;justify-content:flex-end">
                <button class="btn btn-secondary btn-sm" onclick='editRetailer(<?= htmlspecialchars(json_encode($r),ENT_QUOTES) ?>)'>تعديل</button>
                <button class="btn btn-danger btn-sm" onclick="confirmDelete('<?= adminUrl('retailers/'.$r['id'].'/delete') ?>','حذف المتجر؟','')">حذف</button>
              </div>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal-overlay" id="retailerModal">
  <div class="modal">
    <div class="modal-head"><h3 id="rmTitle">+ إضافة متجر</h3><button class="btn btn-ghost btn-icon" onclick="document.getElementById('retailerModal').classList.remove('open')" style="font-size:18px">×</button></div>
    <form method="POST" id="retailerForm" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <div class="form-group" style="margin-bottom:12px">
        <label class="form-label">النوع</label>
        <div style="display:flex;gap:8px">
          <label style="flex:1;display:flex;align-items:center;justify-content:center;gap:8px;background:var(--bg3);border:2px solid var(--bd);border-radius:9px;padding:10px;cursor:pointer">
            <input type="radio" name="type" value="store" id="rmTypeStore" checked style="accent-color:var(--ac)"> <?= svgIcon('store', 15) ?> محل فعلي (على الخريطة)
          </label>
          <label style="flex:1;display:flex;align-items:center;justify-content:center;gap:8px;background:var(--bg3);border:2px solid var(--bd);border-radius:9px;padding:10px;cursor:pointer">
            <input type="radio" name="type" value="online" id="rmTypeOnline" style="accent-color:var(--ac)"> <?= svgIcon('search', 15) ?> شريك بيع أونلاين
          </label>
        </div>
      </div>
      <div class="form-group" style="margin-bottom:12px"><label class="form-label">اسم المتجر <span class="req">*</span></label><input type="text" name="name" id="rmName" class="form-input" required></div>
      <div class="form-group" style="margin-bottom:12px"><label class="form-label">الشعار</label><input type="file" name="logo" class="form-input" accept="image/*" style="padding:6px"></div>
      <div class="form-row-2 form-grid" style="margin-bottom:12px">
        <div class="form-group" style="margin:0"><label class="form-label">الهاتف</label><input type="text" name="phone" id="rmPhone" class="form-input"></div>
        <div class="form-group" style="margin:0"><label class="form-label">المحافظة</label><input type="text" name="governorate" id="rmGov" class="form-input"></div>
      </div>
      <div class="form-group" id="rmAddressWrap" style="margin-bottom:12px"><label class="form-label">العنوان</label><input type="text" name="address" id="rmAddress" class="form-input"></div>
      <div id="rmMapWrap">
        <div class="form-group" style="margin-bottom:12px"><label class="form-label">رابط الموقع على الخريطة (Google Maps)</label><input type="text" name="map_link" id="rmMap" class="form-input" placeholder="https://maps.google.com/..."></div>
        <div class="form-row-2 form-grid" style="margin-bottom:12px">
          <div class="form-group" style="margin:0"><label class="form-label">خط العرض (Latitude)</label><input type="text" name="latitude" id="rmLat" class="form-input" placeholder="30.0444"></div>
          <div class="form-group" style="margin:0"><label class="form-label">خط الطول (Longitude)</label><input type="text" name="longitude" id="rmLng" class="form-input" placeholder="31.2357"></div>
        </div>
        <p style="font-size:11px;color:var(--t3);margin-top:-6px;margin-bottom:12px">افتح المكان على Google Maps، دوس بزرار الفأرة اليمين على النقطة بالظبط، وانسخ الإحداثيات اللي بتظهر (رقمين مفصولين بفاصلة)</p>
      </div>
      <div class="form-group" style="margin-bottom:12px"><label class="form-label">الموقع الإلكتروني (اختياري)</label><input type="text" name="website" id="rmWebsite" class="form-input"></div>
      <div class="form-row-2 form-grid" style="margin-bottom:16px">
        <div class="form-group" style="margin:0"><label class="form-label">الترتيب</label><input type="number" name="sort_order" id="rmSort" class="form-input" value="0"></div>
        <div class="form-group" style="margin:0"><label class="form-label">الحالة</label><div class="toggle-wrap" style="margin-top:8px"><label class="toggle"><input type="checkbox" name="is_active" id="rmActive" value="1" checked><span class="toggle-slider"></span></label><span style="font-size:13px">نشط</span></div></div>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('retailerModal').classList.remove('open')">إلغاء</button>
        <button type="submit" class="btn btn-primary"><?= svgIcon('check', 15) ?> حفظ</button>
      </div>
    </form>
  </div>
</div>

<?php
$extraScript = "
function openRetailerModal(){
  document.getElementById('rmTitle').textContent='+ إضافة متجر';
  document.getElementById('retailerForm').reset();
  document.getElementById('retailerForm').action='/$a/retailers/create';
  toggleRmType();
  document.getElementById('retailerModal').classList.add('open');
}
function toggleRmType(){
  const isOnline = document.getElementById('rmTypeOnline').checked;
  document.getElementById('rmMapWrap').style.display = isOnline ? 'none' : 'block';
  document.getElementById('rmAddressWrap').style.display = isOnline ? 'none' : 'block';
}
document.getElementById('rmTypeStore')?.addEventListener('change', toggleRmType);
document.getElementById('rmTypeOnline')?.addEventListener('change', toggleRmType);
function editRetailer(r){
  document.getElementById('rmTitle').textContent='✏️ تعديل المتجر';
  document.getElementById('retailerForm').action='/$a/retailers/'+r.id;
  document.getElementById('rmName').value=r.name;
  document.getElementById('rmPhone').value=r.phone||'';
  document.getElementById('rmGov').value=r.governorate||'';
  document.getElementById('rmAddress').value=r.address||'';
  document.getElementById('rmMap').value=r.map_link||'';
  document.getElementById('rmLat').value=r.latitude||'';
  document.getElementById('rmLng').value=r.longitude||'';
  document.getElementById('rmWebsite').value=r.website||'';
  document.getElementById('rmSort').value=r.sort_order||0;
  document.getElementById('rmActive').checked=r.is_active==1;
  document.getElementById('rmTypeStore').checked = (r.type||'store')==='store';
  document.getElementById('rmTypeOnline').checked = r.type==='online';
  toggleRmType();
  document.getElementById('retailerModal').classList.add('open');
}
";
$content = ob_get_clean();
require APP_PATH . '/Views/admin/layouts/app.php';
