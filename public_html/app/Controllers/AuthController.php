<?php
class AuthController extends Controller
{
    public function loginForm(): void
    {
        if (isStoreLoggedIn()) { $this->redirect(url('account')); }
        $this->view('store.pages.login');
    }

    public function login(): void
    {
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect(url('login')); }
        $customer = (new CustomerModel())->authenticate(trim($this->post('email','')), $this->post('password',''));
        if ($customer && $customer['is_active']) {
            unset($customer['password']);
            Session::set('store_user', $customer);
            if (($customer['business_type'] ?? 'none') !== 'none') {
                $this->redirect(url('business'));
            }
            $this->redirect($this->post('redirect') ?: url('account'));
        }
        flashError('البريد الإلكتروني أو كلمة المرور غير صحيحة');
        $this->redirect(url('login'));
    }

    public function registerForm(): void
    {
        if (isStoreLoggedIn()) { $this->redirect(url('account')); }
        $this->view('store.pages.register');
    }

    public function register(): void
    {
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect(url('register')); }
        $model = new CustomerModel();
        $email = trim($this->post('email',''));
        if ($model->findByEmail($email)) { flashError('البريد الإلكتروني مستخدم مسبقاً'); $this->redirect(url('register')); }
        $pass = $this->post('password','');
        if (strlen($pass) < 8) { flashError('كلمة المرور يجب أن تكون 8 أحرف على الأقل'); $this->redirect(url('register')); }
        $id = $model->create(['name'=>trim($this->post('name','')), 'email'=>$email, 'password'=>password_hash($pass,PASSWORD_BCRYPT), 'phone'=>trim($this->post('phone',''))]);
        $customer = $model->find($id);
        unset($customer['password']);
        Session::set('store_user', $customer);
        try { EmailService::send('welcome_customer', $customer['email'], $customer['name'], ['name'=>$customer['name']], 'customer', $id); } catch (\Throwable $e) {}
        flashSuccess('مرحباً بك! تم إنشاء حسابك بنجاح');
        $this->redirect(url('account'));
    }

    public function logout(): void
    {
        Session::remove('store_user');
        $this->redirect(url());
    }

    public function forgotForm(): void { $this->view('store.pages.forgot'); }
    public function forgot(): void { flashSuccess('تم إرسال رابط إعادة التعيين إلى بريدك'); $this->redirect(url('login')); }
}
