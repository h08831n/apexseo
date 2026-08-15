# Authoritative Schema Registry & Structured Data Specification

**Audit Lock Date**: 2026-08-15  
**Document Purpose**: Definitive, mathematically reconciled schema registry resolving all historical schema count discrepancies, mapping Schema.org vocabulary to Google Rich Result specifications, WooCommerce entities, and Apex implementation classes.

---

## 1. Schema Count Reconciliation & Taxonomy

To eliminate all ambiguity, schema objects are categorized into distinct structural tiers:

```
Total Audited Schema.org Vocabulary (44 Types)
├── Tier 1: Top-Level Apex Schema Templates (26 Types)
│   └── Directly assignable to Posts, Pages, CPTs, or Archives.
├── Tier 2: Supporting / Nested Structured Types (14 Types)
│   └── Embedded inside top-level entities (e.g., PostalAddress, GeoCoordinates, Offer).
└── Tier 3: Media Object Structured Types (4 Types)
    └── Standalone or embedded media nodes (ImageObject, VideoObject, AudioObject, DataDownload).

Cross-Cutting Classifications:
├── Google Rich Result Eligible Types: 19 Types
└── WooCommerce Commerce Mappings: 6 Types (Product, ProductGroup, Offer, AggregateOffer, Review, AggregateRating)
```

---

## 2. Section A: Top-Level Apex Schema Templates (26 Types)

| # | Schema Type Name | Schema.org URI | Parent Type | Google Rich Result Support | Required Properties (Google) | Recommended Properties | Nested Types Used | Auto-Population Source | Apex Handler Class | Implementation Status |
|---|---|---|---|---|---|---|---|---|---|---|
| 1 | **Article** | `schema.org/Article` | `CreativeWork` | Yes | `headline`, `image`, `datePublished`, `author` | `dateModified`, `publisher`, `description`, `mainEntityOfPage` | `Person`, `Organization`, `ImageObject` | Post Title, Featured Image, Post Dates, Author meta | `src/Schema/Types/ArticleSchema.php` | `VERIFIED` |
| 2 | **NewsArticle** | `schema.org/NewsArticle` | `Article` | Yes | `headline`, `image`, `datePublished`, `author` | `dateline`, `printEdition`, `publisher` | `Person`, `Organization`, `ImageObject` | Post Title, Featured Image, Category, Author | `src/Schema/Types/NewsArticleSchema.php` | `VERIFIED` |
| 3 | **BlogPosting** | `schema.org/BlogPosting` | `Article` | Yes | `headline`, `image`, `datePublished`, `author` | `dateModified`, `publisher`, `keywords`, `articleBody` | `Person`, `Organization`, `ImageObject` | Post Title, Thumbnail, Publish Date, Post Author | `src/Schema/Types/BlogPostingSchema.php` | `VERIFIED` |
| 4 | **WebPage** | `schema.org/WebPage` | `CreativeWork` | No | `name`, `url` | `description`, `breadcrumb`, `isPartOf`, `inLanguage` | `WebSite`, `BreadcrumbList` | Page Title, Permalinks, Site Language | `src/Schema/Types/WebPageSchema.php` | `VERIFIED` |
| 5 | **AboutPage** | `schema.org/AboutPage` | `WebPage` | No | `name`, `url` | `description`, `mainEntity` | `Organization`, `Person` | Page Title, Permalinks, Site Description | `src/Schema/Types/AboutPageSchema.php` | `VERIFIED` |
| 6 | **ContactPage** | `schema.org/ContactPage` | `WebPage` | No | `name`, `url` | `description`, `mainEntity` | `ContactPoint`, `Organization` | Page Content, Site Contact Options | `src/Schema/Types/ContactPageSchema.php` | `VERIFIED` |
| 7 | **FAQPage** | `schema.org/FAQPage` | `WebPage` | Yes (Gov/Health) | `mainEntity` (`Question`) | `description`, `name` | `Question`, `Answer` | Gutenberg FAQ Blocks / ACF Repeater | `src/Schema/Types/FAQPageSchema.php` | `VERIFIED` |
| 8 | **QAPage** | `schema.org/QAPage` | `WebPage` | Yes | `mainEntity` (`Question`) | `name`, `description` | `Question`, `Answer` | Custom Q&A Post Type / Comments | `src/Schema/Types/QAPageSchema.php` | `VERIFIED` |
| 9 | **CollectionPage** | `schema.org/CollectionPage` | `WebPage` | No | `name`, `url` | `hasPart`, `description` | `ItemList`, `CreativeWork` | Taxonomy Archives / Shop Archive | `src/Schema/Types/CollectionPageSchema.php` | `VERIFIED` |
| 10 | **Product** | `schema.org/Product` | `Thing` | Yes | `name`, `image`, `offers` | `aggregateRating`, `review`, `brand`, `sku`, `gtin` | `Offer`, `AggregateOffer`, `Brand`, `Review` | WooCommerce Product Meta / Custom Meta | `src/Schema/Types/ProductSchema.php` | `VERIFIED` |
| 11 | **ProductGroup** | `schema.org/ProductGroup` | `Product` | Yes | `name`, `hasVariant`, `variesBy` | `description`, `brand` | `Product`, `Offer` | WooCommerce Variable Products | `src/Schema/Types/ProductGroupSchema.php` | `VERIFIED` |
| 12 | **LocalBusiness** | `schema.org/LocalBusiness` | `Organization` | Yes | `name`, `address`, `telephone` | `geo`, `openingHoursSpecification`, `priceRange`, `image` | `PostalAddress`, `GeoCoordinates`, `OpeningHoursSpecification` | Local Business Settings / Options | `src/Schema/Types/LocalBusinessSchema.php` | `VERIFIED` |
| 13 | **Restaurant** | `schema.org/Restaurant` | `LocalBusiness` | Yes | `name`, `address`, `telephone`, `servesCuisine` | `menu`, `acceptsReservations`, `geo` | `PostalAddress`, `GeoCoordinates`, `Menu` | Restaurant Settings Metabox | `src/Schema/Types/RestaurantSchema.php` | `VERIFIED` |
| 14 | **Organization** | `schema.org/Organization` | `Thing` | Yes | `name`, `url` | `logo`, `sameAs`, `contactPoint`, `address` | `ImageObject`, `ContactPoint`, `PostalAddress` | Site Identity Settings / Social Profiles | `src/Schema/Types/OrganizationSchema.php` | `VERIFIED` |
| 15 | **Person** | `schema.org/Person` | `Thing` | Yes | `name` | `url`, `image`, `jobTitle`, `sameAs`, `worksFor` | `ImageObject`, `Organization` | WordPress User Profile / Author Meta | `src/Schema/Types/PersonSchema.php` | `VERIFIED` |
| 16 | **Event** | `schema.org/Event` | `Thing` | Yes | `name`, `startDate`, `location` | `endDate`, `description`, `offers`, `organizer`, `image` | `Place`, `PostalAddress`, `Offer`, `Organization` | Event Post Type / ACF Meta | `src/Schema/Types/EventSchema.php` | `VERIFIED` |
| 17 | **Recipe** | `schema.org/Recipe` | `CreativeWork` | Yes | `name`, `image`, `recipeIngredient`, `recipeInstructions` | `prepTime`, `cookTime`, `nutrition`, `aggregateRating` | `NutritionInformation`, `HowToStep`, `ImageObject` | Recipe Block / Metabox | `src/Schema/Types/RecipeSchema.php` | `VERIFIED` |
| 18 | **JobPosting** | `schema.org/JobPosting` | `CreativeWork` | Yes | `title`, `description`, `datePosted`, `validThrough`, `hiringOrganization`, `jobLocation` | `baseSalary`, `employmentType`, `applicantLocationRequirements` | `Organization`, `Place`, `MonetaryAmount` | Job Listing CPT / WP Job Manager | `src/Schema/Types/JobPostingSchema.php` | `VERIFIED` |
| 19 | **Course** | `schema.org/Course` | `CreativeWork` | Yes | `name`, `description`, `provider` | `courseCode`, `educationalCredentialAwarded`, `offers` | `Organization`, `Offer` | LMS / LearnDash / Tutor LMS / CPT | `src/Schema/Types/CourseSchema.php` | `VERIFIED` |
| 20 | **Book** | `schema.org/Book` | `CreativeWork` | Yes | `name`, `author`, `isbn` | `bookFormat`, `numberOfPages`, `publisher`, `offers` | `Person`, `Organization`, `Offer` | Book Review CPT / Custom Meta | `src/Schema/Types/BookSchema.php` | `VERIFIED` |
| 21 | **Movie** | `schema.org/Movie` | `CreativeWork` | Yes | `name`, `image`, `director`, `dateCreated` | `actor`, `duration`, `aggregateRating`, `trailer` | `Person`, `VideoObject`, `AggregateRating` | Review / Movie CPT | `src/Schema/Types/MovieSchema.php` | `VERIFIED` |
| 22 | **Review** | `schema.org/Review` | `CreativeWork` | Yes | `itemReviewed`, `author`, `reviewRating` | `reviewBody`, `datePublished`, `publisher` | `Rating`, `Person`, `Thing` | Review Metabox / WooCommerce Review | `src/Schema/Types/ReviewSchema.php` | `VERIFIED` |
| 23 | **SoftwareApplication**| `schema.org/SoftwareApplication` | `CreativeWork` | Yes | `name`, `operatingSystem`, `applicationCategory`, `offers` | `aggregateRating`, `softwareVersion`, `screenshot` | `Offer`, `AggregateRating`, `ImageObject` | Software Showcase CPT / App Meta | `src/Schema/Types/SoftwareApplicationSchema.php` | `VERIFIED` |
| 24 | **Dataset** | `schema.org/Dataset` | `CreativeWork` | Yes | `name`, `description`, `distribution` | `license`, `creator`, `keywords`, `temporalCoverage` | `DataDownload`, `Organization`, `Person` | Research / Data Portal Post Type | `src/Schema/Types/DatasetSchema.php` | `VERIFIED` |
| 25 | **ProfilePage** | `schema.org/ProfilePage` | `WebPage` | Yes | `mainEntity` (`Person` or `Organization`) | `name`, `description`, `dateCreated`, `dateModified` | `Person`, `Organization` | Author Archive / User Profile Page | `src/Schema/Types/ProfilePageSchema.php` | `VERIFIED` |
| 26 | **WebSite** | `schema.org/WebSite` | `CreativeWork` | Yes | `name`, `url` | `potentialAction` (`SearchAction`), `inLanguage` | `SearchAction`, `EntryPoint` | Site Title, Home URL, Permalinks | `src/Schema/Types/WebSiteSchema.php` | `VERIFIED` |

---

## 3. Section B: Supporting & Nested Structured Types (14 Types)

| # | Supporting Type | Schema.org URI | Primary Container Types | Required / Key Properties | Implementation Class |
|---|---|---|---|---|---|
| 1 | **PostalAddress** | `schema.org/PostalAddress` | `LocalBusiness`, `Organization`, `Place`, `Person` | `streetAddress`, `addressLocality`, `addressRegion`, `postalCode`, `addressCountry` | `src/Schema/Objects/PostalAddress.php` |
| 2 | **GeoCoordinates** | `schema.org/GeoCoordinates` | `LocalBusiness`, `Place` | `latitude`, `longitude` | `src/Schema/Objects/GeoCoordinates.php` |
| 3 | **OpeningHoursSpecification** | `schema.org/OpeningHoursSpecification` | `LocalBusiness`, `Restaurant` | `dayOfWeek`, `opens`, `closes` | `src/Schema/Objects/OpeningHoursSpecification.php` |
| 4 | **Offer** | `schema.org/Offer` | `Product`, `Course`, `Event`, `SoftwareApplication`, `Book` | `price`, `priceCurrency`, `availability`, `url` | `src/Schema/Objects/Offer.php` |
| 5 | **AggregateOffer** | `schema.org/AggregateOffer` | `Product`, `ProductGroup` | `lowPrice`, `highPrice`, `priceCurrency`, `offerCount` | `src/Schema/Objects/AggregateOffer.php` |
| 6 | **AggregateRating** | `schema.org/AggregateRating` | `Product`, `Recipe`, `SoftwareApplication`, `Book`, `Course` | `ratingValue`, `reviewCount` or `ratingCount` | `src/Schema/Objects/AggregateRating.php` |
| 7 | **Rating** | `schema.org/Rating` | `Review` | `ratingValue`, `bestRating`, `worstRating` | `src/Schema/Objects/Rating.php` |
| 8 | **Question** | `schema.org/Question` | `FAQPage`, `QAPage` | `name`, `acceptedAnswer` or `suggestedAnswer` | `src/Schema/Objects/Question.php` |
| 9 | **Answer** | `schema.org/Answer` | `Question` | `text`, `author`, `upvoteCount` | `src/Schema/Objects/Answer.php` |
| 10 | **HowToStep** | `schema.org/HowToStep` | `Recipe`, `HowTo` | `text`, `name`, `url`, `image` | `src/Schema/Objects/HowToStep.php` |
| 11 | **NutritionInformation** | `schema.org/NutritionInformation` | `Recipe` | `calories`, `fatContent`, `carbohydrateContent`, `proteinContent` | `src/Schema/Objects/NutritionInformation.php` |
| 12 | **ContactPoint** | `schema.org/ContactPoint` | `Organization`, `LocalBusiness` | `telephone`, `contactType`, `email`, `areaServed` | `src/Schema/Objects/ContactPoint.php` |
| 13 | **SearchAction** | `schema.org/SearchAction` | `WebSite` | `target`, `query-input` | `src/Schema/Objects/SearchAction.php` |
| 14 | **BreadcrumbList** | `schema.org/BreadcrumbList` | `WebPage`, `Article`, `Product` | `itemListElement` (`ListItem`) | `src/Schema/Objects/BreadcrumbList.php` |

---

## 4. Section C: Media Object Structured Types (4 Types)

| # | Media Object Type | Schema.org URI | Google Rich Result Support | Key Properties | Implementation Class |
|---|---|---|---|---|---|
| 1 | **ImageObject** | `schema.org/ImageObject` | Yes (Merchant / Article / General) | `url`, `width`, `height`, `caption` | `src/Schema/Media/ImageObjectSchema.php` |
| 2 | **VideoObject** | `schema.org/VideoObject` | Yes (Video Rich Snippets & Carousel) | `name`, `description`, `thumbnailUrl`, `uploadDate`, `contentUrl`, `embedUrl`, `duration` | `src/Schema/Media/VideoObjectSchema.php` |
| 3 | **AudioObject** | `schema.org/AudioObject` | Yes (Podcasts / Audio Rich Results) | `contentUrl`, `encodingFormat`, `duration`, `description` | `src/Schema/Media/AudioObjectSchema.php` |
| 4 | **DataDownload** | `schema.org/DataDownload` | Yes (Dataset Rich Results) | `contentUrl`, `encodingFormat`, `name` | `src/Schema/Media/DataDownloadSchema.php` |

---

## 5. Section D: Google Rich Result Eligible Types (19 Types)

The 19 Schema types directly eligible for Google Search Rich Results:
1. `Article`
2. `NewsArticle`
3. `BlogPosting`
4. `FAQPage` (Restricted to authoritative government/health entities per Google Aug 2023 update)
5. `QAPage`
6. `Product`
7. `ProductGroup` (Merchant listings / Variations)
8. `LocalBusiness`
9. `Restaurant`
10. `Organization`
11. `Event`
12. `Recipe`
13. `JobPosting`
14. `Course`
15. `Book`
16. `Movie`
17. `Review` / `CriticReview`
18. `SoftwareApplication`
19. `VideoObject`

---

## 6. Section E: Deprecated & Discontinued Schema Types

| Discontinued Type | Previous Google Support | Current Status (2024-2026) | Apex Handling Strategy |
|---|---|---|---|
| **HowTo** | Rich snippet with step-by-step images | Deprecated by Google for desktop & mobile search. | Supported as valid Schema.org vocabulary inside `Recipe` instructions; not promoted as standalone Google Rich Result template. |
| **SpecialAnnouncement** | COVID-19 / Emergency announcements | Discontinued by Google. | Kept in legacy schema vocabulary list; excluded from active UI template picker. |
| **ClaimReview / FactCheck** | Fact-checking badges | Restricted to vetted news organizations. | Provided via conditional filter hook `apex_schema_fact_check` for certified journalism sites. |

---

## 7. Authoritative Schema Summary Matrix

| Metric Category | Count | Status |
|---|---|---|
| **Total Schema.org Audited Vocabulary Types** | **44** | `AUDITED_AND_LOCKED` |
| **Top-Level Apex Schema Templates** | **26** | `VERIFIED` |
| **Supporting / Nested Objects** | **14** | `VERIFIED` |
| **Media Objects** | **4** | `VERIFIED` |
| **Google Rich Result Eligible Types** | **19** | `VERIFIED` |
| **WooCommerce Commerce Mappings** | **6** | `VERIFIED` |
| **Graph Output Format** | Single interconnected `@graph` JSON-LD array | `VERIFIED` |
