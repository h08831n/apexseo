<?php
namespace ApexSEO\Schema;

use ApexSEO\Core\Contracts\ServiceContractInterface;
use ApexSEO\Schema\Types\SchemaTypeInterface;
use ApexSEO\Schema\Types\ArticleSchema;
use ApexSEO\Schema\Types\ProductSchema;
use ApexSEO\Schema\Types\FAQPageSchema;
use ApexSEO\Schema\Types\LocalBusinessSchema;
use ApexSEO\Schema\Types\OrganizationSchema;
use ApexSEO\Schema\Types\WebSiteSchema;
use ApexSEO\Schema\Types\RecipeSchema;
use ApexSEO\Schema\Types\JobPostingSchema;
use ApexSEO\Schema\Types\CourseSchema;
use ApexSEO\Schema\Types\EventSchema;
use ApexSEO\Schema\Types\SoftwareApplicationSchema;
use ApexSEO\Schema\Media\VideoObjectSchema;

/**
 * Schema Types Registry & Provider.
 */
class SchemaRegistry implements ServiceContractInterface {
    /**
     * Registered schema generators.
     *
     * @var array<string, SchemaTypeInterface>
     */
    protected $types = [];

    /**
     * Constructor.
     */
    public function __construct() {
        $this->registerDefaultTypes();
    }

    /**
     * Register core supported schema type generators.
     */
    protected function registerDefaultTypes() {
        $this->register(new ArticleSchema('Article'));
        $this->register(new ArticleSchema('BlogPosting'));
        $this->register(new ArticleSchema('NewsArticle'));
        $this->register(new ProductSchema());
        $this->register(new FAQPageSchema());
        $this->register(new LocalBusinessSchema('LocalBusiness'));
        $this->register(new LocalBusinessSchema('Restaurant'));
        $this->register(new OrganizationSchema());
        $this->register(new WebSiteSchema());
        $this->register(new RecipeSchema());
        $this->register(new JobPostingSchema());
        $this->register(new CourseSchema());
        $this->register(new EventSchema());
        $this->register(new SoftwareApplicationSchema());
        $this->register(new VideoObjectSchema());
    }

    /**
     * Register a custom schema type generator.
     *
     * @param SchemaTypeInterface $type
     * @return self
     */
    public function register(SchemaTypeInterface $type) {
        $this->types[$type->getType()] = $type;
        return $this;
    }

    /**
     * Get generator for a type name.
     *
     * @param string $typeName
     * @return SchemaTypeInterface|null
     */
    public function getType($typeName) {
        return isset($this->types[$typeName]) ? $this->types[$typeName] : null;
    }

    /**
     * Get all registered schema generators.
     *
     * @return array<string, SchemaTypeInterface>
     */
    public function getAllTypes() {
        return $this->types;
    }
}
