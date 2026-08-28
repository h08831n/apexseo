<?php
namespace ApexSEO\SEO\Variables;

class VariableEngine {
    private $variables = [];

    public function __construct() {
        $this->registerCoreVariables();
    }

    public function registerCoreVariables(): void {
        $this->registerVariable('title', function(array $context) {
            return $context['title'] ?? get_the_title() ?: '';
        });
        $this->registerVariable('sitename', function(array $context) {
            return $context['sitename'] ?? get_bloginfo('name') ?: 'Site';
        });
        $this->registerVariable('sitedesc', function(array $context) {
            return $context['sitedesc'] ?? get_bloginfo('description') ?: '';
        });
        $this->registerVariable('sep', function(array $context) {
            return $context['sep'] ?? '|';
        });
        $this->registerVariable('excerpt', function(array $context) {
            return $context['excerpt'] ?? '';
        });
        $this->registerVariable('author_name', function(array $context) {
            return $context['author_name'] ?? get_the_author() ?: '';
        });
        $this->registerVariable('category', function(array $context) {
            return $context['category'] ?? '';
        });
        $this->registerVariable('currentdate', function(array $context) {
            return date('Y-m-d');
        });
        $this->registerVariable('currentyear', function(array $context) {
            return date('Y');
        });
    }

    public function registerVariable(string $name, callable $callback): void {
        $this->variables[$name] = $callback;
    }

    public function replace(string $template, array $context = []): string {
        return preg_replace_callback('/%%([a-zA-Z0-9_-]+)%%/', function($matches) use ($context) {
            $var = $matches[1];
            if (isset($this->variables[$var])) {
                return (string) call_user_func($this->variables[$var], $context);
            }
            if (isset($context[$var])) {
                return (string) $context[$var];
            }
            return '';
        }, $template);
    }
}
