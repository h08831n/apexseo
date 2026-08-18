# APEX SEO — ZERO-TRUST SCHEMA SUBSYSTEM FORENSIC AUDIT REPORT

> **AUDIT BASELINE**: Physical inspection of `src/Schema/` directory, `SchemaRegistry.php`, `SchemaGraphBuilder.php`, `SchemaValidator.php`, and 12 individual Schema Type classes.  
> **STANDARD**: Schema.org JSON-LD Specification & Google Search Central Rich Results Requirements  

---

## 1. Schema Type Implementation & Validation Matrix

| Schema Type | PHP Source Class | Schema.org `@type` | `@id` Node Resolution | Required Properties Handled | Optional Properties Handled | Rich Results Compliance |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **Article** | `src/Schema/Types/ArticleSchema.php` | `Article`, `NewsArticle`, `BlogPosting` | `{#url}#/schema/article` | `headline, image, datePublished, dateModified, author, publisher` | `description, mainEntityOfPage, articleSection, wordCount` | COMPLIANT |
| **WebSite** | `src/Schema/Types/WebSiteSchema.php` | `WebSite` | `{#home_url}#/schema/website` | `name, url, potentialAction (SearchAction)` | `description, inLanguage, publisher` | COMPLIANT |
| **Organization**| `src/Schema/Types/OrganizationSchema.php` | `Organization` | `{#home_url}#/schema/organization` | `name, url, logo` | `sameAs, contactPoint, foundingDate, legalName` | COMPLIANT |
| **LocalBusiness**| `src/Schema/Types/LocalBusinessSchema.php`| `LocalBusiness`, `Store`, `Restaurant` | `{#home_url}#/schema/localbusiness` | `name, address (PostalAddress), telephone` | `geo (GeoCoordinates), openingHoursSpecification, priceRange` | COMPLIANT |
| **Product** | `src/Schema/Types/ProductSchema.php` | `Product` | `{#url}#/schema/product` | `name, image, offers (Offer/AggregateOffer)` | `description, sku, gtin, brand, aggregateRating, review` | COMPLIANT |
| **FAQPage** | `src/Schema/Types/FAQPageSchema.php` | `FAQPage` | `{#url}#/schema/faq` | `mainEntity (Question -> acceptedAnswer -> Answer)` | `name, description` | COMPLIANT |
| **Recipe** | `src/Schema/Types/RecipeSchema.php` | `Recipe` | `{#url}#/schema/recipe` | `name, image, recipeIngredient, recipeInstructions` | `prepTime, cookTime, totalTime, recipeYield, nutrition` | COMPLIANT |
| **JobPosting** | `src/Schema/Types/JobPostingSchema.php` | `JobPosting` | `{#url}#/schema/job` | `title, description, datePosted, hiringOrganization, jobLocation` | `baseSalary, employmentType, validThrough, directApply` | COMPLIANT |
| **Course** | `src/Schema/Types/CourseSchema.php` | `Course` | `{#url}#/schema/course` | `name, description, provider` | `courseCode, hasCourseInstance, educationalCredentialAwarded`| COMPLIANT |
| **Event** | `src/Schema/Types/EventSchema.php` | `Event` | `{#url}#/schema/event` | `name, startDate, location (Place/VirtualLocation)` | `endDate, description, offers, eventAttendanceMode, organizer` | COMPLIANT |
| **SoftwareApp** | `src/Schema/Types/SoftwareApplicationSchema.php`| `SoftwareApplication` | `{#url}#/schema/software` | `name, operatingSystem, applicationCategory` | `offers, aggregateRating, screenshot, softwareVersion` | COMPLIANT |
| **VideoObject** | `src/Schema/Media/VideoObjectSchema.php` | `VideoObject` | `{#url}#/schema/video` | `name, description, thumbnailUrl, uploadDate` | `contentUrl, embedUrl, duration, expires, hasPart` | COMPLIANT |

---

## 2. Knowledge Graph Compilation Engine (`SchemaGraphBuilder.php`)
- **Graph Structure**: Wraps all active schema entities in a single unified `{"@context": "https://schema.org", "@graph": [...]}` JSON-LD block.
- **Node Interlinking**: Interlinks nodes across the site hierarchy:
  - `WebSite` references `Organization` as `publisher`.
  - `WebPage` references `WebSite` as `isPartOf` and `Organization` as `about`.
  - `Article` / `Product` / `Event` references `WebPage` as `mainEntityOfPage` and `Organization` / `Person` as `author`/`publisher`.
- **Validation**: Every schema array is verified by `SchemaValidator::validate()` before inclusion into the graph.
- **WordPress Integration**: Emitted directly into frontend HTML output via `add_action('wp_head', [$this, 'outputJsonLd'], 20)`.
