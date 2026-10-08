<?php
class BusinessAdminController extends Controller
{
    public function __construct() { AdminAuthMiddleware::handle(); requireAdminPermission('business'); }

    // ══════════════════════════════════════════
    // BUSINESS ACCOUNTS (merchants / distributors / reps)
    // ══════════════════════════════════════════
    public function index(): void
    {
        $type   = $this->get('type', '');
        $status = $this->get('status', 'pending');
        $where = ["c.business_type!='none'"]; $params = [];
        if ($type)   { $where[]='c.business_type=?'; $params[]=$type; }
        if ($status && $status!=='all') { $where[]='c.business_status=?'; $params[]=$status; }
        $w = implode(' AND ', $where);

        $accounts = Database::fetchAll(
            "SELECT c.*, t.name tier_name, r.name rep_name
             FROM customers c
             LEFT JOIN price_tiers t ON t.id=c.price_tier_id
             LEFT JOIN customers r ON r.id=c.parent_rep_id
             WHERE $w ORDER BY c.created_at DESC", $params
        );
        $counts = [
            'pending'  => (int)(Database::fetch("SELECT COUNT(*) c FROM customers WHERE business_type!='none' AND business_status='pending'")['c']??0),
            'approved' => (int)(Database::fetch("SELECT COUNT(*) c FROM customers WHERE business_type!='none' AND business_status='approved'")['c']??0),
        ];
        $tiers = Database::fetchAll("SELECT * FROM price_tiers ORDER BY sort_order");
        $this->view('admin.business.index', compact('accounts','type','status','counts','tiers'));
    }

    public function show(string $id): void
    {
        $account = Database::fetch(
            "SELECT c.*, t.name tier_name, r.name rep_name FROM customers c
             LEFT JOIN price_tiers t ON t.id=c.price_tier_id
             LEFT JOIN customers r ON r.id=c.parent_rep_id
             WHERE c.id=?", [(int)$id]
        );
        if (!$account) { flashError('الحساب غير موجود'); $this->redirect(adminUrl('business')); }
        $orders = Database::fetchAll("SELECT * FROM orders WHERE customer_id=? OR placed_by_business_id=? ORDER BY created_at DESC LIMIT 20", [(int)$id,(int)$id]);
        $tiers  = Database::fetchAll("SELECT * FROM price_tiers ORDER BY sort_order");
        $this->view('admin.business.show', compact('account','orders','tiers'));
    }

    public function approve(string $id): void
    {
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect(adminUrl('business/'.$id)); }

        $customer = Database::fetch("SELECT name,email,business_status,business_type FROM customers WHERE id=?", [(int)$id]);
        if (!$customer) { flashError('الحساب غير موجود'); $this->redirect(adminUrl('business')); }

        $isFirstApproval = $customer['business_status'] !== 'approved';
        Database::execute("UPDATE customers SET business_status='approved' WHERE id=?", [(int)$id]);

        // Apply any active "on registration" reward rules — deposits straight into the wallet
        // (store_credit), configurable from /admin/settings/rewards. Only on first approval, so
        // re-approving an already-approved account never double-pays the reward.
        $rewardGiven = 0;
        if ($isFirstApproval) {
            $rewardGiven = applyRewardRules((int)$id, 'registration', $customer['business_type']);
        }

        try { EmailService::send('business_approved', $customer['email'], $customer['name'], ['name'=>$customer['name']], 'business', (int)$id); } catch (\Throwable $e) {}
        flashSuccess($rewardGiven > 0
            ? 'تم اعتماد الحساب ✅ — وتم إضافة ' . money($rewardGiven) . ' رصيد ترحيبي تلقائياً للمحفظة'
            : 'تم اعتماد الحساب ✅'
        );
        $this->redirect(adminUrl('business/'.$id));
    }

    public function reject(string $id): void
    {
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect(adminUrl('business/'.$id)); }
        Database::execute("UPDATE customers SET business_status='rejected' WHERE id=?", [(int)$id]);
        flashSuccess('تم رفض الحساب');
        $this->redirect(adminUrl('business/'.$id));
    }

    public function assignTier(string $id): void
    {
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect(adminUrl('business/'.$id)); }
        Database::execute("UPDATE customers SET price_tier_id=? WHERE id=?", [(int)$this->post('price_tier_id') ?: null, (int)$id]);
        flashSuccess('تم تحديث الشريحة السعرية ✅');
        $this->redirect(adminUrl('business/'.$id));
    }

    public function setCommission(string $id): void
    {
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect(adminUrl('business/'.$id)); }
        $pct = max(0, min(100, (float)$this->post('commission_percent', 0)));
        Database::execute("UPDATE customers SET commission_percent=? WHERE id=?", [$pct, (int)$id]);
        flashSuccess("تم تحديث نسبة العمولة إلى {$pct}% ✅");
        $this->redirect(adminUrl('business/'.$id));
    }

    public function updateLocation(string $id): void
    {
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect(adminUrl('business/'.$id)); }
        Database::execute(
            "UPDATE customers SET governorate=?, area=?, shop_name=? WHERE id=?",
            [trim($this->post('governorate','')), trim($this->post('area','')), trim($this->post('shop_name','')), (int)$id]
        );
        flashSuccess('تم تحديث بيانات الحساب ✅');
        $this->redirect(adminUrl('business/'.$id));
    }

    public function approveCredit(string $id): void
    {
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect(adminUrl('business/'.$id)); }
        $amount = (float)$this->post('credit_limit', 0);
        Database::execute("UPDATE customers SET credit_status='approved', credit_limit=? WHERE id=?", [$amount, (int)$id]);
        $c = Database::fetch("SELECT name,email FROM customers WHERE id=?", [(int)$id]);
        if ($c) { try { EmailService::send('credit_approved', $c['email'], $c['name'], ['name'=>$c['name'],'credit_limit'=>money($amount)], 'business', (int)$id); } catch (\Throwable $e) {} }
        flashSuccess('تم اعتماد حد الكريدت: ' . money($amount) . ' ✅');
        $this->redirect(adminUrl('business/'.$id));
    }

    public function rejectCredit(string $id): void
    {
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect(adminUrl('business/'.$id)); }
        Database::execute("UPDATE customers SET credit_status='rejected' WHERE id=?", [(int)$id]);
        flashSuccess('تم رفض طلب الكريدت');
        $this->redirect(adminUrl('business/'.$id));
    }

    // ══════════════════════════════════════════
    // PRICE TIERS
    // ══════════════════════════════════════════
    public function tiers(): void
    {
        $tiers = Database::fetchAll("SELECT t.*, (SELECT COUNT(*) FROM customers c WHERE c.price_tier_id=t.id) accounts_count FROM price_tiers t ORDER BY t.sort_order");
        $this->view('admin.business.tiers', compact('tiers'));
    }

    public function storeTier(): void
    {
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect(adminUrl('business/tiers')); }
        if (isset($_POST['is_default'])) Database::execute("UPDATE price_tiers SET is_default=0");
        Database::insert(
            "INSERT INTO price_tiers(name,discount_percent,min_purchase_auto,is_default,sort_order,created_at) VALUES(?,?,?,?,?,NOW())",
            [trim($this->post('name','')), (float)$this->post('discount_percent',0), $this->post('min_purchase_auto') ?: null, isset($_POST['is_default'])?1:0, (int)$this->post('sort_order',0)]
        );
        flashSuccess('تم إضافة الشريحة السعرية ✅');
        $this->redirect(adminUrl('business/tiers'));
    }

    public function updateTier(string $id): void
    {
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect(adminUrl('business/tiers')); }
        if (isset($_POST['is_default'])) Database::execute("UPDATE price_tiers SET is_default=0");
        Database::execute(
            "UPDATE price_tiers SET name=?, discount_percent=?, min_purchase_auto=?, is_default=?, sort_order=? WHERE id=?",
            [trim($this->post('name','')), (float)$this->post('discount_percent',0), $this->post('min_purchase_auto') ?: null, isset($_POST['is_default'])?1:0, (int)$this->post('sort_order',0), (int)$id]
        );
        flashSuccess('تم تحديث الشريحة ✅');
        $this->redirect(adminUrl('business/tiers'));
    }

    public function deleteTier(string $id): void
    {
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect(adminUrl('business/tiers')); }
        Database::execute("UPDATE customers SET price_tier_id=NULL WHERE price_tier_id=?", [(int)$id]);
        Database::execute("DELETE FROM price_tiers WHERE id=?", [(int)$id]);
        flashSuccess('تم حذف الشريحة');
        $this->redirect(adminUrl('business/tiers'));
    }

    // ══════════════════════════════════════════
    // REP EXPENSES
    // ══════════════════════════════════════════
    public function expenses(): void
    {
        $status = $this->get('status', 'pending');
        $where = '1=1'; $params = [];
        if ($status && $status!=='all') { $where = 'e.status=?'; $params[] = $status; }
        $expenses = Database::fetchAll("SELECT e.*, c.name rep_name FROM rep_expenses e JOIN customers c ON c.id=e.rep_id WHERE $where ORDER BY e.created_at DESC", $params);
        $this->view('admin.business.expenses', compact('expenses','status'));
    }

    public function expenseDecide(string $id): void
    {
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect(adminUrl('business/expenses')); }
        $decision = $this->post('decision','approved');
        Database::execute("UPDATE rep_expenses SET status=?, admin_notes=? WHERE id=?", [$decision, trim($this->post('admin_notes','')), (int)$id]);
        flashSuccess($decision==='approved' ? 'تم اعتماد المصروف ✅' : 'تم رفض المصروف');
        $this->redirect(adminUrl('business/expenses'));
    }

    // ══════════════════════════════════════════
    // SAMPLE REQUESTS
    // ══════════════════════════════════════════
    public function samples(): void
    {
        $status = $this->get('status', 'pending');
        $where = '1=1'; $params = [];
        if ($status && $status!=='all') { $where = 'sr.status=?'; $params[] = $status; }
        $samples = Database::fetchAll("SELECT sr.*, c.name rep_name, p.name product_name FROM sample_requests sr JOIN customers c ON c.id=sr.rep_id JOIN products p ON p.id=sr.product_id WHERE $where ORDER BY sr.created_at DESC", $params);
        $this->view('admin.business.samples', compact('samples','status'));
    }

    public function sampleDecide(string $id): void
    {
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect(adminUrl('business/samples')); }
        $decision = $this->post('decision','approved');
        Database::execute("UPDATE sample_requests SET status=?, admin_notes=? WHERE id=?", [$decision, trim($this->post('admin_notes','')), (int)$id]);
        flashSuccess('تم تحديث حالة الطلب ✅');
        $this->redirect(adminUrl('business/samples'));
    }
}
