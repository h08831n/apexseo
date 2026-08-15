# 18 - Architectural Decision Log (ADR)

## Decision 1: Monolithic Unified Plugin vs. Micro-Plugins
- **Decision**: Architect Apex SEO as a single, modular PSR-4 WordPress plugin with lazy-loaded service providers.
- **Rationale**: Eliminates inter-plugin communication overhead, avoids duplicate database tables, and provides a unified UI/UX for administrators.

## Decision 2: Custom Relational Tables vs. wp_options / wp_postmeta
- **Decision**: Establish 11 custom relational InnoDB tables for indexables, schema templates, redirects, 404 logs, link maps, image history, and cache metadata.
- **Rationale**: Prevents massive table bloat in `wp_options` and `wp_postmeta`, speeding up database queries by over 10x on sites with 10,000+ posts.

## Decision 3: Server-Agnostic Cache Architecture
- **Decision**: Implement a unified Cache Driver Interface that automatically detects LiteSpeed, Nginx, Apache, or falls back to disk-based static file caching.
- **Rationale**: Guarantees high-performance full-page caching regardless of hosting provider.

## Decision 4: Deterministic Schema `@id` Graph Model
- **Decision**: Adopt Schema.org unified `@graph` format with deterministic `@id` URI minting.
- **Rationale**: Prevents duplicate organization/author entity declarations and complies with Google Structured Data guidelines.
