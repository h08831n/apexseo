# Performance Budget & Runtime Overhead Specification

**Audit Reference**: Core Web Vitals (CWV), Google Lighthouse Performance Standards  
**Target Architecture**: Zero-bloat, single-query dynamic runtime, sub-50ms cached delivery.

---

## 1. Runtime Overhead Budgets (Strict Limits)

| Metric | Target Budget | Upper Enforcement Limit | Measurement Method / Tool | Failure Action |
|---|---|---|---|---|
| **Static Cache Hit TTFB** | **< 20ms** | **50ms** | `cURL` / WebPageTest (Time to First Byte) | Profile disk I/O and server response headers |
| **Dynamic Frontend PHP Overhead** | **< 8ms** | **15ms** | Query Monitor / Xdebug profiling | Profile DI container resolution and indexables loader |
| **Memory Consumption (Frontend)**| **< 2.5MB** | **4.0MB** | `memory_get_peak_usage(true)` | Audit autoloaded options and memory leaks |
| **Database Queries (Dynamic Frontend)**| **1 Query** | **2 Queries** | `$wpdb->num_queries` delta | Enforce single-query indexables table lookup |
| **Database Queries (Cached Page)** | **0 Queries** | **0 Queries** | Database connection bypass | Verify zero DB socket initialization |
| **Admin Screen PHP Overhead** | **< 25ms** | **45ms** | Query Monitor | Lazy-load inactive admin controllers and modules |

---

## 2. Frontend Client Asset Budgets

| Client Asset Component | File Size Budget (Minified + Gzip) | Execution / Parse Budget | Loading Strategy |
|---|---|---|---|
| **JavaScript Delay Runtime Engine** | **< 1.8 KB** | **< 5ms** | Inlined raw JS in `<head>` (Zero external HTTP requests) |
| **Image LazyLoad Fallback Script** | **< 1.2 KB** | **< 3ms** | Inlined or defer-loaded only if browser lacks native `loading="lazy"` |
| **Critical CSS Above-the-Fold** | **< 14.0 KB** | **< 10ms** | Inlined `<style id="apex-critical-css">` in `<head>` |
| **Instant.page Preload Engine** | **< 1.5 KB** | **< 2ms** | Async defer script on footer |

---

## 3. Database Execution Budget

1. **Frontend Indexables Query**:
   `SELECT * FROM wp_apex_indexables WHERE object_id = %d AND object_type = %s LIMIT 1;`
   - Maximum Execution Time: **< 1.0ms** (Index hit on composite unique key).
2. **Redirect Lookup Query (on 404)**:
   `SELECT * FROM wp_apex_redirects WHERE source_hash = %s LIMIT 1;`
   - Maximum Execution Time: **< 0.8ms** (Index hit on `source_hash`).
3. **Internal Links Graph Batch Query**:
   `SELECT target_post_id, COUNT(*) as count FROM wp_apex_links WHERE source_post_id IN (...) GROUP BY target_post_id;`
   - Executed strictly in admin/background cron queues; forbidden on frontend visitor requests.

---

## 4. Continuous Integration (CI) Benchmark Automation

- **Automated Regression Testing**: GitHub Actions workflow running automated PHPUnit benchmarks with memory and query assertions.
- **Budget Threshold Assertion**:
  ```php
  public function test_frontend_overhead_within_budget(): void {
      $start_memory = memory_get_usage();
      $start_time = microtime(true);

      $this->seo_engine->render_head();

      $time_taken = (microtime(true) - $start_time) * 1000;
      $memory_used = (memory_get_usage() - $start_memory) / (1024 * 1024);

      $this->assertLessThan(15.0, $time_taken, 'Frontend head render exceeded 15ms overhead budget.');
      $this->assertLessThan(4.0, $memory_used, 'Frontend head render exceeded 4MB memory budget.');
  }
  ```
