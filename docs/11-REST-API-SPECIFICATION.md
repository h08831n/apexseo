# 11 - REST API & Headless Architecture Specification

**Namespace**: `/wp-json/apexseo/v1/`  
**Authentication & Permissions**:
- Public Read-Only Endpoints: Open or validated with public site token.
- Protected Mutation Endpoints: Require `X-WP-Nonce` header, Application Passwords, or JWT with `manage_options` capability.

---

## 1. Exhaustive 22 REST API Route Inventory

| Endpoint Route | HTTP Method | Capability Required | Request Body / Query Params | Response Data Contract |
|---|---|---|---|---|
| `/apexseo/v1/meta` | `GET` | Public | `?url={url}` or `?post_id={id}` | Full resolved SEO payload (Title, Desc, Canonical, Robots, Social, Breadcrumbs, Schema graph) |
| `/apexseo/v1/schema` | `GET` | Public | `?post_id={id}` | Raw JSON-LD Schema graph representation for headless consumers |
| `/apexseo/v1/sitemap/index` | `GET` | Public | None | Index list of active sitemaps, chunk counts, and last modification timestamps |
| `/apexseo/v1/llms.txt` | `GET` | Public | None | Plaintext Markdown representation conforming to llmstxt.org |
| `/apexseo/v1/settings` | `GET` | `manage_options` | None | Complete serialized plugin settings object |
| `/apexseo/v1/settings` | `POST` | `manage_options` | `{ settings: { ... } }` | Success status, updated timestamp, validation errors if any |
| `/apexseo/v1/analyze` | `POST` | `edit_posts` | `{ content: string, title: string, keyword: string }` | Full Real-Time Content Analysis, Flesch score, keyword density, improvement checklist |
| `/apexseo/v1/cache/status` | `GET` | `manage_options` | None | Cache driver status, hit rate, cached files count, total disk space used |
| `/apexseo/v1/cache/purge` | `POST` | `manage_options` (or `edit_posts` for single post) | `{ all?: boolean, urls?: string[], tags?: string[] }` | Purge confirmation, invalidated tags count |
| `/apexseo/v1/cache/warmup` | `POST` | `manage_options` | `{ sitemap_url?: string }` | Warmup job dispatched status, queued URLs count |
| `/apexseo/v1/schema/templates` | `GET` | `manage_options` | `?type={schema_type}` | List of visual schema templates with rules |
| `/apexseo/v1/schema/templates` | `POST` | `manage_options` | `{ title: string, schema_type: string, data: object, conditions: object }` | Created/updated template record ID |
| `/apexseo/v1/schema/templates/{id}` | `DELETE` | `manage_options` | None | Deletion status |
| `/apexseo/v1/redirects` | `GET` | `manage_options` | `?page={int}&per_page={int}&search={string}` | Paginated list of redirects, hits, status codes |
| `/apexseo/v1/redirects` | `POST` | `manage_options` | `{ source: string, target: string, status_code: int, is_regex: boolean }` | Created redirect ID |
| `/apexseo/v1/redirects/{id}` | `DELETE` | `manage_options` | None | Deletion status |
| `/apexseo/v1/404-logs` | `GET` | `manage_options` | `?page={int}&per_page={int}` | Paginated 404 URL access logs |
| `/apexseo/v1/404-logs` | `DELETE` | `manage_options` | `{ ids?: int[], all?: boolean }` | Purge confirmation |
| `/apexseo/v1/media/optimize-single` | `POST` | `upload_files` | `{ attachment_id: int, format: 'webp'\|'avif' }` | Image compression statistics, savings percentage |
| `/apexseo/v1/media/bulk-optimize` | `POST` | `manage_options` | `{ batch_size?: int }` | Background queue progress, processed count, remaining count |
| `/apexseo/v1/migrate/execute` | `POST` | `manage_options` | `{ source: string, dry_run?: boolean }` | Migration logs, imported entities count, errors |
| `/apexseo/v1/system/status` | `GET` | `manage_options` | None | PHP version, extensions (GD/Imagick/Redis), server software, table integrity |

---

## 2. Standard Headless JSON Response Structure (`GET /apexseo/v1/meta`)

```json
{
  "status": "success",
  "data": {
    "title": "Unified WordPress SEO & Performance Platform - Apex SEO",
    "meta_description": "Production-grade unified platform combining SEO, schema, cache, and media optimization.",
    "canonical": "https://example.com/sample-post/",
    "meta_robots": {
      "noindex": false,
      "nofollow": false,
      "noarchive": false,
      "max_snippet": -1,
      "max_image_preview": "large",
      "max_video_preview": -1
    },
    "open_graph": {
      "og:title": "Unified WordPress SEO & Performance Platform",
      "og:description": "Production-grade unified platform combining SEO, schema, cache, and media optimization.",
      "og:type": "article",
      "og:url": "https://example.com/sample-post/",
      "og:image": "https://example.com/wp-content/uploads/2026/08/featured.jpg",
      "og:site_name": "Apex SEO Site"
    },
    "twitter": {
      "twitter:card": "summary_large_image",
      "twitter:title": "Unified WordPress SEO & Performance Platform",
      "twitter:image": "https://example.com/wp-content/uploads/2026/08/featured.jpg"
    },
    "breadcrumbs": [
      { "name": "Home", "url": "https://example.com/" },
      { "name": "Blog", "url": "https://example.com/blog/" },
      { "name": "Sample Post", "url": "https://example.com/sample-post/" }
    ],
    "schema": {
      "@context": "https://schema.org",
      "@graph": [
        {
          "@type": "WebSite",
          "@id": "https://example.com/#website",
          "url": "https://example.com/",
          "name": "Apex SEO Site"
        },
        {
          "@type": "Article",
          "@id": "https://example.com/sample-post/#primary",
          "headline": "Unified WordPress SEO & Performance Platform",
          "isPartOf": { "@id": "https://example.com/sample-post/#webpage" }
        }
      ]
    }
  }
}
```
