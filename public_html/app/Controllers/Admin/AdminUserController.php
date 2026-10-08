<?php
class AdminUserController extends Controller
{
    public function __construct()
    {
        AdminAuthMiddleware::handle();
        // Only superadmin can manage other admin users
        if ((adminUser()['role'] ?? '') !== 'superadmin') {
            flashError('صلاحية إدارة المستخدمين للمدير العام فقط');
            $this->redirect(adminUrl());
        }
    }

    public function index(): void
    {
        $users = Database::fetchAll("SELECT id,name,email,role,permissions,is_active,last_login,created_at FROM admin_users ORDER BY created_at DESC");
        $modules = adminPermissionModules();
        $this->view('admin.users.index', compact('users', 'modules'));
    }

    public function store(): void
    {
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect(adminUrl('users')); }
        $email = trim($this->post('email',''));
        $exists = Database::fetch("SELECT id FROM admin_users WHERE email=?", [$email]);
        if ($exists) { flashError('البريد الإلكتروني مستخدم بالفعل'); $this->redirect(adminUrl('users')); }

        $role = $this->post('role', 'staff');
        $perms = $role === 'superadmin' ? null : json_encode($this->buildPermissions());

        Database::insert(
            "INSERT INTO admin_users(name,email,password,role,permissions,is_active,created_at) VALUES(?,?,?,?,?,?,NOW())",
            [
                trim($this->post('name','')), $email,
                password_hash($this->post('password',''), PASSWORD_DEFAULT),
                $role, $perms, isset($_POST['is_active'])?1:0,
            ]
        );
        flashSuccess('تم إضافة المستخدم ✅');
        $this->redirect(adminUrl('users'));
    }

    /** Reads permissions[module][] = action checkboxes from POST into {module: [actions]}.
     *  Any action other than 'view' is meaningless without 'view' also being granted, so we drop
     *  modules that got checked actions but not 'view' itself (defense against a tampered request). */
    private function buildPermissions(): array {
        $modules = adminPermissionModules();
        $result = [];
        foreach ($modules as $key => $meta) {
            $actions = array_values(array_intersect($_POST['permissions'][$key] ?? [], array_keys(adminPermissionActions())));
            if (!empty($actions) && in_array('view', $actions, true)) {
                $result[$key] = $actions;
            }
        }
        return $result;
    }

    public function update(string $id): void
    {
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect(adminUrl('users')); }
        $user = Database::fetch("SELECT * FROM admin_users WHERE id=?", [(int)$id]);
        if (!$user) { flashError('غير موجود'); $this->redirect(adminUrl('users')); }

        // Prevent locking yourself out or demoting the last superadmin accidentally
        $role = $this->post('role', $user['role']);
        $perms = $role === 'superadmin' ? null : json_encode($this->buildPermissions());

        $sql = "UPDATE admin_users SET name=?,email=?,role=?,permissions=?,is_active=? WHERE id=?";
        $params = [trim($this->post('name','')), trim($this->post('email','')), $role, $perms, isset($_POST['is_active'])?1:0, (int)$id];

        if ($this->post('password')) {
            $sql = "UPDATE admin_users SET name=?,email=?,role=?,permissions=?,is_active=?,password=? WHERE id=?";
            $params = [trim($this->post('name','')), trim($this->post('email','')), $role, $perms, isset($_POST['is_active'])?1:0, password_hash($this->post('password',''), PASSWORD_DEFAULT), (int)$id];
        }
        Database::execute($sql, $params);
        flashSuccess('تم التحديث ✅');
        $this->redirect(adminUrl('users'));
    }

    public function delete(string $id): void
    {
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect(adminUrl('users')); }
        if ((int)$id === (int)(adminUser()['id'] ?? 0)) {
            flashError('لا يمكنك حذف حسابك الخاص');
            $this->redirect(adminUrl('users'));
        }
        Database::execute("DELETE FROM admin_users WHERE id=?", [(int)$id]);
        flashSuccess('تم الحذف');
        $this->redirect(adminUrl('users'));
    }
}
