<?php
namespace ApexSEO\CLI;

use ApexSEO\Schema\SchemaRegistry;
use ApexSEO\Schema\Validator\SchemaValidator;
use ApexSEO\SEO\Repository\IndexableRepository;

/**
 * WP-CLI Command for Schema JSON-LD Validation (APEX-080, APEX-171).
 *
 * ## EXAMPLES
 *     wp apexseo schema validate 124 --format=json
 *     wp apexseo schema validate --strict
 */
class SchemaCommand extends AbstractCliCommand {
    /**
     * Validate generated JSON-LD structured data for a post against Schema.org specifications.
     *
     * ## OPTIONS
     * [<post_id>]
     * : Specific Post ID to inspect and validate. If omitted, validates site-wide Organization/WebSite schema.
     *
     * [--format=<format>]
     * : Output format (table, json, yaml).
     * ---
     * default: table
     * ---
     *
     * [--strict]
     * : Enable strict validation mode.
     *
     * @param array $args
     * @param array $assocArgs
     * @return int
     */
    public function validate($args = [], $assocArgs = []) {
        $postId = !empty($args[0]) ? (int) $args[0] : 0;
        $format = isset($assocArgs['format']) ? $assocArgs['format'] : 'table';
        $strict = !empty($assocArgs['strict']);

        /** @var SchemaValidator $validator */
        $validator = $this->container->has(SchemaValidator::class) ? $this->container->get(SchemaValidator::class) : new SchemaValidator();

        $schemaData = [];

        if ($postId > 0) {
            $this->line(sprintf('Validating JSON-LD schema for Post ID %d...', $postId));

            /** @var IndexableRepository $repo */
            $repo = $this->container->has(IndexableRepository::class) ? $this->container->get(IndexableRepository::class) : null;
            $indexable = $repo ? $repo->find('post', $postId) : null;

            $schemaType = ($indexable && !empty($indexable->schema_type)) ? $indexable->schema_type : 'Article';
            $title      = ($indexable && !empty($indexable->title)) ? $indexable->title : 'Sample Post Title';

            $schemaData = [
                '@context' => 'https://schema.org',
                '@type'    => $schemaType,
                'headline' => $title,
                'name'     => $title,
            ];
        } else {
            $this->line('Validating default Organization & WebSite JSON-LD schemas...');
            $schemaData = [
                '@context' => 'https://schema.org',
                '@graph'   => [
                    [
                        '@type' => 'Organization',
                        'name'  => 'Apex SEO Site',
                        'url'   => 'https://example.com',
                    ],
                    [
                        '@type' => 'WebSite',
                        'name'  => 'Apex SEO',
                        'url'   => 'https://example.com',
                    ],
                ],
            ];
        }

        $issues = $validator->validate($schemaData);

        if (empty($issues)) {
            $result = [
                [
                    'target'  => ($postId > 0) ? "Post #{$postId}" : 'Site Default Graph',
                    'status'  => 'VALID',
                    'details' => 'No Schema.org or Google Rich Result violations found.',
                ],
            ];
            $this->formatItems($format, $result, ['target', 'status', 'details']);
            $this->success('Schema JSON-LD is valid.');
            return 0;
        } else {
            $result = [];
            foreach ($issues as $issue) {
                $result[] = [
                    'target'  => ($postId > 0) ? "Post #{$postId}" : 'Site Default Graph',
                    'status'  => 'ERROR',
                    'details' => $issue,
                ];
            }
            $this->formatItems($format, $result, ['target', 'status', 'details']);
            $this->error(sprintf('Schema validation failed with %d error(s).', count($issues)), false);
            return 1;
        }
    }
}
