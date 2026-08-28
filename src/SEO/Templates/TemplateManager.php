<?php
namespace ApexSEO\SEO\Templates;

use ApexSEO\Core\Configuration\ConfigurationManager;

class TemplateManager {
    private $config;

    public function __construct(ConfigurationManager $config) {
        $this->config = $config;
    }

    public function getTitleTemplate(string $type = 'post'): string {
        return $this->config->get("titles.{$type}_title_template", '%%title%% %%sep%% %%sitename%%');
    }

    public function getDescriptionTemplate(string $type = 'post'): string {
        return $this->config->get("titles.{$type}_desc_template", '%%excerpt%%');
    }
}
