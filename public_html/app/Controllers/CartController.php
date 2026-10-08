<?php
class CartController extends Controller
{
    public function index(): void { $this->view('store.pages.cart'); }

    public function add(): void
    {
        if (!verifyCsrf()) { jsonResponse(false, 'خطأ'); }
        $result = Cart::add((int)$this->post('product_id'), (int)$this->post('qty', 1), $this->post('variant_id') ? (int)$this->post('variant_id') : null);
        $ok = $result === true;
        jsonResponse($ok, $ok ? 'تمت الإضافة' : $result, ['count' => Cart::count()]);
    }

    public function update(): void
    {
        if (!verifyCsrf()) { jsonResponse(false, 'خطأ'); }
        Cart::update($this->post('key'), (int)$this->post('qty'));
        jsonResponse(true, 'تم التحديث', ['count' => Cart::count()]);
    }

    public function remove(): void
    {
        if (!verifyCsrf()) { jsonResponse(false, 'خطأ'); }
        Cart::remove($this->post('key'));
        jsonResponse(true, 'تم الحذف', ['count' => Cart::count()]);
    }

    public function clear(): void
    {
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect(url('cart')); }
        Cart::clear();
        $this->redirect(url('cart'));
    }

    /**
     * Adds the main product plus any selected add-ons (from the product page's "add-ons" box)
     * to the cart in one request, pricing each add-on via Cart::addAsAddon() so the bundle
     * discount is always the one the admin configured server-side, never whatever the client sent.
     */
    public function addBundle(): void
    {
        if (!verifyCsrf()) { jsonResponse(false, 'خطأ'); }

        $mainId     = (int)$this->post('main_product_id');
        $mainQty    = (int)$this->post('main_qty', 1);
        $mainVariant = $this->post('main_variant_id') ? (int)$this->post('main_variant_id') : null;
        $addonIds   = json_decode($this->post('addon_ids', '[]'), true) ?: [];

        $errors = [];

        $r = Cart::add($mainId, $mainQty, $mainVariant);
        if ($r !== true) $errors[] = $r;

        foreach ($addonIds as $addonId) {
            $r = Cart::addAsAddon($mainId, (int)$addonId, 1);
            if ($r !== true) $errors[] = $r;
        }

        jsonResponse(empty($errors), empty($errors) ? 'تمت الإضافة للسلة' : implode('، ', $errors), ['count' => Cart::count()]);
    }

    public function discount(): void
    {
        if (!verifyCsrf()) { jsonResponse(false, 'خطأ'); }
        $result = Cart::applyDiscount($this->post('code', ''));
        jsonResponse($result['success'], $result['message'], ['amount' => $result['amount'] ?? 0]);
    }

    public function count(): void
    {
        jsonResponse(true, '', ['count' => Cart::count()]);
    }
}
