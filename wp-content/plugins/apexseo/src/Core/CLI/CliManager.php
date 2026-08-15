<?php
namespace ApexSEO\Core\CLI;

use ApexSEO\Core\Contracts\ServiceContractInterface;
use ApexSEO\Core\Contracts\HookableInterface;

/**
 * WP-CLI Command Infrastructure and Root Namespace Manager.
 */
class CliManager implements ServiceContractInterface, HookableInterface {
    const ROOT_COMMAND = 'apexseo';

    /**
     * Registered CLI subcommands.
     *
     * @var array
     */
    protected $commands = [];

    /**
     * {@inheritdoc}
     */
    public function registerHooks() {
        if (defined('WP_CLI') && WP_CLI && class_exists('\\WP_CLI')) {
            $this->initCommands();
        }
    }

    /**
     * Initialize registered WP-CLI commands.
     *
     * @return void
     */
    public function initCommands() {
        if (!class_exists('\\WP_CLI')) {
            return;
        }

        // Register root command handler
        \WP_CLI::add_command(self::ROOT_COMMAND, [$this, 'rootCommand']);

        foreach ($this->commands as $subcommand => $definition) {
            \WP_CLI::add_command(
                self::ROOT_COMMAND . ' ' . $subcommand,
                $definition['callable'],
                isset($definition['args']) ? $definition['args'] : []
            );
        }
    }

    /**
     * Register a new subcommand under 'wp apexseo <subcommand>'.
     *
     * @param string $subcommand Subcommand name (e.g. 'cache', 'migrate', 'index').
     * @param callable|string $callable Command handler.
     * @param array $args Command registration metadata.
     * @return self
     */
    public function registerCommand($subcommand, $callable, array $args = []) {
        $this->commands[$subcommand] = [
            'callable' => $callable,
            'args'     => $args,
        ];

        return $this;
    }

    /**
     * Root command default handler.
     *
     * @param array $args Positional arguments.
     * @param array $assocArgs Associative arguments.
     * @return void
     */
    public function rootCommand($args, $assocArgs) {
        if (class_exists('\\WP_CLI')) {
            \WP_CLI::log(sprintf('Apex SEO Platform v%s', defined('APEXSEO_VERSION') ? APEXSEO_VERSION : '1.0.0'));
            \WP_CLI::log('Use "wp apexseo <command>" to execute subcommands.');
            \WP_CLI::log('Available commands: ' . implode(', ', array_keys($this->commands)));
        }
    }

    /**
     * Get registered subcommands.
     *
     * @return array
     */
    public function getCommands() {
        return $this->commands;
    }
}
