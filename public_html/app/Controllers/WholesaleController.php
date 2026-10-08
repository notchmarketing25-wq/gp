<?php
class WholesaleController extends Controller
{
    // ── Application page (view status or apply) ────────────────────
    public function apply(): void
    {
        $customer = null;
        if (isStoreLoggedIn()) {
            $user     = storeUser();
            $customer = (new CustomerModel())->find($user['id']);
        }
        $this->view('store.pages.wholesale-apply', compact('customer'));
    }

    public function submitApplication(): void
    {
        if (!isStoreLoggedIn()) { flashError('يجب تسجيل الدخول'); $this->redirect(url('login')); }
        if (!verifyCsrf()) { flashError('خطأ في التحقق'); $this->redirect(url('wholesale')); }

        $user = storeUser();
        (new CustomerModel())->update($user['id'], [
            'account_type'          => 'wholesale',
            'wholesale_status'      => 'pending',
            'company_name'          => trim($this->post('company_name','')),
            'company_address'       => trim($this->post('company_address','')),
            'tax_number'            => trim($this->post('tax_number','')),
            'wholesale_notes'       => trim($this->post('notes','')),
            'wholesale_applied_at'  => date('Y-m-d H:i:s'),
        ]);

        flashSuccess('تم إرسال طلبك بنجاح! سنراجعه ونتواصل معك قريباً 🎉');
        $this->redirect(url('wholesale'));
    }
}
