<?php
namespace ApexSEO\Core\Environment;

use ApexSEO\Core\Environment\Server\ServerAdapterInterface;
use ApexSEO\Core\Environment\Server\ApacheAdapter;
use ApexSEO\Core\Environment\Server\NginxAdapter;
use ApexSEO\Core\Environment\Server\LiteSpeedAdapter;
use ApexSEO\Core\Environment\Server\GenericServerAdapter;

class EnvironmentDetector {
    public function isCli(): bool {
        return (php_sapi_name() === 'cli' || defined('WP_CLI'));
    }

    public function isMultisite(): bool {
        return is_multisite();
    }

    public function getPhpVersion(): string {
        return PHP_VERSION;
    }

    public function getWordPressVersion(): string {
        global $wp_version;
        return $wp_version ?: '6.4.0';
    }

    public function getServerSoftware(): string {
        return $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';
    }

    public function isSsl(): bool {
        return is_ssl();
    }

    public function getMemoryLimit(): string {
        return ini_get('memory_limit') ?: '256M';
    }

    public function detectServerAdapter(): ServerAdapterInterface {
        $apache = new ApacheAdapter();
        if ($apache->isSupported()) {
            return $apache;
        }

        $nginx = new NginxAdapter();
        if ($nginx->isSupported()) {
            return $nginx;
        }

        $litespeed = new LiteSpeedAdapter();
        if ($litespeed->isSupported()) {
            return $litespeed;
        }

        return new GenericServerAdapter();
    }
}
