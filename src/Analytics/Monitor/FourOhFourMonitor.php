<?php
namespace ApexSEO\Analytics\Monitor;

use ApexSEO\Core\Database\DatabaseManager;

class FourOhFourMonitor {
    private $db;

    public function __construct(DatabaseManager $db) {
        $this->db = $db;
    }

    public function log(string $uri, string $referrer = '', string $ua = '', string $ip = ''): void {
        $table = $this->db->getTableName(DatabaseManager::TABLE_404_LOGS);
        $existing = $this->db->get_row($this->db->prepare("SELECT id, hits FROM {$table} WHERE request_uri = %s", $uri), ARRAY_A);
        if ($existing) {
            $this->db->update($table, ['hits' => $existing['hits'] + 1], ['id' => $existing['id']]);
        } else {
            $this->db->insert($table, [
                'request_uri' => $uri,
                'referrer'    => $referrer,
                'user_agent'  => $ua,
                'ip_address'  => $ip,
                'hits'        => 1,
            ]);
        }
    }

    public function getLogs(int $limit = 50): array {
        $table = $this->db->getTableName(DatabaseManager::TABLE_404_LOGS);
        $results = $this->db->get_results("SELECT * FROM {$table} ORDER BY hits DESC LIMIT " . intval($limit), ARRAY_A);
        return is_array($results) ? $results : [];
    }

    public function deleteLog(int $id): bool {
        $table = $this->db->getTableName(DatabaseManager::TABLE_404_LOGS);
        return (bool) $this->db->delete($table, ['id' => $id]);
    }

    public function purgeAll(): bool {
        $table = $this->db->getTableName(DatabaseManager::TABLE_404_LOGS);
        return (bool) $this->db->query("TRUNCATE TABLE {$table}");
    }
}
