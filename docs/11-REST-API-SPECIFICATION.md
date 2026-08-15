# 11 - REST API & Headless SEO Specification

## 1. Base URL & Authentication
- **Namespace**: `/wp-json/apexseo/v1/`
- **Authentication**:
  - Public read-only endpoints (e.g. meta retrieval, schema query) require no auth.
  - State-modifying endpoints (purge cache, optimize media, run migrations, save settings) require standard WordPress Nonce (`X-WP-Nonce`) or Application Passwords / Bearer Token with `manage_options` capability.

---

## 2. Comprehensive Endpoint Index

### 2.1 Public Headless & SEO Query Endpoints
- `GET /wp-json/apexseo/v1/meta?url={url}` -> Returns full calculated SEO payload (Title, Description, Canonical, Robots, Social, Schema graph, Breadcrumbs).
- `GET /wp-json/apexseo/v1/schema?post_id={id}` -> Returns raw JSON-LD graph representation for a specific post.
- `GET /wp-json/apexseo/v1/sitemap/index` -> Returns structured list of all active sitemap chunks and last modification dates.
- `GET /wp-json/apexseo/v1/llms.txt` -> Dynamic plain-text output for AI crawler context.

### 2.2 Content Analysis & AI Endpoints
- `POST /wp-json/apexseo/v1/analyze` -> Receives post content, title, and target keyword; returns full TruScore, Readability metrics, and suggestion checklist.
- `POST /wp-json/apexseo/v1/ai/generate-meta` -> Calls server-side Gemini API with content summary to produce candidate titles, descriptions, and FAQ blocks.

### 2.3 Management & Performance Endpoints
- `POST /wp-json/apexseo/v1/cache/purge` -> Accepts optional `urls` array or `all=true` to invalidate static page cache and server cache tags.
- `POST /wp-json/apexseo/v1/media/optimize` -> Queues or executes synchronous compression on specified attachment IDs.
- `GET  /wp-json/apexseo/v1/system/status` -> Returns JSON matrix of server capabilities, PHP extensions, and DB table health.
- `GET  /wp-json/apexseo/v1/redirects` -> Lists configured redirect rules with pagination, hits, and filter queries.
- `POST /wp-json/apexseo/v1/redirects` -> Creates or updates a redirect rule.
- `GET  /wp-json/apexseo/v1/404-logs` -> Lists recent 404 access records with hit counts.

---

## 3. Standard Headless JSON Response Structure
```json
{
  "status": "success",
  "seo": {
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
