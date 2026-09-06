import http from 'node:http';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const PORT = Number(process.env.PORT) || 3000;

// Read JSON body helper
async function readBody(req) {
  return new Promise((resolve) => {
    let data = '';
    req.on('data', chunk => { data += chunk; });
    req.on('end', () => {
      try {
        resolve(JSON.parse(data));
      } catch {
        resolve({});
      }
    });
  });
}

// Calculate Flesch Reading Ease
function calculateFlesch(text) {
  const clean = text.replace(/<[^>]+>/g, ' ').trim();
  const sentences = clean.split(/[.!?]+/).filter(s => s.trim().length > 0);
  const words = clean.split(/\s+/).filter(w => w.length > 0);
  
  if (words.length === 0 || sentences.length === 0) {
    return { score: 70, ease: 'Fairly Easy', grade: '7th-8th Grade' };
  }

  // Count rough syllables
  let syllables = 0;
  for (const w of words) {
    const word = w.toLowerCase().replace(/[^a-z]/g, '');
    if (word.length <= 3) {
      syllables += 1;
      continue;
    }
    const matches = word.match(/[aeiouy]{1,2}/g);
    let count = matches ? matches.length : 1;
    if (word.endsWith('e') && !word.endsWith('le')) {
      count = Math.max(1, count - 1);
    }
    syllables += count;
  }

  const ASL = words.length / Math.max(1, sentences.length);
  const ASW = syllables / Math.max(1, words.length);
  const rawScore = 206.835 - (1.015 * ASL) - (84.6 * ASW);
  const score = Math.min(100, Math.max(0, Math.round(rawScore * 10) / 10));

  let ease = 'Fairly Easy';
  let grade = '7th-8th Grade';
  if (score >= 90) { ease = 'Very Easy'; grade = '5th Grade'; }
  else if (score >= 80) { ease = 'Easy'; grade = '6th Grade'; }
  else if (score >= 70) { ease = 'Fairly Easy'; grade = '7th Grade'; }
  else if (score >= 60) { ease = 'Standard'; grade = '8th-9th Grade'; }
  else if (score >= 50) { ease = 'Fairly Difficult'; grade = '10th-12th Grade'; }
  else if (score >= 30) { ease = 'Difficult'; grade = 'College'; }
  else { ease = 'Very Confusing'; grade = 'Graduate'; }

  return { score, ease, grade };
}

// REST Route Handlers for the 11 locked endpoints
const REST_MOCKS = {
  '/wp-json/apex/v1/status': {
    status: 'operational',
    version: '1.0.0',
    environment: 'production',
    tables_locked: 8,
    active_controllers: 11,
    active_cli_commands: 11,
    cache_backend: 'in-memory-lru',
    schema_registry: 'synchronized'
  },
  '/wp-json/apex/v1/schema': {
    status: 'success',
    registered_schemas: ['Article', 'NewsArticle', 'Product', 'FAQPage', 'BreadcrumbList', 'Organization', 'WebSite'],
    compiled_graphs_cached: 8920,
    validator_compliance: '100% Schema.org v26.0'
  },
  '/wp-json/apex/v1/analysis': {
    status: 'ready',
    algorithm: 'hybrid-lexical-flesch',
    supported_metrics: ['keyword_density', 'flesch_reading_ease', 'heading_hierarchy', 'link_ratio'],
    latency_ms: 0.8
  },
  '/wp-json/apex/v1/redirects': {
    status: 'success',
    total_rules: 480,
    lookup_complexity: 'O(1)',
    recent_rules: [
      { from: '/old-seo-guide/', to: '/guide/best-wordpress-seo-plugin-guide/', status_code: 301 },
      { from: '/features/', to: '/#features', status_code: 302 }
    ]
  },
  '/wp-json/apex/v1/sitemap': {
    status: 'success',
    index_url: 'http://localhost:8080/sitemap_index.xml',
    total_urls: 61400,
    partitions: ['post-sitemap.xml', 'page-sitemap.xml', 'category-sitemap.xml', 'video-sitemap.xml'],
    last_pinged: '2026-09-06T11:54:00Z'
  },
  '/wp-json/apex/v1/keywords': {
    status: 'success',
    tracked_count: 3250,
    top_rankings: [
      { keyword: 'wordpress seo plugin', position: 1, volume: 45000, trend: '+2' },
      { keyword: 'schema generator wordpress', position: 2, volume: 18000, trend: 'stable' }
    ]
  },
  '/wp-json/apex/v1/links': {
    status: 'success',
    total_internal_links: 45100,
    orphan_pages_detected: 0,
    broken_links: 0,
    average_links_per_post: 6.4
  },
  '/wp-json/apex/v1/performance': {
    status: 'optimized',
    lcp_target_ms: 1200,
    cls_score: 0.01,
    inp_ms: 45,
    critical_css_preloaded: true,
    cache_hit_rate: 99.4
  },
  '/wp-json/apex/v1/audit-log': {
    status: 'success',
    immutable_ledger_entries: 14120,
    recent_events: [
      { id: 14120, action: 'schema_recompiled', user: 'apex_admin', timestamp: '2026-09-06T11:50:00Z' },
      { id: 14119, action: 'redirect_created', user: 'apex_admin', timestamp: '2026-09-06T11:45:12Z' }
    ]
  },
  '/wp-json/apex/v1/multisite': {
    status: 'success',
    is_multisite: false,
    network_replication_ready: true,
    isolated_schemas_supported: true
  },
  '/wp-json/apex/v1/settings': {
    status: 'success',
    title_separator: '–',
    auto_generate_social_meta: true,
    breadcrumbs_enabled: true,
    xml_sitemaps_enabled: true,
    rest_api_auth_required: false
  }
};

const server = http.createServer(async (req, res) => {
  const url = new URL(req.url, `http://${req.headers.host || 'localhost'}`);

  // CORS headers
  res.setHeader('Access-Control-Allow-Origin', '*');
  res.setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
  res.setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');

  if (req.method === 'OPTIONS') {
    res.writeHead(204);
    res.end();
    return;
  }

  // Health API
  if (url.pathname === '/api/health') {
    res.writeHead(200, { 'Content-Type': 'application/json' });
    res.end(JSON.stringify({
      status: 'ok',
      app: 'Apex SEO Platform',
      uptime: process.uptime(),
      timestamp: new Date().toISOString()
    }));
    return;
  }

  // Live SEO Content Analysis API
  if (url.pathname === '/api/analyze' && req.method === 'POST') {
    const body = await readBody(req);
    const title = (body.title || '').trim();
    const description = (body.description || '').trim();
    const content = (body.content || '').trim();
    const keyword = (body.keyword || '').trim().toLowerCase();
    const slug = (body.slug || '').trim().toLowerCase();

    const cleanContent = content.replace(/<[^>]+>/g, ' ');
    const words = cleanContent.split(/\s+/).filter(w => w.length > 0);
    const wordCount = words.length;
    const readingTimeMinutes = Math.max(1, Math.round(wordCount / 200));

    const flesch = calculateFlesch(content);

    // Keyword density
    let kwCount = 0;
    if (keyword && wordCount > 0) {
      const lowerContent = cleanContent.toLowerCase();
      const regex = new RegExp(keyword.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'gi');
      const matches = lowerContent.match(regex);
      kwCount = matches ? matches.length : 0;
    }
    const keywordDensity = wordCount > 0 ? Math.round((kwCount / wordCount) * 1000) / 10 : 0;

    // Checks
    const checks = [];
    let score = 50;

    // 1. Keyword in Title
    if (keyword && title.toLowerCase().includes(keyword)) {
      checks.push({ label: 'Focus Keyword in SEO Title', status: 'pass', message: `Title contains the focus keyword "${keyword}".` });
      score += 10;
    } else {
      checks.push({ label: 'Focus Keyword in SEO Title', status: 'fail', message: `Title does not contain the exact focus keyword.` });
    }

    // 2. Title Length
    if (title.length >= 40 && title.length <= 60) {
      checks.push({ label: 'Optimal SEO Title Length', status: 'pass', message: `Length is ${title.length} characters (Recommended: 40-60).` });
      score += 8;
    } else if (title.length > 60) {
      checks.push({ label: 'SEO Title Length', status: 'warn', message: `Title is ${title.length} chars and may be truncated on mobile SERPs.` });
      score += 4;
    } else {
      checks.push({ label: 'SEO Title Length', status: 'warn', message: `Title is short (${title.length} chars). Consider expanding to 40-60 chars.` });
    }

    // 3. Keyword in Meta Description
    if (keyword && description.toLowerCase().includes(keyword)) {
      checks.push({ label: 'Focus Keyword in Meta Description', status: 'pass', message: `Meta description contains "${keyword}".` });
      score += 8;
    } else {
      checks.push({ label: 'Focus Keyword in Meta Description', status: 'warn', message: `Keyword missing from meta description.` });
    }

    // 4. Meta Description Length
    if (description.length >= 120 && description.length <= 160) {
      checks.push({ label: 'Meta Description Length', status: 'pass', message: `Description is ${description.length} chars (Target: 120-160).` });
      score += 7;
    } else {
      checks.push({ label: 'Meta Description Length', status: 'warn', message: `Description is ${description.length} chars. Optimal is 120-160.` });
      score += 3;
    }

    // 5. Keyword in URL Slug
    const slugKw = keyword.replace(/\s+/g, '-');
    if (keyword && slug.includes(slugKw)) {
      checks.push({ label: 'Focus Keyword in URL Slug', status: 'pass', message: `Permalinks slug includes "${slugKw}".` });
      score += 6;
    } else {
      checks.push({ label: 'Focus Keyword in URL Slug', status: 'warn', message: `Keyword not found in URL slug.` });
    }

    // 6. Content Word Count
    if (wordCount >= 300) {
      checks.push({ label: 'Comprehensive Content Depth', status: 'pass', message: `Body text contains ${wordCount} words (Minimum: 300).` });
      score += 8;
    } else {
      checks.push({ label: 'Content Depth', status: 'warn', message: `Content has only ${wordCount} words. Consider expanding.` });
      score += 2;
    }

    // 7. Readability
    if (flesch.score >= 60) {
      checks.push({ label: 'Reading Ease Standard', status: 'pass', message: `Flesch Reading Ease score of ${flesch.score} is easily understood.` });
      score += 8;
    } else {
      checks.push({ label: 'Reading Ease Standard', status: 'warn', message: `Reading Ease score is ${flesch.score}. Consider shorter sentences.` });
      score += 3;
    }

    score = Math.min(100, Math.max(20, score));

    res.writeHead(200, { 'Content-Type': 'application/json' });
    res.end(JSON.stringify({
      score,
      wordCount,
      readingTimeMinutes,
      readabilityScore: flesch.score,
      readingEase: flesch.ease,
      readingGrade: flesch.grade,
      keywordCount: kwCount,
      keywordDensity,
      checks
    }));
    return;
  }

  // REST API Routes matching `/wp-json/apex/v1/*`
  if (url.pathname.startsWith('/wp-json/apex/v1/')) {
    const route = REST_MOCKS[url.pathname];
    if (route) {
      res.writeHead(200, { 'Content-Type': 'application/json' });
      res.end(JSON.stringify(route));
      return;
    }
  }

  // Default: Serve index.html
  const indexPath = path.join(__dirname, 'index.html');
  if (fs.existsSync(indexPath)) {
    res.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8' });
    fs.createReadStream(indexPath).pipe(res);
  } else {
    res.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8' });
    res.end('<!DOCTYPE html><html><head><title>Apex SEO Platform</title></head><body><h1>Apex SEO Platform</h1></body></html>');
  }
});

server.listen(PORT, '0.0.0.0', () => {
  console.log(`Apex SEO Dev Server listening on port ${PORT}`);
});
