<?php
class Autoloader
{
    private static array $loaded = [];

    // Controllers that live in grouped files
    private static array $grouped = [
        'AdminSettingsController'   => 'Controllers/Admin/AdminOtherControllers.php',
        'AdminCollectionController' => 'Controllers/Admin/AdminOtherControllers.php',
        'AdminBrandController'      => 'Controllers/Admin/AdminOtherControllers.php',
        'AdminCustomerController'   => 'Controllers/Admin/AdminOtherControllers.php',
        'AdminDiscountController'   => 'Controllers/Admin/AdminOtherControllers.php',
        'AdminAnalyticsController'  => 'Controllers/Admin/AdminOtherControllers.php',
        'AdminReviewController'     => 'Controllers/Admin/AdminOtherControllers.php',
        'BrandModel'                => 'Models/CollectionModel.php',
        'CustomerModel'             => 'Models/CollectionModel.php',
        'DiscountModel'             => 'Models/CollectionModel.php',
    ];

    private static array $dirs = [
        'Controllers/Admin/',
        'Controllers/Api/',
        'Controllers/',
        'Models/',
        'Helpers/',
        'Middleware/',
        'Core/',
    ];

    public static function register(): void
    {
        spl_autoload_register(function (string $class) {
            if (isset(self::$grouped[$class])) {
                $file = APP_PATH . '/' . self::$grouped[$class];
                if (!isset(self::$loaded[$file]) && file_exists($file)) {
                    require_once $file;
                    self::$loaded[$file] = true;
                }
                return;
            }
            foreach (self::$dirs as $dir) {
                $file = APP_PATH . '/' . $dir . $class . '.php';
                if (file_exists($file)) {
                    require_once $file;
                    return;
                }
            }
        });
    }
}

Autoloader::register();
