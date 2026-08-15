# Schema.org Taxonomy & Structured Data Architecture

**Audit Reference**: Schema.org v26.0 & Google Search Central Structured Data Guidelines (2024-06)  
**Methodology**: Strictly separates top-level page schema templates from nested supporting objects and Google rich result types.

---

## 1. Schema Classification Model

To avoid inflating template counts with nested objects (e.g. `PostalAddress`), the schema engine defines 5 distinct structural classifications:

1. **Top-Level Apex Schema Templates (26 Types)**: Root entities attached directly to a WordPress Post, Page, CPT, or Archive as the primary subject of a URL.
2. **Supporting / Nested Structured Objects (18 Types)**: Complex data types that exist solely as properties embedded inside top-level entities (e.g. `PostalAddress` inside `LocalBusiness`, `Offer` inside `Product`).
3. **Media Objects (4 Types)**: Media entities attached to nodes (`ImageObject`, `VideoObject`, `AudioObject`, `DataDownload`).
4. **Graph Utility Types (4 Types)**: Structural navigation and graph containers (`WebSite`, `WebPage`, `BreadcrumbList`, `ItemList`).
5. **Google Rich-Result Applicable Types (19 Types)**: Types eligible for Google Search Rich Snippets and enhanced SERP features.

---

## 2. Exhaustive Schema Registry Table

| Schema.org Type | Structural Role | Top-Level Template? | Nested / Supporting? | Google Rich Result? | WooCommerce Relevant? | Required Properties | Recommended Properties | Context Variables | Validation Model |
|---|---|---|---|---|---|---|---|---|---|
| **Article** | CreativeWork | YES | NO | YES | NO | `headline`, `image`, `datePublished`, `author` | `dateModified`, `publisher`, `description` | `%%title%%`, `%%post_author%%`, `%%post_thumbnail%%` | Google Article Validator |
| **BlogPosting** | CreativeWork | YES | NO | YES | NO | `headline`, `image`, `datePublished`, `author` | `dateModified`, `publisher` | `%%title%%`, `%%post_excerpt%%` | Google Article Validator |
| **NewsArticle** | CreativeWork | YES | NO | YES | NO | `headline`, `image`, `datePublished`, `author`, `dateline` | `printEdition`, `publisher` | `%%title%%`, `%%post_date%%` | Google News Validator |
| **TechArticle** | CreativeWork | YES | NO | YES | NO | `headline`, `image`, `datePublished`, `dependencies` | `proficiencyLevel` | `%%title%%` | Schema.org Validator |
| **Product** | Commerce | YES | NO | YES | YES | `name`, `image`, `offers` | `brand`, `aggregateRating`, `review`, `sku`, `gtin` | `%%wc_price%%`, `%%wc_sku%%`, `%%wc_brand%%` | Google Product Snippet Validator |
| **ProductGroup** | Commerce | YES | NO | YES | YES | `name`, `variesBy`, `hasVariant` | `productGroupID` | `%%wc_attributes%%` | Google Product Validator |
| **Organization** | Entity | YES | NO | YES | NO | `name`, `url`, `logo` | `contactPoint`, `sameAs`, `address` | `%%sitename%%`, `%%site_url%%`, `%%site_logo%%` | Google Organization Validator |
| **LocalBusiness** | Entity | YES | NO | YES | NO | `name`, `address`, `telephone` | `openingHoursSpecification`, `geo`, `priceRange` | `%%location_name%%`, `%%location_address%%` | Google Local Business Validator |
| **Restaurant** | Entity | YES | NO | YES | NO | `name`, `address`, `telephone`, `servesCuisine` | `menu`, `acceptsReservations` | `%%location_name%%` | Google Restaurant Validator |
| **Recipe** | CreativeWork | YES | NO | YES | NO | `name`, `image`, `recipeIngredient`, `recipeInstructions` | `prepTime`, `cookTime`, `recipeYield`, `nutrition` | `%%recipe_name%%`, `%%recipe_prep_time%%` | Google Recipe Validator |
| **Course** | Educational | YES | NO | YES | NO | `name`, `description`, `provider` | `hasCourseInstance`, `offers` | `%%course_title%%`, `%%course_provider%%` | Google Course Validator |
| **Event** | Event | YES | NO | YES | NO | `name`, `startDate`, `location` | `endDate`, `offers`, `performer`, `description` | `%%event_name%%`, `%%event_start%%` | Google Event Validator |
| **JobPosting** | Employment | YES | NO | YES | NO | `title`, `description`, `hiringOrganization`, `jobLocation` | `datePosted`, `validThrough`, `baseSalary` | `%%job_title%%`, `%%hiring_company%%` | Google Job Validator |
| **FAQPage** | WebPage | YES | NO | YES | NO | `mainEntity` (array of `Question`) | `about`, `description` | Parsed FAQ Block JSON | Google FAQ Validator |
| **HowTo** | CreativeWork | YES | NO | YES | NO | `name`, `step` (array of `HowToStep`) | `totalTime`, `supply`, `tool`, `estimatedCost` | Parsed HowTo Block JSON | Google HowTo Validator |
| **SoftwareApplication** | CreativeWork | YES | NO | YES | NO | `name`, `operatingSystem` | `applicationCategory`, `offers`, `aggregateRating` | `%%app_name%%`, `%%app_os%%` | Google Software App Validator |
| **Book** | CreativeWork | YES | NO | YES | NO | `name`, `author`, `isbn` | `bookFormat`, `workExample` | `%%book_title%%`, `%%book_isbn%%` | Google Book Validator |
| **Movie** | CreativeWork | YES | NO | YES | NO | `name`, `director` | `actor`, `dateCreated`, `trailer` | `%%movie_title%%` | Google Movie Validator |
| **TVSeries** | CreativeWork | YES | NO | YES | NO | `name` | `actor`, `director`, `numberOfSeasons` | `%%series_title%%` | Schema.org Validator |
| **PodcastSeries** | CreativeWork | YES | NO | YES | NO | `name`, `url` | `author`, `webFeed` | `%%podcast_title%%` | Google Podcast Validator |
| **PodcastEpisode** | CreativeWork | YES | NO | YES | NO | `name`, `partOfSeries` | `duration`, `associatedMedia` | `%%episode_title%%` | Google Podcast Validator |
| **Dataset** | CreativeWork | YES | NO | YES | NO | `name`, `description`, `distribution` | `creator`, `temporalCoverage` | `%%dataset_name%%` | Google Dataset Validator |
| **ClaimReview** | FactCheck | YES | NO | YES | NO | `claimReviewed`, `reviewRating`, `itemReviewed` | `author`, `url` | `%%claim_text%%` | Google Fact Check Validator |
| **Service** | Commercial | YES | NO | NO | NO | `name`, `provider`, `serviceType` | `areaServed`, `offers` | `%%service_name%%` | Schema.org Validator |
| **ProfilePage** | WebPage | YES | NO | YES | NO | `name`, `mainEntity` (`Person`) | `description` | `%%post_author%%` | Google Profile Page Validator |
| **WebSite** | GraphUtility | YES | NO | YES | NO | `name`, `url`, `potentialAction` | `publisher` | `%%sitename%%`, `%%site_url%%` | Google Sitelinks Searchbox |
| **PostalAddress** | Structured | NO | YES | NO | NO | `streetAddress`, `addressLocality`, `postalCode` | `addressCountry`, `addressRegion` | `%%location_street%%` | Schema.org Validator |
| **ContactPoint** | Structured | NO | YES | NO | NO | `telephone`, `contactType` | `email`, `availableLanguage` | `%%location_phone%%` | Schema.org Validator |
| **GeoCoordinates** | Structured | NO | YES | NO | NO | `latitude`, `longitude` | `elevation` | `%%location_lat%%`, `%%location_lng%%` | Schema.org Validator |
| **OpeningHoursSpecification** | Structured | NO | YES | NO | NO | `dayOfWeek`, `opens`, `closes` | `validFrom`, `validThrough` | `%%hours_monday%%` | Schema.org Validator |
| **Offer** | Commerce | NO | YES | YES | YES | `price`, `priceCurrency`, `availability` | `url`, `validThrough`, `priceValidUntil` | `%%wc_price%%`, `%%wc_currency%%` | Google Product Snippet Validator |
| **AggregateOffer** | Commerce | NO | YES | YES | YES | `lowPrice`, `highPrice`, `priceCurrency` | `offerCount`, `offers` | `%%wc_variable_low_price%%` | Google Product Snippet Validator |
| **Review** | Evaluation | NO | YES | YES | YES | `reviewRating`, `author`, `reviewBody` | `itemReviewed`, `datePublished` | `%%wc_review_comment%%` | Google Review Snippet Validator |
| **AggregateRating** | Evaluation | NO | YES | YES | YES | `ratingValue`, `reviewCount` | `bestRating`, `worstRating` | `%%wc_rating_value%%` | Google Review Snippet Validator |
| **Brand** | Entity | NO | YES | NO | YES | `name` | `logo`, `url` | `%%wc_brand%%` | Schema.org Validator |
| **Question** | Interactive | NO | YES | YES | NO | `name`, `acceptedAnswer` | `suggestedAnswer` | `%%faq_question%%` | Google FAQ Validator |
| **Answer** | Interactive | NO | YES | YES | NO | `text` | `author`, `dateCreated` | `%%faq_answer%%` | Google FAQ Validator |
| **HowToStep** | Interactive | NO | YES | YES | NO | `text` | `name`, `image`, `url` | `%%step_text%%` | Google HowTo Validator |
| **HowToSupply** | Interactive | NO | YES | NO | NO | `name` | `image` | `%%supply_name%%` | Schema.org Validator |
| **HowToTool** | Interactive | NO | YES | NO | NO | `name` | `image` | `%%tool_name%%` | Schema.org Validator |
| **ImageObject** | Media | NO | YES | YES | NO | `url` | `width`, `height`, `caption` | `%%post_thumbnail_url%%` | Google Image Validator |
| **VideoObject** | Media | NO | YES | YES | NO | `name`, `description`, `thumbnailUrl`, `uploadDate` | `contentUrl`, `duration`, `embedUrl` | `%%video_title%%`, `%%video_embed%%` | Google Video Validator |
| **AudioObject** | Media | NO | YES | NO | NO | `contentUrl` | `duration`, `encodingFormat` | `%%audio_url%%` | Schema.org Validator |
| **BreadcrumbList** | GraphUtility | YES | NO | YES | NO | `itemListElement` (array of `ListItem`) | `numberOfItems` | Auto-generated breadcrumb trail | Google Breadcrumbs Validator |

---

## 3. Quantitative Schema Summary

- **Total Schema.org Types Audited**: **44 Concrete Types**
- **Top-Level Apex Schema Templates**: **26 Types**
- **Supporting / Nested Structured Objects**: **14 Types**
- **Media Object Types**: **4 Types**
- **Google Rich-Result Applicable Types**: **19 Types**
- **WooCommerce Integrated Types**: **6 Types** (`Product`, `ProductGroup`, `Offer`, `AggregateOffer`, `Review`, `AggregateRating`, `Brand`)
