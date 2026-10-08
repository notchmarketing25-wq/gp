<?php
abstract class Model
{
    protected string $table = '';
    protected string $primaryKey = 'id';
    protected array $fillable = [];

    protected function db(): PDO
    {
        return Database::getInstance();
    }

    public function find(int $id): ?array
    {
        return Database::fetch(
            "SELECT * FROM `{$this->table}` WHERE `{$this->primaryKey}` = ? LIMIT 1",
            [$id]
        );
    }

    public function findBy(string $column, mixed $value): ?array
    {
        return Database::fetch(
            "SELECT * FROM `{$this->table}` WHERE `{$column}` = ? LIMIT 1",
            [$value]
        );
    }

    public function all(string $orderBy = 'id', string $dir = 'DESC'): array
    {
        return Database::fetchAll(
            "SELECT * FROM `{$this->table}` ORDER BY `{$orderBy}` {$dir}"
        );
    }

    public function create(array $data): int
    {
        $data = $this->filterFillable($data);
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');

        $cols   = implode('`, `', array_keys($data));
        $values = implode(', ', array_fill(0, count($data), '?'));

        return Database::insert(
            "INSERT INTO `{$this->table}` (`{$cols}`) VALUES ({$values})",
            array_values($data)
        );
    }

    public function update(int $id, array $data): int
    {
        $data = $this->filterFillable($data);
        $data['updated_at'] = date('Y-m-d H:i:s');

        $set = implode(', ', array_map(fn($col) => "`{$col}` = ?", array_keys($data)));

        return Database::execute(
            "UPDATE `{$this->table}` SET {$set} WHERE `{$this->primaryKey}` = ?",
            [...array_values($data), $id]
        );
    }

    public function delete(int $id): int
    {
        return Database::execute(
            "DELETE FROM `{$this->table}` WHERE `{$this->primaryKey}` = ?",
            [$id]
        );
    }

    public function count(string $where = '', array $params = []): int
    {
        $sql = "SELECT COUNT(*) as cnt FROM `{$this->table}`";
        if ($where) $sql .= " WHERE {$where}";
        $row = Database::fetch($sql, $params);
        return (int) ($row['cnt'] ?? 0);
    }

    public function paginate(int $page, int $perPage = ITEMS_PER_PAGE, string $where = '', array $params = [], string $orderBy = 'id', string $dir = 'DESC'): array
    {
        $offset = ($page - 1) * $perPage;
        $total  = $this->count($where, $params);

        $sql = "SELECT * FROM `{$this->table}`";
        if ($where) $sql .= " WHERE {$where}";
        $sql .= " ORDER BY `{$orderBy}` {$dir} LIMIT {$perPage} OFFSET {$offset}";

        return [
            'data'         => Database::fetchAll($sql, $params),
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'last_page'    => (int) ceil($total / $perPage),
        ];
    }

    private function filterFillable(array $data): array
    {
        if (empty($this->fillable)) return $data;
        return array_intersect_key($data, array_flip($this->fillable));
    }

    // ─── Self-healing columns ─────────────────────────────────────────────────

    protected static array $_columnCache = [];

    protected function ensureColumn(string $table, string $column, string $definition): void
    {
        $cacheKey = "{$table}.{$column}";
        if (isset(self::$_columnCache[$cacheKey])) return;

        if (!Database::columnExists($table, $column)) {
            Database::execute("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
        }
        self::$_columnCache[$cacheKey] = true;
    }
}
