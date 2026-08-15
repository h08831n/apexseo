# Asset Optimization Safety & Execution Contract

**Audit Reference**: WP Rocket, LiteSpeed Cache, Core Web Vitals Optimization Guidelines  
**Purpose**: Rigorous contracts preventing frontend layout shifts, script execution breakage, and visual regressions.

---

## 1. JavaScript Delay Execution Contract

### 1.1 Trigger Events
When "Delay JavaScript Execution" is enabled, scripts marked for delay have their `type` attribute modified to `type="text/apex-delay"`. Execution is triggered upon the first occurrence of ANY of the following browser events:
- `mousemove` (Desktop cursor movement)
- `keydown` (Keyboard interaction)
- `touchstart` / `touchmove` (Mobile touchscreen interaction)
- `scroll` (Page scrolling)
- `wheel` (Mouse wheel interaction)
- `DOMContentLoaded` + 8000ms safety timer (Fallback to ensure scripts execute even with no user interaction)

### 1.2 Guaranteed JS Exclusion Whitelist
The following scripts and identifiers are **NEVER delayed** to prevent breaking core UI functionality and analytics:

| Script Handle / Identifier | Ecosystem | Reason for Mandatory Exclusion |
|---|---|---|
| `jquery.js`, `jquery-core.js` | WordPress Core | Dependency for theme navigation menus, sliders, and form builders |
| `wc-cart-fragments.js` | WooCommerce | Dynamic cart counter updates |
| `woocommerce.js`, `add-to-cart.js` | WooCommerce | AJAX cart operations |
| `stripe.com/v3`, `pay.google.com` | Payment Gateways | Security token generation on checkout |
| `gtag.js`, `google-analytics.com` | Analytics | Accurate bounce rate and pageview tracking |
| `elementor-frontend.js` (Core) | Elementor Builder | Mobile hamburger menu rendering |
| `et-builder-modules-script.js` | Divi Builder | Layout initialization |

---

## 2. CSS Optimization & Topological Dependency Graph

### 2.1 CSS Combination Safety Rules
- **Topological Sorting**: Stylesheets are combined strictly in the order of their WordPress dependency declarations (`wp_enqueue_style( $handle, $src, $deps )`).
- **Media Query Preservation**: Combined styles are grouped into `@media` blocks matching their registered media types (`all`, `screen`, `print`, `(max-width: 768px)`).
- **`@import` Hoisting**: All external `@import` rules are extracted and hoisted to the absolute top of the combined stylesheet.
- **Relative URL Rewriting**: Background image paths (e.g. `url(../images/bg.png)`) are rewritten to absolute or correctly resolved relative paths.

### 2.2 Critical CSS Extraction & Fallback Contract
- **Generation**: Above-the-fold CSS is extracted for standard layout archetypes (`front_page`, `single_post`, `single_page`, `archive`, `product`).
- **Head Inlining**: Critical CSS is inlined directly inside `<style id="apex-critical-css">` in the document `<head>`.
- **Async Full CSS**: Remaining stylesheets are loaded asynchronously with `<link rel="preload" as="style" onload="this.onload=null;this.rel='stylesheet'">`.
- **Fallback**: If Critical CSS generation is pending or fails, all stylesheets immediately load via standard synchronous `<link rel="stylesheet">` tags without blocking page rendering.

---

## 3. Image Optimization & Core Web Vitals (LCP) Safety Contract

### 3.1 Automatic Largest Contentful Paint (LCP) Protection
To prevent lazy-loading the LCP image (which destroys Google PageSpeed LCP scores):
1. **Featured Images**: The post featured image (`wp-post-image`) is automatically excluded from `loading="lazy"` and placeholder replacement.
2. **First Content Image**: The first `<img>` tag discovered in the post content DOM is excluded from lazy loading.
3. **`fetchpriority="high"`**: Automatically injected onto the detected LCP image.

### 3.2 WebP & AVIF Fallback Delivery
Images converted to WebP or AVIF are delivered using `<picture>` tag rewriting:

```html
<!-- Native Multi-Format Delivery -->
<picture class="apex-optimized-media">
    <source srcset="/wp-content/uploads/2026/08/hero.avif" type="image/avif">
    <source srcset="/wp-content/uploads/2026/08/hero.webp" type="image/webp">
    <img src="/wp-content/uploads/2026/08/hero.jpg" width="1200" height="630" alt="Hero Image" loading="lazy" decoding="async">
</picture>
```
If the browser does not support AVIF or WebP, it automatically falls back to the original JPEG/PNG without JavaScript intervention.

---

## 4. Diagnostics & Safe Mode Contract

### `?apex_safe_mode=1` Diagnostic Bypass
- **Functionality**: Appending `?apex_safe_mode=1` to any frontend URL immediately disables all caching, JS delay/minification, CSS combination, and HTML minification for that specific request.
- **Security Check**: Restricted to logged-in administrators (`current_user_can('manage_options')`) or validated via a one-time diagnostic nonce.
- **Admin Tool**: Allows site owners to instantly isolate whether a visual defect is caused by their theme/plugins or by optimization filters.
