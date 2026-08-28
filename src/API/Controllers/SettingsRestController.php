<?php
namespace ApexSEO\API\Controllers;

use ApexSEO\Core\Security\SecurityManager;
use ApexSEO\Core\Configuration\ConfigurationManager;

class SettingsRestController extends AbstractRestController {
    private $config;

    public function __construct(SecurityManager $security, ConfigurationManager $config) {
        parent::__construct($security);
        $this->config = $config;
    }

    public function registerRoutes(): void {
        register_rest_route(self::NAMESPACE, '/settings', [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [$this, 'getSettings'],
                'permission_callback' => [$this, 'checkAdminPermission'],
            ],
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'updateSettings'],
                'permission_callback' => [$this, 'checkAdminPermission'],
                'args'                => [
                    'settings' => [
                        'required'          => true,
                        'validate_callback' => function($param) {
                            return is_array($param);
                        }
                    ]
                ]
            ],
        ]);

        register_rest_route(self::NAMESPACE, '/settings/reset', [
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'resetSettings'],
                'permission_callback' => [$this, 'checkAdminPermission'],
            ]
        ]);
    }

    public function getSettings($request) {
        return $this->sendResponse([
            'success'  => true,
            'settings' => $this->config->all(),
        ]);
    }

    public function updateSettings($request) {
        $settings = $request->get_param('settings');
        if (!is_array($settings)) {
            return $this->sendError('invalid_settings_payload', 'Settings payload must be an associative array.', 400);
        }

        $sanitized = $this->security->sanitizeArray($settings);
        foreach ($sanitized as $key => $val) {
            $this->config->set($key, $val);
        }
        $this->config->save();

        return $this->sendResponse([
            'success'  => true,
            'message'  => 'Settings successfully updated.',
            'settings' => $this->config->all(),
        ]);
    }

    public function resetSettings($request) {
        $this->config->reset();
        return $this->sendResponse([
            'success'  => true,
            'message'  => 'Settings reset to factory defaults.',
            'settings' => $this->config->all(),
        ]);
    }
}
