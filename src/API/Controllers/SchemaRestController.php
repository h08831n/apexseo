<?php
namespace ApexSEO\API\Controllers;

use ApexSEO\Core\Security\SecurityManager;
use ApexSEO\Schema\SchemaRegistry;
use ApexSEO\Schema\Validator\SchemaValidator;
use ApexSEO\Schema\SchemaGraphBuilder;

class SchemaRestController extends AbstractRestController {
    private $registry;
    private $validator;
    private $graphBuilder;

    public function __construct(
        SecurityManager $security,
        SchemaRegistry $registry,
        SchemaValidator $validator,
        SchemaGraphBuilder $graphBuilder
    ) {
        parent::__construct($security);
        $this->registry = $registry;
        $this->validator = $validator;
        $this->graphBuilder = $graphBuilder;
    }

    public function registerRoutes(): void {
        register_rest_route(self::NAMESPACE, '/schema/templates', [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [$this, 'getTemplates'],
                'permission_callback' => [$this, 'checkEditorPermission'],
            ]
        ]);

        register_rest_route(self::NAMESPACE, '/schema/validate', [
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'validateSchema'],
                'permission_callback' => [$this, 'checkEditorPermission'],
            ]
        ]);

        register_rest_route(self::NAMESPACE, '/schema/generate', [
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'generateSchema'],
                'permission_callback' => [$this, 'checkEditorPermission'],
            ]
        ]);
    }

    public function getTemplates($request) {
        return $this->sendResponse([
            'success'   => true,
            'templates' => $this->registry->getRegisteredTypes(),
        ]);
    }

    public function validateSchema($request) {
        $json = $request->get_param('schema_json');
        if (is_string($json)) {
            $data = json_decode($json, true);
        } else {
            $data = $json;
        }

        if (!is_array($data)) {
            return $this->sendError('invalid_schema_json', 'Schema must be a valid JSON object or array.', 400);
        }

        $res = $this->validator->validate($data);
        return $this->sendResponse([
            'success' => $res['valid'],
            'valid'   => $res['valid'],
            'errors'  => $res['errors'] ?? [],
        ]);
    }

    public function generateSchema($request) {
        $type = $request->get_param('type') ?: 'Article';
        $context = $request->get_param('context') ?: [];

        $schema = $this->graphBuilder->buildGraph($type, is_array($context) ? $context : []);
        return $this->sendResponse([
            'success' => true,
            'schema'  => $schema,
        ]);
    }
}
