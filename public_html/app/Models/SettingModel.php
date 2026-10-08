<?php
class SettingModel extends Model
{
    protected string $table = 'settings';
    private static array $_cache = [];
    private static bool $_loaded = false;

    public static function get(string $key, mixed $default = ''): mixed
    {
        if (!self::$_loaded) {
            self::loadAll();
        }
        return self::$_cache[$key] ?? $default;
    }

    /** Force the next get() call to re-read from the database */
    public static function resetCache(): void
    {
        self::$_loaded = false;
        self::$_cache = [];
    }

    public static function set(string $key, mixed $value): void
    {
        Database::execute(
            "INSERT INTO `settings` (`key`, `value`) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)",
            [$key, $value]
        );
        self::$_cache[$key] = $value;
    }

    public static function setMany(array $data): void
    {
        foreach ($data as $key => $value) {
            self::set($key, $value);
        }
        // Reset cache so next load reads fresh from DB
        self::$_loaded = false;
        self::$_cache  = [];
    }

    private static function loadAll(): void
    {
        $rows = Database::fetchAll("SELECT `key`, `value` FROM `settings`");
        foreach ($rows as $row) {
            self::$_cache[$row['key']] = $row['value'];
        }
        self::$_loaded = true;
    }

    public static function getAllSettings(): array
    {
        if (!self::$_loaded) {
            self::loadAll();
        }
        return self::$_cache;
    }
}
