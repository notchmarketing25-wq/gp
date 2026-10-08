<?php
class StoreController extends Controller
{
    public function home(): void { $this->view('store.pages.home'); }
    public function products(): void { $this->view('store.pages.products'); }
    public function search(): void { $this->view('store.pages.products'); }
    public function about(): void { $this->view('store.pages.about'); }
    public function contact(): void { $this->view('store.pages.contact'); }
    public function contactSubmit(): void { flashSuccess('تم إرسال رسالتك!'); $this->redirect(url('contact')); }

    public function product(string $slug): void
    {
        $model = new ProductModel();
        $product = $model->getBySlug($slug);
        if (!$product) { http_response_code(404); $this->view('store.pages.404'); return; }
        $model->incrementViews($product['id']);
        if ($this->isPost() && $this->post('action') === 'review') {
            if (!verifyCsrf()) { flashError('خطأ'); $this->redirect(url('products/'.$slug)); }
            if (!isStoreLoggedIn()) { flashError('يجب تسجيل الدخول'); $this->redirect(url('login')); }
            $user = storeUser();
            Database::insert("INSERT INTO `reviews`(`product_id`,`customer_id`,`name`,`rating`,`title`,`body`)VALUES(?,?,?,?,?,?)",
                [$product['id'],$user['id'],$user['name'],(int)$this->post('rating',5),trim($this->post('title','')),trim($this->post('body',''))]);
            flashSuccess('تم إرسال تقييمك وسيظهر بعد المراجعة');
            $this->redirect(url('products/'.$slug));
        }
        $this->view('store.pages.product', compact('product'));
    }

    public function collection(string $slug): void
    {
        $col = (new CollectionModel())->getBySlug($slug);
        if (!$col) { http_response_code(404); $this->view('store.pages.404'); return; }
        $_GET['collection'] = $slug;
        $this->view('store.pages.products');
    }

    public function brand(string $slug): void
    {
        $brand = (new BrandModel())->getBySlug($slug);
        if (!$brand) { http_response_code(404); $this->view('store.pages.404'); return; }
        $_GET['brand'] = $slug;
        $this->view('store.pages.products');
    }
}
