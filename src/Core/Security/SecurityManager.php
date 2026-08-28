<?php
namespace ApexSEO\Core\Security;

class SecurityManager {
    public function verifyNonce(string $nonce, string $action): bool {
        return (bool) wp_verify_nonce($nonce, $action);
    }

    public function checkAdminPermission(): bool {
        return current_user_can('manage_options');
    }

    public function checkEditorPermission(): bool {
        return current_user_can('edit_posts');
    }

    public function checkUploadPermission(): bool {
        return current_user_can('upload_files');
    }

    public function sanitizeString(string $input): string {
        return sanitize_text_field($input);
    }

    public function sanitizeArray(array $input): array {
        $output = [];
        foreach ($input as $k => $v) {
            $cleanKey = sanitize_key($k);
            if (is_array($v)) {
                $output[$cleanKey] = $this->sanitizeArray($v);
            } elseif (is_string($v)) {
                $output[$cleanKey] = sanitize_text_field($v);
            } else {
                $output[$cleanKey] = $v;
            }
        }
        return $output;
    }

    public function validateRedirect(string $url): string {
        return wp_sanitize_redirect($url);
    }
}
