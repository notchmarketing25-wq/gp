<?php
class AdminBulkController extends Controller
{
    public function __construct() { AdminAuthMiddleware::handle(); requireAdminPermission('business'); }

    // ── Bulk Orders ───────────────────────────────────────────────
    public function orders(): void
    {
        $status  = trim($this->get('status',''));
        $payment = trim($this->get('payment',''));
        $from    = trim($this->get('from',''));
        $to      = trim($this->get('to',''));
        $search  = trim($this->get('search',''));

        $where  = ['1=1']; $params = [];
        if ($status)  { $where[] = "status=?";         $params[] = $status; }
        if ($payment) { $where[] = "payment_status=?"; $params[] = $payment; }
        if ($from)    { $where[] = "DATE(created_at)>=?"; $params[] = $from; }
        if ($to)      { $where[] = "DATE(created_at)<=?"; $params[] = $to; }
        if ($search)  { $where[] = "(order_number LIKE ? OR customer_name LIKE ? OR customer_phone LIKE ?)";
                        $params = array_merge($params,["%$search%","%$search%","%$search%"]); }

        $w = implode(' AND ', $where);
        $orders = Database::fetchAll("SELECT * FROM orders WHERE $w ORDER BY created_at DESC LIMIT 200", $params);
        $this->view('admin.bulk.orders', compact('orders','status','payment','from','to','search'));
    }

    public function ordersAction(): void
    {
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect(adminUrl('bulk/orders')); }
        $ids    = array_map('intval', $_POST['ids'] ?? []);
        $action = $this->post('action','');
        if (empty($ids) || !$action) { flashError('اختر طلبات وإجراء'); $this->redirect(adminUrl('bulk/orders')); }

        $count = 0;
        foreach ($ids as $id) {
            switch ($action) {
                case 'mark_processing':
                    Database::execute("UPDATE orders SET status='processing',updated_at=NOW() WHERE id=?",[$id]); $count++; break;
                case 'mark_shipped':
                    Database::execute("UPDATE orders SET status='shipped',shipped_at=NOW(),updated_at=NOW() WHERE id=?",[$id]); $count++; break;
                case 'mark_delivered':
                    Database::execute("UPDATE orders SET status='delivered',delivered_at=NOW(),updated_at=NOW() WHERE id=?",[$id]); $count++; break;
                case 'mark_cancelled':
                    Database::execute("UPDATE orders SET status='cancelled',updated_at=NOW() WHERE id=?",[$id]); $count++; break;
                case 'mark_paid':
                    Database::execute("UPDATE orders SET payment_status='paid',updated_at=NOW() WHERE id=?",[$id]); $count++; break;
                case 'export_csv':
                    // handled below
                    break;
            }
        }

        if ($action === 'export_csv') {
            $this->exportOrdersCsv($ids);
        }

        flashSuccess("تم تطبيق الإجراء على $count طلب");
        $this->redirect(adminUrl('bulk/orders'));
    }

    private function exportOrdersCsv(array $ids): never
    {
        $ph  = implode(',', array_fill(0, count($ids), '?'));
        $rows = Database::fetchAll("SELECT o.*,GROUP_CONCAT(oi.name SEPARATOR '|') items FROM orders o LEFT JOIN order_items oi ON oi.order_id=o.id WHERE o.id IN ($ph) GROUP BY o.id ORDER BY o.created_at DESC", $ids);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment;filename=orders_'.date('Ymd_His').'.csv');
        $f = fopen('php://output','w');
        fprintf($f, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
        fputcsv($f,['رقم الطلب','العميل','الهاتف','البريد','المبلغ','الشحن','الإجمالي','حالة الطلب','حالة الدفع','طريقة الدفع','المحافظة','المدينة','التاريخ','المنتجات']);
        foreach ($rows as $r) {
            fputcsv($f,[
                $r['order_number'],$r['customer_name'],$r['customer_phone'],$r['customer_email'],
                $r['subtotal'],$r['shipping_price'],$r['total'],
                orderStatusLabel($r['status']),paymentStatusLabel($r['payment_status']),$r['payment_method'],
                $r['shipping_gov'],$r['shipping_city'],
                $r['created_at'],$r['items']??''
            ]);
        }
        fclose($f);
        exit;
    }

    // ── Bulk Customers ────────────────────────────────────────────
    public function customers(): void
    {
        $status = trim($this->get('status',''));
        $search = trim($this->get('search',''));
        $from   = trim($this->get('from',''));

        $where = ['1=1']; $params = [];
        if ($status !== '') { $where[]="is_active=?"; $params[]=(int)$status; }
        if ($search)        { $where[]="(name LIKE ? OR email LIKE ? OR phone LIKE ?)"; $params=array_merge($params,["%$search%","%$search%","%$search%"]); }
        if ($from)          { $where[]="DATE(created_at)>=?"; $params[]=$from; }

        $w = implode(' AND ',$where);
        $customers = Database::fetchAll("
            SELECT c.*, COUNT(o.id) orders_count, COALESCE(SUM(o.total),0) orders_total
            FROM customers c LEFT JOIN orders o ON o.customer_id=c.id
            WHERE $w GROUP BY c.id ORDER BY c.created_at DESC LIMIT 200
        ", $params);

        $this->view('admin.bulk.customers', compact('customers','status','search','from'));
    }

    public function customersAction(): void
    {
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect(adminUrl('bulk/customers')); }
        $ids    = array_map('intval', $_POST['ids'] ?? []);
        $action = $this->post('action','');
        if (empty($ids) || !$action) { flashError('اختر عملاء وإجراء'); $this->redirect(adminUrl('bulk/customers')); }

        $count = 0;
        if ($action === 'export_csv') {
            $this->exportCustomersCsv($ids);
        }
        $customerModel = new CustomerModel();
        $creditAmount  = (float)$this->post('credit_amount', 0);
        $creditReason  = trim($this->post('credit_reason', 'إضافة جماعية'));
        $admin = adminUser();

        foreach ($ids as $id) {
            switch ($action) {
                case 'activate':   Database::execute("UPDATE customers SET is_active=1 WHERE id=?",[$id]); $count++; break;
                case 'deactivate': Database::execute("UPDATE customers SET is_active=0 WHERE id=?",[$id]); $count++; break;
                case 'add_credit':
                    if ($creditAmount > 0) { $customerModel->adjustCredit($id, $creditAmount, $creditReason, null, $admin['id'] ?? null); $count++; }
                    break;
                case 'deduct_credit':
                    if ($creditAmount > 0) { $customerModel->adjustCredit($id, -$creditAmount, $creditReason, null, $admin['id'] ?? null); $count++; }
                    break;
            }
        }
        flashSuccess("تم تطبيق الإجراء على $count عميل");
        $this->redirect(adminUrl('bulk/customers'));
    }

    private function exportCustomersCsv(array $ids): never
    {
        $ph  = implode(',', array_fill(0, count($ids), '?'));
        $rows = Database::fetchAll("SELECT c.*,COUNT(o.id) orders_count,COALESCE(SUM(o.total),0) orders_total FROM customers c LEFT JOIN orders o ON o.customer_id=c.id WHERE c.id IN ($ph) GROUP BY c.id", $ids);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment;filename=customers_'.date('Ymd_His').'.csv');
        $f = fopen('php://output','w');
        fprintf($f, chr(0xEF).chr(0xBB).chr(0xBF));
        fputcsv($f,['الاسم','البريد','الهاتف','الطلبات','الإجمالي','الحالة','تاريخ التسجيل']);
        foreach ($rows as $r) {
            fputcsv($f,[$r['name'],$r['email'],$r['phone']??'',$r['orders_count'],$r['orders_total'],$r['is_active']?'نشط':'موقوف',$r['created_at']]);
        }
        fclose($f);
        exit;
    }
}
