# 04 - SEO Subsystem Specification

## 1. Architecture Overview
The SEO Subsystem in Apex SEO Platform handles title generation, description rendering, canonical URL calculation, robots directive compilation, Open Graph/Twitter social tagging, breadcrumb construction, and relational indexable caching.

```
                    REQUEST DISPATCH (wp_head / REST API)
                                    │
                                    ▼
                     ┌─────────────────────────────┐
                     │   ApexSEO\SEO\Indexables    │
                     │ (Lookup or Compute Record)  │
                     └──────────────┬──────────────┘
                                    │
    ┌────────────────┬──────────────┼───────────────┬────────────────┐
    ▼                ▼              ▼               ▼                ▼
┌───────────┐ ┌─────────────┐ ┌───────────┐ ┌───────────────┐ ┌─────────────┐
│  Titles   │ │Descriptions │ │ Canonical │ │ Meta Robots   │ │ Social Tags │
│ Presenter │ │  Presenter  │ │ Presenter │ │   Presenter   │ │  Presenter  │
└───────────┘ └─────────────┘ └───────────┘ └───────────────┘ └─────────────┘
```

---

## 2. Dynamic Variable Engine Specification
Supported token variables dynamically interpolated across all metadata templates:

### 2.1 Core System & Site Variables
- `%%sitename%%` -> `get_bloginfo('name')`
- `%%sitedesc%%` -> `get_bloginfo('description')`
- `%%siteurl%%` -> `home_url()`
- `%%currentdate%%` -> Current date (e.g. `August 2026`)
- `%%currentyear%%` -> Current year (`2026`)
- `%%sep%%` -> Configured separator character (e.g. `-`, `|`, `•`, `»`)

### 2.2 Post & Page Variables
- `%%title%%` -> `get_the_title($post_id)`
- `%%excerpt%%` -> Sanitized post excerpt (fallback to first 160 characters of content)
- `%%content%%` -> Sanitized post content stripped of shortcodes and HTML
- `%%date%%` -> Post published date
- `%%modified%%` -> Post last modified date
- `%%id%%` -> `$post->ID`
- `%%slug%%` -> `$post->post_name`
- `%%author_name%%` -> Author display name (`get_the_author_meta('display_name', $post->post_author)`)
- `%%author_bio%%` -> Author description bio
- `%%featured_image%%` -> URL of featured attachment

### 2.3 Taxonomy & Term Variables
- `%%term_title%%` -> Single term title (`single_term_title('', false)`)
- `%%term_description%%` -> Term description sanitized
- `%%category%%` -> Primary category of the current post
- `%%tag%%` -> Primary post tag

### 2.4 Custom Field & Third-Party Tokens
- `%%cf_<field_name>%%` -> Value of custom field `get_post_meta($post_id, '<field_name>', true)`
- `%%acf_<field_name>%%` -> Value from Advanced Custom Fields `get_field('<field_name>', $post_id)`
- `%%wc_price%%` -> Formatted WooCommerce product price
- `%%wc_sku%%` -> Product SKU
- `%%wc_brand%%` -> Product brand attribute / taxonomy
- `%%wc_short_desc%%` -> Product short description

---

## 3. Meta Robots Calculation Pipeline
The robots tag compiler computes the exact composite string according to the following precedence hierarchy:

```
Global Setting -> Post Type Setting -> Post Level Override -> Privacy Setting
```

Supported direct attributes:
- `noindex` / `index`
- `nofollow` / `follow`
- `noarchive`
- `nosnippet`
- `noimageindex`
- `max-snippet:-1` (or custom integer)
- `max-image-preview:large` (or `standard`, `none`)
- `max-video-preview:-1` (or custom integer)

---

## 4. Breadcrumb Engine
The breadcrumb system builds both an accessible semantic HTML breadcrumb navigation trail (`<nav class="apex-breadcrumbs" aria-label="Breadcrumb">`) and an accompanying JSON-LD `BreadcrumbList` schema graph node with zero discrepancy.
