<?php
namespace ApexSEO\SEO\Repository;

use ApexSEO\Core\Database\DatabaseManager;
use ApexSEO\SEO\Models\Indexable;

/**
 * High-Performance Repository for reading and persisting Indexables in wp_apex_indexables.
 */
class IndexableRepository {
    /**
     * Database manager.
     *
     * @var DatabaseManager
     */
    protected $db;

    /**
     * Table name.
     *
     * @var string
     */
    protected $table;

    /**
     * In-memory cache for request lifecycle query deduplication.
     *
     * @var array<string, Indexable|null>
     */
    protected $runtimeCache = [];

    /**
     * Constructor.
     *
     * @param DatabaseManager $db
     */
    public function __construct(DatabaseManager $db) {
        $this->db = $db;
        $this->table = $db->getPrefix() . 'apex_indexables';
    }

    /**
     * Find an indexable record by its object type and ID.
     *
     * @param string $objectType e.g. 'post', 'term', 'user'
     * @param int $objectId
     * @return Indexable|null
     */
    public function findByObject($objectType, $objectId) {
        $cacheKey = "{$objectType}:{$objectId}";
        if (array_key_exists($cacheKey, $this->runtimeCache)) {
            return $this->runtimeCache[$cacheKey];
        }

        $wpdb = $this->db->getWpdb();
        $query = $wpdb->prepare(
            "SELECT * FROM `{$this->table}` WHERE `object_type` = %s AND `object_id` = %d LIMIT 1",
            $objectType,
            $objectId
        );

        $row = $wpdb->get_row($query, ARRAY_A);
        if (!$row) {
            $this->runtimeCache[$cacheKey] = null;
            return null;
        }

        $indexable = Indexable::fromArray($row);
        $this->runtimeCache[$cacheKey] = $indexable;
        if (!empty($indexable->permalink_hash)) {
            $this->runtimeCache['hash:' . $indexable->permalink_hash] = $indexable;
        }

        return $indexable;
    }

    /**
     * Find an indexable record by its permalink hash (MD5).
     *
     * @param string $permalinkHash
     * @return Indexable|null
     */
    public function findByPermalinkHash($permalinkHash) {
        $cacheKey = "hash:{$permalinkHash}";
        if (array_key_exists($cacheKey, $this->runtimeCache)) {
            return $this->runtimeCache[$cacheKey];
        }

        $wpdb = $this->db->getWpdb();
        $query = $wpdb->prepare(
            "SELECT * FROM `{$this->table}` WHERE `permalink_hash` = %s LIMIT 1",
            $permalinkHash
        );

        $row = $wpdb->get_row($query, ARRAY_A);
        if (!$row) {
            $this->runtimeCache[$cacheKey] = null;
            return null;
        }

        $indexable = Indexable::fromArray($row);
        $this->runtimeCache[$cacheKey] = $indexable;
        $this->runtimeCache["{$indexable->object_type}:{$indexable->object_id}"] = $indexable;

        return $indexable;
    }

    /**
     * Save (insert or update) an indexable record.
     *
     * @param Indexable $indexable
     * @return bool True on success
     */
    public function save(Indexable $indexable) {
        $data = $indexable->toArray();
        $wpdb = $this->db->getWpdb();

        // Check if existing record by ID or unique key (object_type, object_id)
        $existing = null;
        if (!empty($indexable->id)) {
            $existing = $wpdb->get_row(
                $wpdb->prepare("SELECT id FROM `{$this->table}` WHERE `id` = %d LIMIT 1", $indexable->id)
            );
        } else {
            $existing = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT id FROM `{$this->table}` WHERE `object_type` = %s AND `object_id` = %d LIMIT 1",
                    $indexable->object_type,
                    $indexable->object_id
                )
            );
        }

        if ($existing) {
            $indexable->id = (int) $existing->id;
            $format = [
                '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d',
                '%d', '%d', '%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s',
                '%s', '%s', '%d', '%d', '%d', '%d', '%d', '%d', '%s'
            ];
            $res = $wpdb->update(
                $this->table,
                $data,
                ['id' => $indexable->id],
                $format,
                ['%d']
            );
        } else {
            $format = [
                '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d',
                '%d', '%d', '%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s',
                '%s', '%s', '%d', '%d', '%d', '%d', '%d', '%d', '%s'
            ];
            $res = $wpdb->insert($this->table, $data, $format);
            if ($res !== false) {
                $indexable->id = (int) $wpdb->insert_id;
            }
        }

        // Invalidate and refresh runtime cache
        $cacheKey = "{$indexable->object_type}:{$indexable->object_id}";
        $this->runtimeCache[$cacheKey] = $indexable;
        if (!empty($indexable->permalink_hash)) {
            $this->runtimeCache['hash:' . $indexable->permalink_hash] = $indexable;
        }

        return $res !== false;
    }

    /**
     * Delete an indexable record by object type and ID.
     *
     * @param string $objectType
     * @param int $objectId
     * @return bool
     */
    public function deleteByObject($objectType, $objectId) {
        $wpdb = $this->db->getWpdb();
        $res = $wpdb->delete(
            $this->table,
            [
                'object_type' => $objectType,
                'object_id'   => $objectId,
            ],
            ['%s', '%d']
        );

        $cacheKey = "{$objectType}:{$objectId}";
        unset($this->runtimeCache[$cacheKey]);

        return $res !== false;
    }

    /**
     * Clear local runtime cache.
     *
     * @return void
     */
    public function clearRuntimeCache() {
        $this->runtimeCache = [];
    }

    /**
     * Count total indexable entries.
     *
     * @return int
     */
    public function count() {
        $wpdb = $this->db->getWpdb();
        $count = $wpdb->get_var("SELECT COUNT(*) FROM `{$this->table}`");
        return (int) $count;
    }
}
