<?php
class SupportController extends Controller
{
    // ── Public support / help center page ─────────────────────────
    public function index(): void
    {
        $faqs = Database::fetchAll("SELECT * FROM support_faqs WHERE is_active=1 ORDER BY sort_order, id");
        $faqsByCategory = [];
        foreach ($faqs as $f) { $faqsByCategory[$f['category']][] = $f; }
        $this->view('store.pages.support', compact('faqsByCategory'));
    }

    public function submit(): void
    {
        if (!verifyCsrf()) { flashError('خطأ في التحقق'); $this->redirect(url('support')); }

        $name = trim($this->post('name',''));
        $email = trim($this->post('email',''));
        $subject = trim($this->post('subject',''));
        $message = trim($this->post('message',''));
        if (!$name || !$email || !$subject || !$message) {
            flashError('من فضلك املأ كل الحقول المطلوبة');
            $this->redirect(url('support'));
        }

        $attachment = null;
        if (!empty($_FILES['attachment']['name'])) {
            $up = uploadFile($_FILES['attachment'], 'support');
            if ($up) $attachment = $up;
        }

        $ticketNumber = 'TKT-' . strtoupper(substr(uniqid(), -6));
        $customerId = isStoreLoggedIn() ? storeUser()['id'] : null;

        Database::insert(
            "INSERT INTO support_tickets(ticket_number,customer_id,name,email,phone,subject,category,order_number,message,attachment,status,created_at)
             VALUES(?,?,?,?,?,?,?,?,?,?,'open',NOW())",
            [
                $ticketNumber, $customerId, $name, $email,
                trim($this->post('phone','')) ?: null, $subject,
                $this->post('category','general'), trim($this->post('order_number','')) ?: null,
                $message, $attachment,
            ]
        );

        // Notify the store owner (best-effort, doesn't block the customer if it fails)
        try {
            $adminEmail = SettingModel::get('store_email') ?: SettingModel::get('contact_email');
            if ($adminEmail) {
                EmailService::send('support_ticket_new', $adminEmail, 'Admin',
                    ['ticket_number'=>$ticketNumber,'name'=>$name,'subject'=>$subject,'message'=>$message], 'support');
            }
        } catch (\Throwable $e) {}

        flashSuccess("تم إرسال طلبك بنجاح ✅ — رقم التذكرة: {$ticketNumber}. هنتواصل معاك في أقرب وقت.");
        $this->redirect(url('support'));
    }

    // ── Track a ticket by number (public) ─────────────────────────
    public function track(): void
    {
        $number = trim($this->get('ticket', ''));
        $ticket = null; $replies = [];
        if ($number) {
            $ticket = Database::fetch("SELECT * FROM support_tickets WHERE ticket_number=?", [$number]);
            if ($ticket) {
                $replies = Database::fetchAll("SELECT * FROM support_ticket_replies WHERE ticket_id=? ORDER BY created_at ASC", [$ticket['id']]);
            }
        }
        $this->view('store.pages.support-track', compact('ticket', 'replies', 'number'));
    }
}
