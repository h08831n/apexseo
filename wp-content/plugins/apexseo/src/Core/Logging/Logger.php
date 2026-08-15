<?php
namespace ApexSEO\Core\Logging;

/**
 * Structured, privacy-safe Logger implementation for Apex SEO Platform.
 */
class Logger implements LoggerInterface {
    /**
     * File path for the log destination.
     *
     * @var string
     */
    protected $logFile;

    /**
     * Minimum log severity level to record.
     *
     * @var string
     */
    protected $minLevel;

    /**
     * Maximum log file size before automatic rollover (in bytes).
     *
     * @var int
     */
    protected $maxFileSize;

    /**
     * Severity weights mapping.
     *
     * @var array
     */
    protected static $severity = [
        self::LEVEL_DEBUG    => 100,
        self::LEVEL_INFO     => 200,
        self::LEVEL_NOTICE   => 250,
        self::LEVEL_WARNING  => 300,
        self::LEVEL_ERROR    => 400,
        self::LEVEL_CRITICAL => 500,
    ];

    /**
     * Sensitive key names to redact automatically in context arrays.
     *
     * @var array
     */
    protected static $sensitiveKeys = [
        'password', 'secret', 'token', 'access_token', 'refresh_token',
        'api_key', 'authorization', 'bearer', 'cookie', 'auth', 'private_key',
        'stripe_secret', 'gemini_api_key', 'client_secret', 'pass', 'pwd'
    ];

    /**
     * Constructor.
     *
     * @param string|null $logFile Custom log file path.
     * @param string $minLevel Minimum level threshold.
     * @param int $maxFileSize Max file size before rotation in bytes (default 5MB).
     */
    public function __construct($logFile = null, $minLevel = self::LEVEL_INFO, $maxFileSize = 5242880) {
        if ($logFile !== null) {
            $this->logFile = $logFile;
        } else {
            $uploadDir = function_exists('wp_upload_dir') ? wp_upload_dir() : ['basedir' => sys_get_temp_dir()];
            $baseDir = isset($uploadDir['basedir']) ? $uploadDir['basedir'] : sys_get_temp_dir();
            $this->logFile = rtrim($baseDir, '/\\') . '/apex-audit.log';
        }

        $this->minLevel = strtoupper($minLevel);
        $this->maxFileSize = (int) $maxFileSize;
    }

    /**
     * {@inheritdoc}
     */
    public function debug($message, array $context = []) {
        $this->log(self::LEVEL_DEBUG, $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function info($message, array $context = []) {
        $this->log(self::LEVEL_INFO, $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function notice($message, array $context = []) {
        $this->log(self::LEVEL_NOTICE, $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function warning($message, array $context = []) {
        $this->log(self::LEVEL_WARNING, $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function error($message, array $context = []) {
        $this->log(self::LEVEL_ERROR, $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function critical($message, array $context = []) {
        $this->log(self::LEVEL_CRITICAL, $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function log($level, $message, array $context = []) {
        $level = strtoupper($level);

        $currentWeight = isset(self::$severity[$level]) ? self::$severity[$level] : 200;
        $minWeight = isset(self::$severity[$this->minLevel]) ? self::$severity[$this->minLevel] : 200;

        if ($currentWeight < $minWeight) {
            return;
        }

        $cleanContext = $this->sanitizeContext($context);
        $entry = sprintf(
            "[%s] [%s] %s %s\n",
            gmdate('Y-m-d H:i:s T'),
            $level,
            $this->maskSensitiveStrings($message),
            !empty($cleanContext) ? json_encode($cleanContext, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : ''
        );

        $this->writeEntry($entry);
    }

    /**
     * Sanitize context array to redact secrets and mask IPs.
     *
     * @param array $context
     * @return array
     */
    protected function sanitizeContext(array $context) {
        $sanitized = [];

        foreach ($context as $key => $value) {
            $lowerKey = strtolower((string) $key);

            // Check if key matches sensitive patterns
            $isSensitive = false;
            foreach (self::$sensitiveKeys as $sensitive) {
                if (strpos($lowerKey, $sensitive) !== false) {
                    $isSensitive = true;
                    break;
                }
            }

            if ($isSensitive) {
                $sanitized[$key] = '***REDACTED***';
                continue;
            }

            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizeContext($value);
            } elseif (is_string($value)) {
                $sanitized[$key] = $this->maskSensitiveStrings($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Mask sensitive strings such as IP addresses or tokens embedded in messages.
     *
     * @param string $message
     * @return string
     */
    public function maskSensitiveStrings($message) {
        if (!is_string($message)) {
            return $message;
        }

        // Mask IPv4 addresses: 192.168.1.123 -> 192.168.1.0
        $message = preg_replace(
            '/(\b\d{1,3}\.\d{1,3}\.\d{1,3}\.)\d{1,3}\b/',
            '${1}0',
            $message
        );

        // Mask Bearer tokens: Bearer abc123xyz -> Bearer ***REDACTED***
        $message = preg_replace(
            '/Bearer\s+[A-Za-z0-9\-\._~\+\/]+=*/i',
            'Bearer ***REDACTED***',
            $message
        );

        return $message;
    }

    /**
     * Write formatted log entry to disk with rotation support.
     *
     * @param string $entry
     * @return void
     */
    protected function writeEntry($entry) {
        $dir = dirname($this->logFile);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        if (file_exists($this->logFile) && filesize($this->logFile) > $this->maxFileSize) {
            $rotated = $this->logFile . '.1';
            if (file_exists($rotated)) {
                @unlink($rotated);
            }
            @rename($this->logFile, $rotated);
        }

        @file_put_contents($this->logFile, $entry, FILE_APPEND | LOCK_EX);
    }

    /**
     * Get configured log file path.
     *
     * @return string
     */
    public function getLogFile() {
        return $this->logFile;
    }

    /**
     * Set minimum severity level.
     *
     * @param string $level
     * @return void
     */
    public function setMinLevel($level) {
        $this->minLevel = strtoupper($level);
    }
}
