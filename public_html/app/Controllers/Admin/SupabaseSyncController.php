<?php
class SupabaseSyncController extends Controller
{
    /** Local entity presets: target table, model class, candidate unique keys, syncable fields */
    private array $entities = [
        'products' => [
            'label' => 'المنتجات', 'table' => 'products', 'model' => 'ProductModel',
            'unique_keys' => ['sku','slug'],
            'fields' => ['name','slug','sku','description','short_desc','price','compare_price','stock','thumbnail','status'],
        ],
        'collections' => [
            'label' => 'التصنيفات', 'table' => 'collections', 'model' => 'CollectionModel',
            'unique_keys' => ['slug','name'],
            'fields' => ['name','slug','description','image','is_active'],
        ],
        'brands' => [
            'label' => 'الماركات', 'table' => 'brands', 'model' => null,
            'unique_keys' => ['slug','name'],
            'fields' => ['name','slug','logo','is_active'],
        ],
        'customers' => [
            'label' => 'العملاء', 'table' => 'customers', 'model' => 'CustomerModel',
            'unique_keys' => ['email'],
            'fields' => ['name','email','phone'],
        ],
    ];

    public function __construct() { AdminAuthMiddleware::handle(); requireAdminPermission('settings'); }

    // ── Connection settings ──────────────────────────────────────────
    public function index(): void
    {
        $configured = SettingModel::get('supabase_url') && SettingModel::get('supabase_api_key');
        $s = ['supabase_url' => SettingModel::get('supabase_url',''), 'supabase_api_key' => SettingModel::get('supabase_api_key','')];
        $logs = Database::fetchAll("SELECT * FROM supabase_sync_logs ORDER BY created_at DESC LIMIT 20");
        $this->view('admin.sync.index', ['configured'=>$configured,'s'=>$s,'entities'=>$this->entities,'logs'=>$logs]);
    }

    public function saveConfig(): void
    {
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect(adminUrl('sync/supabase')); }
        SettingModel::setMany([
            'supabase_url'     => trim($this->post('supabase_url','')),
            'supabase_api_key' => trim($this->post('supabase_api_key','')),
        ]);
        flashSuccess('تم حفظ بيانات الاتصال ✅');
        $this->redirect(adminUrl('sync/supabase'));
    }

    public function testConnection(string $nonce = ''): void
    {
        $client = new SupabaseClient();
        if (!$client->isConfigured()) { $this->json(['ok'=>false,'error'=>'أدخل بيانات الاتصال أولاً']); }
        $tables = $client->listTables();
        if (empty($tables)) {
            $this->json(['ok'=>false,'error'=> $client->lastError ?? 'لا يوجد جداول متاحة لهذا المفتاح — تأكد من صلاحيات الـ API key']);
        }
        $this->json(['ok'=>true,'tables'=>$tables]);
    }

    // ── Table columns (AJAX, for building the mapping UI) ────────────
    public function tableColumns(string $nonce = ''): void
    {
        $table = $this->get('table', '');
        if (!$table) { $this->json(['ok'=>false,'error'=>'اسم جدول مطلوب']); }
        $client = new SupabaseClient();
        $cols = $client->sampleColumns($table);
        if (empty($cols)) { $this->json(['ok'=>false,'error'=> $client->lastError ?? 'الجدول فارغ أو غير موجود']); }
        $this->json(['ok'=>true,'columns'=>$cols]);
    }

    // ── Mapping form ──────────────────────────────────────────────────
    public function mapForm(string $entity): void
    {
        if (!isset($this->entities[$entity])) { flashError('نوع بيانات غير معروف'); $this->redirect(adminUrl('sync/supabase')); }
        $client = new SupabaseClient();
        $tables = $client->isConfigured() ? $client->listTables() : [];
        $this->view('admin.sync.map', ['entity'=>$entity, 'config'=>$this->entities[$entity], 'tables'=>$tables]);
    }

    // ── Preview: fetch, map, dedupe-check — NOTHING is written yet ───
    public function preview(string $entity): void
    {
        if (!isset($this->entities[$entity])) { flashError('نوع بيانات غير معروف'); $this->redirect(adminUrl('sync/supabase')); }
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect(adminUrl('sync/supabase/'.$entity)); }

        $config = $this->entities[$entity];
        $supaTable = trim($this->post('supabase_table', ''));
        $uniqueKey = $this->post('unique_key', $config['unique_keys'][0]);
        $mapping = $_POST['map'] ?? []; // [local_field => supabase_column]

        if (!$supaTable) { flashError('اختر جدول Supabase'); $this->redirect(adminUrl('sync/supabase/'.$entity)); }

        $client = new SupabaseClient();
        if (!$client->isConfigured()) { flashError('بيانات الاتصال بـ Supabase غير مكتملة'); $this->redirect(adminUrl('sync/supabase')); }

        $result = $client->fetchAllRows($supaTable);
        if ($result['error']) { flashError('فشل الجلب من Supabase: ' . $result['error']); $this->redirect(adminUrl('sync/supabase/'.$entity)); }
        $rows = $result['rows'];
        if (empty($rows)) { flashError('الجدول فارغ في Supabase'); $this->redirect(adminUrl('sync/supabase/'.$entity)); }

        // Map each Supabase row to local field names
        $mapped = [];
        foreach ($rows as $row) {
            $item = [];
            foreach ($mapping as $localField => $supaCol) {
                if ($supaCol === '') continue;
                $item[$localField] = $row[$supaCol] ?? null;
            }
            if (empty($item[$uniqueKey])) continue; // skip rows without a usable unique key value
            $mapped[] = $item;
        }

        // Check existing records in the local table by the unique key, in one query (avoids N+1)
        $keyValues = array_column($mapped, $uniqueKey);
        $existing = [];
        if (!empty($keyValues)) {
            $placeholders = implode(',', array_fill(0, count($keyValues), '?'));
            $existingRows = Database::fetchAll("SELECT `{$uniqueKey}` FROM `{$config['table']}` WHERE `{$uniqueKey}` IN ($placeholders)", $keyValues);
            $existing = array_column($existingRows, $uniqueKey);
        }

        foreach ($mapped as &$item) {
            $item['_exists'] = in_array($item[$uniqueKey], $existing);
        }
        unset($item);

        $batchId = 'sbs_' . uniqid();
        Session::set($batchId, ['entity'=>$entity, 'table'=>$config['table'], 'unique_key'=>$uniqueKey, 'supabase_table'=>$supaTable, 'items'=>$mapped]);

        $this->view('admin.sync.preview', [
            'entity' => $entity, 'config' => $config, 'items' => $mapped, 'batchId' => $batchId,
            'uniqueKey' => $uniqueKey, 'supaTable' => $supaTable, 'totalFetched' => count($rows),
        ]);
    }

    // ── Confirm: insert only the NEW records ──────────────────────────
    public function confirm(string $entity): void
    {
        if (!verifyCsrf()) { flashError('خطأ'); $this->redirect(adminUrl('sync/supabase')); }
        $batchId = $this->post('batch_id', '');
        $batch = Session::get($batchId);
        if (!$batch) { flashError('انتهت صلاحية المعاينة، أعد المحاولة'); $this->redirect(adminUrl('sync/supabase/'.$entity)); }

        $config = $this->entities[$entity] ?? null;
        if (!$config) { flashError('نوع بيانات غير معروف'); $this->redirect(adminUrl('sync/supabase')); }

        $new = 0; $skipped = 0; $errors = 0; $errorMsgs = [];

        foreach ($batch['items'] as $item) {
            if ($item['_exists']) { $skipped++; continue; }
            unset($item['_exists']);
            $item = array_filter($item, fn($v) => $v !== null);

            try {
                if ($entity === 'products' && empty($item['slug']) && !empty($item['name'])) {
                    $item['slug'] = slug($item['name']) . '-' . uniqid();
                }
                if (empty($item['status']) && $entity === 'products') $item['status'] = 'active';
                if (!isset($item['is_active']) && in_array($entity, ['collections','brands'])) $item['is_active'] = 1;

                if ($entity === 'customers') {
                    if (empty($item['password'])) $item['password'] = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
                    $item['is_active'] = $item['is_active'] ?? 1;
                }

                $cols = array_keys($item);
                $placeholders = implode(',', array_fill(0, count($cols), '?'));
                $colList = implode(',', array_map(fn($c) => "`{$c}`", $cols));
                Database::insert("INSERT INTO `{$config['table']}` ({$colList}) VALUES ({$placeholders})", array_values($item));
                $new++;
            } catch (\Throwable $e) {
                $errors++;
                $errorMsgs[] = $e->getMessage();
            }
        }

        Database::insert(
            "INSERT INTO supabase_sync_logs(entity,supabase_table,fetched_count,new_count,skipped_count,error_count,error_details,admin_id,created_at) VALUES(?,?,?,?,?,?,?,?,NOW())",
            [$entity, $batch['supabase_table'], count($batch['items']), $new, $skipped, $errors, $errors ? implode(' | ', array_slice($errorMsgs,0,5)) : null, adminUser()['id'] ?? null]
        );

        Session::remove($batchId);
        flashSuccess("تمت المزامنة ✅ — جديد: $new، متخطى (موجود مسبقاً): $skipped" . ($errors ? "، أخطاء: $errors" : ''));
        $this->redirect(adminUrl('sync/supabase'));
    }
}
