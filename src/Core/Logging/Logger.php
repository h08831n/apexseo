<?php
namespace ApexSEO\Core\Logging;

class Logger implements LoggerInterface {
    private $channel;

    public function __construct(string $channel = 'apexseo') {
        $this->channel = $channel;
    }

    public function emergency(string $message, array $context = []): void {
        $this->log('EMERGENCY', $message, $context);
    }

    public function alert(string $message, array $context = []): void {
        $this->log('ALERT', $message, $context);
    }

    public function critical(string $message, array $context = []): void {
        $this->log('CRITICAL', $message, $context);
    }

    public function error(string $message, array $context = []): void {
        $this->log('ERROR', $message, $context);
    }

    public function warning(string $message, array $context = []): void {
        $this->log('WARNING', $message, $context);
    }

    public function notice(string $message, array $context = []): void {
        $this->log('NOTICE', $message, $context);
    }

    public function info(string $message, array $context = []): void {
        $this->log('INFO', $message, $context);
    }

    public function debug(string $message, array $context = []): void {
        $this->log('DEBUG', $message, $context);
    }

    public function log(string $level, string $message, array $context = []): void {
        $formatted = sprintf('[%s] [%s] [%s] %s %s', date('Y-m-d H:i:s'), $this->channel, strtoupper($level), $message, !empty($context) ? json_encode($context) : '');
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log($formatted);
        }
    }
}
