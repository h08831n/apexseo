<?php
namespace ApexSEO\Schema;

use ApexSEO\Schema\Types\SchemaTypeInterface;
use ApexSEO\Schema\Types\ArticleSchema;
use ApexSEO\Schema\Types\WebSiteSchema;
use ApexSEO\Schema\Types\OrganizationSchema;
use ApexSEO\Schema\Types\LocalBusinessSchema;
use ApexSEO\Schema\Types\ProductSchema;
use ApexSEO\Schema\Types\RecipeSchema;
use ApexSEO\Schema\Types\FAQPageSchema;
use ApexSEO\Schema\Types\JobPostingSchema;
use ApexSEO\Schema\Types\EventSchema;
use ApexSEO\Schema\Types\CourseSchema;
use ApexSEO\Schema\Types\SoftwareApplicationSchema;
use ApexSEO\Schema\Media\VideoObjectSchema;

class SchemaRegistry {
    private $types = [];

    public function __construct() {
        $this->register(new ArticleSchema());
        $this->register(new WebSiteSchema());
        $this->register(new OrganizationSchema());
        $this->register(new LocalBusinessSchema());
        $this->register(new ProductSchema());
        $this->register(new RecipeSchema());
        $this->register(new FAQPageSchema());
        $this->register(new JobPostingSchema());
        $this->register(new EventSchema());
        $this->register(new CourseSchema());
        $this->register(new SoftwareApplicationSchema());
        $this->register(new VideoObjectSchema());
    }

    public function register(SchemaTypeInterface $type): void {
        $this->types[$type->getType()] = $type;
    }

    public function get(string $typeName): ?SchemaTypeInterface {
        return $this->types[$typeName] ?? null;
    }

    public function getRegisteredTypes(): array {
        return array_keys($this->types);
    }
}
