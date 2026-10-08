<?php
class AdminAuthController extends Controller
{
    public function loginForm(): void
    {
        if (isAdminLoggedIn()) {
            $this->redirect($this->adminUrl());
        }
        $this->view('admin.auth.login');
    }

    public function login(): void
    {
        if (!verifyCsrf()) {
            flashError('انتهت الجلسة، أعد المحاولة');
            $this->redirect($this->adminUrl('login'));
        }

        $email    = trim($this->post('email', ''));
        $password = $this->post('password', '');

        $user = Database::fetch(
            "SELECT * FROM `admin_users` WHERE `email` = ? AND `is_active` = 1",
            [$email]
        );

        if ($user && password_verify($password, $user['password'])) {
            Database::execute(
                "UPDATE `admin_users` SET `last_login` = NOW() WHERE `id` = ?",
                [$user['id']]
            );
            unset($user['password']);
            Session::set('admin_user', $user);
            $this->redirect($this->adminUrl());
        }

        flashError('البريد الإلكتروني أو كلمة المرور غير صحيحة');
        $this->redirect($this->adminUrl('login'));
    }

    public function logout(): void
    {
        Session::remove('admin_user');
        $this->redirect($this->adminUrl('login'));
    }
}
