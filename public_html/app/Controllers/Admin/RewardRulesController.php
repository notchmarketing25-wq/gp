<?php
class RewardRulesController extends Controller
{
    public function __construct() { AdminAuthMiddleware::handle(); requireAdminPermission('settings'); }

    public function index(): void
    {
        $rules = Database::fetchAll("SELECT * FROM reward_rules ORDER BY trigger_type, id");
        $this->view('admin.settings.rewards', compact('rules'));
    }

    public function store(): void
    {
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect(adminUrl('settings/rewards')); }
        $this->save(null);
    }

    public function update(string $id): void
    {
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect(adminUrl('settings/rewards')); }
        $this->save((int)$id);
    }

    private function save(?int $id): void
    {
        $label = trim($this->post('label', ''));
        $trigger = $this->post('trigger_type', 'registration');
        $accountType = $this->post('account_type', 'all');
        $rewardType = $this->post('reward_type', 'fixed');
        $rewardValue = (float)$this->post('reward_value', 0);
        $minOrder = $this->post('min_order_amount') !== '' ? (float)$this->post('min_order_amount') : null;
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if (!$label || $rewardValue <= 0) {
            flashError('اكتب اسم القاعدة وقيمة مكافأة أكبر من صفر');
            $this->redirect(adminUrl('settings/rewards'));
        }
        if (!in_array($trigger, ['registration','per_order'], true)) $trigger = 'registration';
        if (!in_array($accountType, ['all','merchant','distributor','rep','customer'], true)) $accountType = 'all';
        if (!in_array($rewardType, ['fixed','percent'], true)) $rewardType = 'fixed';

        if ($id) {
            Database::execute(
                "UPDATE reward_rules SET label=?,trigger_type=?,account_type=?,reward_type=?,reward_value=?,min_order_amount=?,is_active=? WHERE id=?",
                [$label, $trigger, $accountType, $rewardType, $rewardValue, $minOrder, $isActive, $id]
            );
            flashSuccess('تم تحديث القاعدة ✅');
        } else {
            Database::insert(
                "INSERT INTO reward_rules(label,trigger_type,account_type,reward_type,reward_value,min_order_amount,is_active,created_at) VALUES(?,?,?,?,?,?,?,NOW())",
                [$label, $trigger, $accountType, $rewardType, $rewardValue, $minOrder, $isActive]
            );
            flashSuccess('تم إنشاء القاعدة ✅');
        }
        $this->redirect(adminUrl('settings/rewards'));
    }

    public function toggle(string $id): void
    {
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect(adminUrl('settings/rewards')); }
        Database::execute("UPDATE reward_rules SET is_active = NOT is_active WHERE id=?", [(int)$id]);
        $this->redirect(adminUrl('settings/rewards'));
    }

    public function delete(string $id): void
    {
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect(adminUrl('settings/rewards')); }
        Database::execute("DELETE FROM reward_rules WHERE id=?", [(int)$id]);
        flashSuccess('تم حذف القاعدة');
        $this->redirect(adminUrl('settings/rewards'));
    }
}
