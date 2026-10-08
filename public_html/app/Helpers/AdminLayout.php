<?php
/**
 * Renders a view inside the admin layout.
 * Usage from any admin view file:
 *   adminLayout('Page Title', function() { ... }, $breadcrumb);
 */
function adminLayout(string $pageTitle, callable $contentFn, array $breadcrumb = [], string $extraHead = '', string $extraScript = ''): void
{
    ob_start();
    $contentFn();
    $content = ob_get_clean();

    require APP_PATH . '/Views/admin/layouts/app.php';
}
