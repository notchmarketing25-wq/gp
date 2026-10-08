<?php
class OrderModel extends Model
{
    protected string $table = 'orders';
    protected array $fillable = [
        'order_number','customer_id','customer_name','customer_email','customer_phone',
        'shipping_address','shipping_city','shipping_gov','shipping_price',
        'subtotal','discount_code','discount_amount','total',
        'payment_method','payment_status','payment_ref',
        'status','notes','admin_notes',
        'order_type','placed_by_business_id','rep_commission_percent','rep_commission_amount',
    ];

    public function generateOrderNumber(): string
    {
        do {
            $num = 'NT-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));
        } while ($this->findBy('order_number', $num));
        return $num;
    }

    public function createWithItems(array $order, array $items): int
    {
        Database::beginTransaction();
        try {
            $orderId = $this->create($order);

            foreach ($items as $item) {
                $item['order_id'] = $orderId;
                $item['total']    = $item['price'] * $item['qty'];
                // Snapshot the product's current cost so COGS/profit reports stay accurate for
                // this order even if the product's cost_price changes later on.
                $costPrice = null;
                if (!empty($item['product_id'])) {
                    $costPrice = Database::fetch("SELECT cost_price FROM products WHERE id=?", [$item['product_id']])['cost_price'] ?? null;
                }
                Database::insert(
                    "INSERT INTO `order_items` (`order_id`,`product_id`,`variant_id`,`name`,`variant`,`sku`,`price`,`cost_price`,`qty`,`total`,`image`)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?)",
                    [
                        $orderId,
                        $item['product_id'] ?? null,
                        $item['variant_id'] ?? null,
                        $item['name'],
                        $item['variant'] ?? null,
                        $item['sku'] ?? null,
                        $item['price'],
                        $costPrice,
                        $item['qty'],
                        $item['total'],
                        $item['image'] ?? null,
                    ]
                );

                // Reserve stock immediately at order creation (not at payment confirmation) so a
                // COD order locks its units the same as a paid one, and two customers can't both
                // check out the last piece. This is the ONLY place stock is decremented for a
                // sale — createIssueVoucherForOrder() (Helpers/functions.php), which used to also
                // decrement products.stock when a payment was confirmed, now just logs the
                // movement that already happened here; see its docblock for why that double
                // decrement was the root cause of stock numbers drifting wrong over time.
                if (!empty($item['product_id'])) {
                    $productModel = new ProductModel();
                    $flags = $productModel->getStockFlags($item['product_id']);
                    if ($flags['track_stock']) {
                        $ok = $productModel->decrementStock(
                            $item['product_id'],
                            $item['qty'],
                            $item['variant_id'] ?? null,
                            $flags['allow_backorder']
                        );
                        if (!$ok) {
                            // Lost the race to another buyer (or stock changed) between add-to-cart
                            // and checkout, and backorders aren't allowed for this product — abort
                            // the whole order rather than sell a unit that no longer exists.
                            throw new \RuntimeException('نفذت الكمية المتاحة من "' . $item['name'] . '" أثناء إتمام الطلب');
                        }
                    }
                }
            }

            Database::commit();
            return $orderId;
        } catch (\Throwable $e) {
            Database::rollback();
            throw $e;
        }
    }

    public function getWithItems(int $id): ?array
    {
        $order = $this->find($id);
        if (!$order) return null;
        $order['items'] = Database::fetchAll(
            "SELECT oi.*, COALESCE(NULLIF(oi.sku,''), p.sku) AS sku
             FROM `order_items` oi LEFT JOIN `products` p ON p.id = oi.product_id
             WHERE oi.order_id = ?",
            [$id]
        );
        if (!empty($order['placed_by_business_id'])) {
            $order['placed_by'] = Database::fetch(
                "SELECT c.name, c.business_type, t.name tier_name, t.discount_percent
                 FROM customers c LEFT JOIN price_tiers t ON t.id=c.price_tier_id
                 WHERE c.id=?", [$order['placed_by_business_id']]
            );
        }
        return $order;
    }

    public function getByNumber(string $number): ?array
    {
        $order = $this->findBy('order_number', $number);
        if (!$order) return null;
        $order['items'] = Database::fetchAll(
            "SELECT * FROM `order_items` WHERE `order_id` = ?",
            [$order['id']]
        );
        return $order;
    }

    public function adminList(int $page = 1, string $search = '', string $status = '', string $payment = '', string $orderType = ''): array
    {
        $where  = [];
        $params = [];

        if ($search) {
            $where[]  = "(o.order_number LIKE ? OR o.customer_name LIKE ? OR o.customer_email LIKE ?)";
            $params   = array_merge($params, ["%{$search}%", "%{$search}%", "%{$search}%"]);
        }
        if ($status) {
            $where[]  = "o.status = ?";
            $params[] = $status;
        }
        if ($payment) {
            $where[]  = "o.payment_status = ?";
            $params[] = $payment;
        }
        if ($orderType) {
            $where[]  = "o.order_type = ?";
            $params[] = $orderType;
        }

        $whereStr = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $offset   = ($page - 1) * ITEMS_PER_PAGE;

        $total = Database::fetch("SELECT COUNT(*) as cnt FROM `orders` o {$whereStr}", $params)['cnt'];
        $data  = Database::fetchAll(
            "SELECT o.*, b.name placed_by_name, b.business_type placed_by_type
             FROM `orders` o
             LEFT JOIN customers b ON b.id = o.placed_by_business_id
             {$whereStr} ORDER BY o.created_at DESC LIMIT " . ITEMS_PER_PAGE . " OFFSET {$offset}",
            $params
        );

        return [
            'data'         => $data,
            'total'        => (int)$total,
            'current_page' => $page,
            'last_page'    => (int)ceil($total / ITEMS_PER_PAGE),
        ];
    }

    public function stats(): array
    {
        $today = date('Y-m-d');
        $month = date('Y-m');

        return [
            'total_orders'    => (int)Database::fetch("SELECT COUNT(*) as c FROM `orders`")['c'],
            'today_orders'    => (int)Database::fetch("SELECT COUNT(*) as c FROM `orders` WHERE DATE(created_at) = ?", [$today])['c'],
            'month_orders'    => (int)Database::fetch("SELECT COUNT(*) as c FROM `orders` WHERE YEAR(created_at)=YEAR(NOW()) AND MONTH(created_at)=MONTH(NOW())", [])['c'],
            'total_revenue'   => (float)(Database::fetch("SELECT COALESCE(SUM(total),0) as s FROM `orders` WHERE payment_status = 'paid'")['s']),
            'today_revenue'   => (float)(Database::fetch("SELECT COALESCE(SUM(total),0) as s FROM `orders` WHERE payment_status = 'paid' AND DATE(created_at) = ?", [$today])['s']),
            'month_revenue'   => (float)(Database::fetch("SELECT COALESCE(SUM(total),0) as s FROM `orders` WHERE payment_status = 'paid' AND YEAR(created_at)=YEAR(NOW()) AND MONTH(created_at)=MONTH(NOW())", [])['s']),
            'pending_orders'  => (int)Database::fetch("SELECT COUNT(*) as c FROM `orders` WHERE status = 'pending'")['c'],
        ];
    }

    public function recentOrders(int $limit = 10): array
    {
        return Database::fetchAll(
            "SELECT * FROM `orders` ORDER BY created_at DESC LIMIT {$limit}", []
        );
    }

    public function salesChart(int $days = 30): array
    {
        return Database::fetchAll(
            "SELECT DATE(created_at) as date, COUNT(*) as orders, COALESCE(SUM(total),0) as revenue
             FROM `orders`
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY) AND payment_status = 'paid'
             GROUP BY DATE(created_at)
             ORDER BY date ASC",
            [$days]
        );
    }
}
