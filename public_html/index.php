<?php
// ═══════════════════════════════════════════════════════════════════
// Notch Technology — Front controller (كل الطلبات تمر من هنا عبر .htaccess)
// ═══════════════════════════════════════════════════════════════════

define('ROOT_PATH',    __DIR__);
define('APP_PATH',     ROOT_PATH . '/app');
define('STORAGE_PATH', ROOT_PATH . '/storage');

if (!file_exists(ROOT_PATH . '/config/config.php')) {
    http_response_code(500);
    exit('config/config.php غير موجود — انسخ config/config.example.php إلى config/config.php وضع بيانات قاعدة البيانات.');
}

require ROOT_PATH . '/config/config.php';

foreach ([STORAGE_PATH . '/logs', UPLOAD_PATH] as $dir) {
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
}

require APP_PATH . '/Core/Autoloader.php';
require APP_PATH . '/Helpers/functions.php';

Session::start();

$router = new Router();
require ROOT_PATH . '/config/routes.php';
$router->dispatch();
