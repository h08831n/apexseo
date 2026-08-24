<?php
namespace ApexSEO\Core\Security;

use ApexSEO\Core\Contracts\ServiceContractInterface;
use ApexSEO\Core\Exceptions\SecurityException;

/**
 * Security, capability, and nonce manager for Apex SEO Platform.
 */
class SecurityManager implements ServiceContractInterface {
    const NONCE_ACTION_ADMIN     = 'apexseo_admin_action';
    const NONCE_ACTION_REST      = 'wp_rest';
    const NONCE_ACTION_MIGRATION = 'apexseo_migration_action';

    /**
     * Check if current user has 'manage_options' capability.
     *
     * @return bool
     */
    public function canManageOptions() {
        return function_exists('current_user_can') && current_user_can('manage_options');
    }

    /**
     * Check if current user has capability to edit a given post or term.
     *
     * @param int $objectId Post or term ID.
     * @param string $objectType 'post' or 'term'.
     * @return bool
     */
    public function canEditObject($objectId, $objectType = 'post') {
        if (!function_exists('current_user_can')) {
            return false;
        }

        if ($objectType === 'post') {
            return current_user_can('edit_post', $objectId);
        }

        if ($objectType === 'term') {
            return current_user_can('edit_term', $objectId);
        }

        if ($objectType === 'user') {
            return current_user_can('edit_user', $objectId);
        }

        return current_user_can('edit_posts');
    }

    /**
     * Create a WordPress security nonce for a given action.
     *
     * @param string $action Action slug.
     * @return string Nonce string.
     */
    public function createNonce($action = self::NONCE_ACTION_ADMIN) {
        return function_exists('wp_create_nonce') ? wp_create_nonce($action) : md5($action . 'fallback_nonce');
    }

    /**
     * Verify a WordPress security nonce.
     *
     * @param string $nonce Nonce string to verify.
     * @param string $action Action slug.
     * @return bool True if valid.
     */
    public function verifyNonce($nonce, $action = self::NONCE_ACTION_ADMIN) {
        if (function_exists('wp_verify_nonce')) {
            return (bool) wp_verify_nonce($nonce, $action);
        }
        return !empty($nonce);
    }

    /**
     * REST API Admin permission callback.
     *
     * @param \WP_REST_Request|null $request
     * @return bool|\WP_Error
     */
    public function restAdminPermissionCallback($request = null) {
        if ($this->canManageOptions()) {
            return true;
        }

        if (class_exists('\\WP_Error')) {
            return new \WP_Error(
                'rest_forbidden',
                __('You do not have administrative permissions to access this endpoint.', 'apexseo'),
                ['status' => 403]
            );
        }

        return false;
    }

    /**
     * REST API Post/Content Editor permission callback.
     *
     * @param \WP_REST_Request|null $request
     * @return bool|\WP_Error
     */
    public function restEditorPermissionCallback($request = null) {
        if (function_exists('current_user_can') && (current_user_can('edit_posts') || current_user_can('manage_options'))) {
            return true;
        }

        if (class_exists('\\WP_Error')) {
            return new \WP_Error(
                'rest_forbidden',
                __('You do not have permissions to edit content.', 'apexseo'),
                ['status' => 403]
            );
        }

        return false;
    }

    /**
     * Validate and sanitize an input redirect URL.
     *
     * @param string $url
     * @param string|null $fallback
     * @return string
     */
    public function validateRedirect($url, $fallback = null) {
        return SecurityUtils::validateRedirectUrl($url, $fallback);
    }

    /**
     * Sanitize text input.
     *
     * @param string $text
     * @return string
     */
    public function sanitizeText($text) {
        return function_exists('sanitize_text_field') ? sanitize_text_field($text) : strip_tags(trim((string) $text));
    }

    /**
     * Sanitize textarea input preserving line breaks.
     *
     * @param string $textarea
     * @return string
     */
    public function sanitizeTextarea($textarea) {
        return function_exists('sanitize_textarea_field') ? sanitize_textarea_field($textarea) : strip_tags((string) $textarea);
    }

    /**
     * Sanitize URL.
     *
     * @param string $url
     * @return string
     */
    public function sanitizeUrl($url) {
        return function_exists('esc_url_raw') ? esc_url_raw($url) : filter_var($url, FILTER_SANITIZE_URL);
    }
}
