<?php
class SupportAdminController extends Controller
{
    public function __construct() { AdminAuthMiddleware::handle(); requireAdminPermission('support'); }

    public function index(): void
    {
        $status = trim($this->get('status',''));
        $search = trim($this->get('search',''));
        $where = ['1=1']; $params = [];
        if ($status) { $where[] = 't.status=?'; $params[] = $status; }
        if ($search) { $where[] = '(t.ticket_number LIKE ? OR t.name LIKE ? OR t.email LIKE ? OR t.subject LIKE ?)'; $s = "%$search%"; $params = array_merge($params, [$s,$s,$s,$s]); }
        $w = implode(' AND ', $where);

        $tickets = Database::fetchAll("SELECT t.*, a.name assigned_name FROM support_tickets t LEFT JOIN admin_users a ON a.id=t.assigned_to WHERE $w ORDER BY FIELD(t.status,'open','in_progress','resolved','closed'), t.created_at DESC", $params);
        $counts = Database::fetch("SELECT
            SUM(status='open') open_c, SUM(status='in_progress') progress_c,
            SUM(status='resolved') resolved_c, COUNT(*) total_c
            FROM support_tickets");
        $admins = Database::fetchAll("SELECT id,name FROM admin_users WHERE is_active=1 ORDER BY name");
        $this->view('admin.support.index', compact('tickets', 'counts', 'status', 'search', 'admins'));
    }

    public function show(string $id): void
    {
        $ticket = Database::fetch("SELECT * FROM support_tickets WHERE id=?", [(int)$id]);
        if (!$ticket) { flashError('غير موجود'); $this->redirect(adminUrl('support')); }
        $replies = Database::fetchAll("SELECT * FROM support_ticket_replies WHERE ticket_id=? ORDER BY created_at ASC", [(int)$id]);
        $admins = Database::fetchAll("SELECT id,name FROM admin_users WHERE is_active=1 ORDER BY name");
        $this->view('admin.support.show', compact('ticket', 'replies', 'admins'));
    }

    public function reply(string $id): void
    {
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect(adminUrl('support/'.$id)); }
        $message = trim($this->post('message',''));
        if (!$message) { flashError('اكتب رد'); $this->redirect(adminUrl('support/'.$id)); }

        $attachment = null;
        if (!empty($_FILES['attachment']['name'])) {
            $up = uploadFile($_FILES['attachment'], 'support');
            if ($up) $attachment = $up;
        }

        Database::insert(
            "INSERT INTO support_ticket_replies(ticket_id,sender_type,sender_name,message,attachment,created_at) VALUES(?,?,?,?,?,NOW())",
            [(int)$id, 'admin', adminUser()['name'] ?? 'Admin', $message, $attachment]
        );
        Database::execute("UPDATE support_tickets SET status='in_progress' WHERE id=? AND status='open'", [(int)$id]);

        // Best-effort email notification to the customer
        try {
            $ticket = Database::fetch("SELECT * FROM support_tickets WHERE id=?", [(int)$id]);
            if ($ticket && $ticket['email']) {
                EmailService::send('support_ticket_reply', $ticket['email'], $ticket['name'],
                    ['ticket_number'=>$ticket['ticket_number'],'name'=>$ticket['name'],'reply'=>$message], 'support', (int)$id);
            }
        } catch (\Throwable $e) {}

        flashSuccess('تم إرسال الرد ✅');
        $this->redirect(adminUrl('support/'.$id));
    }

    public function updateStatus(string $id): void
    {
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect(adminUrl('support/'.$id)); }
        $status = $this->post('status', 'open');
        $assignedTo = $this->post('assigned_to') ?: null;
        Database::execute("UPDATE support_tickets SET status=?, assigned_to=? WHERE id=?", [$status, $assignedTo, (int)$id]);
        flashSuccess('تم التحديث ✅');
        $this->redirect(adminUrl('support/'.$id));
    }
}
