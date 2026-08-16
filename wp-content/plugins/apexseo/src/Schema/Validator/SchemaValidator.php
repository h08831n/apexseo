<?php
namespace ApexSEO\Schema\Validator;

/**
 * Schema Validation & Linting Engine (APEX-080).
 * Validates generated JSON-LD structure against Schema.org and Google Rich Results guidelines.
 */
class SchemaValidator {
    /**
     * Validate a schema node or graph array.
     *
     * @param array $schema
     * @return array Array of validation errors/warnings. Empty array if valid.
     */
    public function validate(array $schema) {
        $issues = [];

        if (empty($schema)) {
            $issues[] = 'Schema object cannot be empty.';
            return $issues;
        }

        if (isset($schema['@graph']) && is_array($schema['@graph'])) {
            foreach ($schema['@graph'] as $node) {
                $nodeIssues = $this->validateNode($node);
                $issues = array_merge($issues, $nodeIssues);
            }
        } else {
            $issues = $this->validateNode($schema);
        }

        return $issues;
    }

    /**
     * Validate an individual schema entity node.
     *
     * @param array $node
     * @return array
     */
    protected function validateNode(array $node) {
        $issues = [];

        if (empty($node['@type'])) {
            $issues[] = 'Missing @type attribute in schema node.';
            return $issues;
        }

        $type = $node['@type'];

        switch ($type) {
            case 'Article':
            case 'NewsArticle':
            case 'BlogPosting':
                if (empty($node['headline']) && empty($node['name'])) {
                    $issues[] = "{$type} requires 'headline' or 'name'.";
                }
                break;

            case 'Product':
                if (empty($node['name'])) {
                    $issues[] = "Product requires 'name'.";
                }
                if (empty($node['offers'])) {
                    $issues[] = "Product requires 'offers'.";
                }
                break;

            case 'FAQPage':
                if (empty($node['mainEntity']) || !is_array($node['mainEntity'])) {
                    $issues[] = "FAQPage requires non-empty 'mainEntity' question list.";
                }
                break;

            case 'LocalBusiness':
            case 'Restaurant':
                if (empty($node['name'])) {
                    $issues[] = "{$type} requires 'name'.";
                }
                if (empty($node['address'])) {
                    $issues[] = "{$type} requires 'address'.";
                }
                break;

            case 'Recipe':
                if (empty($node['name'])) {
                    $issues[] = "Recipe requires 'name'.";
                }
                if (empty($node['recipeIngredient'])) {
                    $issues[] = "Recipe requires 'recipeIngredient'.";
                }
                if (empty($node['recipeInstructions'])) {
                    $issues[] = "Recipe requires 'recipeInstructions'.";
                }
                break;

            case 'JobPosting':
                if (empty($node['title'])) {
                    $issues[] = "JobPosting requires 'title'.";
                }
                if (empty($node['hiringOrganization'])) {
                    $issues[] = "JobPosting requires 'hiringOrganization'.";
                }
                if (empty($node['jobLocation'])) {
                    $issues[] = "JobPosting requires 'jobLocation'.";
                }
                break;

            case 'Course':
                if (empty($node['name'])) {
                    $issues[] = "Course requires 'name'.";
                }
                if (empty($node['provider'])) {
                    $issues[] = "Course requires 'provider'.";
                }
                break;

            case 'Event':
                if (empty($node['name'])) {
                    $issues[] = "Event requires 'name'.";
                }
                if (empty($node['startDate'])) {
                    $issues[] = "Event requires 'startDate'.";
                }
                if (empty($node['location'])) {
                    $issues[] = "Event requires 'location'.";
                }
                break;

            case 'SoftwareApplication':
                if (empty($node['name'])) {
                    $issues[] = "SoftwareApplication requires 'name'.";
                }
                if (empty($node['offers'])) {
                    $issues[] = "SoftwareApplication requires 'offers'.";
                }
                break;

            case 'VideoObject':
                if (empty($node['name'])) {
                    $issues[] = "VideoObject requires 'name'.";
                }
                if (empty($node['thumbnailUrl'])) {
                    $issues[] = "VideoObject requires 'thumbnailUrl'.";
                }
                if (empty($node['uploadDate'])) {
                    $issues[] = "VideoObject requires 'uploadDate'.";
                }
                break;
        }

        return $issues;
    }
}
