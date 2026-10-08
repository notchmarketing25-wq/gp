<?php
class MediaController extends Controller
{
    private string $dir;
    private string $url;

    public function __construct()
    {
        AdminAuthMiddleware::handle();
        requireAdminPermission('marketing');
        $this->dir = ROOT_PATH . '/uploads/media/';
        $this->url = APP_URL . '/uploads/media/';
        if (!is_dir($this->dir)) mkdir($this->dir, 0755, true);
    }

    public function index(): void
    {
        $files = $this->getFiles();
        $this->view('admin.media.index', compact('files'));
    }

    public function list(string $nonce = ''): void
    {
        $q = trim($this->get('q',''));
        $files = $this->getFiles($q);
        $this->json(['files' => $files]);
    }

    public function upload(string $nonce = ''): void
    {
        if (!verifyCsrf()) { $this->json(['ok'=>false,'msg'=>'خطأ في التحقق (CSRF) — أعد تحميل الصفحة وحاول تاني']); }

        // Classic silent-failure case: the browser sent a file larger than the server's post_max_size,
        // so PHP discards the whole request body before it reaches us — $_FILES AND $_POST both come
        // back completely empty, with no error code to catch. Detect it via Content-Length so the
        // person gets an accurate message instead of a generic "no files received".
        if (empty($_FILES) && empty($_POST) && !empty($_SERVER['CONTENT_LENGTH']) && (int)$_SERVER['CONTENT_LENGTH'] > 0) {
            $sentMb = round((int)$_SERVER['CONTENT_LENGTH'] / 1024 / 1024, 1);
            $this->json(['ok'=>false,'msg'=>"الملف ({$sentMb} ميجا) أكبر من الحد المسموح به على السيرفر حالياً — قلل حجم الملف أو تواصل مع الدعم لرفع الحد"]);
        }
        if (empty($_FILES['images']['tmp_name'])) { $this->json(['ok'=>false,'msg'=>'لم يتم استلام أي ملفات']); }

        $videoExts = ['mp4','webm','mov'];
        $uploaded = [];
        $errors = [];
        foreach ($_FILES['images']['tmp_name'] as $i => $tmp) {
            $file = [
                'name'     => $_FILES['images']['name'][$i],
                'type'     => $_FILES['images']['type'][$i],
                'tmp_name' => $tmp,
                'error'    => $_FILES['images']['error'][$i],
                'size'     => $_FILES['images']['size'][$i],
            ];
            if ($file['error'] === UPLOAD_ERR_NO_FILE) continue;

            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $path = in_array($ext, $videoExts, true)
                ? uploadVideoFile($file, 'media')
                : uploadFile($file, 'media');

            if ($path) {
                $uploaded[] = ['name' => basename($path), 'url' => uploadUrl($path), 'type' => in_array($ext, $videoExts, true) ? 'video' : 'image'];
            } else {
                $errors[] = ($file['name'] ?: 'ملف') . ': ' . (uploadFileError() ?? 'فشل غير معروف');
            }
        }

        $this->json(['ok' => !empty($uploaded), 'uploaded' => $uploaded, 'errors' => $errors]);
    }

    public function delete(): void
    {
        if (!verifyCsrf()) { $this->json(['ok'=>false]); }
        $name = basename($this->post('name',''));
        $path = $this->dir . $name;
        if ($name && file_exists($path)) unlink($path);
        $this->json(['ok'=>true]);
    }

    private function getFiles(string $q = ''): array
    {
        $files = [];
        // GLOB_BRACE isn't supported on every PHP/hosting build — glob per extension instead for reliability
        $exts = ['jpg','jpeg','png','gif','webp','JPG','JPEG','PNG','GIF','WEBP','mp4','webm','mov','MP4','WEBM','MOV'];
        $videoExts = ['mp4','webm','mov'];
        $seen = [];
        foreach ($exts as $ext) {
            foreach (glob($this->dir . '*.' . $ext) ?: [] as $f) {
                $name = basename($f);
                if (isset($seen[$name])) continue; // avoid duplicates on case-insensitive filesystems
                $seen[$name] = true;
                if ($q && stripos($name, $q) === false) continue;
                $files[] = [
                    'name'     => $name,
                    'url'      => $this->url . $name,
                    'size'     => round(filesize($f)/1024,1) . ' KB',
                    'modified' => filemtime($f),
                    'type'     => in_array(strtolower($ext), $videoExts, true) ? 'video' : 'image',
                ];
            }
        }
        usort($files, fn($a,$b) => $b['modified'] - $a['modified']);
        return $files;
    }
}
