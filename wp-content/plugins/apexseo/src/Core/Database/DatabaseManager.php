<?php
namespace ApexSEO\Core\Database;

use ApexSEO\Core\Contracts\ServiceContractInterface;
use ApexSEO\Core\Exceptions\DatabaseException;

/**
 * Database abstraction manager for Apex SEO Platform.
 */
class DatabaseManager implements ServiceContractInterface {
    /**
     * Table names without prefix.
     */
    const TABLE_INDEXABLES     = 'apex_indexables';
    const TABLE_SCHEMA         = 'apex_schema';
    const TABLE_REDIRECTS      = 'apex_redirects';
    const TABLE_404_LOGS       = 'apex_404_logs';
    const TABLE_LINKS          = 'apex_links';
    const TABLE_IMAGE_HISTORY  = 'apex_image_history';
    const TABLE_ANALYTICS      = 'apex_analytics';
    const TABLE_RANK_TRACKING  = 'apex_rank_tracking';

    /**
     * WordPress database instance or mock.
     *
     * @var object
     */
    protected $wpdb;

    /**
     * Table prefix override.
     *
     * @var string|null
     */
    protected $prefixOverride = null;

    /**
     * Constructor.
     *
     * @param object|null $wpdb Optional custom wpdb instance.
     */
    public function __construct($wpdb = null) {
        if ($wpdb !== null) {
            $this->wpdb = $wpdb;
        } else {
            global $wpdb;
            $this->wpdb = $wpdb;
        }
    }

    /**
     * Get resolved full table name with current prefix.
     *
     * @param string $table Canonical table name constant or slug.
     * @return string
     */
    public function getTableName($table) {
        $prefix = $this->getPrefix();
        $cleanTable = ltrim($table, 'wp_');
        if (strpos($cleanTable, 'apex_') === 0) {
            return $prefix . $cleanTable;
        }
        return $prefix . 'apex_' . $cleanTable;
    }

    /**
     * Get database table prefix.
     *
     * @return string
     */
    public function getPrefix() {
        if ($this->prefixOverride !== null) {
            return $this->prefixOverride;
        }
        return isset($this->wpdb->prefix) ? $this->wpdb->prefix : 'wp_';
    }

    /**
     * Set table prefix override (e.g. for multisite blog switching or tests).
     *
     * @param string|null $prefix
     * @return void
     */
    public function setPrefix($prefix) {
        $this->prefixOverride = $prefix;
    }

    /**
     * Get charset and collation string.
     *
     * @return string
     */
    public function getCharsetCollate() {
        if (is_object($this->wpdb) && method_exists($this->wpdb, 'get_charset_collate')) {
            return $this->wpdb->get_charset_collate();
        }
        return 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci';
    }

    /**
     * Check if a table exists in the database.
     *
     * @param string $tableName Full table name or un-prefixed name.
     * @return bool
     */
    public function hasTable($tableName) {
        $fullTable = (strpos($tableName, $this->getPrefix()) === 0) ? $tableName : $this->getTableName($tableName);

        if (!is_object($this->wpdb)) {
            return false;
        }

        $query = "SHOW TABLES LIKE %s";
        $prepared = $this->prepare($query, $fullTable);
        $result = $this->getVar($prepared);

        return !empty($result) && $result === $fullTable;
    }

    /**
     * Execute a raw SQL query.
     *
     * @param string $sql
     * @return int|bool
     * @throws DatabaseException
     */
    public function query($sql) {
        if (!is_object($this->wpdb)) {
            throw new DatabaseException('WordPress database instance is not available.');
        }

        $result = $this->wpdb->query($sql);
        if ($result === false && !empty($this->wpdb->last_error)) {
            throw new DatabaseException(sprintf('Database query error: %s', $this->wpdb->last_error), 0, null, ['sql' => $sql]);
        }

        return $result;
    }

    /**
     * Execute schema delta DDL safely using WordPress dbDelta.
     *
     * @param string $ddl
     * @return array Result array from dbDelta.
     */
    public function delta($ddl) {
        if (function_exists('dbDelta')) {
            return dbDelta($ddl);
        }

        // Load upgrade.php if not loaded
        if (defined('ABSPATH') && file_exists(ABSPATH . 'wp-admin/includes/upgrade.php')) {
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
            if (function_exists('dbDelta')) {
                return dbDelta($ddl);
            }
        }

        // Fallback to direct query if dbDelta unavailable
        $this->query($ddl);
        return [];
    }

    /**
     * Prepare a SQL query safely.
     *
     * @param string $query
     * @param mixed ...$args
     * @return string
     */
    public function prepare($query, ...$args) {
        if (is_object($this->wpdb) && method_exists($this->wpdb, 'prepare')) {
            return $this->wpdb->prepare($query, ...$args);
        }
        return vsprintf(str_replace(['%s', '%d', '%f'], ["'%s'", "%d", "%f"], $query), $args);
    }

    /**
     * Get a single variable from query.
     *
     * @param string $query
     * @return mixed
     */
    public function getVar($query) {
        if (is_object($this->wpdb) && method_exists($this->wpdb, 'get_var')) {
            return $this->wpdb->get_var($query);
        }
        return null;
    }

    /**
     * Get a single row.
     *
     * @param string $query
     * @param string $output
     * @return mixed
     */
    public function getRow($query, $output = 'OBJECT') {
        if (is_object($this->wpdb) && method_exists($this->wpdb, 'get_row')) {
            return $this->wpdb->get_row($query, $output);
        }
        return null;
    }

    /**
     * Get results array.
     *
     * @param string $query
     * @param string $output
     * @return array
     */
    public function getResults($query, $output = 'OBJECT') {
        if (is_object($this->wpdb) && method_exists($this->wpdb, 'get_results')) {
            return (array) $this->wpdb->get_results($query, $output);
        }
        return [];
    }

    /**
     * Determine if the database engine supports transactions (e.g. InnoDB).
     *
     * @return bool
     */
    public function supportsTransactions() {
        return true;
    }

    /**
     * Begin a database transaction.
     *
     * @return void
     */
    public function beginTransaction() {
        $this->query('START TRANSACTION');
    }

    /**
     * Commit a database transaction.
     *
     * @return void
     */
    public function commit() {
        $this->query('COMMIT');
    }

    /**
     * Roll back a database transaction.
     *
     * @return void
     */
    public function rollback() {
        $this->query('ROLLBACK');
    }

    /**
     * Get raw underlying $wpdb instance.
     *
     * @return object
     */
    public function getWpdb() {
        return $this->wpdb;
    }
}
