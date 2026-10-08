<?php
/**
 * Net income report: Revenue − COGS (cost of goods sold) − Expenses = Net Profit.
 * COGS is computed from order_items.cost_price (snapshotted at sale time — see OrderModel),
 * falling back to the product's current cost_price for older orders placed before that
 * snapshot existed.
 */
class FinanceController extends Controller
{
    public function __construct() { AdminAuthMiddleware::handle(); requireAdminPermission('analytics'); }

    public function index(): void
    {
        $month = $this->get('month', date('Y-m'));
        $from = $month . '-01';
        $to   = date('Y-m-t', strtotime($from));

        try {
            $expenses = Database::fetchAll(
                "SELECT c.name, c.icon, COALESCE(SUM(e.amount),0) total
                 FROM expense_categories c LEFT JOIN expenses e ON e.category_id=c.id AND DATE(e.expense_date) BETWEEN ? AND ?
                 GROUP BY c.id ORDER BY total DESC", [$from, $to]
            );
        } catch (\Throwable $e) {
            $this->view('admin.finance.index', [
                'migrationMissing' => true, 'month' => $month,
                'revenue'=>0,'discounts'=>0,'shipping'=>0,'cogs'=>0,'grossProfit'=>0,
                'expenses'=>[],'totalExpenses'=>0,'netProfit'=>0,'netMargin'=>0,'missingCostCount'=>0,'trend'=>[],
            ]);
            return;
        }

        // Revenue — paid orders only, within the period
        $revenue = (float)(Database::fetch(
            "SELECT COALESCE(SUM(total),0) v FROM orders WHERE payment_status='paid' AND DATE(created_at) BETWEEN ? AND ?",
            [$from, $to]
        )['v'] ?? 0);

        $discounts = (float)(Database::fetch(
            "SELECT COALESCE(SUM(discount_amount),0) v FROM orders WHERE payment_status='paid' AND DATE(created_at) BETWEEN ? AND ?",
            [$from, $to]
        )['v'] ?? 0);

        $shipping = (float)(Database::fetch(
            "SELECT COALESCE(SUM(shipping_price),0) v FROM orders WHERE payment_status='paid' AND DATE(created_at) BETWEEN ? AND ?",
            [$from, $to]
        )['v'] ?? 0);

        // COGS — sum of (snapshotted cost_price, or current product cost as fallback) × qty,
        // for items belonging to paid orders in the period.
        $cogs = (float)(Database::fetch(
            "SELECT COALESCE(SUM(COALESCE(oi.cost_price, p.cost_price, 0) * oi.qty),0) v
             FROM order_items oi
             JOIN orders o ON o.id=oi.order_id
             LEFT JOIN products p ON p.id=oi.product_id
             WHERE o.payment_status='paid' AND DATE(o.created_at) BETWEEN ? AND ?",
            [$from, $to]
        )['v'] ?? 0);

        // How many order items had no cost recorded at all (neither snapshot nor current
        // product cost) — flagged so the admin knows COGS here is understated, not wrong data.
        $missingCostCount = (int)(Database::fetch(
            "SELECT COUNT(*) c FROM order_items oi
             JOIN orders o ON o.id=oi.order_id
             LEFT JOIN products p ON p.id=oi.product_id
             WHERE o.payment_status='paid' AND DATE(o.created_at) BETWEEN ? AND ?
             AND oi.cost_price IS NULL AND (p.cost_price IS NULL OR p.cost_price=0)",
            [$from, $to]
        )['c'] ?? 0);

        $grossProfit = $revenue - $cogs;

        $expenses = Database::fetchAll(
            "SELECT c.name, c.icon, COALESCE(SUM(e.amount),0) total
             FROM expense_categories c LEFT JOIN expenses e ON e.category_id=c.id AND DATE(e.expense_date) BETWEEN ? AND ?
             GROUP BY c.id ORDER BY total DESC", [$from, $to]
        );
        $totalExpenses = array_sum(array_column($expenses, 'total'));

        $netProfit = $grossProfit - $totalExpenses;
        $netMargin = $revenue > 0 ? round($netProfit / $revenue * 100, 1) : 0;

        // 6-month trend for the chart
        $trend = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = date('Y-m', strtotime("-$i months", strtotime($from)));
            $mFrom = $m . '-01'; $mTo = date('Y-m-t', strtotime($mFrom));
            $mRevenue = (float)(Database::fetch("SELECT COALESCE(SUM(total),0) v FROM orders WHERE payment_status='paid' AND DATE(created_at) BETWEEN ? AND ?", [$mFrom, $mTo])['v'] ?? 0);
            $mCogs = (float)(Database::fetch(
                "SELECT COALESCE(SUM(COALESCE(oi.cost_price, p.cost_price, 0) * oi.qty),0) v FROM order_items oi JOIN orders o ON o.id=oi.order_id LEFT JOIN products p ON p.id=oi.product_id WHERE o.payment_status='paid' AND DATE(o.created_at) BETWEEN ? AND ?", [$mFrom, $mTo]
            )['v'] ?? 0);
            $mExpenses = (float)(Database::fetch("SELECT COALESCE(SUM(amount),0) v FROM expenses WHERE DATE(expense_date) BETWEEN ? AND ?", [$mFrom, $mTo])['v'] ?? 0);
            $trend[] = ['month' => $m, 'net' => $mRevenue - $mCogs - $mExpenses];
        }

        $this->view('admin.finance.index', compact(
            'month','revenue','discounts','shipping','cogs','grossProfit',
            'expenses','totalExpenses','netProfit','netMargin','missingCostCount','trend'
        ));
    }
}
