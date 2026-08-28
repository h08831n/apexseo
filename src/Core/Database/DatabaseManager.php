<?php
namespace ApexSEO\Core\Database;

class DatabaseManager {
    const TABLE_INDEXABLES = 'apex_indexables';
    const TABLE_SCHEMA = 'apex_schema';
    const TABLE_REDIRECTS = 'apex_redirects';
    const TABLE_404_LOGS = 'apex_404_logs';
    const TABLE_LINKS = 'apex_links';
    const TABLE_IMAGE_HISTORY = 'apex_image_history';
    const TABLE_ANALYTICS = 'apex_analytics';
    const TABLE_RANK_TRACKING = 'apex_rank_tracking';

    /**
     * @var \wpdb
     */
    protected $wpdb;

    /**
     * @var string|null
     */
    protected $prefix = null;

    public function __construct($wpdb = null) {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    public function setPrefix(?string $prefix): void {
        $this->prefix = $prefix;
    }

    public function getPrefix(): string {
        if ($this->prefix !== null) {
            return $this->prefix;
        }
        return $this->wpdb->prefix ?? 'wp_';
    }

    public function getTableName(string $tableKey): string {
        $prefix = $this->getPrefix();
        if (strpos($tableKey, $prefix) === 0) {
            return $tableKey;
        }
        return $prefix . $tableKey;
    }

    public function hasTable(string $fullTableName): bool {
        if (!$this->wpdb) {
            return false;
        }
        if (isset($this->wpdb->mock_tables)) {
            return in_array($fullTableName, $this->wpdb->mock_tables, true);
        }
        $query = $this->wpdb->prepare("SHOW TABLES LIKE %s", $fullTableName);
        return $this->wpdb->get_var($query) === $fullTableName;
    }

    public function query(string $sql) {
        return $this->wpdb ? $this->wpdb->query($sql) : false;
    }

    public function prepare(string $sql, ...$args): string {
        return $this->wpdb ? $this->wpdb->prepare($sql, ...$args) : $sql;
    }

    public function insert(string $table, array $data, array $format = []) {
        return $this->wpdb ? $this->wpdb->insert($table, $data, $format) : false;
    }

    public function update(string $table, array $data, array $where, array $format = [], array $whereFormat = []) {
        return $this->wpdb ? $this->wpdb->update($table, $data, $where, $format, $whereFormat) : false;
    }

    public function delete(string $table, array $where, array $whereFormat = []) {
        return $this->wpdb ? $this->wpdb->delete($table, $where, $whereFormat) : false;
    }

    public function get_row(string $query, string $output = OBJECT) {
        return $this->wpdb ? $this->wpdb->get_row($query, $output) : null;
    }

    public function get_results(string $query, string $output = OBJECT) {
        return $this->wpdb ? $this->wpdb->get_results($query, $output) : [];
    }

    public function get_var(string $query) {
        return $this->wpdb ? $this->wpdb->get_var($query) : null;
    }

    public function getWpdb() {
        return $this->wpdb;
    }
}
