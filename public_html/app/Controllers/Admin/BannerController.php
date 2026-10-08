<?php
class BannerController extends Controller
{
    public function __construct() { AdminAuthMiddleware::handle(); requireAdminPermission('marketing'); }

    public function index(): void
    {
        $sliders = Database::fetchAll("SELECT * FROM banners WHERE type='slider' ORDER BY sort_order,id");
        $smalls  = Database::fetchAll("SELECT * FROM banners WHERE type='small'  ORDER BY sort_order,id");
        $mediums = Database::fetchAll("SELECT * FROM banners WHERE type='medium' ORDER BY sort_order,id");
        $middles = Database::fetchAll("SELECT * FROM banners WHERE type='middle' ORDER BY sort_order,id");
        $this->view('admin.banners.index', compact('sliders','smalls','mediums','middles'));
    }

    public function store(): void
    {
        // If post_max_size was exceeded, PHP discards the whole request body (including the CSRF
        // field), so a plain CSRF check here would show a confusing "خطأ" with no real explanation.
        // Content-Length still arrives even when the body was dropped, so check it first.
        if (empty($_POST) && empty($_FILES) && !empty($_SERVER['CONTENT_LENGTH']) && (int)$_SERVER['CONTENT_LENGTH'] > 2*1024*1024) {
            $sentMb = round((int)$_SERVER['CONTENT_LENGTH'] / 1024 / 1024, 1);
            flashError("الملف ({$sentMb} ميجا) أكبر من الحد المسموح به على السيرفر حالياً — قلل حجم الملف أو تواصل مع الدعم لرفع الحد");
            $this->redirect(adminUrl('banners'));
        }
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect(adminUrl('banners')); }
        requireAdminPermission('marketing', 'create');
        $type = $this->post('type','slider');
        $mediaType = (in_array($type, ['slider','medium'], true) && $this->post('media_type') === 'video') ? 'video' : 'image';

        $image = trim($this->post('image_url',''));
        if ($mediaType === 'video') {
            if (empty($_FILES['video']['name']) || $_FILES['video']['error']===UPLOAD_ERR_NO_FILE) {
                flashError('من فضلك ارفع ملف فيديو'); $this->redirect(adminUrl('banners'));
            }
            $up = uploadVideoFile($_FILES['video'], 'banners');
            if (!$up) { flashError(uploadFileError() ?? 'فشل رفع الفيديو'); $this->redirect(adminUrl('banners')); }
            $image = $up;
        } elseif (!empty($_FILES['image']['name']) && $_FILES['image']['error']!==UPLOAD_ERR_NO_FILE) {
            $up = uploadFile($_FILES['image'], 'banners');
            if ($up) { $image = $up; }
            else { flashError(uploadFileError() ?? 'فشل رفع الصورة'); $this->redirect(adminUrl('banners')); }
        } elseif ($image) {
            $image = ltrim(str_replace(APP_URL.'/uploads/', '', $image), '/');
        }
        if (!$image) { flashError('يجب رفع صورة أو فيديو أو اختيار من المكتبة'); $this->redirect(adminUrl('banners')); }

        try {
            Database::insert("INSERT INTO banners(type,title,subtitle,btn_text,link,image,media_type,sort_order,is_active) VALUES(?,?,?,?,?,?,?,?,?)",[
                $type,
                trim($this->post('title','')),
                trim($this->post('subtitle','')),
                trim($this->post('btn_text','')),
                trim($this->post('link','')),
                $image,
                $mediaType,
                (int)$this->post('sort_order',0),
                isset($_POST['is_active']) ? 1 : 0,
            ]);
            flashSuccess('تم إضافة البانر ✅');
        } catch (\Throwable $e) {
            // The banners table may still be on an older schema (missing "middle" type or the
            // media_type column) if banners_fix_migration.sql hasn't been run yet — this used to
            // throw an uncaught PDOException and crash the page, silently losing the banner.
            flashError('حصلت مشكلة في حفظ البانر — شغّل ملف database/banners_fix_migration.sql على قاعدة البيانات وجرب تاني');
        }
        $this->redirect(adminUrl('banners'));
    }

    public function update(string $id): void
    {
        if (empty($_POST) && empty($_FILES) && !empty($_SERVER['CONTENT_LENGTH']) && (int)$_SERVER['CONTENT_LENGTH'] > 2*1024*1024) {
            $sentMb = round((int)$_SERVER['CONTENT_LENGTH'] / 1024 / 1024, 1);
            flashError("الملف ({$sentMb} ميجا) أكبر من الحد المسموح به على السيرفر حالياً — قلل حجم الملف أو تواصل مع الدعم لرفع الحد");
            $this->redirect(adminUrl('banners'));
        }
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect(adminUrl('banners')); }
        requireAdminPermission('marketing', 'edit');
        $banner = Database::fetch("SELECT * FROM banners WHERE id=?",[(int)$id]);
        if (!$banner) { flashError('البانر غير موجود'); $this->redirect(adminUrl('banners')); }

        $mediaType = $banner['media_type'] ?? 'image';
        $image = $banner['image'];

        if (in_array($banner['type'], ['slider','medium'], true) && $this->post('media_type') === 'video') {
            $mediaType = 'video';
            if (!empty($_FILES['video']['name']) && $_FILES['video']['error']!==UPLOAD_ERR_NO_FILE) {
                $up = uploadVideoFile($_FILES['video'], 'banners');
                if (!$up) { flashError(uploadFileError() ?? 'فشل رفع الفيديو'); $this->redirect(adminUrl('banners')); }
                $image = $up;
            }
        } elseif (in_array($banner['type'], ['slider','medium'], true) && $this->post('media_type') === 'image') {
            $mediaType = 'image';
        }

        if ($mediaType === 'image') {
            if (!empty($_FILES['image']['name']) && $_FILES['image']['error']!==UPLOAD_ERR_NO_FILE) {
                $up = uploadFile($_FILES['image'], 'banners');
                if ($up) { $image = $up; }
                else { flashError(uploadFileError() ?? 'فشل رفع الصورة'); $this->redirect(adminUrl('banners')); }
            } elseif (!empty($_POST['image_url'])) {
                $image = ltrim(str_replace(APP_URL.'/uploads/', '', $_POST['image_url']), '/');
            }
        }

        try {
            Database::execute("UPDATE banners SET title=?,subtitle=?,btn_text=?,link=?,image=?,media_type=?,sort_order=?,is_active=? WHERE id=?",[
                trim($this->post('title','')),
                trim($this->post('subtitle','')),
                trim($this->post('btn_text','')),
                trim($this->post('link','')),
                $image,
                $mediaType,
                (int)$this->post('sort_order',0),
                isset($_POST['is_active']) ? 1 : 0,
                (int)$id,
            ]);
            flashSuccess('تم تحديث البانر ✅');
        } catch (\Throwable $e) {
            flashError('حصلت مشكلة في تحديث البانر — شغّل ملف database/banners_fix_migration.sql على قاعدة البيانات وجرب تاني');
        }
        $this->redirect(adminUrl('banners'));
    }

    public function delete(string $id): void
    {
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect(adminUrl('banners')); }
        requireAdminPermission('marketing', 'delete');
        $b = Database::fetch("SELECT image FROM banners WHERE id=?",[(int)$id]);
        if ($b && $b['image']) { try { deleteFile($b['image']); } catch(\Throwable $e){} }
        Database::execute("DELETE FROM banners WHERE id=?",[(int)$id]);
        flashSuccess('تم حذف البانر');
        $this->redirect(adminUrl('banners'));
    }
}
