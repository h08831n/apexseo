<?php
namespace ApexSEO\Schema;

use ApexSEO\Core\Contracts\ServiceContractInterface;

/**
 * Unified JSON-LD Schema @graph Builder.
 */
class SchemaGraphBuilder implements ServiceContractInterface {
    /**
     * @var SchemaRegistry
     */
    protected $registry;

    /**
     * Constructor.
     *
     * @param SchemaRegistry $registry
     */
    public function __construct(SchemaRegistry $registry) {
        $this->registry = $registry;
    }

    /**
     * Build the interconnected JSON-LD @graph node collection for the current context.
     *
     * @param array $context
     * @return array
     */
    public function buildGraph(array $context = []) {
        $graph = [];
        $types = $this->registry->getAllTypes();

        foreach ($types as $typeObj) {
            if ($typeObj->isApplicable($context)) {
                $node = $typeObj->generate($context);
                if (!empty($node)) {
                    $graph[] = $node;
                }
            }
        }

        return [
            '@context' => 'https://schema.org',
            '@graph'   => $graph,
        ];
    }

    /**
     * Render the JSON-LD <script> block.
     *
     * @param array $context
     * @return string
     */
    public function renderScript(array $context = []) {
        $data = $this->buildGraph($context);
        if (empty($data['@graph'])) {
            return '';
        }

        $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        return sprintf("<script type=\"application/ld+json\" class=\"apex-schema-graph\">\n%s\n</script>\n", $json);
    }
}
