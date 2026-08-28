<?php
namespace ApexSEO\Schema;

use ApexSEO\Core\Contracts\ModuleInterface;

class SchemaModule implements ModuleInterface {
    private $graphBuilder;

    public function __construct(SchemaGraphBuilder $graphBuilder) {
        $this->graphBuilder = $graphBuilder;
    }

    public function getName(): string {
        return 'schema';
    }

    public function boot(): void {}

    public function registerHooks(): void {
        add_action('wp_head', [$this, 'renderSchemaGraph'], 20);
    }

    public function renderSchemaGraph(): void {
        $graph = $this->graphBuilder->buildGraph('Article', [
            'title' => get_the_title(),
            'sitename' => get_bloginfo('name'),
        ]);
        echo '<script type="application/ld+json">' . json_encode($graph, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>' . "
";
    }
}
