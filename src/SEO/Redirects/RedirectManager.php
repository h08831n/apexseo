<?php
namespace ApexSEO\SEO\Redirects;

use ApexSEO\Core\Database\DatabaseManager;

class RedirectManager {
    private $db;

    public function __construct(DatabaseManager $db) {
        $this->db = $db;
    }

    public function getAllRedirects(): array {
        $table = $this->db->getTableName(DatabaseManager::TABLE_REDIRECTS);
        $results = $this->db->get_results("SELECT * FROM {$table} ORDER BY id DESC");
        return is_array($results) ? $results : [];
    }

    public function addRedirect(string $source, string $target, int $status = 301): ?int {
        $table = $this->db->getTableName(DatabaseManager::TABLE_REDIRECTS);
        $inserted = $this->db->insert($table, [
            'source_path' => '/' . ltrim($source, '/'),
            'target_url'  => $target,
            'status_code' => $status,
            'match_type'  => 'exact',
            'is_active'   => 1,
        ]);
        return $inserted ? (int)$this->db->get_var("SELECT LAST_INSERT_ID()") : 1;
    }

    public function deleteRedirect(int $id): bool {
        $table = $this->db->getTableName(DatabaseManager::TABLE_REDIRECTS);
        return (bool) $this->db->delete($table, ['id' => $id]);
    }

    public function matchRedirect(string $requestPath): ?array {
        $table = $this->db->getTableName(DatabaseManager::TABLE_REDIRECTS);
        $clean = '/' . ltrim($requestPath, '/');
        $query = $this->db->prepare("SELECT * FROM {$table} WHERE source_path = %s AND is_active = 1 LIMIT 1", $clean);
        $row = $this->db->get_row($query, ARRAY_A);
        return $row ?: null;
    }
}
