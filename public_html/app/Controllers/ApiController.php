<?php
/**
 * Public REST API — protected by an API key (see /admin/settings/api). External systems
 * (accountant tools, dashboards, custom integrations) can pull a snapshot of account,
 * financial, order, and shipment data. Read-only by design: no endpoint here can create,
 * modify, or delete anything, to limit the blast radius of a leaked key.
 */
class ApiController extends Controller
{
    private function authenticate(): ?array
    {
        $key = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        $key = preg_replace('/^Bearer\s+/i', '', trim($key));
        if (!$key) $key = $this->get('api_key', '');

        if (!$key) return null;
        $row = Database::fetch("SELECT * FROM api_keys WHERE api_key=? AND is_active=1", [$key]);
        if ($row) {
            Database::execute("UPDATE api_keys SET last_used_at=NOW() WHERE id=?", [$row['id']]);
        }
        return $row ?: null;
    }

    protected function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    private function requireAuth(): void
    {
        if (!$this->authenticate()) {
            $this->json(['error' => 'Unauthorized — pass a valid API key via "Authorization: Bearer {key}" header or ?api_key='], 401);
        }
    }

    /** GET /api/v1/summary — the main "everything at a glance" endpoint */
    public function summary(): void
    {
        $this->requireAuth();

        $accounts = [
            'total_customers'  => (int)(Database::fetch("SELECT COUNT(*) c FROM customers")['c'] ?? 0),
            'business_accounts'=> (int)(Database::fetch("SELECT COUNT(*) c FROM customers WHERE business_type!='none'")['c'] ?? 0),
            'pending_approval' => (int)(Database::fetch("SELECT COUNT(*) c FROM customers WHERE business_type!='none' AND business_status='pending'")['c'] ?? 0),
        ];

        $financial = [
            'total_revenue'    => (float)(Database::fetch("SELECT COALESCE(SUM(total),0) v FROM orders WHERE payment_status='paid'")['v'] ?? 0),
            'this_month_revenue' => (float)(Database::fetch("SELECT COALESCE(SUM(total),0) v FROM orders WHERE payment_status='paid' AND created_at>=DATE_FORMAT(NOW(),'%Y-%m-01')")['v'] ?? 0),
            'pending_cod'      => (float)(Database::fetch("SELECT COALESCE(SUM(cod_amount),0) v FROM shipments WHERE cod_collected=0 AND cod_amount>0")['v'] ?? 0),
            'total_credit_issued' => (float)(Database::fetch("SELECT COALESCE(SUM(credit_limit),0) v FROM customers WHERE credit_status='approved'")['v'] ?? 0),
        ];

        $orders = [
            'total'      => (int)(Database::fetch("SELECT COUNT(*) c FROM orders")['c'] ?? 0),
            'pending'    => (int)(Database::fetch("SELECT COUNT(*) c FROM orders WHERE status='pending'")['c'] ?? 0),
            'processing' => (int)(Database::fetch("SELECT COUNT(*) c FROM orders WHERE status='processing'")['c'] ?? 0),
            'shipped'    => (int)(Database::fetch("SELECT COUNT(*) c FROM orders WHERE status='shipped'")['c'] ?? 0),
            'delivered'  => (int)(Database::fetch("SELECT COUNT(*) c FROM orders WHERE status='delivered'")['c'] ?? 0),
            'cancelled'  => (int)(Database::fetch("SELECT COUNT(*) c FROM orders WHERE status='cancelled'")['c'] ?? 0),
            'today'      => (int)(Database::fetch("SELECT COUNT(*) c FROM orders WHERE DATE(created_at)=CURDATE()")['c'] ?? 0),
        ];

        $shipments = [
            'total'         => (int)(Database::fetch("SELECT COUNT(*) c FROM shipments")['c'] ?? 0),
            'in_transit'    => (int)(Database::fetch("SELECT COUNT(*) c FROM shipments WHERE status IN ('picked_up','in_transit','out_for_delivery')")['c'] ?? 0),
            'delivered'     => (int)(Database::fetch("SELECT COUNT(*) c FROM shipments WHERE status='delivered'")['c'] ?? 0),
        ];

        $products = [
            'total_active'  => (int)(Database::fetch("SELECT COUNT(*) c FROM products WHERE status='active'")['c'] ?? 0),
            'out_of_stock'  => (int)(Database::fetch("SELECT COUNT(*) c FROM products WHERE track_stock=1 AND stock=0")['c'] ?? 0),
        ];

        $this->json([
            'generated_at' => date('c'),
            'accounts'     => $accounts,
            'financial'    => $financial,
            'orders'       => $orders,
            'shipments'    => $shipments,
            'products'     => $products,
        ]);
    }

    /** GET /api/v1/orders — paginated raw order list */
    public function orders(): void
    {
        $this->requireAuth();
        $page = max(1, (int)$this->get('page', 1));
        $perPage = min(100, max(1, (int)$this->get('per_page', 20)));
        $offset = ($page - 1) * $perPage;

        $total = (int)(Database::fetch("SELECT COUNT(*) c FROM orders")['c'] ?? 0);
        $orders = Database::fetchAll("SELECT id,order_number,customer_name,customer_phone,total,payment_status,payment_method,status,created_at FROM orders ORDER BY created_at DESC LIMIT $perPage OFFSET $offset");

        $this->json(['page' => $page, 'per_page' => $perPage, 'total' => $total, 'data' => $orders]);
    }

    /** GET /api/v1/shipments — paginated raw shipment list */
    public function shipments(): void
    {
        $this->requireAuth();
        $page = max(1, (int)$this->get('page', 1));
        $perPage = min(100, max(1, (int)$this->get('per_page', 20)));
        $offset = ($page - 1) * $perPage;

        $total = (int)(Database::fetch("SELECT COUNT(*) c FROM shipments")['c'] ?? 0);
        $shipments = Database::fetchAll(
            "SELECT s.id,s.tracking_number,s.status,s.cod_amount,s.cod_collected,o.order_number
             FROM shipments s LEFT JOIN orders o ON o.id=s.order_id
             ORDER BY s.created_at DESC LIMIT $perPage OFFSET $offset"
        );

        $this->json(['page' => $page, 'per_page' => $perPage, 'total' => $total, 'data' => $shipments]);
    }
}
