# 14 - Security, Nonces & Sanitization Specification

## 1. Core Security Principles
Every action in Apex SEO Platform conforms to strict WordPress Security Standards:

### 1.1 Nonce Validation & Capability Checks
- All admin actions verify `check_admin_referer('apex_seo_action', 'apex_nonce')`.
- All AJAX actions verify `check_ajax_referer('apex_ajax_nonce', 'security')`.
- Capability checks enforce `current_user_can('manage_options')` for global settings and `current_user_can('edit_post', $post_id)` for post metabox updates.

### 1.2 SQL Injection Prevention
- All database queries against custom tables use `$wpdb->prepare()`.
- Dynamic table names strictly use `$wpdb->prefix . 'apex_*'` with no unescaped user input in table or column identifiers.

### 1.3 XSS & Output Escaping
- Text inputs sanitized via `sanitize_text_field()`, `sanitize_textarea_field()`, `wp_strip_all_tags()`.
- Output rendering uses `esc_html()`, `esc_attr()`, `esc_url()`, and `wp_kses_post()`.
- JSON-LD blocks escaped using `wp_json_encode($graph, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)`.

### 1.4 SSRF & Remote Request Protection
- Remote requests validate target URLs with `wp_http_validate_url()`.
- Private IP addresses (`127.0.0.1`, `10.0.0.0/8`, `192.168.0.0/16`) blocked on public user-facing crawler inputs.

### 1.5 Safe Uninstall Protocol
- `uninstall.php` only drops custom database tables and removes options if the administrator explicitly enabled the `"Delete plugin data on uninstall"` toggle.
