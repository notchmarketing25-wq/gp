<?php
define('DB_HOST',    'localhost');
define('DB_NAME',    'u363175221_notchtechnew');
define('DB_USER',    'u363175221_notchtechnew');
define('DB_PASS',    'ضع_كلمة_مرور_قاعدة_البيانات_هنا');
define('DB_CHARSET', 'utf8mb4');

define('APP_NAME',   'Notch Technology');
define('APP_URL',    'https://notchtech.co');
define('APP_DEBUG',  false);

define('ADMIN_PREFIX',     'admin');
define('SESSION_NAME',     'nt_8318202c');
define('SESSION_LIFETIME', 86400);

define('UPLOAD_PATH',       ROOT_PATH . '/uploads');
define('UPLOAD_URL',         APP_URL . '/uploads');
define('MAX_FILE_SIZE',      5 * 1024 * 1024);
define('ALLOWED_IMAGE_TYPES', ['image/jpeg','image/png','image/webp','image/gif']);

define('APP_CURRENCY',        'EGP');
define('APP_CURRENCY_SYMBOL', 'ج.م');
define('ITEMS_PER_PAGE',       20);

define('FAWATEERK_API_KEY', '');
define('FAWATEERK_API_URL', 'https://app.fawaterk.com/api/v2');
define('FAWATEERK_CALLBACK_URL', APP_URL . '/payment/callback');

date_default_timezone_set('Africa/Cairo');
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', STORAGE_PATH . '/logs/php.log');
