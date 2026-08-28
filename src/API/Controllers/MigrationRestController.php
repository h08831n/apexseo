<?php
namespace ApexSEO\API\Controllers;

use ApexSEO\Core\Security\SecurityManager;
use ApexSEO\Core\Database\DatabaseManager;
use ApexSEO\Core\Database\MigrationRunner;
use ApexSEO\Core\Database\SchemaVersion;

class MigrationRestController extends AbstractRestController {
    private $db;
    private $runner;

    public function __construct(SecurityManager $security, DatabaseManager $db) {
        parent::__construct($security);
        $this->db = $db;
        $this->runner = new MigrationRunner($db);
    }

    public function registerRoutes(): void {
        register_rest_route(self::NAMESPACE, '/migration/status', [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [$this, 'getStatus'],
                'permission_callback' => [$this, 'checkAdminPermission'],
            ]
        ]);

        register_rest_route(self::NAMESPACE, '/migration/execute', [
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'executeMigration'],
                'permission_callback' => [$this, 'checkAdminPermission'],
            ]
        ]);
    }

    public function getStatus($request) {
        return $this->sendResponse([
            'success'           => true,
            'installed_version' => SchemaVersion::getInstalledVersion() ?: '0.0.0',
            'latest_version'    => '1.0.0',
        ]);
    }

    public function executeMigration($request) {
        $executed = $this->runner->migrate();
        return $this->sendResponse([
            'success'  => true,
            'executed' => $executed,
            'version'  => SchemaVersion::getInstalledVersion(),
        ]);
    }
}
