<?php
// ═══════════════════════════════════════════════════════════════════
// Notch Technology — Routes
// ═══════════════════════════════════════════════════════════════════

$admin = ADMIN_PREFIX;

// ─── Store Routes ──────────────────────────────────────────────────

// Home
$router->get('', 'StoreController@home');
$router->get('products', 'StoreController@products');
$router->get('products/{slug}', 'StoreController@product');
$router->get('collections/{slug}', 'StoreController@collection');
$router->get('brands/{slug}', 'StoreController@brand');
$router->get('search', 'StoreController@search');
$router->get('about', 'StoreController@about');
$router->get('contact', 'StoreController@contact');
$router->post('contact', 'StoreController@contactSubmit');

// Auth — Customer
$router->get('login', 'AuthController@loginForm');
$router->post('login', 'AuthController@login');
$router->get('register', 'AuthController@registerForm');
$router->post('register', 'AuthController@register');
$router->get('logout', 'AuthController@logout');
$router->get('forgot-password', 'AuthController@forgotForm');
$router->post('forgot-password', 'AuthController@forgot');

// Customer Account
$router->get('account', 'AccountController@dashboard');
$router->get('account/orders', 'AccountController@orders');
$router->get('account/orders/{id}', 'AccountController@orderDetail');
$router->get('account/addresses', 'AccountController@addresses');
$router->post('account/addresses', 'AccountController@addAddress');
$router->post('account/addresses/delete', 'AccountController@deleteAddress');
$router->get('account/wishlist', 'AccountController@wishlist');
$router->get('account/warranties', 'AccountController@warranties');
$router->get('account/profile', 'AccountController@profile');
$router->post('account/profile', 'AccountController@updateProfile');

// Cart
$router->get('cart', 'CartController@index');
$router->post('cart/add', 'CartController@add');
$router->post('cart/update', 'CartController@update');
$router->post('cart/remove', 'CartController@remove');
$router->post('cart/clear', 'CartController@clear');
$router->post('cart/discount', 'CartController@discount');
$router->get('cart/count', 'CartController@count');

// Checkout
$router->get('checkout', 'CheckoutController@index');
$router->post('checkout', 'CheckoutController@process');
$router->get('checkout/success/{order_number}', 'CheckoutController@success');

// Payment
$router->get('payment/callback', 'PaymentController@callback');
$router->post('payment/callback', 'PaymentController@callback');
$router->get('payment/success', 'PaymentController@success');
$router->get('payment/failed', 'PaymentController@failed');

// Wishlist (Ajax)
$router->post('wishlist/toggle', 'WishlistController@toggle');

// ─── Admin Routes ──────────────────────────────────────────────────

// Auth
$router->get("{$admin}/login", 'AdminAuthController@loginForm');
$router->post("{$admin}/login", 'AdminAuthController@login');
$router->get("{$admin}/logout", 'AdminAuthController@logout');

// Dashboard
$router->get($admin, 'DashboardController@index');
$router->get("{$admin}/dashboard", 'DashboardController@index');

// Products
$router->get("{$admin}/products", 'AdminProductController@index');
$router->post("{$admin}/products/{id}/merge", 'AdminProductController@merge');
$router->get("{$admin}/products/create", 'AdminProductController@create');
$router->post("{$admin}/products/create", 'AdminProductController@store');
$router->get("{$admin}/products/search-json", 'AdminProductController@searchJson');
$router->get("{$admin}/products/{id}", 'AdminProductController@show');
$router->get("{$admin}/products/{id}/edit", 'AdminProductController@edit');
$router->post("{$admin}/products/{id}/edit", 'AdminProductController@update');
$router->post("{$admin}/products/{id}/delete", 'AdminProductController@delete');
$router->post("{$admin}/products/{id}/quick-status", 'AdminProductController@quickStatus');
$router->post("{$admin}/products/{id}/quick-price", 'AdminProductController@quickPrice');
$router->post("{$admin}/products/{id}/quick-channel", 'AdminProductController@quickChannel');
$router->post("{$admin}/products/{id}/duplicate", 'AdminProductController@duplicate');
$router->post("{$admin}/products/bulk-update", 'AdminProductController@bulkUpdate');
$router->post("{$admin}/products/{id}/variants/create",              'AdminProductController@variantStore');
$router->post("{$admin}/products/{id}/variants/{variantId}",         'AdminProductController@variantUpdate');
$router->post("{$admin}/products/{id}/variants/{variantId}/delete",  'AdminProductController@variantDelete');
$router->post("{$admin}/products/{id}/barcode", 'AdminProductController@saveBarcode');
$router->post("{$admin}/products/{id}/image-delete", 'AdminProductController@deleteImage');
$router->post('admin/products/{id}/addons/create', 'AdminProductController@addonStore');
$router->post('admin/products/{id}/addons/{addonId}/delete', 'AdminProductController@addonDelete');
$router->post('cart/add-bundle', 'CartController@addBundle');

// Collections
$router->get("{$admin}/collections", 'AdminCollectionController@index');
$router->get("{$admin}/collections/create", 'AdminCollectionController@create');
$router->post("{$admin}/collections/create", 'AdminCollectionController@store');
$router->get("{$admin}/collections/{id}/edit", 'AdminCollectionController@edit');
$router->post("{$admin}/collections/{id}/edit", 'AdminCollectionController@update');
$router->post("{$admin}/collections/{id}/delete", 'AdminCollectionController@delete');
$router->post("{$admin}/collections/merge", 'AdminCollectionController@merge');

// Brands
$router->get("{$admin}/brands", 'AdminBrandController@index');
$router->post("{$admin}/brands/create", 'AdminBrandController@store');
$router->post("{$admin}/brands/{id}/edit", 'AdminBrandController@update');
$router->post("{$admin}/brands/{id}/delete", 'AdminBrandController@delete');
$router->post("{$admin}/brands/merge", 'AdminBrandController@merge');

// Orders
$router->get("{$admin}/orders", 'AdminOrderController@index');
$router->get("{$admin}/orders/orphans", 'AdminOrderController@orphanProducts');
$router->get("{$admin}/orders/{id}", 'AdminOrderController@show');
$router->post("{$admin}/orders/{id}/status", 'AdminOrderController@updateStatus');
$router->post("{$admin}/orders/{id}/notes", 'AdminOrderController@updateNotes');
$router->post("{$admin}/orders/relink-products", 'AdminOrderController@relinkProducts');

// ── Shipping Module (Carriers + Shipments) ────────────────────────────
$router->get("{$admin}/shipping",                   'ShippingController@index');
$router->post("{$admin}/shipping/create",           'ShippingController@store');
$router->post("{$admin}/shipping/{id}",             'ShippingController@update');
$router->post("{$admin}/shipping/{id}/delete",      'ShippingController@delete');
$router->get("{$admin}/shipping/carriers",          'ShippingController@carriers');
$router->post("{$admin}/shipping/carriers/create",  'ShippingController@storeCarrier');
$router->post("{$admin}/shipping/carriers/{id}",    'ShippingController@updateCarrier');
$router->post("{$admin}/shipping/carriers/{id}/delete", 'ShippingController@deleteCarrier');
$router->get("{$admin}/shipping/import",            'ShippingController@importForm');
$router->post("{$admin}/shipping/import/preview",   'ShippingController@previewImport');
$router->post("{$admin}/shipping/import/confirm",   'ShippingController@confirmImport');

// Customers
$router->get("{$admin}/customers", 'AdminCustomerController@index');
$router->get("{$admin}/customers/{id}", 'AdminCustomerController@show');
$router->post("{$admin}/customers/{id}/toggle", 'AdminCustomerController@toggle');
$router->post("{$admin}/customers/{id}/wholesale/approve", 'AdminCustomerController@approveWholesale');
$router->post("{$admin}/customers/{id}/wholesale/reject",  'AdminCustomerController@rejectWholesale');
$router->post("{$admin}/customers/{id}/credit",             'AdminCustomerController@adjustCredit');

// ── Wholesale (storefront) ──────────────────────────────────────────
$router->get("wholesale", 'WholesaleController@apply');
$router->post("wholesale/apply", 'WholesaleController@submitApplication');

// ── Support system (public + admin) ──────────────────────────────────
$router->get("support",         'SupportController@index');
$router->post("support/submit", 'SupportController@submit');
$router->get("support/track",   'SupportController@track');
$router->get("{$admin}/support",              'SupportAdminController@index');
$router->get("{$admin}/support/{id}",         'SupportAdminController@show');
$router->post("{$admin}/support/{id}/reply",  'SupportAdminController@reply');
$router->post("{$admin}/support/{id}/status", 'SupportAdminController@updateStatus');

// ── Instagram reels carousel (admin) ──────────────────────────────────
$router->get("{$admin}/instagram",              'InstagramReelController@index');
$router->post("{$admin}/instagram/create",      'InstagramReelController@store');
$router->post("{$admin}/instagram/{id}/delete", 'InstagramReelController@delete');
$router->post("{$admin}/instagram/{id}/toggle", 'InstagramReelController@toggle');
$router->post("{$admin}/instagram/{id}/reorder", 'InstagramReelController@reorder');
$router->get("warranty",           'WarrantyPublicController@form');
$router->post("warranty/activate", 'WarrantyPublicController@activate');
$router->post("warranty/check",    'WarrantyPublicController@check');

// ── Retailers (public + admin) ────────────────────────────────────────
$router->get("retailers", 'RetailerPublicController@index');
$router->get("{$admin}/retailers",             'RetailerController@index');
$router->post("{$admin}/retailers/create",     'RetailerController@store');
$router->post("{$admin}/retailers/{id}",       'RetailerController@update');
$router->post("{$admin}/retailers/{id}/delete", 'RetailerController@delete');

// ── Menu management (admin) ────────────────────────────────────────
$router->get("{$admin}/menu",              'MenuController@index');
$router->post("{$admin}/menu/create",      'MenuController@store');
$router->post("{$admin}/menu/{id}",        'MenuController@update');
$router->post("{$admin}/menu/{id}/delete", 'MenuController@delete');
$router->post("{$admin}/menu/{id}/reorder", 'MenuController@reorder');

// ── Admin Users (superadmin only) ────────────────────────────────────
$router->get("{$admin}/users",             'AdminUserController@index');
$router->post("{$admin}/users/create",     'AdminUserController@store');
$router->post("{$admin}/users/{id}",       'AdminUserController@update');
$router->post("{$admin}/users/{id}/delete", 'AdminUserController@delete');

// ── Notch Business Platform (merchants / distributors / reps) ───────
$router->get("business/register",             'BusinessController@registerChoice');
$router->get("business/register/{type}",      'BusinessController@registerForm');
$router->post("business/register/{type}",     'BusinessController@register');
$router->get("business",                      'BusinessController@dashboard');
$router->get("business/orders",               'BusinessController@ordersList');
$router->get("business/products",             'BusinessController@products');
$router->get("business/products/{id}",        'BusinessController@productShow');
$router->get("business/commission",           'BusinessController@myCommission');
$router->post("business/id-card",             'BusinessController@uploadIdCard');
$router->get("business/catalog",              'BusinessController@catalog');
$router->get("business/catalog/print",        'BusinessController@catalogPrint');
$router->get("business/orders/create",        'BusinessController@orderForm');
$router->post("business/orders",              'BusinessController@orderStore');
$router->get("business/credit",               'BusinessController@creditPage');
$router->post("business/credit/apply",        'BusinessController@creditApply');
$router->get("business/expenses",             'BusinessController@expenses');
$router->post("business/expenses",            'BusinessController@expenseStore');
$router->get("business/samples",              'BusinessController@samples');
$router->post("business/samples",             'BusinessController@sampleStore');
$router->get("business/add-merchant",         'BusinessController@addMerchantForm');
$router->post("business/add-merchant",        'BusinessController@addMerchantStore');

// ── Wholesale (admin) ────────────────────────────────────────────────
$router->get("{$admin}/wholesale",                          'WholesaleAdminController@index');
$router->get("{$admin}/wholesale/bulk-upload",               'WholesaleAdminController@bulkUpload');
$router->post("{$admin}/wholesale/bulk-upload/preview",      'WholesaleAdminController@previewBulkUpload');
$router->post("{$admin}/wholesale/bulk-upload/confirm",      'WholesaleAdminController@confirmBulkUpload');
$router->get("{$admin}/wholesale/customers/bulk-upload",          'WholesaleAdminController@bulkUploadCustomers');
$router->post("{$admin}/wholesale/customers/bulk-upload/preview", 'WholesaleAdminController@previewBulkCustomers');
$router->post("{$admin}/wholesale/customers/bulk-upload/confirm", 'WholesaleAdminController@confirmBulkCustomers');

// ── Notch Business Platform (admin) ───────────────────────────────────
$router->get("{$admin}/business",                    'BusinessAdminController@index');
$router->get("{$admin}/business/tiers",               'BusinessAdminController@tiers');
$router->post("{$admin}/business/tiers/create",       'BusinessAdminController@storeTier');
$router->post("{$admin}/business/tiers/{id}",         'BusinessAdminController@updateTier');
$router->post("{$admin}/business/tiers/{id}/delete",  'BusinessAdminController@deleteTier');
$router->get("{$admin}/business/expenses",            'BusinessAdminController@expenses');
$router->post("{$admin}/business/expenses/{id}/decide", 'BusinessAdminController@expenseDecide');
$router->get("{$admin}/business/samples",             'BusinessAdminController@samples');
$router->post("{$admin}/business/samples/{id}/decide", 'BusinessAdminController@sampleDecide');
$router->get("{$admin}/business/{id}",                'BusinessAdminController@show');
$router->post("{$admin}/business/{id}/approve",       'BusinessAdminController@approve');
$router->post("{$admin}/business/{id}/reject",        'BusinessAdminController@reject');
$router->post("{$admin}/business/{id}/tier",          'BusinessAdminController@assignTier');
$router->post("{$admin}/business/{id}/commission",    'BusinessAdminController@setCommission');
$router->post("{$admin}/business/{id}/location",      'BusinessAdminController@updateLocation');

// ── API settings (admin) ──────────────────────────────────────────────
$router->get("{$admin}/settings/api",              'ApiSettingsController@index');
$router->post("{$admin}/settings/api/create",       'ApiSettingsController@store');
$router->post("{$admin}/settings/api/{id}/toggle",  'ApiSettingsController@toggle');
$router->post("{$admin}/settings/api/{id}/delete",  'ApiSettingsController@delete');

// ── Reward rules (wallet incentives) ──────────────────────────────────
$router->get("{$admin}/settings/rewards",           'RewardRulesController@index');
$router->post("{$admin}/settings/rewards/create",   'RewardRulesController@store');
$router->post("{$admin}/settings/rewards/{id}",     'RewardRulesController@update');
$router->post("{$admin}/settings/rewards/{id}/toggle", 'RewardRulesController@toggle');
$router->post("{$admin}/settings/rewards/{id}/delete", 'RewardRulesController@delete');

// ── Accounting: expenses + net income report ──────────────────────────
$router->get("{$admin}/expenses",           'ExpenseController@index');
$router->post("{$admin}/expenses/create",   'ExpenseController@store');
$router->post("{$admin}/expenses/{id}",     'ExpenseController@update');
$router->post("{$admin}/expenses/{id}/delete", 'ExpenseController@delete');
$router->get("{$admin}/finance",            'FinanceController@index');

// ── Inventory vouchers (formal stock in/out documents) ────────────────
$router->get("{$admin}/inventory",           'InventoryVoucherController@index');
$router->get("{$admin}/inventory/create",    'InventoryVoucherController@create');
$router->post("{$admin}/inventory/create",   'InventoryVoucherController@store');
$router->get("{$admin}/inventory/{id}",      'InventoryVoucherController@show');
$router->post("{$admin}/inventory/{id}/confirm", 'InventoryVoucherController@confirm');
$router->post("{$admin}/inventory/{id}/cancel",  'InventoryVoucherController@cancel');

// ── Public REST API (key-protected, read-only) ───────────────────────
$router->get("api/v1/summary",   'ApiController@summary');
$router->get("api/v1/orders",    'ApiController@orders');
$router->get("api/v1/shipments", 'ApiController@shipments');
$router->post("{$admin}/business/{id}/credit/approve", 'BusinessAdminController@approveCredit');
$router->post("{$admin}/business/{id}/credit/reject",  'BusinessAdminController@rejectCredit');

// ── Email templates & tracking (admin) ─────────────────────────────
$router->get("{$admin}/emails/templates",              'EmailAdminController@templates');
$router->get("{$admin}/emails/templates/{id}/edit",    'EmailAdminController@editTemplate');
$router->post("{$admin}/emails/templates/{id}",        'EmailAdminController@updateTemplate');
$router->post("{$admin}/emails/templates/{id}/test",   'EmailAdminController@sendTest');
$router->get("{$admin}/emails/logs",                   'EmailAdminController@logs');
$router->get("{$admin}/emails/settings",                'EmailAdminController@settings');
$router->post("{$admin}/emails/settings",               'EmailAdminController@updateSettings');

// ── Cache utilities ──────────────────────────────────────────────────
$router->post("{$admin}/cache/clear", 'CacheController@clear');

// ── Supabase Sync Tool (admin) ──────────────────────────────────────
$router->get("{$admin}/sync/supabase",                  'SupabaseSyncController@index');
$router->post("{$admin}/sync/supabase/config",           'SupabaseSyncController@saveConfig');
$router->get("{$admin}/sync/supabase/test",              'SupabaseSyncController@testConnection');
$router->get("{$admin}/sync/supabase/test/{nonce}",       'SupabaseSyncController@testConnection');
$router->get("{$admin}/sync/supabase/columns",           'SupabaseSyncController@tableColumns');
$router->get("{$admin}/sync/supabase/columns/{nonce}",    'SupabaseSyncController@tableColumns');
$router->get("{$admin}/sync/supabase/{entity}",          'SupabaseSyncController@mapForm');
$router->post("{$admin}/sync/supabase/{entity}/preview", 'SupabaseSyncController@preview');
$router->post("{$admin}/sync/supabase/{entity}/confirm", 'SupabaseSyncController@confirm');

// ── Shopify Import (admin) ───────────────────────────────────────────
$router->get("{$admin}/import/shopify",          'ImportController@shopify');
$router->post("{$admin}/import/shopify/preview", 'ImportController@previewShopify');
$router->post("{$admin}/import/shopify/confirm", 'ImportController@confirmShopify');
$router->get("{$admin}/import/shopify-customers",          'ImportController@shopifyCustomers');
$router->post("{$admin}/import/shopify-customers/preview", 'ImportController@previewShopifyCustomers');
$router->post("{$admin}/import/shopify-customers/confirm", 'ImportController@confirmShopifyCustomers');
$router->get("{$admin}/import/shopify-orders",          'ImportController@shopifyOrders');
$router->post("{$admin}/import/shopify-orders/preview", 'ImportController@previewShopifyOrders');
$router->post("{$admin}/import/shopify-orders/confirm", 'ImportController@confirmShopifyOrders');

// Discounts
$router->get("{$admin}/discounts", 'AdminDiscountController@index');
$router->post("{$admin}/discounts/create", 'AdminDiscountController@store');
$router->post("{$admin}/discounts/{id}/toggle", 'AdminDiscountController@toggle');
$router->post("{$admin}/discounts/{id}/delete", 'AdminDiscountController@delete');

// Analytics
$router->get("{$admin}/analytics", 'AdminAnalyticsController@index');
$router->get("{$admin}/analytics/export", 'AdminAnalyticsController@export');

// Settings
$router->get("{$admin}/settings", 'AdminSettingsController@index');
$router->post("{$admin}/settings", 'AdminSettingsController@update');
$router->get("{$admin}/settings/shipping", 'AdminSettingsController@shipping');
$router->post("{$admin}/settings/shipping", 'AdminSettingsController@updateShipping');
$router->post("{$admin}/settings/shipping/{id}/delete", 'AdminSettingsController@deleteShipping');
$router->post("{$admin}/settings/shipping/{id}", 'AdminSettingsController@editShipping');

// Reviews
$router->get("{$admin}/reviews", 'AdminReviewController@index');
$router->post("{$admin}/reviews/{id}/approve", 'AdminReviewController@approve');
$router->post("{$admin}/reviews/{id}/delete", 'AdminReviewController@delete');

// Updater
$router->get("{$admin}/updater", 'UpdaterController@index');
$router->post("{$admin}/updater/upload", 'UpdaterController@upload');
$router->post("{$admin}/updater/apply/{version}", 'UpdaterController@apply');
$router->post("{$admin}/updater/rollback/{version}", 'UpdaterController@rollback');

// ── Warehouse ─────────────────────────────────────────────────────
$router->get("{$admin}/warehouse",                'WarehouseController@index');
$router->post("{$admin}/warehouse/adjust",        'WarehouseController@adjust');
$router->post("{$admin}/warehouse/bulk-adjust",   'WarehouseController@bulkAdjust');
$router->post("{$admin}/warehouse/bulk-save",     'WarehouseController@bulkSave');
$router->get("{$admin}/warehouse/movements",      'WarehouseController@movements');

// ── Bulk Operations ───────────────────────────────────────────────
$router->get("{$admin}/bulk/orders",                'AdminBulkController@orders');
$router->post("{$admin}/bulk/orders/action",        'AdminBulkController@ordersAction');
$router->get("{$admin}/bulk/customers",             'AdminBulkController@customers');
$router->post("{$admin}/bulk/customers/action",     'AdminBulkController@customersAction');

// ── Media Library ─────────────────────────────────────────────────
$router->get("{$admin}/media",             'MediaController@index');
$router->get("{$admin}/media/list",        'MediaController@list');
$router->get("{$admin}/media/list/{nonce}", 'MediaController@list');
$router->post("{$admin}/media/upload",     'MediaController@upload');
$router->post("{$admin}/media/upload/{nonce}", 'MediaController@upload');
$router->post("{$admin}/media/delete",     'MediaController@delete');

// ── Warranties ────────────────────────────────────────────────────
$router->get("{$admin}/warranties",            'WarrantyController@index');
$router->post("{$admin}/warranties/create",    'WarrantyController@store');
$router->post("{$admin}/warranties/check",     'WarrantyController@check');
$router->post("{$admin}/warranties/{id}/update", 'WarrantyController@update');

// ── Theme quick toggle ─────────────────────────────────────────────
$router->post("{$admin}/settings/theme",    'AdminSettingsController@setTheme');

// ── Banners ────────────────────────────────────────────────────────
$router->get("{$admin}/banners",             'BannerController@index');
$router->post("{$admin}/banners/create",     'BannerController@store');
$router->post("{$admin}/banners/{id}",       'BannerController@update');
$router->post("{$admin}/banners/{id}/delete",'BannerController@delete');
