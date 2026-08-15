<?php
namespace ApexSEO\Core\Logging;

use ApexSEO\Core\Contracts\ServiceContractInterface;

/**
 * Structured Logger Interface for Apex SEO Platform.
 */
interface LoggerInterface extends ServiceContractInterface {
    const LEVEL_DEBUG     = 'DEBUG';
    const LEVEL_INFO      = 'INFO';
    const LEVEL_NOTICE    = 'NOTICE';
    const LEVEL_WARNING   = 'WARNING';
    const LEVEL_ERROR     = 'ERROR';
    const LEVEL_CRITICAL  = 'CRITICAL';

    /**
     * Log a debug level message.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function debug($message, array $context = []);

    /**
     * Log an informational message.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function info($message, array $context = []);

    /**
     * Log a normal but significant event.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function notice($message, array $context = []);

    /**
     * Log an exceptional occurrence that is not an error.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function warning($message, array $context = []);

    /**
     * Log a runtime error that does not require immediate action.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function error($message, array $context = []);

    /**
     * Log a critical condition requiring immediate attention.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function critical($message, array $context = []);

    /**
     * Log a message with arbitrary level.
     *
     * @param string $level
     * @param string $message
     * @param array $context
     * @return void
     */
    public function log($level, $message, array $context = []);
}
