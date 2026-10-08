<?php
class PaymentController extends Controller
{
    public function callback(): void
    {
        $data = array_merge($_GET, $_POST);
        try {
            $result = Fawateerk::verifyCallback($data);
        } catch (\Throwable $e) {
            error_log('[Fawateerk callback] verification threw: ' . $e->getMessage());
            $this->redirect(url('payment/failed'));
        }

        if ($result['success'] && $result['order_number']) {
            Database::execute("UPDATE `orders` SET `payment_status`='paid', `status`='processing', `payment_ref`=? WHERE `order_number`=?",
                [$result['ref'], $result['order_number']]);
            $paidOrder = Database::fetch("SELECT id FROM orders WHERE order_number=?", [$result['order_number']]);
            if ($paidOrder) createIssueVoucherForOrder((int)$paidOrder['id']);
            $this->redirect(url('checkout/success/' . $result['order_number']));
        }

        // Not a confirmed payment — log for later reconciliation instead of failing silently,
        // since a transient network error talking to the gateway would otherwise look identical
        // to a genuinely declined/failed payment.
        error_log('[Fawateerk callback] unverified or failed payment, raw data: ' . json_encode($data));
        $this->redirect(url('payment/failed'));
    }
    public function success(): void { $this->redirect(url()); }
    public function failed(): void
    {
        flashError('فشل الدفع. يرجى المحاولة مرة أخرى أو اختيار الدفع عند الاستلام.');
        $this->redirect(url('checkout'));
    }
}
