<?php
namespace ApexSEO\API\Controllers;

use ApexSEO\Core\Security\SecurityManager;
use ApexSEO\Core\Configuration\ConfigurationManager;

/**
 * REST API Controller for Global SEO Settings (API-01, API-02).
 */
class SettingsRestController extends AbstractRestController {
    /**
     * Configuration manager.
     *
     * @var ConfigurationManager
     */
    protected $config;

    /**
     * Constructor.
     *
     * @param SecurityManager $security
     * @param ConfigurationManager $config
     */
    public function __construct(SecurityManager $security, ConfigurationManager $config) {
        parent::__construct($security);
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function registerRoutes() {
        // GET /apexseo/v1/settings (API-01)
        $this->registerRoute('/settings', [
            'methods'             => 'GET',
            'callback'            => [$this, 'getSettings'],
            'permission_callback' => [$this, 'checkAdminPermission'],
        ]);

        // POST /apexseo/v1/settings (API-02)
        $this->registerRoute('/settings', [
            'methods'             => 'POST',
            'callback'            => [$this, 'updateSettings'],
            'permission_callback' => [$this, 'checkAdminPermission'],
            'args'                => [
                'settings' => [
                    'required'          => true,
                    'type'              => 'object',
                    'validate_callback' => function($param) {
                        return is_array($param);
                    },
                ],
            ],
        ]);
    }

    /**
     * Get all plugin settings (API-01).
     *
     * @param \WP_REST_Request|null $request
     * @return \WP_REST_Response
     */
    public function getSettings($request = null) {
        $settings = $this->config->getAll();
        return $this->success([
            'success'  => true,
            'settings' => $settings,
        ]);
    }

    /**
     * Update plugin settings (API-02).
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response|\WP_Error
     */
    public function updateSettings($request) {
        $newSettings = $request instanceof \WP_REST_Request ? $request->get_param('settings') : (isset($request['settings']) ? $request['settings'] : []);

        if (!is_array($newSettings)) {
            return $this->error('apexseo_invalid_settings', 'Settings payload must be an object or array.', 422);
        }

        // Apply and persist each config key recursively
        foreach ($newSettings as $section => $values) {
            if (is_array($values)) {
                foreach ($values as $key => $val) {
                    $this->config->set("{$section}.{$key}", $val);
                }
            } else {
                $this->config->set($section, $values);
            }
        }

        // Save to WordPress options
        $this->config->save();

        return $this->success([
            'success'    => true,
            'settings'   => $this->config->getAll(),
            'updated_at' => gmdate('Y-m-d\TH:i:s\Z'),
        ]);
    }
}
