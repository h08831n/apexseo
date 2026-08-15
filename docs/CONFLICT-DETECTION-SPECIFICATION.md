# Conflict Detection & Ecosystem Deconfliction Specification

**Audit Reference**: WordPress Active Plugin Registry & Server Environment Detectors  
**Purpose**: Automated detection and resolution of duplicate meta tag emissions, overlapping cache layers, and redundant asset minification.

---

## 1. Third-Party Plugin Conflict Matrix

Apex SEO scans active plugins on `admin_init` and evaluates specific overlapping feature modules:

| Conflicting Plugin | Overlapping Capability Areas | Severity Level | Automated Remediation / 1-Click Action |
|---|---|---|---|
| **Yoast SEO** (`wordpress-seo`) | Meta Titles, Canonical, Sitemaps, Schema JSON-LD, Breadcrumbs | `CRITICAL` | Prompt migration wizard; Offer 1-click deactivation of Yoast or disable duplicate Apex SEO SEO output. |
| **Rank Math** (`seo-by-rank-math`) | Meta Titles, Sitemaps, Schema Builder, Redirections, Analytics | `CRITICAL` | Prompt migration wizard; Offer 1-click deactivation of Rank Math. |
| **All in One SEO** (`all-in-one-seo-pack`) | Meta Titles, Canonical, Sitemaps, Schema JSON-LD | `CRITICAL` | Prompt migration wizard; Offer 1-click deactivation of AIOSEO. |
| **SEOPress** (`wp-seopress`) | Meta Titles, Sitemaps, Breadcrumbs, Redirections | `CRITICAL` | Prompt migration wizard; Offer 1-click deactivation. |
| **The SEO Framework** (`autodescription`)| Meta Titles, Robots, Canonical, Sitemaps | `CRITICAL` | Prompt migration wizard; Offer 1-click deactivation. |
| **WP Rocket** (`wp-rocket`) | Page Cache, JS Delay, CSS Combine/Minify, LazyLoad | `HIGH` | Offer selective modular deconfliction: "Let WP Rocket handle Page Cache while Apex handles SEO" or 1-click deactivation. |
| **LiteSpeed Cache** (`lscache_wp`) | Page Cache, Object Cache, Image Optimization, CSS/JS Optimization | `HIGH` | Detect LiteSpeed server; If active, delegate Page Cache to LiteSpeed and handle Schema/SEO in Apex. |
| **Autoptimize** (`autoptimize`) | CSS/JS Minification and Combination | `MEDIUM` | Auto-disable Apex CSS/JS optimizer or prompt deactivation of Autoptimize. |
| **W3 Total Cache** (`w3-total-cache`) | Page Cache, Object Cache, Minification | `HIGH` | Prompt deactivation of duplicate cache engines. |
| **WP Super Cache** (`wp-super-cache`) | Page Cache | `HIGH` | Prompt deactivation to avoid duplicate static file writing. |
| **Smush / ShortPixel** | Image WebP Conversion and Compression | `MEDIUM` | Auto-disable Apex Media Optimizer background queue to avoid duplicate processing. |
| **Redirection** (`redirection`) | 301 Redirect Routing & 404 Logging | `MEDIUM` | 1-click migration of redirect rules to `wp_apex_redirects` followed by plugin deactivation. |

---

## 2. Server Module & Environment Incompatibility Matrix

| Server Module / Configuration | Conflict Description | Risk Factor | Apex SEO Automated Fallback |
|---|---|---|---|
| **Apache `mod_pagespeed`** | Double-minification of CSS/JS causing syntax truncation | `HIGH` | Detect `X-Page-Speed` response header; auto-disable internal JS/CSS combiner. |
| **Cloudflare Auto Minify / Rocket Loader** | Conflict with JS interaction delay (`type="text/apex-delay"`) | `MEDIUM` | Emit admin warning instructing to disable Cloudflare Rocket Loader for compatibility with delayed JS. |
| **PHP `opcache.enable = 0`** | Severe backend PHP performance degradation | `WARNING` | Emit admin performance notice recommending OpCache activation. |
| **Missing PHP `gd` / `imagick`** | Inability to encode WebP / AVIF images locally | `WARNING` | Fallback to original image uploads; mark WebP/AVIF generation disabled in Media settings. |
| **`DISABLE_WP_CRON = true` without system cron** | Delayed cache warmup and background image processing | `WARNING` | Check Action Scheduler status; emit warning if scheduled tasks are backlogged. |

---

## 3. Admin Notice System & 1-Click Resolution Contract

- **Notice Priority**: Critical conflicts display a persistent high-priority notice at the top of all admin screens (`notice-error`).
- **Dismissibility**: Informational notices can be dismissed for 30 days (`apex_dismiss_conflict_{hash}`); critical conflicts cannot be dismissed until resolved.
- **1-Click Deconfliction**: Provides a one-click button executing `ApexSEO\Admin\ConflictResolver::resolve($conflict_id)` with verified CSRF nonce (`check_admin_referer`).
