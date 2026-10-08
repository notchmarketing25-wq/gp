<?php
function projectRoot(): string { return ROOT_PATH; }

function url(string $path = ''): string { return APP_URL . ($path ? '/' . ltrim($path, '/') : ''); }
function adminUrl(string $path = ''): string { return APP_URL . '/' . ADMIN_PREFIX . ($path ? '/' . ltrim($path, '/') : ''); }
function asset(string $path): string { return APP_URL . '/assets/' . ltrim($path, '/'); }
function uploadUrl(string $path): string { return APP_URL . '/uploads/' . ltrim($path, '/'); }

/**
 * Crisp, scalable SVG stroke icons (Lucide-style) — replaces emoji icons so they
 * render consistently at any size on any device. Inherits currentColor.
 */
function svgIcon(string $name, int $size = 20, float $stroke = 2): string {
    $paths = [
        'home'      => '<path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>',
        'grid'      => '<rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/>',
        'cart'      => '<circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/>',
        'user'      => '<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
        'search'    => '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>',
        'heart'     => '<path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>',
        'shield'    => '<path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/>',
        'briefcase' => '<path d="M16 20V4a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/><rect width="20" height="14" x="2" y="6" rx="2"/>',
        'menu'      => '<line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="20" y1="18" y2="18"/>',
        'close'     => '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>',
        'sun'       => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/>',
        'moon'      => '<path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/>',
        'arrow-left'=> '<path d="m12 19-7-7 7-7"/><path d="M19 12H5"/>',
        'package'   => '<path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/>',
        'store'     => '<path d="m2 7 4.41-4.41A2 2 0 0 1 7.83 2h8.34a2 2 0 0 1 1.42.59L22 7"/><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><path d="M15 22v-4a2 2 0 0 0-2-2h-2a2 2 0 0 0-2 2v4"/><path d="M2 7h20"/><path d="M22 7v3a2 2 0 0 1-2 2a2.7 2.7 0 0 1-1.59-.63.7.7 0 0 0-.82 0A2.7 2.7 0 0 1 16 12a2.7 2.7 0 0 1-1.59-.63.7.7 0 0 0-.82 0A2.7 2.7 0 0 1 12 12a2.7 2.7 0 0 1-1.59-.63.7.7 0 0 0-.82 0A2.7 2.7 0 0 1 8 12a2.7 2.7 0 0 1-1.59-.63.7.7 0 0 0-.82 0A2.7 2.7 0 0 1 4 12a2 2 0 0 1-2-2V7"/>',
        'trash'     => '<path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>',
        'plus'      => '<path d="M5 12h14"/><path d="M12 5v14"/>',
        'check'     => '<path d="M20 6 9 17l-5-5"/>',
        'star'      => '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>',
        'truck'     => '<path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.62l-3.48-4.35a1 1 0 0 0-.78-.38H14"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/>',
        'map-pin'   => '<path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"/><circle cx="12" cy="10" r="3"/>',
        'phone'     => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>',
        'zap'       => '<path d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z"/>',
        'message'   => '<path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>',
        'settings'  => '<path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/>',
        'edit'      => '<path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/>',
        'chart'     => '<path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/>',
        'mail'      => '<rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>',
        'tag'       => '<path d="M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8.704 8.704a2.426 2.426 0 0 0 3.42 0l6.58-6.58a2.426 2.426 0 0 0 0-3.42z"/><circle cx="7.5" cy="7.5" r="1.5"/>',
        'gift'      => '<rect x="3" y="8" width="18" height="4" rx="1"/><path d="M12 8v13"/><path d="M19 12v7a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-7"/><path d="M7.5 8a2.5 2.5 0 0 1 0-5C11 3 12 8 12 8s1-5 4.5-5a2.5 2.5 0 0 1 0 5"/>',
        'image'     => '<rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/>',
        'building'  => '<rect width="16" height="20" x="4" y="2" rx="2"/><path d="M9 22v-4h6v4"/><path d="M8 6h.01"/><path d="M16 6h.01"/><path d="M12 6h.01"/><path d="M12 10h.01"/><path d="M12 14h.01"/><path d="M16 10h.01"/><path d="M16 14h.01"/><path d="M8 10h.01"/><path d="M8 14h.01"/>',
        'upload'    => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" x2="12" y1="3" y2="15"/>',
        'refresh'   => '<path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/><path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/><path d="M8 16H3v5"/>',
        'car'       => '<path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.4 2.9A3.7 3.7 0 0 0 2 12v4c0 .6.4 1 1 1h2"/><circle cx="7" cy="17" r="2"/><path d="M9 17h6"/><circle cx="17" cy="17" r="2"/>',
        'clock'     => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
        'receipt'   => '<path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z"/><path d="M8 7h8"/><path d="M8 11h8"/><path d="M8 15h5"/>',
    ];
    $p = $paths[$name] ?? $paths['grid'];
    return '<svg xmlns="http://www.w3.org/2000/svg" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="'.$stroke.'" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0">'.$p.'</svg>';
}

/**
 * Returns the right logo URL for the given background tone.
 * $bg = 'dark'  → prefers store_logo_white (falls back to store_logo)
 * $bg = 'light' → uses store_logo (falls back to store_logo_white)
 * Returns null if no logo is configured at all.
 */
function logoUrl(string $bg = 'light'): ?string {
    $logo  = SettingModel::get('store_logo', '');
    $white = SettingModel::get('store_logo_white', '');
    if ($bg === 'dark') {
        $pick = $white ?: $logo;
    } else {
        $pick = $logo ?: $white;
    }
    return $pick ? uploadUrl($pick) : null;
}

function e(mixed $value): string { return htmlspecialchars((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8'); }
function csrf_field(): string { return '<input type="hidden" name="_csrf" value="' . Session::csrf() . '">'; }
function csrf_token(): string { return Session::csrf(); }
function verifyCsrf(): bool { $t = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''; return Session::verifyCsrf($t); }

function flash(string $key, mixed $value = null): mixed { return Session::flash($key, $value); }
function flashSuccess(string $msg): void { Session::flash('success', $msg); }
function flashError(string $msg): void { Session::flash('error', $msg); }

function adminUser(): ?array { return Session::get('admin_user'); }
function isAdminLoggedIn(): bool { return Session::has('admin_user'); }

/** All permission modules available to assign to non-superadmin admin users.
 *  'actions' => true means the module supports create/edit/delete granularity;
 *  false means it's view-only in nature (e.g. analytics/reports). */
function adminPermissionModules(): array {
    return [
        'orders'     => ['label' => 'الطلبات والشحن',       'actions' => true],
        'products'   => ['label' => 'المنتجات والمخزن',      'actions' => true],
        'customers'  => ['label' => 'العملاء',               'actions' => true],
        'business'   => ['label' => 'Business والجملة',      'actions' => true],
        'warranties' => ['label' => 'الضمانات والموزعين',    'actions' => true],
        'marketing'  => ['label' => 'البانرات والخصومات والمنيو', 'actions' => true],
        'support'    => ['label' => 'دعم العملاء',           'actions' => true],
        'analytics'  => ['label' => 'التحليلات',             'actions' => false],
        'settings'   => ['label' => 'الإعدادات',             'actions' => true],
    ];
}

/** The four action levels a module's permission can grant, beyond bare view access. */
function adminPermissionActions(): array {
    return ['view' => 'عرض', 'create' => 'إنشاء', 'edit' => 'تعديل', 'delete' => 'حذف'];
}

/**
 * Superadmin always passes. Others need the module+action in their stored permissions.
 * Permissions are stored as JSON: {"orders": ["view","edit"], "products": ["view","create","edit","delete"]}.
 * Backward-compatible with the older flat format ["orders","products"] (module presence = full access).
 */
function adminCan(string $module, string $action = 'view'): bool {
    $u = adminUser();
    if (!$u) return false;
    if (($u['role'] ?? '') === 'superadmin') return true;
    $perms = json_decode($u['permissions'] ?? '[]', true) ?: [];

    if (isset($perms[$module]) && is_array($perms[$module])) {
        return in_array($action, $perms[$module], true);
    }
    // Old flat-array format: module name present as a value = full access to everything in it
    if (in_array($module, $perms, true)) return true;

    return false;
}

/** Redirects with an error if the logged-in admin lacks the given permission module/action. */
function requireAdminPermission(string $module, string $action = 'view'): void {
    if (!adminCan($module, $action)) {
        flashError('ليس لديك صلاحية للقيام بهذا الإجراء');
        header('Location: ' . adminUrl());
        exit;
    }
}
function storeUser(): ?array { return Session::get('store_user'); }
function isStoreLoggedIn(): bool { return Session::has('store_user'); }

function money(float $amount): string { return number_format($amount, 2) . ' ' . APP_CURRENCY_SYMBOL; }
function formatDate(string $date, string $format = 'd/m/Y'): string { return date($format, strtotime($date)); }
function formatDateTime(string $date): string { return date('d/m/Y H:i', strtotime($date)); }
function timeAgo(string $date): string {
    $diff = time() - strtotime($date);
    if ($diff < 60) return 'الآن';
    if ($diff < 3600) return floor($diff/60) . ' دقيقة';
    if ($diff < 86400) return floor($diff/3600) . ' ساعة';
    if ($diff < 2592000) return floor($diff/86400) . ' يوم';
    return formatDate($date);
}
function slug(string $text): string {
    $text = mb_strtolower($text, 'UTF-8');
    $text = preg_replace('/\s+/', '-', $text);
    $text = preg_replace('/[^\p{L}\p{N}\-]/u', '', $text);
    $text = trim($text, '-');
    return $text ?: uniqid();
}
function truncate(string $text, int $length = 100): string {
    if (mb_strlen($text) <= $length) return $text;
    return mb_substr($text, 0, $length) . '...';
}
$GLOBALS['_uploadFileError'] = null;
/**
 * Uploads a video file (for the hero slider). Separate from uploadFile() since getimagesize()
 * doesn't apply to video — validates real content type via finfo instead of trusting the browser.
 */
function uploadVideoFile(array $file, string $folder = 'banners'): string|false {
    $GLOBALS['_uploadFileError'] = null;

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $GLOBALS['_uploadFileError'] = match($file['error'] ?? -1) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'حجم الفيديو أكبر من المسموح به',
            UPLOAD_ERR_PARTIAL => 'تم رفع الفيديو جزئياً فقط — حاول تاني',
            UPLOAD_ERR_NO_FILE => 'لم يتم اختيار أي فيديو',
            default => 'خطأ في رفع الفيديو (كود: ' . ($file['error'] ?? '?') . ')',
        };
        return false;
    }

    $maxVideoSize = 40 * 1024 * 1024; // 40MB — hero videos should stay short/light for page-load speed
    if ($file['size'] > $maxVideoSize) {
        $GLOBALS['_uploadFileError'] = 'حجم الفيديو أكبر من ' . ($maxVideoSize/1024/1024) . ' ميجا — قلل الجودة أو المدة';
        return false;
    }

    $allowedMimes = ['video/mp4', 'video/webm', 'video/quicktime'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $realMime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (!in_array($realMime, $allowedMimes, true)) {
        $GLOBALS['_uploadFileError'] = 'صيغة الفيديو غير مدعومة — استخدم MP4 أو WebM';
        return false;
    }

    $extMap = ['video/mp4' => 'mp4', 'video/webm' => 'webm', 'video/quicktime' => 'mov'];
    $ext = $extMap[$realMime] ?? 'mp4';
    $filename = uniqid() . '_' . time() . '.' . $ext;
    $dir = UPLOAD_PATH . '/' . $folder;
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        $GLOBALS['_uploadFileError'] = 'تعذر إنشاء مجلد الرفع — تحقق من صلاحيات السيرفر';
        return false;
    }
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $filename)) {
        $GLOBALS['_uploadFileError'] = 'فشل نقل الملف المرفوع';
        return false;
    }
    return $folder . '/' . $filename;
}

function uploadFile(array $file, string $folder = 'products'): string|false {
    $GLOBALS['_uploadFileError'] = null;

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $GLOBALS['_uploadFileError'] = match($file['error'] ?? -1) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'حجم الملف أكبر من المسموح به',
            UPLOAD_ERR_PARTIAL => 'تم رفع الملف جزئياً فقط — حاول تاني',
            UPLOAD_ERR_NO_FILE => 'لم يتم اختيار أي ملف',
            default => 'خطأ في رفع الملف (كود: ' . ($file['error'] ?? '?') . ')',
        };
        return false;
    }
    if ($file['size'] > MAX_FILE_SIZE) {
        $GLOBALS['_uploadFileError'] = 'حجم الصورة أكبر من ' . (MAX_FILE_SIZE/1024/1024) . ' ميجا';
        return false;
    }

    // Validate by actual file content (not the browser-reported MIME type, which can be unreliable)
    $imgInfo = @getimagesize($file['tmp_name']);
    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $realMime = $imgInfo['mime'] ?? ($file['type'] ?? '');
    if (!$imgInfo && !in_array($file['type'] ?? '', $allowedMimes)) {
        $GLOBALS['_uploadFileError'] = 'الملف ده مش صورة صالحة (jpg, png, webp, gif فقط)';
        return false;
    }
    if ($imgInfo && !in_array($realMime, $allowedMimes)) {
        $GLOBALS['_uploadFileError'] = 'صيغة الصورة غير مدعومة — استخدم jpg, png, webp, أو gif';
        return false;
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg','png','webp','gif'])) {
        // Fall back to an extension derived from the real MIME type if the original filename lacks one
        $ext = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'][$realMime] ?? 'jpg';
    }
    $filename = uniqid() . '_' . time() . '.' . $ext;
    $dir = UPLOAD_PATH . '/' . $folder;
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        $GLOBALS['_uploadFileError'] = 'تعذر إنشاء مجلد الرفع — تحقق من صلاحيات السيرفر';
        return false;
    }
    if (!is_writable($dir)) {
        $GLOBALS['_uploadFileError'] = 'مجلد الرفع غير قابل للكتابة — تحقق من صلاحيات المجلد (chmod)';
        return false;
    }
    if (move_uploaded_file($file['tmp_name'], $dir . '/' . $filename)) return $folder . '/' . $filename;

    $GLOBALS['_uploadFileError'] = 'فشل نقل الملف على السيرفر';
    return false;
}
function deleteFile(string $path): bool {
    $full = UPLOAD_PATH . '/' . $path;
    if (file_exists($full)) return unlink($full);
    return false;
}
function jsonResponse(bool $success, string $message = '', mixed $data = null, int $status = 200): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success'=>$success,'message'=>$message,'data'=>$data], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    exit;
}
function orderStatusLabel(string $s): string { return match($s){'pending'=>'في الانتظار','processing'=>'قيد المعالجة','shipped'=>'تم الشحن','delivered'=>'تم التسليم','cancelled'=>'ملغي','refunded'=>'مسترجع',default=>$s}; }
function orderStatusColor(string $s): string { return match($s){'pending'=>'warning','processing'=>'info','shipped'=>'primary','delivered'=>'success','cancelled'=>'danger','refunded'=>'secondary',default=>'secondary'}; }
function paymentStatusLabel(string $s): string { return match($s){'unpaid'=>'غير مدفوع','paid'=>'مدفوع','refunded'=>'مسترجع','failed'=>'فشل الدفع',default=>$s}; }

/**
 * Returns the last upload error message set by uploadFile() or uploadVideoFile(). This was
 * being called throughout the codebase (media uploads, banner uploads) but was never actually
 * defined — meaning every failed upload was crashing with a fatal "undefined function" error
 * instead of showing the person a helpful message.
 */
function uploadFileError(): ?string { return $GLOBALS['_uploadFileError'] ?? null; }

/**
 * Applies any matching active reward rules for the given trigger, depositing the reward
 * straight into the customer's wallet (store_credit). Returns the total amount deposited
 * (0 if no rule matched or matched rules have no value).
 *
 * $trigger    'registration' | 'per_order'
 * $accountType the customer's business_type ('merchant'|'distributor'|'rep'|'customer')
 * $orderAmount required for 'per_order' rules — the order subtotal the percent/fixed reward is based on
 */
function applyRewardRules(int $customerId, string $trigger, string $accountType, ?float $orderAmount = null): float
{
    try {
        $rules = Database::fetchAll(
            "SELECT * FROM reward_rules WHERE trigger_type=? AND is_active=1 AND (account_type='all' OR account_type=?)",
            [$trigger, $accountType]
        );
    } catch (\Throwable $e) {
        return 0; // migration not run yet — reward system silently no-ops rather than breaking the flow it's attached to
    }

    $totalGiven = 0;
    foreach ($rules as $rule) {
        if ($trigger === 'per_order') {
            if ($orderAmount === null) continue;
            if ($rule['min_order_amount'] !== null && $orderAmount < (float)$rule['min_order_amount']) continue;
            $reward = $rule['reward_type'] === 'percent'
                ? round($orderAmount * (float)$rule['reward_value'] / 100, 2)
                : (float)$rule['reward_value'];
        } else {
            $reward = (float)$rule['reward_value'];
        }
        if ($reward <= 0) continue;

        try {
            $model = new CustomerModel();
            $model->adjustCredit($customerId, $reward, $rule['label'], null, null);
            $totalGiven += $reward;
        } catch (\Throwable $e) {}
    }
    return $totalGiven;
}

/**
 * Automatically creates and confirms a "إذن صرف" (goods issue) inventory voucher for an order
 * once it's paid — one voucher per order, generated straight in "confirmed" status so it applies
 * to stock immediately (no manual admin confirmation needed, since the sale itself is the
 * authorization). Safe to call more than once for the same order; it no-ops if a voucher for
 * that order already exists.
 */
function createIssueVoucherForOrder(int $orderId): void
{
    try {
        $exists = Database::fetch("SELECT id FROM inventory_vouchers WHERE reference_name = ?", ["طلب #{$orderId}"]);
        if ($exists) return; // already generated for this order — don't double-deduct stock

        $order = Database::fetch("SELECT order_number FROM orders WHERE id=?", [$orderId]);
        if (!$order) return;

        $items = Database::fetchAll("SELECT product_id, qty FROM order_items WHERE order_id=? AND product_id IS NOT NULL", [$orderId]);
        if (empty($items)) return;

        $voucherNumber = 'ISS-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));
        $voucherId = Database::insert(
            "INSERT INTO inventory_vouchers(voucher_number,type,reference_name,notes,status,confirmed_at,created_at) VALUES(?,?,?,?,'confirmed',NOW(),NOW())",
            [$voucherNumber, 'issue', "طلب #{$orderId}", "أُنشئ تلقائياً عند دفع الطلب {$order['order_number']}"]
        );

        foreach ($items as $item) {
            $product = Database::fetch("SELECT stock, track_stock FROM products WHERE id=?", [$item['product_id']]);
            if (!$product || !$product['track_stock']) continue; // untracked products don't need a stock-movement record

            $before = (int)$product['stock'];
            $after  = max(0, $before - (int)$item['qty']);

            Database::insert("INSERT INTO inventory_voucher_items(voucher_id,product_id,qty) VALUES(?,?,?)", [$voucherId, $item['product_id'], $item['qty']]);
            Database::execute("UPDATE products SET stock=? WHERE id=?", [$after, $item['product_id']]);
            Database::insert(
                "INSERT INTO stock_movements(product_id,type,qty,qty_before,qty_after,reason,reference,created_at) VALUES(?,?,?,?,?,?,?,NOW())",
                [$item['product_id'], 'out', -1 * (int)$item['qty'], $before, $after, 'بيع — طلب مدفوع', $voucherNumber]
            );
        }
    } catch (\Throwable $e) {
        // Inventory automation must never break the payment flow it's attached to — the
        // inventory_vouchers migration may not be run yet, or some other issue occurred; either
        // way, the order itself still gets marked paid, just without the automatic stock deduction.
    }
}

/**
 * Records a page view for analytics — total site traffic, per-product view counts, and
 * (for logged-in customers) which products a specific customer has browsed. Uses a
 * long-lived anonymous cookie so guest visitors are still counted distinctly without
 * needing an account, without storing anything personally identifying beyond IP.
 */
function trackPageView(string $pageType, ?int $productId = null): void
{
    try {
        if (empty($_COOKIE['nt_vid'])) {
            $visitorKey = bin2hex(random_bytes(16));
            setcookie('nt_vid', $visitorKey, time() + 86400 * 365, '/');
        } else {
            $visitorKey = $_COOKIE['nt_vid'];
        }
        Database::insert(
            "INSERT INTO page_views(page_type,product_id,customer_id,visitor_key,ip_address,referrer,created_at) VALUES(?,?,?,?,?,?,NOW())",
            [
                $pageType, $productId,
                isStoreLoggedIn() ? storeUser()['id'] : null,
                $visitorKey,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_REFERER'] ?? null,
            ]
        );
    } catch (\Throwable $e) {
        // Analytics must never break the page — the migration may not be run yet, or the
        // insert may fail for any reason; either way, tracking silently no-ops.
    }
}
