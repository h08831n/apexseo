<?php
namespace ApexSEO\Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Real REST API Integration Test.
 */
class RealRestApiIntegrationTest extends TestCase {
    public function testRestRoutesRegistered() {
        if (!function_exists('rest_get_server')) {
            $this->markTestSkipped('Real WordPress REST Server not available.');
        }

        $server = rest_get_server();
        $routes = $server->get_routes();

        $this->assertArrayHasKey('/apexseo/v1/status', $routes);
        $this->assertArrayHasKey('/apexseo/v1/settings', $routes);
        $this->assertArrayHasKey('/apexseo/v1/redirects', $routes);
        $this->assertArrayHasKey('/apexseo/v1/404', $routes);
        $this->assertArrayHasKey('/apexseo/v1/cache/status', $routes);
        $this->assertArrayHasKey('/apexseo/v1/analysis/post/(?P<id>[\\d]+)', $routes);
    }

    public function testUnauthorizedStatusEndpointRejection() {
        if (!function_exists('rest_do_request')) {
            $this->markTestSkipped('Real WordPress REST Server not available.');
        }

        wp_set_current_user(0); // Unauthenticated guest
        $request = new \WP_REST_Request('GET', '/apexseo/v1/status');
        $response = rest_do_request($request);

        $this->assertContains($response->get_status(), [401, 403]);
    }

    public function testAuthorizedStatusEndpoint() {
        if (!function_exists('rest_do_request') || !function_exists('wp_set_current_user')) {
            $this->markTestSkipped('Real WordPress REST Server not available.');
        }

        $adminId = 1;
        wp_set_current_user($adminId);

        $request = new \WP_REST_Request('GET', '/apexseo/v1/status');
        $response = rest_do_request($request);

        $this->assertEquals(200, $response->get_status());
        $data = $response->get_data();
        $this->assertEquals('apexseo/v1', $data['namespace']);
    }

    public function testAuthorizedSettingsGetAndPost() {
        if (!function_exists('rest_do_request')) {
            $this->markTestSkipped('Real WordPress REST Server not available.');
        }

        wp_set_current_user(1);

        // GET
        $getRequest = new \WP_REST_Request('GET', '/apexseo/v1/settings');
        $getResponse = rest_do_request($getRequest);
        $this->assertEquals(200, $getResponse->get_status());

        // POST update
        $postRequest = new \WP_REST_Request('POST', '/apexseo/v1/settings');
        $postRequest->set_json_params(['enable_breadcrumbs' => true]);
        $postResponse = rest_do_request($postRequest);
        $this->assertEquals(200, $postResponse->get_status());
    }
}
