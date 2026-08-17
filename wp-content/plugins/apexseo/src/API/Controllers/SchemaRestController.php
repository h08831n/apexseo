<?php
namespace ApexSEO\API\Controllers;

use ApexSEO\Core\Security\SecurityManager;
use ApexSEO\Schema\SchemaRegistry;
use ApexSEO\Schema\Validator\SchemaValidator;
use ApexSEO\Core\Database\DatabaseManager;

/**
 * REST API Controller for Custom Schema Entities (API-05, API-06, API-07, API-08).
 */
class SchemaRestController extends AbstractRestController {
    /**
     * Schema registry.
     *
     * @var SchemaRegistry
     */
    protected $registry;

    /**
     * Schema validator.
     *
     * @var SchemaValidator
     */
    protected $validator;

    /**
     * Database manager.
     *
     * @var DatabaseManager
     */
    protected $db;

    /**
     * Constructor.
     *
     * @param SecurityManager $security
     * @param SchemaRegistry $registry
     * @param SchemaValidator $validator
     * @param DatabaseManager $db
     */
    public function __construct(SecurityManager $security, SchemaRegistry $registry, SchemaValidator $validator, DatabaseManager $db) {
        parent::__construct($security);
        $this->registry  = $registry;
        $this->validator = $validator;
        $this->db        = $db;
    }

    /**
     * {@inheritdoc}
     */
    public function registerRoutes() {
        // GET /apexseo/v1/schema (API-05)
        $this->registerRoute('/schema', [
            'methods'             => 'GET',
            'callback'            => [$this, 'getSchemas'],
            'permission_callback' => [$this, 'checkEditorPermission'],
        ]);

        // POST /apexseo/v1/schema (API-06)
        $this->registerRoute('/schema', [
            'methods'             => 'POST',
            'callback'            => [$this, 'createSchema'],
            'permission_callback' => [$this, 'checkAdminPermission'],
        ]);

        // PUT /apexseo/v1/schema/{id} (API-07)
        $this->registerRoute('/schema/(?P<id>\d+)', [
            'methods'             => 'PUT',
            'callback'            => [$this, 'updateSchema'],
            'permission_callback' => [$this, 'checkAdminPermission'],
        ]);

        // DELETE /apexseo/v1/schema/{id} (API-08)
        $this->registerRoute('/schema/(?P<id>\d+)', [
            'methods'             => 'DELETE',
            'callback'            => [$this, 'deleteSchema'],
            'permission_callback' => [$this, 'checkAdminPermission'],
        ]);
    }

    /**
     * Get available schema types and custom saved schema rules (API-05).
     *
     * @param \WP_REST_Request|null $request
     * @return \WP_REST_Response
     */
    public function getSchemas($request = null) {
        $registeredTypes = array_keys($this->registry->getAllTypes());

        $table = $this->db->getPrefix() . 'apex_schema';
        $customSchemas = [];

        $results = $this->db->getResults("SELECT * FROM {$table} ORDER BY id DESC LIMIT 100");
        if (is_array($results)) {
            $customSchemas = $results;
        }

        return $this->success([
            'success'          => true,
            'supported_types'  => $registeredTypes,
            'custom_templates' => $customSchemas,
        ]);
    }

    /**
     * Create custom schema template (API-06).
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response|\WP_Error
     */
    public function createSchema($request) {
        $params = $request instanceof \WP_REST_Request ? $request->get_json_params() : $request;

        $type       = isset($params['schema_type']) ? sanitize_text_field($params['schema_type']) : '';
        $rawObjType = isset($params['object_type']) ? sanitize_key($params['object_type']) : 'global';
        $objectId   = isset($params['object_id']) ? max(0, (int) $params['object_id']) : 0;
        $schemaData = isset($params['schema_data']) ? $params['schema_data'] : [];

        $validObjectTypes = ['global', 'post', 'term', 'user'];
        $objectType = in_array($rawObjType, $validObjectTypes, true) ? $rawObjType : 'global';

        if (empty($type)) {
            return $this->error('apexseo_invalid_type', 'schema_type is required.', 422);
        }

        if (!is_array($schemaData)) {
            return $this->error('apexseo_invalid_data', 'schema_data must be a valid JSON array or object.', 422);
        }

        $issues = $this->validator->validate($schemaData);
        if (!empty($issues)) {
            return $this->error('apexseo_schema_validation_failed', implode(' ', $issues), 422, ['validation_errors' => $issues]);
        }

        $table = $this->db->getPrefix() . 'apex_schema';
        $inserted = $this->db->insert($table, [
            'object_type' => $objectType,
            'object_id'   => $objectId,
            'schema_type' => $type,
            'schema_data' => wp_json_encode($schemaData),
            'is_active'   => 1,
            'created_at'  => gmdate('Y-m-d H:i:s'),
        ]);

        if (!$inserted) {
            return $this->error('apexseo_db_insert_failed', 'Failed to store custom schema template.', 500);
        }

        return $this->success([
            'success'     => true,
            'id'          => $this->db->getInsertId(),
            'schema_type' => $type,
        ], 201);
    }

    /**
     * Update custom schema template (API-07).
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response|\WP_Error
     */
    public function updateSchema($request) {
        $id     = $request instanceof \WP_REST_Request ? (int) $request->get_param('id') : (isset($request['id']) ? (int) $request['id'] : 0);
        $params = $request instanceof \WP_REST_Request ? $request->get_json_params() : $request;

        if ($id <= 0) {
            return $this->error('apexseo_invalid_id', 'Valid schema ID required.', 400);
        }

        $table = $this->db->getPrefix() . 'apex_schema';
        $existCheck = $this->db->getVar($this->db->prepare("SELECT id FROM {$table} WHERE id = %d", $id));
        if (!$existCheck) {
            return $this->error('apexseo_not_found', 'Schema template not found.', 404);
        }

        $updateData = [];

        if (isset($params['schema_type'])) {
            $updateData['schema_type'] = sanitize_text_field($params['schema_type']);
        }
        if (isset($params['schema_data']) && is_array($params['schema_data'])) {
            $issues = $this->validator->validate($params['schema_data']);
            if (!empty($issues)) {
                return $this->error('apexseo_schema_validation_failed', implode(' ', $issues), 422, ['validation_errors' => $issues]);
            }
            $updateData['schema_data'] = wp_json_encode($params['schema_data']);
        }
        if (isset($params['is_active'])) {
            $updateData['is_active'] = !empty($params['is_active']) ? 1 : 0;
        }

        if (empty($updateData)) {
            return $this->error('apexseo_no_data', 'No update payload supplied.', 400);
        }

        $this->db->update($table, $updateData, ['id' => $id]);

        return $this->success([
            'success' => true,
            'id'      => $id,
            'updated' => true,
        ]);
    }

    /**
     * Delete custom schema template (API-08).
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response|\WP_Error
     */
    public function deleteSchema($request) {
        $id = $request instanceof \WP_REST_Request ? (int) $request->get_param('id') : (isset($request['id']) ? (int) $request['id'] : 0);

        if ($id <= 0) {
            return $this->error('apexseo_invalid_id', 'Valid schema ID required.', 400);
        }

        $table = $this->db->getPrefix() . 'apex_schema';
        $existCheck = $this->db->getVar($this->db->prepare("SELECT id FROM {$table} WHERE id = %d", $id));
        if (!$existCheck) {
            return $this->error('apexseo_not_found', 'Schema template not found.', 404);
        }

        $this->db->delete($table, ['id' => $id]);

        return $this->success([
            'success' => true,
            'id'      => $id,
            'deleted' => true,
        ]);
    }
}
