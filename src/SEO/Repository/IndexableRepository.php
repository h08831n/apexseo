<?php
namespace ApexSEO\SEO\Repository;

use ApexSEO\Core\Database\DatabaseManager;
use ApexSEO\SEO\Models\Indexable;

class IndexableRepository {
    private $db;

    public function __construct(DatabaseManager $db) {
        $this->db = $db;
    }

    public function find(int $objectId, string $objectType = 'post'): ?Indexable {
        $table = $this->db->getTableName(DatabaseManager::TABLE_INDEXABLES);
        $query = $this->db->prepare("SELECT * FROM {$table} WHERE object_id = %d AND object_type = %s LIMIT 1", $objectId, $objectType);
        $row = $this->db->get_row($query, ARRAY_A);
        return $row ? new Indexable($row) : null;
    }

    public function save(Indexable $indexable): bool {
        $table = $this->db->getTableName(DatabaseManager::TABLE_INDEXABLES);
        $data = $indexable->toArray();
        unset($data['id']);
        $data['content_analysis'] = json_encode($data['content_analysis']);

        $existing = $this->find($indexable->getObjectId(), $indexable->getObjectType());
        if ($existing && $existing->getId()) {
            return (bool) $this->db->update($table, $data, ['id' => $existing->getId()]);
        }

        $inserted = $this->db->insert($table, $data);
        if ($inserted && method_exists($this->db, 'get_var')) {
            $indexable->setId((int)$this->db->get_var("SELECT LAST_INSERT_ID()"));
        }
        return (bool) $inserted;
    }

    public function delete(int $objectId, string $objectType = 'post'): bool {
        $table = $this->db->getTableName(DatabaseManager::TABLE_INDEXABLES);
        return (bool) $this->db->delete($table, ['object_id' => $objectId, 'object_type' => $objectType]);
    }
}
