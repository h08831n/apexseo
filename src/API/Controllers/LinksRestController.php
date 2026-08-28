<?php
namespace ApexSEO\API\Controllers;

use ApexSEO\Core\Security\SecurityManager;
use ApexSEO\SEO\Analysis\LinkGraphScanner;

class LinksRestController extends AbstractRestController {
    private $scanner;

    public function __construct(SecurityManager $security, LinkGraphScanner $scanner) {
        parent::__construct($security);
        $this->scanner = $scanner;
    }

    public function registerRoutes(): void {
        register_rest_route(self::NAMESPACE, '/links/suggestions', [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [$this, 'getSuggestions'],
                'permission_callback' => [$this, 'checkEditorPermission'],
            ]
        ]);

        register_rest_route(self::NAMESPACE, '/links/scan', [
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'scanContent'],
                'permission_callback' => [$this, 'checkEditorPermission'],
            ]
        ]);
    }

    public function getSuggestions($request) {
        $postId = (int)($request->get_param('post_id') ?: 0);
        $suggestions = $this->scanner->getInternalLinkSuggestions($postId);
        return $this->sendResponse([
            'success'     => true,
            'suggestions' => $suggestions,
        ]);
    }

    public function scanContent($request) {
        $postId = (int)($request->get_param('post_id') ?: 0);
        $content = (string)($request->get_param('content') ?: '');
        $links = $this->scanner->scanHtmlLinks($content, $postId);
        return $this->sendResponse([
            'success' => true,
            'links'   => $links,
        ]);
    }
}
