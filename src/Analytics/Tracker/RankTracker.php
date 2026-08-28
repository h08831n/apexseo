<?php
namespace ApexSEO\Analytics\Tracker;

use ApexSEO\Core\Database\DatabaseManager;

class RankTracker {
    private $db;

    public function __construct(DatabaseManager $db) {
        $this->db = $db;
    }

    public function getKeywords(): array {
        $table = $this->db->getTableName(DatabaseManager::TABLE_RANK_TRACKING);
        $results = $this->db->get_results("SELECT * FROM {$table} ORDER BY checked_at DESC", ARRAY_A);
        return is_array($results) ? $results : [];
    }

    public function addKeyword(string $keyword, string $url): bool {
        $table = $this->db->getTableName(DatabaseManager::TABLE_RANK_TRACKING);
        return (bool) $this->db->insert($table, [
            'keyword' => $keyword,
            'url'     => $url,
        ]);
    }
}
