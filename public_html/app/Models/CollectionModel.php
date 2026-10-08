<?php
class CollectionModel extends Model
{
    protected string $table = 'collections';
    protected array $fillable = ['name','slug','description','image','parent_id','sort_order','is_active','meta_title','meta_desc'];

    public function getActive(): array
    {
        return Database::fetchAll(
            "SELECT * FROM `collections` WHERE `is_active` = 1 ORDER BY `sort_order` ASC, `name` ASC"
        );
    }

    public function getBySlug(string $slug): ?array
    {
        return $this->findBy('slug', $slug);
    }

    public function withProductCount(): array
    {
        return Database::fetchAll(
            "SELECT c.*, COUNT(p.id) as product_count
             FROM `collections` c
             LEFT JOIN `products` p ON p.collection_id = c.id AND p.status = 'active'
             WHERE c.is_active = 1
             GROUP BY c.id
             ORDER BY c.sort_order ASC"
        );
    }
}

class BrandModel extends Model
{
    protected string $table = 'brands';
    protected array $fillable = ['name','slug','logo','is_active','sort_order'];

    public function getActive(): array
    {
        return Database::fetchAll(
            "SELECT * FROM `brands` WHERE `is_active` = 1 ORDER BY `sort_order` ASC, `name` ASC"
        );
    }

    public function getBySlug(string $slug): ?array
    {
        return $this->findBy('slug', $slug);
    }
}

class CustomerModel extends Model
{
    protected string $table = 'customers';
    protected array $fillable = ['name','email','password','phone','is_active','email_verified',
        'account_type','wholesale_status','company_name','company_address','tax_number','wholesale_notes','wholesale_applied_at','store_credit',
        'business_type','business_status','governorate','area','shop_name','price_tier_id','parent_rep_id',
        'id_card_number','id_card_image','company_papers_image','credit_requested','credit_limit','credit_status','credit_used'];

    /** Apply this customer's price-tier discount (if any) to a base price */
    public function tierPrice(array $customer, float $basePrice): float
    {
        if (empty($customer['price_tier_id'])) return $basePrice;
        $tier = Database::fetch("SELECT discount_percent FROM price_tiers WHERE id=?", [$customer['price_tier_id']]);
        if (!$tier) return $basePrice;
        return round($basePrice * (1 - ((float)$tier['discount_percent'] / 100)), 2);
    }

    /** Available credit (limit - used) */
    public function creditAvailable(array $customer): float
    {
        return max(0, (float)($customer['credit_limit'] ?? 0) - (float)($customer['credit_used'] ?? 0));
    }

    /** Check purchase total against tier thresholds and auto-upgrade if eligible */
    public function maybeAutoUpgradeTier(int $customerId): void
    {
        $c = $this->find($customerId);
        if (!$c) return;
        $tier = Database::fetch(
            "SELECT id FROM price_tiers WHERE min_purchase_auto IS NOT NULL AND min_purchase_auto <= ? ORDER BY min_purchase_auto DESC LIMIT 1",
            [(float)($c['total_spent'] ?? 0)]
        );
        if ($tier && (int)$tier['id'] !== (int)($c['price_tier_id'] ?? 0)) {
            $this->update($customerId, ['price_tier_id' => $tier['id']]);
        }
    }

    public function findByEmail(string $email): ?array
    {
        return $this->findBy('email', $email);
    }

    /**
     * Adjust a customer's store credit balance by $amount (positive = add, negative = deduct).
     * Logs the transaction and returns the new balance. Never lets balance go below zero.
     */
    public function adjustCredit(int $customerId, float $amount, string $reason = '', ?int $orderId = null, ?int $adminId = null): float
    {
        $customer = $this->find($customerId);
        if (!$customer) return 0;
        $current = (float)($customer['store_credit'] ?? 0);
        $new = max(0, $current + $amount);
        Database::execute("UPDATE customers SET store_credit=? WHERE id=?", [$new, $customerId]);
        Database::insert(
            "INSERT INTO store_credit_transactions(customer_id,amount,balance_after,reason,order_id,admin_id,created_at) VALUES(?,?,?,?,?,?,NOW())",
            [$customerId, $new - $current, $new, $reason, $orderId, $adminId]
        );
        return $new;
    }

    public function creditHistory(int $customerId, int $limit = 20): array
    {
        return Database::fetchAll("SELECT * FROM store_credit_transactions WHERE customer_id=? ORDER BY created_at DESC LIMIT $limit", [$customerId]);
    }

    /** Find or create a customer by email — used when importing orders that reference a new customer */
    public function findOrCreateByEmail(string $email, string $name = '', string $phone = ''): ?array
    {
        $email = strtolower(trim($email));
        if (!$email) return null;
        $existing = $this->findByEmail($email);
        if ($existing) return $existing;
        $id = $this->create([
            'name'     => $name ?: $email,
            'email'    => $email,
            'phone'    => $phone,
            'password' => password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT),
            'is_active' => 1,
        ]);
        return $this->find($id);
    }

    public function authenticate(string $email, string $password): ?array
    {
        $customer = $this->findByEmail($email);
        if ($customer && password_verify($password, $customer['password'])) {
            return $customer;
        }
        return null;
    }

    public function updateStats(int $id): void
    {
        Database::execute(
            "UPDATE `customers` SET
               `total_orders` = (SELECT COUNT(*) FROM `orders` WHERE customer_id = ?),
               `total_spent` = (SELECT COALESCE(SUM(total),0) FROM `orders` WHERE customer_id = ? AND payment_status = 'paid'),
               `last_order_at` = (SELECT MAX(created_at) FROM `orders` WHERE customer_id = ?)
             WHERE id = ?",
            [$id, $id, $id, $id]
        );
    }

    public function adminList(int $page = 1, string $search = ''): array
    {
        $where  = '';
        $params = [];
        if ($search) {
            $where  = "WHERE c.name LIKE ? OR c.email LIKE ? OR c.phone LIKE ?";
            $params = ["%{$search}%", "%{$search}%", "%{$search}%"];
        }
        $offset = ($page - 1) * ITEMS_PER_PAGE;
        $total  = Database::fetch("SELECT COUNT(*) as c FROM `customers` c {$where}", $params)['c'];
        $data   = Database::fetchAll(
            "SELECT c.*, a.city AS addr_city, a.governorate AS addr_gov
             FROM `customers` c
             LEFT JOIN customer_addresses a ON a.customer_id=c.id AND a.is_default=1
             {$where} ORDER BY c.created_at DESC LIMIT " . ITEMS_PER_PAGE . " OFFSET {$offset}",
            $params
        );
        return ['data' => $data, 'total' => (int)$total, 'current_page' => $page, 'last_page' => (int)ceil($total / ITEMS_PER_PAGE)];
    }
}

class DiscountModel extends Model
{
    protected string $table = 'discounts';
    protected array $fillable = ['code','type','value','min_order','max_uses','starts_at','expires_at','is_active'];

    public function adminList(int $page = 1): array
    {
        $offset = ($page - 1) * ITEMS_PER_PAGE;
        $total  = Database::fetch("SELECT COUNT(*) as c FROM `discounts`")['c'];
        $data   = Database::fetchAll("SELECT * FROM `discounts` ORDER BY created_at DESC LIMIT " . ITEMS_PER_PAGE . " OFFSET {$offset}");
        return ['data' => $data, 'total' => (int)$total, 'current_page' => $page, 'last_page' => (int)ceil($total / ITEMS_PER_PAGE)];
    }

    public function incrementUsed(int $id): void
    {
        Database::execute("UPDATE `discounts` SET `used_count` = `used_count` + 1 WHERE `id` = ?", [$id]);
    }
}
