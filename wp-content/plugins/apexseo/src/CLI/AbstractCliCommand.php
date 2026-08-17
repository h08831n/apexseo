<?php
namespace ApexSEO\CLI;

use ApexSEO\Core\Bootstrap\Plugin;
use ApexSEO\Core\Container\ContainerInterface;

/**
 * Abstract Base WP-CLI Command for Apex SEO.
 */
abstract class AbstractCliCommand {
    /**
     * DI container.
     *
     * @var ContainerInterface
     */
    protected $container;

    /**
     * Last output messages (for headless/testing inspection).
     *
     * @var array
     */
    protected $outputBuffer = [];

    /**
     * Constructor.
     *
     * @param ContainerInterface|null $container
     */
    public function __construct(ContainerInterface $container = null) {
        $this->container = $container ?: Plugin::getInstance()->getContainer();
    }

    /**
     * Format and output items.
     *
     * @param string $format
     * @param array $items
     * @param array $fields
     * @return void
     */
    protected function formatItems($format, array $items, array $fields) {
        $format = !empty($format) ? strtolower($format) : 'table';

        if ($format === 'json') {
            $json = json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            $this->line($json);
            return;
        }

        if ($format === 'count') {
            $this->line((string) count($items));
            return;
        }

        if ($format === 'ids') {
            $ids = [];
            foreach ($items as $item) {
                if (is_array($item) && isset($item['id'])) {
                    $ids[] = $item['id'];
                } elseif (is_object($item) && isset($item->id)) {
                    $ids[] = $item->id;
                }
            }
            $this->line(implode(' ', $ids));
            return;
        }

        if (class_exists('\\WP_CLI\\Utils') && method_exists('\\WP_CLI\\Utils', 'format_items')) {
            \WP_CLI\Utils\format_items($format, $items, $fields);
            return;
        }

        // Fallback for tests/plain output
        $this->line(json_encode($items, JSON_PRETTY_PRINT));
    }

    /**
     * Print a line to stdout or buffer.
     *
     * @param string $message
     * @return void
     */
    protected function line($message) {
        $this->outputBuffer[] = ['type' => 'line', 'message' => $message];
        if (class_exists('\\WP_CLI') && method_exists('\\WP_CLI', 'line')) {
            \WP_CLI::line($message);
        }
    }

    /**
     * Print a success message.
     *
     * @param string $message
     * @return void
     */
    protected function success($message) {
        $this->outputBuffer[] = ['type' => 'success', 'message' => $message];
        if (class_exists('\\WP_CLI') && method_exists('\\WP_CLI', 'success')) {
            \WP_CLI::success($message);
        }
    }

    /**
     * Print a warning message.
     *
     * @param string $message
     * @return void
     */
    protected function warning($message) {
        $this->outputBuffer[] = ['type' => 'warning', 'message' => $message];
        if (class_exists('\\WP_CLI') && method_exists('\\WP_CLI', 'warning')) {
            \WP_CLI::warning($message);
        }
    }

    /**
     * Print an error message.
     *
     * @param string $message
     * @param bool $exit
     * @return void
     */
    protected function error($message, $exit = true) {
        $this->outputBuffer[] = ['type' => 'error', 'message' => $message];
        if (class_exists('\\WP_CLI') && method_exists('\\WP_CLI', 'error')) {
            \WP_CLI::error($message, $exit);
        }
    }

    /**
     * Confirm a prompt if not forced or bypassed.
     *
     * @param string $question
     * @param array $assocArgs
     * @return bool
     */
    protected function confirm($question, array $assocArgs) {
        if (!empty($assocArgs['yes']) || !empty($assocArgs['force'])) {
            return true;
        }

        if (class_exists('\\WP_CLI') && method_exists('\\WP_CLI', 'confirm')) {
            \WP_CLI::confirm($question, $assocArgs);
            return true;
        }

        return true;
    }

    /**
     * Get output buffer.
     *
     * @return array
     */
    public function getOutputBuffer() {
        return $this->outputBuffer;
    }

    /**
     * Clear output buffer.
     *
     * @return void
     */
    public function clearOutputBuffer() {
        $this->outputBuffer = [];
    }
}
