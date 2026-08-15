# WordPress Multisite Architecture Specification

**Audit Reference**: WordPress Multisite Core (`wp-includes/ms-*`, `switch_to_blog()`)  
**Scope**: Subdomain, Subdirectory, and Multi-Domain Network Topologies

---

## 1. Activation Models & Lifecycle Hooks

Apex SEO fully supports both Network Activation and Individual Subsite Activation:

### 1.1 Network-Wide Activation (`network_activate`)
- **Execution Hook**: `activate_plugin` with `$network_wide = true`.
- **Behavior**:
  - Iterates through all active network sites via `get_sites(['fields' => 'ids'])`.
  - Executes database table migration (`dbDelta`) for each subsite using `switch_to_blog($site_id)`.
  - Sets default network options in `wp_site_options`.
- **New Site Creation Hook**: Hooks `wp_initialize_site` (WP 5.1+) to provision custom tables and default options for newly created subsites.
- **Site Deletion Hook**: Hooks `wp_uninitialize_site` to drop custom tables and clean up subsite options when a subsite is permanently deleted.

### 1.2 Subsite-Level Activation
- **Behavior**: Operates in isolated mode, provisioning tables with the current `$wpdb->prefix` without creating network admin screens.

---

## 2. Table Allocation Model (Per-Site vs Network-Wide)

| Custom Table Name | Storage Scope | Table Name Format | Architectural Rationale |
|---|---|---|---|
| `wp_apex_indexables` | **Per-Site** | `{$wpdb->prefix}apex_indexables` (e.g. `wp_2_apex_indexables`) | Subsites have independent posts, taxonomies, and SEO metadata. |
| `wp_apex_schema` | **Per-Site** | `{$wpdb->prefix}apex_schema` | Subsite owners configure unique schema templates and rules. |
| `wp_apex_redirects` | **Per-Site** | `{$wpdb->prefix}apex_redirects` | Redirection routing is strictly isolated per subsite domain/path. |
| `wp_apex_404_logs` | **Per-Site** | `{$wpdb->prefix}apex_404_logs` | High-frequency error logging isolated to prevent noisy neighbors. |
| `wp_apex_links` | **Per-Site** | `{$wpdb->prefix}apex_links` | Internal link graphs are specific to each subsite content tree. |
| `wp_apex_image_history`| **Per-Site** | `{$wpdb->prefix}apex_image_history`| Media libraries are isolated per subsite. |
| `wp_apex_analytics` | **Per-Site** | `{$wpdb->prefix}apex_analytics` | Google Search Console properties are site-specific. |
| `wp_apex_rank_tracking`| **Per-Site** | `{$wpdb->prefix}apex_rank_tracking`| Tracked keywords are configured per subsite. |

---

## 3. Network Admin vs Subsite Admin Capabilities

```
┌────────────────────────────────────────────────────────┐
│                   Network Admin Panel                  │
│  - Enforce Network-Wide SEO Policies (e.g. robots.txt) │
│  - Global API Keys (Google Search Console, Cloudflare) │
│  - Subsite Permission Delegation Control               │
│  - Network-Wide Cache Purge Master Button              │
└───────────────────────────┬────────────────────────────┘
                            │ Delegates / Enforces
                            ▼
┌────────────────────────────────────────────────────────┐
│                   Subsite Admin Panel                  │
│  - Per-Post Titles, Descriptions, Focus Keywords       │
│  - Local Redirection Management                        │
│  - Subsite XML Sitemaps                                │
│  - Subsite Media Optimization                          │
└────────────────────────────────────────────────────────┘
```

---

## 4. `switch_to_blog()` Safety Contract

To eliminate memory leaks, stale cached options, and cross-site data pollution during batch cron or CLI operations:

```php
/**
 * Safe multi-site iterator pattern
 */
public function run_on_all_sites(callable $callback): void {
    if (!is_multisite()) {
        $callback(get_current_blog_id());
        return;
    }

    $site_ids = get_sites(['fields' => 'ids', 'number' => 1000]);
    foreach ($site_ids as $site_id) {
        switch_to_blog($site_id);
        try {
            // Re-bind table prefix dynamically
            $this->database_manager->refresh_table_names();
            $callback($site_id);
        } finally {
            restore_current_blog();
        }
    }
}
```
