<?php
class UpdaterController extends Controller
{
    private string $updatesDir;

    public function __construct()
    {
        AdminAuthMiddleware::handle();
        requireAdminPermission('settings');
        $this->updatesDir = ROOT_PATH . '/updates';
        if (!is_dir($this->updatesDir)) mkdir($this->updatesDir, 0755, true);
    }

    public function index(): void
    {
        $packages = $this->getPackages();
        $history  = Database::fetchAll("SELECT * FROM `update_log` ORDER BY applied_at DESC LIMIT 20");
        $this->view('admin.settings.updater', compact('packages', 'history'));
    }

    public function upload(): void
    {
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect($this->adminUrl('updater')); }

        if (empty($_FILES['package']['name']) || $_FILES['package']['error'] !== 0) {
            flashError('حدث خطأ أثناء الرفع');
            $this->redirect($this->adminUrl('updater'));
        }

        $file = $_FILES['package'];
        if (pathinfo($file['name'], PATHINFO_EXTENSION) !== 'zip') {
            flashError('يجب أن يكون الملف بصيغة ZIP');
            $this->redirect($this->adminUrl('updater'));
        }

        $dest = $this->updatesDir . '/' . $file['name'];
        move_uploaded_file($file['tmp_name'], $dest);

        flashSuccess('تم رفع الحزمة: ' . $file['name']);
        $this->redirect($this->adminUrl('updater'));
    }

    public function apply(string $version): void
    {
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect($this->adminUrl('updater')); }

        $zipFile = $this->updatesDir . '/' . $version . '.zip';
        if (!file_exists($zipFile)) {
            flashError('الحزمة غير موجودة');
            $this->redirect($this->adminUrl('updater'));
        }

        // Backup config
        $configBackup = ROOT_PATH . '/updates/' . $version . '_config_backup.php';
        copy(ROOT_PATH . '/config/config.php', $configBackup);

        $zip = new ZipArchive();
        if ($zip->open($zipFile) !== true) {
            flashError('تعذر فتح ملف ZIP');
            $this->redirect($this->adminUrl('updater'));
        }

        $zip->extractTo(ROOT_PATH . '/');
        $zip->close();

        // Restore config
        if (file_exists($configBackup)) {
            copy($configBackup, ROOT_PATH . '/config/config.php');
        }

        // Log
        Database::insert(
            "INSERT INTO `update_log` (`version`, `description`) VALUES (?, ?)",
            [$version, 'تم التحديث عبر لوحة التحكم']
        );

        flashSuccess('تم تطبيق التحديث ' . $version . ' بنجاح!');
        $this->redirect($this->adminUrl('updater'));
    }

    public function rollback(string $version): void
    {
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect($this->adminUrl('updater')); }

        $backup = $this->updatesDir . '/' . $version . '_config_backup.php';
        if (file_exists($backup)) {
            copy($backup, ROOT_PATH . '/config/config.php');
            flashSuccess('تم استعادة الإعدادات السابقة');
        } else {
            flashError('لا يوجد نسخة احتياطية لهذا الإصدار');
        }

        $this->redirect($this->adminUrl('updater'));
    }

    private function getPackages(): array
    {
        $files = glob($this->updatesDir . '/*.zip') ?: [];
        $packages = [];
        foreach ($files as $f) {
            $name = basename($f, '.zip');
            $packages[] = [
                'name'     => $name,
                'file'     => basename($f),
                'size'     => round(filesize($f) / 1024, 1) . ' KB',
                'modified' => date('d/m/Y H:i', filemtime($f)),
                'hasBackup' => file_exists($this->updatesDir . '/' . $name . '_config_backup.php'),
            ];
        }
        return array_reverse($packages);
    }
}
