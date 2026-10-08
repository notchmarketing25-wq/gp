<?php
class EmailAdminController extends Controller
{
    public function __construct() { AdminAuthMiddleware::handle(); requireAdminPermission('settings'); }

    public function templates(): void
    {
        $templates = Database::fetchAll("SELECT * FROM email_templates ORDER BY name");
        $this->view('admin.emails.templates', compact('templates'));
    }

    public function editTemplate(string $id): void
    {
        $tpl = Database::fetch("SELECT * FROM email_templates WHERE id=?", [(int)$id]);
        if (!$tpl) { flashError('القالب غير موجود'); $this->redirect(adminUrl('emails/templates')); }
        $this->view('admin.emails.template-edit', compact('tpl'));
    }

    public function updateTemplate(string $id): void
    {
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect(adminUrl('emails/templates')); }
        Database::execute(
            "UPDATE email_templates SET name=?, subject=?, html_body=?, is_active=? WHERE id=?",
            [trim($this->post('name','')), trim($this->post('subject','')), $this->post('html_body',''), isset($_POST['is_active'])?1:0, (int)$id]
        );
        flashSuccess('تم حفظ القالب ✅');
        $this->redirect(adminUrl('emails/templates/'.$id.'/edit'));
    }

    public function sendTest(string $id): void
    {
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect(adminUrl('emails/templates/'.$id.'/edit')); }
        $tpl = Database::fetch("SELECT key_name FROM email_templates WHERE id=?", [(int)$id]);
        if (!$tpl) { flashError('القالب غير موجود'); $this->redirect(adminUrl('emails/templates')); }
        $to = trim($this->post('test_email',''));
        $result = EmailService::sendTest($tpl['key_name'], $to);
        if ($result['ok']) flashSuccess("تم إرسال إيميل تجريبي إلى $to ✅");
        else flashError('فشل الإرسال: ' . ($result['error'] ?? 'خطأ غير معروف'));
        $this->redirect(adminUrl('emails/templates/'.$id.'/edit'));
    }

    // ── Email logs / delivery tracking ───────────────────────────────
    public function logs(): void
    {
        $status = $this->get('status', '');
        $search = trim($this->get('search', ''));
        $where = ['1=1']; $params = [];
        if ($status) { $where[] = 'status=?'; $params[] = $status; }
        if ($search) { $where[] = '(to_email LIKE ? OR to_name LIKE ? OR subject LIKE ?)'; $params = array_merge($params, ["%$search%","%$search%","%$search%"]); }
        $w = implode(' AND ', $where);

        $logs = Database::fetchAll("SELECT * FROM email_logs WHERE $w ORDER BY created_at DESC LIMIT 300", $params);
        $stats = [
            'total'  => (int)(Database::fetch("SELECT COUNT(*) c FROM email_logs")['c'] ?? 0),
            'sent'   => (int)(Database::fetch("SELECT COUNT(*) c FROM email_logs WHERE status='sent'")['c'] ?? 0),
            'failed' => (int)(Database::fetch("SELECT COUNT(*) c FROM email_logs WHERE status='failed'")['c'] ?? 0),
        ];
        $this->view('admin.emails.logs', compact('logs','stats','status','search'));
    }

    // ── SMTP settings ─────────────────────────────────────────────────
    public function settings(): void
    {
        $s = SettingModel::getAllSettings();
        $this->view('admin.emails.settings', compact('s'));
    }

    public function updateSettings(): void
    {
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect(adminUrl('emails/settings')); }
        SettingModel::setMany([
            'smtp_enabled'     => isset($_POST['smtp_enabled']) ? '1' : '0',
            'smtp_host'        => trim($this->post('smtp_host','')),
            'smtp_port'        => trim($this->post('smtp_port','587')),
            'smtp_username'    => trim($this->post('smtp_username','')),
            'smtp_password'    => $this->post('smtp_password','') ?: SettingModel::get('smtp_password',''),
            'smtp_encryption'  => $this->post('smtp_encryption','tls'),
            'smtp_from_email'  => trim($this->post('smtp_from_email','')),
            'smtp_from_name'   => trim($this->post('smtp_from_name','')),
            'email_notifications' => isset($_POST['email_notifications']) ? '1' : '0',
        ]);
        flashSuccess('تم حفظ إعدادات البريد ✅');
        $this->redirect(adminUrl('emails/settings'));
    }
}
