<?php
$pageTitle  = 'مستخدمو الأدمن';
$breadcrumb = [['label'=>'مستخدمو الأدمن']];
$a = ADMIN_PREFIX;
ob_start();
?>
<div class="ph">
  <div class="ph-left"><h1><span style="display:inline-flex;align-items:center;gap:9px"><?= svgIcon('user', 22) ?> مستخدمو الأدمن</span></h1><p>إدارة حسابات فريقك وصلاحيات كل واحد فيهم</p></div>
  <div class="ph-right"><button class="btn btn-primary" onclick="openAddUser()">+ إضافة مستخدم</button></div>
</div>

<div class="card">
  <div class="table-wrap">
    <table>
      <thead><tr><th>الاسم</th><th>البريد الإلكتروني</th><th>الدور</th><th>آخر دخول</th><th>الحالة</th><th></th></tr></thead>
      <tbody>
        <?php if (empty($users)): ?>
          <tr><td colspan="6"><div class="empty"><div class="empty-icon"><?= svgIcon('user', 32) ?></div><div class="empty-title">لا يوجد مستخدمون</div></div></td></tr>
        <?php else: foreach ($users as $u): ?>
          <tr>
            <td style="font-weight:600"><?= e($u['name']) ?></td>
            <td class="td-dim"><?= e($u['email']) ?></td>
            <td>
              <span class="badge <?= $u['role']==='superadmin'?'badge-purple':($u['role']==='admin'?'badge-ok':'badge-dim') ?>">
                <?= ['superadmin'=>'مدير عام','admin'=>'أدمن','staff'=>'موظف'][$u['role']] ?? $u['role'] ?>
              </span>
            </td>
            <td class="td-dim" style="font-size:12px"><?= $u['last_login'] ? formatDateTime($u['last_login']) : 'لم يسجل دخول بعد' ?></td>
            <td><span class="badge <?= $u['is_active']?'badge-ok':'badge-dim' ?>"><?= $u['is_active']?'نشط':'موقوف' ?></span></td>
            <td>
              <div style="display:flex;gap:5px;justify-content:flex-end">
                <button class="btn btn-secondary btn-sm" onclick='editUser(<?= htmlspecialchars(json_encode($u),ENT_QUOTES) ?>)'>تعديل</button>
                <?php if ((int)$u['id'] !== (int)(adminUser()['id']??0)): ?>
                  <button class="btn btn-danger btn-sm" onclick="confirmDelete('<?= adminUrl('users/'.$u['id'].'/delete') ?>','حذف المستخدم؟','')">حذف</button>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal-overlay" id="userModal">
  <div class="modal modal-lg">
    <div class="modal-head"><h3 id="umTitle">+ إضافة مستخدم</h3><button class="btn btn-ghost btn-icon" onclick="document.getElementById('userModal').classList.remove('open')" style="font-size:18px">×</button></div>
    <form method="POST" id="userForm">
      <?= csrf_field() ?>
      <div class="form-group" style="margin-bottom:12px"><label class="form-label">الاسم <span class="req">*</span></label><input type="text" name="name" id="umName" class="form-input" required></div>
      <div class="form-group" style="margin-bottom:12px"><label class="form-label">البريد الإلكتروني <span class="req">*</span></label><input type="email" name="email" id="umEmail" class="form-input" required></div>
      <div class="form-group" style="margin-bottom:12px"><label class="form-label" id="umPassLabel">كلمة المرور <span class="req">*</span></label><input type="password" name="password" id="umPassword" class="form-input" placeholder="اتركه فارغاً للإبقاء على القديمة (عند التعديل)"></div>
      <div class="form-group" style="margin-bottom:12px">
        <label class="form-label">الدور</label>
        <select name="role" id="umRole" class="form-select" onchange="toggleUmPerms()">
          <option value="staff">موظف</option>
          <option value="admin">أدمن</option>
          <option value="superadmin">مدير عام (صلاحية كاملة)</option>
        </select>
      </div>
      <div class="form-group" id="umPermsWrap" style="margin-bottom:16px">
        <label class="form-label">الصلاحيات (حدد بالضبط إيه المسموح بيه في كل قسم)</label>
        <div class="table-wrap" style="margin-top:8px;border:1px solid var(--bd);border-radius:10px">
          <table>
            <thead><tr><th>القسم</th><?php foreach (adminPermissionActions() as $ak=>$al): ?><th style="text-align:center"><?= $al ?></th><?php endforeach; ?></tr></thead>
            <tbody>
              <?php foreach ($modules as $mKey => $mMeta): ?>
                <tr>
                  <td style="font-weight:600;font-size:12.5px"><?= e($mMeta['label']) ?></td>
                  <?php foreach (adminPermissionActions() as $ak=>$al): ?>
                    <td style="text-align:center">
                      <?php if ($ak === 'view' || $mMeta['actions']): ?>
                        <input type="checkbox" name="permissions[<?= $mKey ?>][]" value="<?= $ak ?>" class="um-perm-cb" data-module="<?= $mKey ?>" data-action="<?= $ak ?>" style="accent-color:var(--ac);cursor:pointer;width:15px;height:15px" onchange="umPermChanged(this)">
                      <?php else: ?>
                        <span style="color:var(--t3)">—</span>
                      <?php endif; ?>
                    </td>
                  <?php endforeach; ?>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <p style="font-size:11px;color:var(--t3);margin-top:6px">لازم "عرض" مفعّلة عشان أي صلاحية تانية في نفس القسم تشتغل.</p>
      </div>
      <div class="toggle-wrap" style="margin-bottom:16px"><label class="toggle"><input type="checkbox" name="is_active" id="umActive" value="1" checked><span class="toggle-slider"></span></label><span style="font-size:13px">نشط</span></div>
      <div class="modal-actions">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('userModal').classList.remove('open')">إلغاء</button>
        <button type="submit" class="btn btn-primary">💾 حفظ</button>
      </div>
    </form>
  </div>
</div>

<?php
$extraScript = "
function toggleUmPerms(){
  document.getElementById('umPermsWrap').style.display = document.getElementById('umRole').value==='superadmin' ? 'none' : 'block';
}
// If 'view' gets unchecked, uncheck the other actions in that module (they're meaningless without view).
// If any other action gets checked, auto-check 'view' for that module.
function umPermChanged(cb){
  const mod = cb.dataset.module, action = cb.dataset.action;
  if (action === 'view' && !cb.checked) {
    document.querySelectorAll('.um-perm-cb[data-module=\\\"'+mod+'\\\"]').forEach(o => { if(o.dataset.action!=='view') o.checked = false; });
  } else if (action !== 'view' && cb.checked) {
    const viewCb = document.querySelector('.um-perm-cb[data-module=\\\"'+mod+'\\\"][data-action=\\\"view\\\"]');
    if (viewCb) viewCb.checked = true;
  }
}
function openAddUser(){
  document.getElementById('umTitle').textContent='+ إضافة مستخدم';
  document.getElementById('userForm').reset();
  document.getElementById('userForm').action='/$a/users/create';
  document.getElementById('umPassword').required = true;
  document.getElementById('umPassLabel').innerHTML = 'كلمة المرور <span class=\\\"req\\\">*</span>';
  toggleUmPerms();
  document.getElementById('userModal').classList.add('open');
}
function editUser(u){
  document.getElementById('umTitle').textContent='✏️ تعديل المستخدم';
  document.getElementById('userForm').action='/$a/users/'+u.id;
  document.getElementById('umName').value=u.name;
  document.getElementById('umEmail').value=u.email;
  document.getElementById('umPassword').required = false;
  document.getElementById('umPassLabel').textContent = 'كلمة المرور (اختياري)';
  document.getElementById('umRole').value=u.role;
  document.getElementById('umActive').checked = u.is_active==1;
  document.querySelectorAll('.um-perm-cb').forEach(cb=>cb.checked=false);
  try {
    const perms = JSON.parse(u.permissions || '{}');
    if (Array.isArray(perms)) {
      // Old flat format — check every action for the listed modules (full access)
      perms.forEach(mod => { document.querySelectorAll('.um-perm-cb[data-module=\\\"'+mod+'\\\"]').forEach(cb=>cb.checked=true); });
    } else {
      Object.keys(perms).forEach(mod => {
        (perms[mod]||[]).forEach(action => {
          const cb = document.querySelector('.um-perm-cb[data-module=\\\"'+mod+'\\\"][data-action=\\\"'+action+'\\\"]');
          if (cb) cb.checked = true;
        });
      });
    }
  } catch(e){}
  toggleUmPerms();
  document.getElementById('userModal').classList.add('open');
}
";
$content = ob_get_clean();
require APP_PATH . '/Views/admin/layouts/app.php';
