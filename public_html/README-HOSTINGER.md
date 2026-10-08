# Notch Technology — الرفع على Hostinger (PHP)

محتوى هذا المجلد يُرفع **كما هو** داخل `public_html` على Hostinger.

```
public_html/
├── index.php          ← نقطة الدخول (كانت ناقصة → سبب خطأ 403)
├── .htaccess          ← يوجّه كل الروابط إلى index.php (كان ناقصًا)
├── config/
│   ├── config.php     ← بيانات قاعدة البيانات (لا تُرفع على GitHub)
│   └── routes.php
├── app/  (Controllers · Models · Views · Core · Helpers · Middleware)
├── storage/logs/      ← سجل الأخطاء php.log
├── uploads/           ← صور المنتجات (لا تحذفه)
└── assets/
```

## الخطوات
1. خذ نسخة احتياطية من `public_html` الحالي (خصوصًا `uploads/` و`config/config.php`).
2. ارفع محتوى هذا المجلد داخل `public_html` (File Manager → Upload → zip → Extract).
3. لو `config/config.php` غير موجود: انسخ `config/config.example.php` باسم `config.php` وضع كلمة مرور قاعدة البيانات.
4. hPanel → Advanced → **PHP Configuration**: اختر **PHP 8.1 أو أحدث**.
5. افتح الموقع، ولوحة التحكم على `/admin/login`.

لو ظهر خطأ: اجعل `APP_DEBUG` = `true` مؤقتًا في `config/config.php`، أو راجع `storage/logs/php.log`.
