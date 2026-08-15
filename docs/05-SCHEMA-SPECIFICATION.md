# 05 - Schema Subsystem Specification & Complete Schema Matrix

## 1. Schema Architecture & Graph Model
Apex SEO outputs a unified `<script type="application/ld+json">` containing a top-level `@graph` array adhering strictly to the Schema.org 2026 vocabulary.

### 1.1 Deterministic `@id` Reference Specification
- **Organization Node**: `{home_url}#organization`
- **WebSite Node**: `{home_url}#website` (references publisher Organization via `@id`)
- **WebPage Node**: `{canonical_url}#webpage` (references isPartOf WebSite, breadcrumb BreadcrumbList)
- **Primary Entity Node**: `{canonical_url}#primary` (e.g. `Article`, `Product`, `Event`)
- **Author / Person Node**: `{author_posts_url}#author`
- **Primary Image Node**: `{canonical_url}#primaryimage`

---

## 2. Complete Schema Types Registry (52 Verified Types)

| # | Schema Type | Schema.org Type | Source Plugin | Conditions Supported | WooCommerce Ready | Status |
|---|---|---|---|---|---|---|
| 1 | **Article** | `https://schema.org/Article` | Yoast / RM / AIOSEO | Post Type, Category, Author | No | VERIFIED |
| 2 | **BlogPosting** | `https://schema.org/BlogPosting` | Yoast / RM | Post Type | No | VERIFIED |
| 3 | **NewsArticle** | `https://schema.org/NewsArticle` | Yoast / RM / AIOSEO | Post Type, Category | No | VERIFIED |
| 4 | **TechArticle** | `https://schema.org/TechArticle` | Rank Math | Post Type, Tag | No | VERIFIED |
| 5 | **Report** | `https://schema.org/Report` | AIOSEO | Post Type | No | VERIFIED |
| 6 | **WebPage** | `https://schema.org/WebPage` | Yoast / RM | Universal | No | VERIFIED |
| 7 | **AboutPage** | `https://schema.org/AboutPage` | Yoast / RM | Page Template | No | VERIFIED |
| 8 | **ContactPage** | `https://schema.org/ContactPage` | Yoast / RM | Page Template | No | VERIFIED |
| 9 | **ProfilePage** | `https://schema.org/ProfilePage` | Yoast / RM | Author Archive | No | VERIFIED |
| 10 | **CollectionPage**| `https://schema.org/CollectionPage`| Yoast / RM | Taxonomies, CPT Archives | No | VERIFIED |
| 11 | **ItemPage** | `https://schema.org/ItemPage` | Yoast / RM | Single Post Types | No | VERIFIED |
| 12 | **SearchResultsPage**| `https://schema.org/SearchResultsPage`| Yoast / RM | Search Query | No | VERIFIED |
| 13 | **FAQPage** | `https://schema.org/FAQPage` | Yoast / RM / SEOPress | Post Level / Block | No | VERIFIED |
| 14 | **QAPage** | `https://schema.org/QAPage` | Rank Math | Post Level / Forum | No | VERIFIED |
| 15 | **HowTo** | `https://schema.org/HowTo` | Yoast / RM / AIOSEO | Post Level / Block | No | VERIFIED |
| 16 | **BreadcrumbList**| `https://schema.org/BreadcrumbList`| All Sources | Universal | Yes | VERIFIED |
| 17 | **WebSite** | `https://schema.org/WebSite` | All Sources | Universal | Yes | VERIFIED |
| 18 | **Organization** | `https://schema.org/Organization` | All Sources | Global / Site Identity | Yes | VERIFIED |
| 19 | **Corporation** | `https://schema.org/Corporation` | Rank Math / AIOSEO | Global / Site Identity | No | VERIFIED |
| 20 | **LocalBusiness** | `https://schema.org/LocalBusiness` | RM / AIOSEO / SEOPress | Multi-Location CPT | Yes | VERIFIED |
| 21 | **Store** | `https://schema.org/Store` | Rank Math | Multi-Location CPT | Yes | VERIFIED |
| 22 | **Restaurant** | `https://schema.org/Restaurant` | Rank Math / AIOSEO | Multi-Location CPT | No | VERIFIED |
| 23 | **MedicalOrganization**| `https://schema.org/MedicalOrganization`| AIOSEO Pro | Global / CPT | No | VERIFIED |
| 24 | **EducationalOrganization**| `https://schema.org/EducationalOrganization`| AIOSEO Pro | Global / CPT | No | VERIFIED |
| 25 | **Person** | `https://schema.org/Person` | All Sources | Author / User Profile | No | VERIFIED |
| 26 | **Product** | `https://schema.org/Product` | RM / Yoast / AIOSEO | WooCommerce / CPT | Yes | VERIFIED |
| 27 | **ProductGroup** | `https://schema.org/ProductGroup` | Rank Math Pro | Variable Products | Yes | VERIFIED |
| 28 | **Offer** | `https://schema.org/Offer` | All Sources | WooCommerce Product | Yes | VERIFIED |
| 29 | **AggregateOffer**| `https://schema.org/AggregateOffer`| Rank Math | Variable Products | Yes | VERIFIED |
| 30 | **Review** | `https://schema.org/Review` | Rank Math / AIOSEO | Post Level / Comments | Yes | VERIFIED |
| 31 | **AggregateRating**| `https://schema.org/AggregateRating`| All Sources | Product / Reviews | Yes | VERIFIED |
| 32 | **Service** | `https://schema.org/Service` | Rank Math Pro | CPT / Pages | No | VERIFIED |
| 33 | **Event** | `https://schema.org/Event` | RM / AIOSEO / SEOPress | Post Level / CPT | No | VERIFIED |
| 34 | **Course** | `https://schema.org/Course` | Rank Math / AIOSEO | Post Level / LMS | No | VERIFIED |
| 35 | **CourseInstance**| `https://schema.org/CourseInstance`| Rank Math Pro | LMS Integrations | No | VERIFIED |
| 36 | **JobPosting** | `https://schema.org/JobPosting` | RM / AIOSEO / SEOPress | Post Level / CPT | No | VERIFIED |
| 37 | **Recipe** | `https://schema.org/Recipe` | RM / Yoast / AIOSEO | Post Level / Block | No | VERIFIED |
| 38 | **VideoObject** | `https://schema.org/VideoObject` | All Sources | Embedded Video / Post | No | VERIFIED |
| 39 | **ImageObject** | `https://schema.org/ImageObject` | All Sources | Attachment / Media | Yes | VERIFIED |
| 40 | **AudioObject** | `https://schema.org/AudioObject` | Rank Math Pro | Podcast / Audio | No | VERIFIED |
| 41 | **Movie** | `https://schema.org/Movie` | Rank Math Pro | Post Level / CPT | No | VERIFIED |
| 42 | **TVSeries** | `https://schema.org/TVSeries` | Rank Math Pro | Post Level / CPT | No | VERIFIED |
| 43 | **PodcastSeries**| `https://schema.org/PodcastSeries`| Rank Math Pro | Post Level / Audio | No | VERIFIED |
| 44 | **PodcastEpisode**| `https://schema.org/PodcastEpisode`| Rank Math Pro | Post Level / Audio | No | VERIFIED |
| 45 | **SoftwareApplication**| `https://schema.org/SoftwareApplication`| Rank Math / AIOSEO | Post Level / CPT | No | VERIFIED |
| 46 | **MobileApplication**| `https://schema.org/MobileApplication`| Rank Math Pro | Post Level / CPT | No | VERIFIED |
| 47 | **WebApplication**| `https://schema.org/WebApplication`| Rank Math Pro | Post Level / CPT | No | VERIFIED |
| 48 | **Book** | `https://schema.org/Book` | Rank Math / AIOSEO | Post Level / CPT | No | VERIFIED |
| 49 | **Dataset** | `https://schema.org/Dataset` | Rank Math Pro | Post Level / CPT | No | VERIFIED |
| 50 | **ClaimReview** | `https://schema.org/ClaimReview` | Rank Math / AIOSEO | Fact Check / Post | No | VERIFIED |
| 51 | **DiscussionForumPosting**| `https://schema.org/DiscussionForumPosting`| Rank Math Pro | Forum / Community | No | VERIFIED |
| 52 | **ItemList** | `https://schema.org/ItemList` | Yoast / RM | Archive / Category | Yes | VERIFIED |
