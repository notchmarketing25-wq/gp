<?php
/**
 * SupabaseClient — thin wrapper around the Supabase REST API (PostgREST).
 * Reads connection details from settings (supabase_url, supabase_api_key).
 */
class SupabaseClient
{
    private string $url;
    private string $key;
    public ?string $lastError = null;

    public function __construct(?string $url = null, ?string $key = null)
    {
        $this->url = rtrim(trim($url ?? SettingModel::get('supabase_url', '')), '/');
        $this->key = trim($key ?? SettingModel::get('supabase_api_key', ''));
    }

    public function isConfigured(): bool
    {
        return $this->url !== '' && $this->key !== '';
    }

    /** List all tables the API key can see (via PostgREST OpenAPI root document). Sets lastError on failure. */
    public function listTables(): array
    {
        $res = $this->request('GET', '/', ['Accept: application/openapi+json']);
        if (!$res['ok']) { $this->lastError = $res['error']; return []; }

        $paths = $res['body']['paths'] ?? null;
        if ($paths === null) {
            $this->lastError = 'الاتصال نجح لكن الاستجابة ما فيهاش قائمة جداول (paths) — تأكد إن الـ API key نوعه صحيح (anon أو service_role)';
            return [];
        }
        $tables = [];
        foreach (array_keys($paths) as $p) {
            $t = trim($p, '/');
            if ($t && !str_starts_with($t, 'rpc/')) $tables[] = $t;
        }
        sort($tables);
        return $tables;
    }

    /** Fetch rows from a table with pagination. Returns [rows, error] */
    public function fetchRows(string $table, int $limit = 1000, int $offset = 0): array
    {
        $res = $this->request('GET', "/{$table}?select=*&limit={$limit}&offset={$offset}");
        if (!$res['ok']) return ['rows' => [], 'error' => $res['error']];
        return ['rows' => is_array($res['body']) ? $res['body'] : [], 'error' => null];
    }

    /** Fetch ALL rows from a table, paging automatically (capped for safety) */
    public function fetchAllRows(string $table, int $maxRows = 20000): array
    {
        $all = []; $offset = 0; $pageSize = 1000;
        while (count($all) < $maxRows) {
            $res = $this->fetchRows($table, $pageSize, $offset);
            if ($res['error']) return ['rows' => $all, 'error' => $res['error']];
            if (empty($res['rows'])) break;
            $all = array_merge($all, $res['rows']);
            if (count($res['rows']) < $pageSize) break;
            $offset += $pageSize;
        }
        return ['rows' => $all, 'error' => null];
    }

    /** Get column names by sampling the first row of a table */
    public function sampleColumns(string $table): array
    {
        $res = $this->fetchRows($table, 1, 0);
        if ($res['error']) { $this->lastError = $res['error']; return []; }
        if (empty($res['rows'])) { $this->lastError = 'الجدول موجود لكن فارغ من البيانات — تعذر معرفة أسماء الأعمدة'; return []; }
        return array_keys($res['rows'][0]);
    }

    private function request(string $method, string $path, array $extraHeaders = []): array
    {
        if (!function_exists('curl_init')) {
            return ['ok' => false, 'body' => null, 'error' => 'إضافة cURL غير مفعّلة على السيرفر — تواصل مع الاستضافة لتفعيلها (PHP curl extension)'];
        }
        if (!preg_match('#^https?://#i', $this->url)) {
            return ['ok' => false, 'body' => null, 'error' => 'رابط Supabase لازم يبدأ بـ https:// — مثال: https://xxxxx.supabase.co'];
        }

        $ch = curl_init($this->url . '/rest/v1' . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 3,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTPHEADER     => array_merge([
                'apikey: ' . $this->key,
                'Authorization: Bearer ' . $this->key,
                'Content-Type: application/json',
            ], $extraHeaders),
        ]);
        $body = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        $errno = curl_errno($ch);
        curl_close($ch);

        // SSL cert issues are common on shared hosting with outdated CA bundles — retry once without strict verification
        // so the user gets a clear result instead of a silent connection failure (still uses HTTPS transport).
        if ($errno === CURLE_SSL_CACERT || $errno === CURLE_SSL_CONNECT_ERROR) {
            $ch = curl_init($this->url . '/rest/v1' . $path);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST  => $method,
                CURLOPT_TIMEOUT        => 30,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_HTTPHEADER     => array_merge([
                    'apikey: ' . $this->key,
                    'Authorization: Bearer ' . $this->key,
                    'Content-Type: application/json',
                ], $extraHeaders),
            ]);
            $body = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err = curl_error($ch);
            curl_close($ch);
        }

        if ($err) return ['ok' => false, 'body' => null, 'error' => "تعذر الاتصال بالسيرفر: $err"];
        if ($httpCode === 401 || $httpCode === 403) return ['ok' => false, 'body' => null, 'error' => "مرفوض (HTTP $httpCode) — الـ API key غلط أو منتهي الصلاحية"];
        if ($httpCode === 404) return ['ok' => false, 'body' => null, 'error' => "الرابط غير صحيح (HTTP 404) — تأكد إن رابط Supabase مكتوب صح بدون / في الآخر"];
        if ($httpCode >= 400) return ['ok' => false, 'body' => null, 'error' => "HTTP $httpCode: " . substr((string)$body, 0, 300)];
        if ($body === false || $body === '') return ['ok' => false, 'body' => null, 'error' => 'السيرفر رجّع استجابة فارغة'];

        $decoded = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['ok' => false, 'body' => null, 'error' => 'استجابة غير صالحة (ليست JSON): ' . substr((string)$body, 0, 200)];
        }

        return ['ok' => true, 'body' => $decoded, 'error' => null];
    }
}
