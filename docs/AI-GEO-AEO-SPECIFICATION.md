# AI, GEO & Answer Engine Optimization (AEO) Specification

**Audit Reference**: OpenAI GPTBot, Anthropic ClaudeBot, PerplexityBot, Google-Extended, llmstxt.org Standard  
**Purpose**: Comprehensive technical specification for AI Crawler governance, Generative Engine Optimization (GEO), and Answer Engine Optimization (AEO).

---

## 1. AI Crawler & Bot Governance Matrix

Apex SEO provides granular access control for major Large Language Model (LLM) and Search AI crawlers:

| Bot Identifier | Operating Entity | User-Agent Token | Default Policy | Purpose & Impact | Configurable Directives |
|---|---|---|---|---|---|
| **GPTBot** | OpenAI | `GPTBot` | `ALLOW` | Powers ChatGPT search, summaries, and real-time knowledge retrieval | `Allow: /`, `Disallow: /`, `Disallow: /wp-admin/` |
| **ChatGPT-User** | OpenAI | `ChatGPT-User` | `ALLOW` | Direct user-initiated URL browsing within ChatGPT | `Allow: /`, `Disallow: /` |
| **ClaudeBot** | Anthropic | `ClaudeBot` | `ALLOW` | Powers Claude AI summaries, web retrieval, and contextual analysis | `Allow: /`, `Disallow: /` |
| **PerplexityBot** | Perplexity AI | `PerplexityBot` | `ALLOW` | Powers Perplexity AI real-time search and direct citations | `Allow: /`, `Disallow: /` |
| **Google-Extended** | Google | `Google-Extended` | `ALLOW` | Training and real-time retrieval for Gemini and Vertex AI models | `Allow: /`, `Disallow: /` |
| **Applebot-Extended** | Apple | `Applebot-Extended` | `ALLOW` | Powers Apple Intelligence and Siri search summaries | `Allow: /`, `Disallow: /` |
| **CCBot** | Common Crawl | `CCBot` | `DISALLOW` | Large-scale AI training dataset scraping (high server bandwidth consumption) | `Disallow: /` |
| **Bytespider** | ByteDance | `Bytespider` | `DISALLOW` | Aggressive TikTok / ByteDance AI crawler | `Disallow: /` |

---

## 2. Dynamic `robots.txt` Generation Contract

The `robots.txt` generator emits clean, compliant rules based on admin toggle settings:

```
# START APEX SEO AI BOT DIRECTIVES
User-agent: GPTBot
Allow: /
Disallow: /wp-admin/

User-agent: ClaudeBot
Allow: /
Disallow: /wp-admin/

User-agent: PerplexityBot
Allow: /
Disallow: /wp-admin/

User-agent: Google-Extended
Allow: /

User-agent: Applebot-Extended
Allow: /

User-agent: CCBot
Disallow: /

User-agent: Bytespider
Disallow: /
# END APEX SEO AI BOT DIRECTIVES

Sitemap: https://example.com/sitemap_index.xml
```

---

## 3. `llms.txt` and `llms-full.txt` Specification

Conforming strictly to the **llmstxt.org** standard, Apex SEO automatically generates structured Markdown index files for AI consumption:

### 3.1 Route 1: `/llms.txt` (Curated Directory)
- **Path**: `https://example.com/llms.txt`
- **Output Format**: Clean Markdown listing site identity, primary sections, documentation, and core post links.
- **Example Output**:
```markdown
# Example Company

> The leading developer of enterprise performance software.

## Core Documentation
- [Getting Started](https://example.com/docs/getting-started/): Step-by-step setup guide.
- [API Reference](https://example.com/docs/api/): REST API endpoints and data models.

## Key Articles
- [Core Web Vitals Guide](https://example.com/cwv-guide/): Complete performance optimization manual.
```

### 3.2 Route 2: `/llms-full.txt` (Full Plaintext Index)
- **Path**: `https://example.com/llms-full.txt`
- **Output Format**: Complete plaintext / markdown representations of indexed content, optimized for direct LLM ingestion without HTML boilerplate.

---

## 4. Answer Engine Optimization (AEO) & Structured Data Extensions

To maximize visibility in Google AI Overviews, Perplexity Citations, and ChatGPT Browse results:

1. **`SpeakableSpecification` JSON-LD**:
   Emitted on Article and NewsArticle nodes, pinpointing the exact CSS selectors (`.article-summary`, `.apex-key-takeaways`) suitable for text-to-speech and AI snippet extraction.
2. **`FAQPage` & `HowTo` Structural Entities**:
   Directly mapped to question/answer trees, formatted to allow immediate single-answer parsing by LLM context windows.
3. **Citation-Optimized Metadata**:
   Explicit OpenGraph and Meta tags defining `og:article:published_time`, `og:article:author`, and `og:article:section` to establish authoritative entity attribution.
