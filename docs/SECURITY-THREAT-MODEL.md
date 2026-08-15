# Security Threat Model & Vulnerability Mitigation Matrix

**Audit Reference**: OWASP Top 10, WordPress Core Security Standards  
**Scope**: All input vectors, cache endpoints, file operations, REST controllers, and database queries.

---

## 1. Threat Vectors & Concrete Defense Contracts

| Threat Vector | Attack Scenario / Mechanism | Likelihood | Impact | Concrete Defense Contract in Apex SEO |
|---|---|---|---|---|
| **Cache Poisoning** | Attacker injects malicious `X-Forwarded-Host` or crafted query params to poison cached static HTML served to subsequent visitors. | `MEDIUM` | `CRITICAL` | Cache keys strictly use sanitized `$_SERVER['HTTP_HOST']` matching configured `WP_SITEURL`. Untrusted proxy headers are ignored unless explicitly whitelisted in `wp-config.php`. Query strings are sanitized and stripped. |
| **Server-Side Request Forgery (SSRF)** | Malicious sitemap warmup or webhook requests directed at internal network resources (`http://169.254.169.254/` or `http://localhost/admin`). | `MEDIUM` | `HIGH` | All outgoing HTTP requests utilize `wp_safe_remote_get()` with `reject_unsafe_urls = true`, disallowing private IP ranges (`10.0.0.0/8`, `172.16.0.0/12`, `192.168.0.0/16`, `127.0.0.1`). |
| **Path Traversal & Arbitrary File Unlink** | Attacker manipulates cache purge parameter (e.g. `?purge=../../wp-config.php`) to delete or access system files. | `LOW` | `CRITICAL` | All file operations use `realpath()` and strictly validate that target paths reside within `WP_CONTENT_DIR . '/cache/'`. Any path resolving outside is rejected with security logging. |
| **SQL Injection (SQLi)** | Attacker submits malicious SQL payloads inside redirect import, search analytics queries, or regex rules. | `LOW` | `CRITICAL` | 100% of database queries execute through `$wpdb->prepare()` with explicit parameter placeholders (`%d`, `%s`, `%f`). Direct string concatenation in queries is strictly prohibited. |
| **Stored Cross-Site Scripting (XSS)** | Malicious JavaScript injected into Meta Titles, Schema JSON templates, or 404 URL logs executed in admin panel. | `MEDIUM` | `HIGH` | Frontend meta outputs are escaped with `esc_attr()` / `esc_html()`. Schema JSON-LD is sanitized via `wp_json_encode()` with `JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT`. Admin tables escape all rendered strings. |
| **Cross-Site Request Forgery (CSRF)** | Attacker tricks authenticated admin into triggering cache purges, schema template deletions, or settings resets. | `MEDIUM` | `HIGH` | Every admin POST action verifies nonce via `check_admin_referer()`. REST API mutations require valid `X-WP-Nonce` header. |
| **Privilege Escalation** | Low-privilege user (Subscriber/Contributor) attempting to modify global SEO settings or redirect rules via REST endpoints. | `LOW` | `HIGH` | Every REST route enforces strict `permission_callback` checking `current_user_can('manage_options')` or dedicated granular capability (`manage_apex_seo`). |
| **Unauthenticated File Modification** | Writing `.htaccess`, `robots.txt`, or `llms.txt` via unverified web requests. | `LOW` | `CRITICAL` | Direct file writes are restricted strictly to admin sessions passing `current_user_can('manage_options')` and CSRF nonce validation. |

---

## 2. Capability Matrix for REST API & Admin Endpoints

| REST Endpoint / Admin Action | Required WordPress Capability | Nonce Verification Method | Audit Log Level |
|---|---|---|---|
| `GET /apex-seo/v1/settings` | `manage_options` | `X-WP-Nonce` | `INFO` |
| `POST /apex-seo/v1/settings` | `manage_options` | `X-WP-Nonce` | `SECURITY` |
| `POST /apex-seo/v1/cache/purge` | `manage_options` (or `edit_posts` for single post) | `X-WP-Nonce` | `INFO` |
| `POST /apex-seo/v1/schema/templates` | `manage_options` | `X-WP-Nonce` | `SECURITY` |
| `DELETE /apex-seo/v1/schema/templates/{id}` | `manage_options` | `X-WP-Nonce` | `SECURITY` |
| `POST /apex-seo/v1/redirects` | `manage_options` | `X-WP-Nonce` | `SECURITY` |
| `POST /apex-seo/v1/migrate/execute` | `manage_options` | `X-WP-Nonce` | `CRITICAL_SECURITY` |
| `POST /apex-seo/v1/media/optimize-single` | `upload_files` | `X-WP-Nonce` | `INFO` |
| `POST /apex-seo/v1/media/bulk-optimize` | `manage_options` | `X-WP-Nonce` | `INFO` |
| `GET /apex-seo/v1/diagnostics/system-status` | `manage_options` | `X-WP-Nonce` | `INFO` |
