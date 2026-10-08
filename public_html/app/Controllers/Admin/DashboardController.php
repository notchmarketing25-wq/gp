<?php
class DashboardController extends Controller
{
    public function index(): void
    {
        AdminAuthMiddleware::handle();

        $orderModel   = new OrderModel();
        $productModel = new ProductModel();
        $customerModel = new CustomerModel();

        // Every admin sees the dashboard, but each section only loads/renders if they actually
        // have permission for that module — financial figures (revenue, sales chart, top-product
        // revenue) specifically require 'analytics', not just 'orders' or 'products'.
        $canOrders    = adminCan('orders');
        $canProducts  = adminCan('products');
        $canCustomers = adminCan('customers');
        $canAnalytics = adminCan('analytics');

        $stats = ['total_orders'=>0,'pending_orders'=>0,'today_orders'=>0,'month_revenue'=>0,'total_revenue'=>0,'total_products'=>0,'total_customers'=>0,'low_stock'=>[]];
        $recentOrders = $salesChart = $topProducts = $shipments = $paymentMix = $categoryMix = [];

        if ($canOrders || $canAnalytics) {
            $orderStats = $orderModel->stats();
            $stats = array_merge($stats, $orderStats);
            if (!$canAnalytics) { $stats['month_revenue'] = null; $stats['total_revenue'] = null; }
        }
        if ($canProducts) {
            $stats['total_products'] = $productModel->count();
            $stats['low_stock'] = Database::fetchAll(
                "SELECT * FROM `products` WHERE stock <= 5 AND track_stock = 1 AND status = 'active' ORDER BY stock ASC LIMIT 5"
            );
        }
        if ($canCustomers) {
            $stats['total_customers'] = $customerModel->count();
        }
        if ($canOrders) {
            $recentOrders = $orderModel->recentOrders(8);
            $shipments = Database::fetchAll("SELECT * FROM orders WHERE status='shipped' ORDER BY updated_at DESC LIMIT 5");
        }
        if ($canAnalytics) {
            $salesChart = $orderModel->salesChart(19);
            $topProducts = Database::fetchAll(
                "SELECT p.name, p.thumbnail, SUM(oi.qty) as sold, SUM(oi.total) as revenue
                 FROM `order_items` oi
                 JOIN `products` p ON p.id = oi.product_id
                 GROUP BY oi.product_id ORDER BY sold DESC LIMIT 5"
            );
            $paymentMix = Database::fetchAll("SELECT payment_method, COUNT(*) cnt FROM orders GROUP BY payment_method");
            $categoryMix = Database::fetchAll(
                "SELECT c.name, COUNT(oi.id) cnt
                 FROM order_items oi
                 JOIN products p ON p.id = oi.product_id
                 LEFT JOIN collections c ON c.id = p.collection_id
                 GROUP BY c.id ORDER BY cnt DESC LIMIT 5"
            );
        }

        $this->view('admin.dashboard.index', compact(
            'stats', 'recentOrders', 'salesChart', 'topProducts', 'shipments', 'paymentMix', 'categoryMix',
            'canOrders', 'canProducts', 'canCustomers', 'canAnalytics'
        ));
    }
}
