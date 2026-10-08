<?php
class ProductModel extends Model
{
    protected string $table = 'products';
    protected array $fillable = [
        'name','slug','description','short_desc','sku','collection_id','brand_id',
        'price','compare_price','cost_price','stock','track_stock','allow_backorder',
        'weight','status','is_featured','has_variants','thumbnail',
        'meta_title','meta_desc','sort_order',
        'wholesale_enabled','wholesale_min_qty','channel'
    ];

    /** Get price tiers for a product, ordered by min_qty ascending */
    public function getPriceTiers(int $productId): array
    {
        return Database::fetchAll("SELECT * FROM product_price_tiers WHERE product_id=? ORDER BY min_qty ASC", [$productId]);
    }

    /** Replace all price tiers for a product */
    public function savePriceTiers(int $productId, array $tiers): void
    {
        Database::execute("DELETE FROM product_price_tiers WHERE product_id=?", [$productId]);
        foreach ($tiers as $t) {
            $qty = (int)($t['qty'] ?? 0);
            $price = (float)($t['price'] ?? 0);
            if ($qty > 0 && $price > 0) {
                Database::insert("INSERT INTO product_price_tiers(product_id,min_qty,price) VALUES(?,?,?)", [$productId, $qty, $price]);
            }
        }
    }

    /** Best matching wholesale price for a given quantity (highest min_qty <= qty), falls back to normal price */
    public function wholesalePriceFor(int $productId, int $qty, float $fallbackPrice): float
    {
        $tier = Database::fetch(
            "SELECT price FROM product_price_tiers WHERE product_id=? AND min_qty<=? ORDER BY min_qty DESC LIMIT 1",
            [$productId, $qty]
        );
        return $tier ? (float)$tier['price'] : $fallbackPrice;
    }

    /**
     * The price shown to a Business Platform account (merchant/distributor/rep) for a product.
     * Priority: the product's own quantity-based wholesale tiers (starting from the LOWEST min_qty,
     * i.e. the entry-level wholesale price) — falls back to the customer's percentage price-tier
     * discount only when the product has no quantity tiers defined.
     * Returns ['price'=>float, 'min_qty'=>int, 'has_tiers'=>bool, 'tiers'=>array]
     */
    public function businessPriceInfo(array $product, array $customer): array
    {
        $tiers = $this->getPriceTiers($product['id']);
        if (!empty($tiers)) {
            $entry = $tiers[0]; // lowest min_qty first (getPriceTiers orders ASC)
            return [
                'price'     => (float)$entry['price'],
                'min_qty'   => (int)$entry['min_qty'],
                'has_tiers' => true,
                'tiers'     => $tiers,
            ];
        }
        // No product-level quantity tiers — fall back to the account's percentage tier discount
        return [
            'price'     => $this->tierPriceFallback($product, $customer),
            'min_qty'   => 1,
            'has_tiers' => false,
            'tiers'     => [],
        ];
    }

    private function tierPriceFallback(array $product, array $customer): float
    {
        if (empty($customer['price_tier_id'])) return (float)$product['price'];
        $tier = Database::fetch("SELECT discount_percent FROM price_tiers WHERE id=?", [$customer['price_tier_id']]);
        if (!$tier) return (float)$product['price'];
        return round((float)$product['price'] * (1 - ((float)$tier['discount_percent'] / 100)), 2);
    }

    /**
     * Generate a unique slug, appending -2, -3, etc. on collision.
     * Excludes $excludeId when checking (for updates).
     */
    public function uniqueSlug(string $base, ?int $excludeId = null): string
    {
        $slug = $base;
        $i = 2;
        while (true) {
            $sql = "SELECT id FROM `products` WHERE slug = ?";
            $params = [$slug];
            if ($excludeId) { $sql .= " AND id != ?"; $params[] = $excludeId; }
            $exists = Database::fetch($sql, $params);
            if (!$exists) return $slug;
            $slug = $base . '-' . $i;
            $i++;
            if ($i > 50) { return $base . '-' . uniqid(); } // safety net
        }
    }

    public function getActive(int $limit = 20, int $offset = 0): array
    {
        return Database::fetchAll(
            "SELECT p.*, c.name as collection_name, b.name as brand_name
             FROM `products` p
             LEFT JOIN `collections` c ON p.collection_id = c.id
             LEFT JOIN `brands` b ON p.brand_id = b.id
             WHERE p.status = 'active' AND p.channel != 'wholesale_only'
             ORDER BY p.sort_order DESC, p.created_at DESC
             LIMIT {$limit} OFFSET {$offset}", []
        );
    }

    public function getBySlug(string $slug): ?array
    {
        $product = Database::fetch(
            "SELECT p.*, c.name as collection_name, b.name as brand_name
             FROM `products` p
             LEFT JOIN `collections` c ON p.collection_id = c.id
             LEFT JOIN `brands` b ON p.brand_id = b.id
             WHERE p.slug = ? AND p.status = 'active' AND p.channel != 'wholesale_only'",
            [$slug]
        );

        if (!$product) return null;

        $product['images'] = $this->getImages($product['id']);
        $product['variants'] = $this->getVariants($product['id']);
        $product['rating'] = $this->getRating($product['id']);

        return $product;
    }

    public function getImages(int $productId): array
    {
        return Database::fetchAll(
            "SELECT * FROM `product_images` WHERE `product_id` = ? ORDER BY `sort_order` ASC",
            [$productId]
        );
    }

    public function getVariants(int $productId): array
    {
        return Database::fetchAll(
            "SELECT * FROM `product_variants` WHERE `product_id` = ? ORDER BY `sort_order` ASC",
            [$productId]
        );
    }

    public function getRating(int $productId): array
    {
        $row = Database::fetch(
            "SELECT AVG(rating) as avg, COUNT(*) as count
             FROM `reviews`
             WHERE product_id = ? AND is_approved = 1",
            [$productId]
        );
        return [
            'avg'   => round((float)($row['avg'] ?? 0), 1),
            'count' => (int)($row['count'] ?? 0),
        ];
    }

    public function getFeatured(int $limit = 8): array
    {
        return Database::fetchAll(
            "SELECT p.*, b.name as brand_name
             FROM `products` p
             LEFT JOIN `brands` b ON p.brand_id = b.id
             WHERE p.status = 'active' AND p.is_featured = 1 AND p.channel != 'wholesale_only'
             ORDER BY p.sort_order DESC LIMIT {$limit}", []
        );
    }

    public function getByCollection(int $collectionId, int $limit = 20, int $offset = 0): array
    {
        return Database::fetchAll(
            "SELECT p.*, b.name as brand_name
             FROM `products` p
             LEFT JOIN `brands` b ON p.brand_id = b.id
             WHERE p.collection_id = ? AND p.status = 'active' AND p.channel != 'wholesale_only'
             ORDER BY p.sort_order DESC LIMIT {$limit} OFFSET {$offset}", [$collectionId]
        );
    }

    public function getByBrand(int $brandId, int $limit = 20, int $offset = 0): array
    {
        return Database::fetchAll(
            "SELECT p.*, c.name as collection_name
             FROM `products` p
             LEFT JOIN `collections` c ON p.collection_id = c.id
             WHERE p.brand_id = ? AND p.status = 'active' AND p.channel != 'wholesale_only'
             ORDER BY p.sort_order DESC LIMIT {$limit} OFFSET {$offset}", [$brandId]
        );
    }

    public function search(string $q, int $limit = 20): array
    {
        return Database::fetchAll(
            "SELECT p.*, b.name as brand_name
             FROM `products` p
             LEFT JOIN `brands` b ON p.brand_id = b.id
             WHERE p.status = 'active' AND p.channel != 'wholesale_only'
             AND (p.name LIKE ? OR p.description LIKE ? OR p.sku LIKE ?)
             ORDER BY p.views DESC LIMIT {$limit}", ["%{$q}%", "%{$q}%", "%{$q}%"]
        );
    }

    /**
     * Reserves stock for a sale — called once per order item, at order-creation time (not at
     * payment confirmation), so stock is locked for COD orders too and two simultaneous buyers
     * can never both "win" the last unit.
     *
     * IMPORTANT: a product with variants tracks availability on `product_variants.stock` only —
     * `products.stock` is a separate, independently-edited field for simple products with no
     * variants. Touching both for a variant sale (as this used to do) made the parent product's
     * stock number drift further from reality on every single order, with no way to tell from
     * the admin panel why it no longer matched what was actually in the warehouse. So: decrement
     * exactly one of the two, whichever one is authoritative for this item.
     *
     * The decrement is also conditional (`stock >= ?`) so it can never take a row negative or
     * double-sell the last unit under concurrent checkouts — it reports back whether it actually
     * applied, so the caller can treat a race-lost sale as "out of stock" instead of silently
     * going negative.
     */
    public function decrementStock(int $productId, int $qty, ?int $variantId = null, bool $allowNegative = false): bool
    {
        $table  = $variantId ? 'product_variants' : 'products';
        $rowId  = $variantId ?: $productId;

        if ($allowNegative) {
            // Product explicitly allows backorders — sell past zero rather than block the order.
            $affected = Database::execute(
                "UPDATE `{$table}` SET `stock` = `stock` - ? WHERE `id` = ?",
                [$qty, $rowId]
            );
        } else {
            $affected = Database::execute(
                "UPDATE `{$table}` SET `stock` = `stock` - ? WHERE `id` = ? AND `stock` >= ?",
                [$qty, $rowId, $qty]
            );
        }
        return (bool)$affected;
    }

    /** Stock-tracking flags needed to decide how decrementStock() should behave for this product. */
    public function getStockFlags(int $productId): array
    {
        $row = Database::fetch("SELECT track_stock, allow_backorder FROM products WHERE id = ?", [$productId]);
        return [
            'track_stock'     => $row ? (bool)$row['track_stock'] : false,
            'allow_backorder' => $row ? (bool)$row['allow_backorder'] : false,
        ];
    }

    /**
     * The mirror of decrementStock() — returns reserved stock to the pool when an order is
     * cancelled before it shipped. Same "touch only the authoritative field" rule applies.
     */
    public function restoreStock(int $productId, int $qty, ?int $variantId = null): void
    {
        if ($variantId) {
            Database::execute(
                "UPDATE `product_variants` SET `stock` = `stock` + ? WHERE `id` = ?",
                [$qty, $variantId]
            );
        } else {
            Database::execute(
                "UPDATE `products` SET `stock` = `stock` + ? WHERE `id` = ?",
                [$qty, $productId]
            );
        }
    }

    public function incrementViews(int $productId): void
    {
        Database::execute(
            "UPDATE `products` SET `views` = `views` + 1 WHERE `id` = ?",
            [$productId]
        );
    }

    public function addImage(int $productId, string $image, string $alt = ''): int
    {
        return Database::insert(
            "INSERT INTO `product_images` (`product_id`, `image`, `alt`) VALUES (?, ?, ?)",
            [$productId, $image, $alt]
        );
    }

    public function deleteImage(int $imageId): ?array
    {
        $img = Database::fetch("SELECT * FROM `product_images` WHERE `id` = ?", [$imageId]);
        if ($img) {
            Database::execute("DELETE FROM `product_images` WHERE `id` = ?", [$imageId]);
        }
        return $img;
    }

    public function adminList(int $page = 1, string $search = '', string $status = '', string $channel = ''): array
    {
        $where  = [];
        $params = [];

        if ($search) {
            $where[]  = "(p.name LIKE ? OR p.sku LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
        if ($status) {
            $where[]  = "p.status = ?";
            $params[] = $status;
        } else {
            // Archived products are intentionally hidden from the mixed "all" view — they must
            // be requested explicitly (via the "مؤرشف" tab) so they don't clutter day-to-day
            // browsing of active/draft products.
            $where[] = "p.status != 'archived'";
        }
        if ($channel) {
            $where[]  = "p.channel = ?";
            $params[] = $channel;
        }

        $whereStr = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $offset   = ($page - 1) * ITEMS_PER_PAGE;

        $total = Database::fetch(
            "SELECT COUNT(*) as cnt FROM `products` p {$whereStr}",
            $params
        )['cnt'];

        $data = Database::fetchAll(
            "SELECT p.*, c.name as collection_name, b.name as brand_name,
                    (SELECT MIN(price) FROM product_price_tiers WHERE product_id = p.id) AS wholesale_from_price,
                    (SELECT COUNT(*) FROM product_price_tiers WHERE product_id = p.id) AS wholesale_tier_count,
                    (SELECT COUNT(*) FROM product_variants WHERE product_id = p.id) AS variant_count,
                    (SELECT COALESCE(SUM(stock),0) FROM product_variants WHERE product_id = p.id) AS variant_stock_total
             FROM `products` p
             LEFT JOIN `collections` c ON p.collection_id = c.id
             LEFT JOIN `brands` b ON p.brand_id = b.id
             {$whereStr}
             ORDER BY p.created_at DESC
             LIMIT " . ITEMS_PER_PAGE . " OFFSET {$offset}",
            $params
        );

        return [
            'data'         => $data,
            'total'        => (int)$total,
            'current_page' => $page,
            'last_page'    => (int)ceil($total / ITEMS_PER_PAGE),
        ];
    }

    /** Product counts per sales channel — used for the "قطاعي/جملة/الكل" tabs in the admin list */
    public function channelCounts(): array
    {
        $rows = Database::fetchAll("SELECT channel, COUNT(*) cnt FROM products GROUP BY channel");
        $counts = ['both' => 0, 'retail_only' => 0, 'wholesale_only' => 0];
        foreach ($rows as $r) { $counts[$r['channel'] ?: 'both'] = (int)$r['cnt']; }
        $counts['all'] = array_sum($counts);
        return $counts;
    }

    /** Product counts per status — used for the "نشط/مسودة/مؤرشف" tabs in the admin list */
    public function statusCounts(): array
    {
        $rows = Database::fetchAll("SELECT status, COUNT(*) cnt FROM products GROUP BY status");
        $counts = ['active' => 0, 'draft' => 0, 'archived' => 0];
        foreach ($rows as $r) { $counts[$r['status']] = (int)$r['cnt']; }
        $counts['all'] = $counts['active'] + $counts['draft']; // "all" intentionally excludes archived, matching adminList()
        return $counts;
    }
}
