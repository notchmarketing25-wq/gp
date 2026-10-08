<?php
class AccountController extends Controller
{
    public function __construct()
    {
        if (!isStoreLoggedIn()) {
            flashError('يجب تسجيل الدخول');
            header('Location: ' . url('login'));
            exit;
        }
    }
    public function dashboard(): void { $this->orders(); }
    public function orders(): void
    {
        $user   = storeUser();
        $orders = Database::fetchAll("SELECT * FROM `orders` WHERE customer_id=? ORDER BY created_at DESC", [$user['id']]);
        $warranties = [];
        try {
            $warranties = Database::fetchAll(
                "SELECT w.*, p.name product_name, p.thumbnail FROM warranties w LEFT JOIN products p ON p.id=w.product_id WHERE w.customer_id=? ORDER BY w.created_at DESC",
                [$user['id']]
            );
        } catch (\Throwable $e) {}
        $this->view('store.pages.account', compact('orders', 'warranties'));
    }

    public function warranties(): void
    {
        $user = storeUser();
        $warranties = [];
        try {
            $warranties = Database::fetchAll(
                "SELECT w.*, p.name product_name, p.thumbnail FROM warranties w LEFT JOIN products p ON p.id=w.product_id WHERE w.customer_id=? ORDER BY w.created_at DESC",
                [$user['id']]
            );
        } catch (\Throwable $e) {}
        $orders = [];
        $tab = 'warranties';
        $this->view('store.pages.account', compact('orders', 'warranties', 'tab'));
    }
    public function orderDetail(string $id): void
    {
        $user  = storeUser();
        $order = (new OrderModel())->getWithItems((int)$id);
        if (!$order || $order['customer_id'] != $user['id']) { $this->redirect(url('account/orders')); }
        $this->view('store.pages.order-detail', compact('order'));
    }
    public function wishlist(): void
    {
        $user     = storeUser();
        $products = Database::fetchAll("SELECT p.*, b.name as brand_name FROM products p JOIN wishlists w ON w.product_id=p.id LEFT JOIN brands b ON b.id=p.brand_id WHERE w.customer_id=? AND p.channel != 'wholesale_only' ORDER BY w.created_at DESC", [$user['id']]);
        $this->view('store.pages.account', ['wishlist' => $products, 'orders' => []]);
    }
    public function profile(): void { $this->view('store.pages.account', ['tab'=>'profile','orders'=>[]]); }
    public function updateProfile(): void
    {
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect(url('account/profile')); }
        $user = storeUser();
        (new CustomerModel())->update($user['id'], ['name'=>trim($this->post('name','')), 'phone'=>trim($this->post('phone',''))]);
        $updated = (new CustomerModel())->find($user['id']);
        unset($updated['password']);
        Session::set('store_user', $updated);
        flashSuccess('تم تحديث الملف الشخصي');
        $this->redirect(url('account/profile'));
    }
    public function addresses(): void { $this->view('store.pages.account', ['tab'=>'addresses','orders'=>[]]); }
    public function addAddress(): void { flashSuccess('تم إضافة العنوان'); $this->redirect(url('account/addresses')); }
    public function deleteAddress(): void { $this->redirect(url('account/addresses')); }
}
