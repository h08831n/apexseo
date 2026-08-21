# APEX SEO — PHASE 3E FINAL SCHEMA SUBSYSTEM AUDIT REPORT

**Audit Date**: 2026-08-21 06:46:25 UTC  
**Standard**: Schema.org JSON-LD Specification & Google Search Central Rich Results Requirements

---

## 1. Schema Type Implementation & Validation Matrix

| Schema Type | PHP Source Class | Schema.org `@type` | `@id` Node Resolution | Google Required Properties Handled | Google Optional / Recommended Handled | Rich Results Eligibility |
| :--- | :--- | :--- | :--- | :--- | :--- | :---: |
| **Article** | `src/Schema/Types/ArticleSchema.php` | `Article`, `NewsArticle`, `BlogPosting` | `{#url}#/schema/article` | `headline`, `image`, `datePublished`, `dateModified`, `author`, `publisher` | `description`, `mainEntityOfPage`, `articleSection`, `wordCount` | **ELIGIBLE** |
| **WebSite** | `src/Schema/Types/WebSiteSchema.php` | `WebSite` | `{#home_url}#/schema/website` | `name`, `url`, `potentialAction` (SearchAction) | `description`, `inLanguage`, `publisher` | **ELIGIBLE** |
| **Organization**| `src/Schema/Types/OrganizationSchema.php` | `Organization` | `{#home_url}#/schema/organization` | `name`, `url`, `logo` | `sameAs`, `contactPoint`, `foundingDate`, `legalName` | **ELIGIBLE** |
| **LocalBusiness**| `src/Schema/Types/LocalBusinessSchema.php`| `LocalBusiness`, `Store`, `Restaurant` | `{#home_url}#/schema/localbusiness` | `name`, `address` (PostalAddress), `telephone` | `geo` (GeoCoordinates), `openingHoursSpecification`, `priceRange` | **ELIGIBLE** |
| **Product** | `src/Schema/Types/ProductSchema.php` | `Product` | `{#url}#/schema/product` | `name`, `image`, `offers` (Offer/AggregateOffer) | `description`, `sku`, `gtin`, `brand`, `aggregateRating`, `review` | **ELIGIBLE** |
| **FAQPage** | `src/Schema/Types/FAQPageSchema.php` | `FAQPage` | `{#url}#/schema/faq` | `mainEntity` (Question -> acceptedAnswer -> Answer) | `name`, `description` | **ELIGIBLE** |
| **Recipe** | `src/Schema/Types/RecipeSchema.php` | `Recipe` | `{#url}#/schema/recipe` | `name`, `image`, `recipeIngredient`, `recipeInstructions` | `prepTime`, `cookTime`, `totalTime`, `recipeYield`, `nutrition` | **ELIGIBLE** |
| **JobPosting** | `src/Schema/Types/JobPostingSchema.php` | `JobPosting` | `{#url}#/schema/job` | `title`, `description`, `datePosted`, `hiringOrganization`, `jobLocation` | `baseSalary`, `employmentType`, `validThrough`, `directApply` | **ELIGIBLE** |
| **Course** | `src/Schema/Types/CourseSchema.php` | `Course` | `{#url}#/schema/course` | `name`, `description`, `provider` | `courseCode`, `hasCourseInstance`, `educationalCredentialAwarded` | **ELIGIBLE** |
| **Event** | `src/Schema/Types/EventSchema.php` | `Event` | `{#url}#/schema/event` | `name`, `startDate`, `location` (Place/VirtualLocation) | `endDate`, `description`, `offers`, `eventAttendanceMode`, `organizer` | **ELIGIBLE** |
| **SoftwareApp** | `src/Schema/Types/SoftwareApplicationSchema.php`| `SoftwareApplication` | `{#url}#/schema/software` | `name`, `operatingSystem`, `applicationCategory` | `offers`, `aggregateRating`, `screenshot`, `softwareVersion` | **ELIGIBLE** |
| **VideoObject** | `src/Schema/Media/VideoObjectSchema.php` | `VideoObject` | `{#url}#/schema/video` | `name`, `description`, `thumbnailUrl`, `uploadDate` | `contentUrl`, `embedUrl`, `duration`, `expires`, `hasPart` | **ELIGIBLE** |

---

## 2. Knowledge Graph Compilation Engine (`SchemaGraphBuilder.php`)

- **Graph Structure**: Wraps all active schema entities in a single unified `{"@context": "https://schema.org", "@graph": [...]}` JSON-LD block.
- **Node Interlinking**:
  - `WebSite` references `Organization` as `publisher`.
  - `WebPage` references `WebSite` as `isPartOf` and `Organization` as `about`.
  - `Article` / `Product` / `Event` references `WebPage` as `mainEntityOfPage` and `Organization` / `Person` as `author`/`publisher`.
- **Validation**: Schema arrays are validated by `SchemaValidator::validate()` prior to inclusion into the `@graph`.
- **Frontend Integration**: Hooked into `wp_head` via `add_action('wp_head', [$this, 'outputJsonLd'], 20)`.
