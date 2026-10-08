<?php
class Cart
{
    private const SESSION_KEY = 'cart';

    public static function get(): array
    {
        return Session::get(self::SESSION_KEY, []);
    }

    /** Returns true on success, or a specific Arabic error message string on failure. */
    public static function add(int $productId, int $qty = 1, ?int $variantId = null): bool|string
    {
        $productModel = new ProductModel();
        $product = $productModel->find($productId);
        if (!$product || $product['status'] !== 'active') return 'المنتج غير متاح';

        // Wholesale-only products are hidden from browsing, search and direct product-page URLs
        // (see ProductModel's WHERE channel != 'wholesale_only' clauses), but Cart::add() itself
        // had no matching check — meaning the product ID could still be posted directly to
        // cart/add, bypassing that restriction entirely. This closes that gap.
        if (($product['channel'] ?? 'both') === 'wholesale_only' && !self::isApprovedWholesaleCustomer()) {
            return 'المنتج غير متاح للبيع القطاعي';
        }

        $cart = self::get();
        $key  = $productId . '_' . ($variantId ?? 0);

        $price = $product['price'];
        $name  = $product['name'];
        $sku   = $product['sku'] ?? '';
        $image = $product['thumbnail'] ?? '';
        $variant = null;
        $variantRow = null;

        if ($variantId) {
            $variantRow = Database::fetch("SELECT * FROM `product_variants` WHERE `id` = ? AND `product_id` = ?", [$variantId, $productId]);
            if ($variantRow) {
                $price   = $variantRow['price'];
                $variant = $variantRow['title'];
                $image   = $variantRow['image'] ?: $image;
                $sku     = $variantRow['sku'] ?: $sku;
            }
        }

        $newQty = $qty + (isset($cart[$key]) ? $cart[$key]['qty'] : 0);

        // Stock validation — was previously entirely missing, meaning a customer could add more
        // than what's actually available. A selected variant has its OWN stock count that's
        // authoritative once variants exist for a product (see hasStockPerVariant()); the parent
        // product's own "stock" field is only checked when no variant applies. Skipped entirely
        // when the product allows backorders — that flag exists specifically so a merchant can
        // keep selling past zero, and this check was blocking that on every add-to-cart.
        if ($product['track_stock'] && empty($product['allow_backorder'])) {
            if ($variantId && $variantRow) {
                if ($newQty > (int)$variantRow['stock']) {
                    return (int)$variantRow['stock'] <= 0
                        ? 'هذا الاختيار نفذ من المخزون'
                        : 'الكمية المتاحة من هذا الاختيار: ' . (int)$variantRow['stock'] . ' فقط';
                }
            } elseif (!$variantId) {
                if ($newQty > (int)$product['stock']) {
                    return (int)$product['stock'] <= 0
                        ? 'المنتج نفذ من المخزون'
                        : 'الكمية المتاحة: ' . (int)$product['stock'] . ' فقط';
                }
            }
        }

        // Wholesale tier pricing: only for approved wholesale customers, on wholesale-enabled products
        $isWholesale = false;
        if (!empty($product['wholesale_enabled']) && self::isApprovedWholesaleCustomer()) {
            $minQty = (int)($product['wholesale_min_qty'] ?? 1);
            if ($newQty >= $minQty) {
                $price = $productModel->wholesalePriceFor($productId, $newQty, $price);
                $isWholesale = true;
            }
        }

        if (isset($cart[$key])) {
            $cart[$key]['qty']       = $newQty;
            $cart[$key]['price']     = (float)$price;
            $cart[$key]['wholesale'] = $isWholesale;
        } else {
            $cart[$key] = [
                'product_id' => $productId,
                'variant_id' => $variantId,
                'name'       => $name,
                'variant'    => $variant,
                'price'      => (float)$price,
                'qty'        => $newQty,
                'sku'        => $sku,
                'image'      => $image,
                'wholesale'  => $isWholesale,
            ];
        }

        Session::set(self::SESSION_KEY, $cart);
        return true;
    }

    /**
     * Adds a product to the cart as a configured "add-on" of another product (e.g. the cable
     * added on the charger's page), applying whatever bundle discount the admin set up for that
     * specific pair in `product_addons` — looked up server-side so the price can never be
     * tampered with from the client. Falls back to the addon's normal price if no discount rule
     * is configured for this pair (still a legitimate "frequently bought together" add, just
     * without a price break).
     */
    public static function addAsAddon(int $mainProductId, int $addonProductId, int $qty = 1, ?int $variantId = null): bool|string
    {
        $result = self::add($addonProductId, $qty, $variantId);
        if ($result !== true) return $result;

        $rule = Database::fetch(
            "SELECT discount_type, discount_value FROM product_addons WHERE product_id = ? AND addon_product_id = ?",
            [$mainProductId, $addonProductId]
        );
        if ($rule && $rule['discount_type'] !== 'none') {
            $cart = self::get();
            $key  = $addonProductId . '_' . ($variantId ?? 0);
            if (isset($cart[$key])) {
                $price = $cart[$key]['price'];
                $price = $rule['discount_type'] === 'percent'
                    ? $price * (1 - ((float)$rule['discount_value'] / 100))
                    : max(0, $price - (float)$rule['discount_value']);
                $cart[$key]['price'] = round($price, 2);
                Session::set(self::SESSION_KEY, $cart);
            }
        }
        return true;
    }

    /** True if the logged-in store customer has an approved wholesale account.
     *  Reads fresh from DB (not session) since pricing must reflect the latest admin decision. */
    public static function isApprovedWholesaleCustomer(): bool
    {
        $user = Session::get('store_user');
        if (!$user || empty($user['id'])) return false;
        static $cache = null;
        if ($cache === null) {
            // NOTE: this used to check the legacy account_type/wholesale_status columns from an
            // older standalone wholesale system, which the current Business platform (merchant/
            // distributor/rep registration, business_type/business_status columns) never writes
            // to — meaning every approved Business customer was silently failing this check and
            // never seeing their own wholesale pricing on the regular storefront product page.
            $row = Database::fetch("SELECT business_type, business_status FROM customers WHERE id=?", [$user['id']]);
            $cache = $row ?: ['business_type'=>'none','business_status'=>'pending'];
        }
        return ($cache['business_type'] ?? 'none') !== 'none'
            && ($cache['business_status'] ?? 'pending') === 'approved';
    }

    /** True if any item currently in the cart is priced at wholesale */
    public static function hasWholesaleItems(): bool
    {
        foreach (self::get() as $item) {
            if (!empty($item['wholesale'])) return true;
        }
        return false;
    }

    public static function update(string $key, int $qty): void
    {
        $cart = self::get();
        if ($qty <= 0) {
            unset($cart[$key]);
        } elseif (isset($cart[$key])) {
            $cart[$key]['qty'] = $qty;
        }
        Session::set(self::SESSION_KEY, $cart);
    }

    public static function remove(string $key): void
    {
        $cart = self::get();
        unset($cart[$key]);
        Session::set(self::SESSION_KEY, $cart);
    }

    public static function clear(): void
    {
        Session::remove(self::SESSION_KEY);
        Session::remove('discount_code');
        Session::remove('discount_amount');
    }

    public static function count(): int
    {
        return array_sum(array_column(self::get(), 'qty'));
    }

    public static function subtotal(): float
    {
        $total = 0;
        foreach (self::get() as $item) {
            $total += $item['price'] * $item['qty'];
        }
        return $total;
    }

    public static function isEmpty(): bool
    {
        return empty(self::get());
    }

    public static function applyDiscount(string $code): array
    {
        $discount = Database::fetch(
            "SELECT * FROM `discounts`
             WHERE code = ? AND is_active = 1
             AND (starts_at IS NULL OR starts_at <= NOW())
             AND (expires_at IS NULL OR expires_at >= NOW())
             AND (max_uses IS NULL OR used_count < max_uses)",
            [strtoupper(trim($code))]
        );

        if (!$discount) {
            return ['success' => false, 'message' => 'كود الخصم غير صحيح أو منتهي'];
        }

        $subtotal = self::subtotal();

        if ($discount['min_order'] && $subtotal < $discount['min_order']) {
            return [
                'success' => false,
                'message' => 'الحد الأدنى للطلب هو ' . money($discount['min_order'])
            ];
        }

        $amount = $discount['type'] === 'percentage'
            ? ($subtotal * $discount['value'] / 100)
            : (float)$discount['value'];

        $amount = min($amount, $subtotal);

        Session::set('discount_code', $discount['code']);
        Session::set('discount_amount', $amount);
        Session::set('discount_id', $discount['id']);

        return [
            'success' => true,
            'message' => 'تم تطبيق كود الخصم!',
            'amount'  => $amount,
        ];
    }

    public static function getDiscount(): array
    {
        return [
            'code'   => Session::get('discount_code', ''),
            'amount' => (float)Session::get('discount_amount', 0),
            'id'     => Session::get('discount_id'),
        ];
    }

    public static function summary(float $shippingPrice = 0): array
    {
        $subtotal = self::subtotal();
        $discount = self::getDiscount();

        return [
            'subtotal'        => $subtotal,
            'discount_code'   => $discount['code'],
            'discount_amount' => $discount['amount'],
            'shipping'        => $shippingPrice,
            'total'           => max(0, $subtotal - $discount['amount'] + $shippingPrice),
        ];
    }
}
