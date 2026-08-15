<?php
namespace ApexSEO\AI;

use ApexSEO\Core\Contracts\ModuleInterface;
use ApexSEO\Core\Container\ContainerInterface;
use ApexSEO\SEO\Variables\VariableEngine;
use ApexSEO\AI\LlmsTxt\LlmsTxtGenerator;
use ApexSEO\AI\SearchIntent\SearchIntentAnalyzer;
use ApexSEO\AI\Generators\MetadataAiGenerator;

/**
 * Apex AI, GEO, AEO & LLMS.txt Subsystem Module.
 */
class AiModule implements ModuleInterface {
    const ID = 'ai';
    const VERSION = '1.0.0';

    /**
     * {@inheritdoc}
     */
    public function getId() {
        return self::ID;
    }

    /**
     * {@inheritdoc}
     */
    public function getName() {
        return 'Apex AI, GEO & LLMS Subsystem';
    }

    /**
     * {@inheritdoc}
     */
    public function getVersion() {
        return self::VERSION;
    }

    /**
     * {@inheritdoc}
     */
    public function isEnabled() {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function register(ContainerInterface $container) {
        $container->singleton(LlmsTxtGenerator::class, function() {
            return new LlmsTxtGenerator();
        });

        $container->singleton(SearchIntentAnalyzer::class, function() {
            return new SearchIntentAnalyzer();
        });

        $container->singleton(MetadataAiGenerator::class, function(ContainerInterface $c) {
            return new MetadataAiGenerator($c->get(VariableEngine::class));
        });
    }

    /**
     * {@inheritdoc}
     */
    public function boot(ContainerInterface $container) {
        // Dynamic /llms.txt route hook
        if (function_exists('add_action')) {
            add_action('init', function() use ($container) {
                if (isset($_SERVER['REQUEST_URI']) && $_SERVER['REQUEST_URI'] === '/llms.txt') {
                    header('Content-Type: text/plain; charset=utf-8');
                    $generator = $container->get(LlmsTxtGenerator::class);
                    echo $generator->generateLlmsTxt();
                    exit;
                }
            });
        }
    }
}
