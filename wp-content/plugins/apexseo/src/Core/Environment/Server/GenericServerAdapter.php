<?php
namespace ApexSEO\Core\Environment\Server;

/**
 * Generic Fallback Web Server Capability Adapter (Caddy, IIS, Built-in, unknown).
 */
class GenericServerAdapter implements ServerAdapterInterface {
    /**
     * Server type identifier.
     *
     * @var string
     */
    protected $serverType;

    /**
     * Server display name.
     *
     * @var string
     */
    protected $serverName;

    /**
     * Constructor.
     *
     * @param string $serverType
     * @param string $serverName
     */
    public function __construct($serverType = 'generic', $serverName = 'Generic Web Server') {
        $this->serverType = (string) $serverType;
        $this->serverName = (string) $serverName;
    }

    /**
     * {@inheritdoc}
     */
    public function getServerType() {
        return $this->serverType;
    }

    /**
     * {@inheritdoc}
     */
    public function getName() {
        return $this->serverName;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsHtaccess() {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNginxDirectives() {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsLiteSpeedEngine() {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsEsi() {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDirectGzipServing() {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDirectBrotliServing() {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsEarlyHints() {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function flushServerCache($tags = null) {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function generateDirectGzipRules(array $cachePaths = []) {
        return "";
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheControlHeaders(array $params = []) {
        $maxAge = isset($params['max_age']) ? (int) $params['max_age'] : 3600;
        return [
            'Cache-Control' => 'public, max-age=' . $maxAge,
        ];
    }
}
