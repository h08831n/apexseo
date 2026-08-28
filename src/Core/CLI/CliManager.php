<?php
namespace ApexSEO\Core\CLI;

use ApexSEO\Core\Container\ContainerInterface;
use ApexSEO\CLI\RootCommand;
use ApexSEO\CLI\IndexCommand;
use ApexSEO\CLI\CacheCommand;
use ApexSEO\CLI\MediaCommand;
use ApexSEO\CLI\RedirectCommand;
use ApexSEO\CLI\DatabaseCommand;
use ApexSEO\CLI\MigrateCommand;
use ApexSEO\CLI\SitemapCommand;
use ApexSEO\CLI\DoctorCommand;
use ApexSEO\CLI\SchemaCommand;
use ApexSEO\CLI\AnalysisCommand;

class CliManager {
    private $commands = [];

    public function __construct() {
        $this->registerDefaultCommands();
    }

    private function registerDefaultCommands(): void {
        $this->commands['index'] = IndexCommand::class;
        $this->commands['cache'] = CacheCommand::class;
        $this->commands['media'] = MediaCommand::class;
        $this->commands['redirect'] = RedirectCommand::class;
        $this->commands['db'] = DatabaseCommand::class;
        $this->commands['migrate'] = MigrateCommand::class;
        $this->commands['sitemap'] = SitemapCommand::class;
        $this->commands['doctor'] = DoctorCommand::class;
        $this->commands['report'] = DoctorCommand::class;
        $this->commands['schema'] = SchemaCommand::class;
        $this->commands['analysis'] = AnalysisCommand::class;
    }

    public function getCommands(): array {
        return $this->commands;
    }

    public function registerWpCli(ContainerInterface $container): void {
        if (!defined('WP_CLI') || !WP_CLI) {
            return;
        }

        \WP_CLI::add_command('apexseo', $container->get(RootCommand::class));

        foreach ($this->commands as $name => $class) {
            $instance = $container->get($class);
            \WP_CLI::add_command("apexseo {$name}", $instance);
        }
    }
}
