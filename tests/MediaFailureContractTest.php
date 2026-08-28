<?php
namespace ApexSEO\Tests;

use ApexSEO\Media\Optimizer\ImageOptimizer;
use ApexSEO\API\Controllers\MediaRestController;
use ApexSEO\Core\Security\SecurityManager;

class MediaFailureContractTest extends TestCase {
    private $optimizer;
    private $security;
    private $controller;
    private $tempFiles = [];

    public function setUp(): void {
        $this->optimizer = new ImageOptimizer(null);
        $this->security = new SecurityManager();
        $this->controller = new MediaRestController($this->security, $this->optimizer);
    }

    public function tearDown(): void {
        foreach ($this->tempFiles as $f) {
            if (file_exists($f)) {
                @unlink($f);
            }
        }
    }

    private function createTempImage(string $content = 'FAKE_IMAGE_DATA'): string {
        $file = tempnam(sys_get_temp_dir(), 'apex_test_img_');
        file_put_contents($file, $content);
        $this->tempFiles[] = $file;
        return $file;
    }

    // Scenario 1: Invalid attachment ID (<= 0)
    public function testInvalidAttachmentIdReturnsWpError() {
        $result = $this->optimizer->optimizeAttachment(0);
        $this->assertInstanceOf('WP_Error', $result);
        $this->assertEquals('invalid_attachment_id', $result->get_error_code());
        $errData = $result->get_error_data();
        $this->assertEquals(400, $errData['status'] ?? 0);
    }

    // Scenario 2: Attachment not found
    public function testAttachmentNotFoundReturnsWpError() {
        $result = $this->optimizer->optimizeAttachment(999999);
        $this->assertInstanceOf('WP_Error', $result);
        $this->assertEquals('attachment_not_found', $result->get_error_code());
        $errData = $result->get_error_data();
        $this->assertEquals(404, $errData['status'] ?? 0);
    }

    // Scenario 3: Source file missing on disk
    public function testSourceFileMissingReturnsWpError() {
        $missingPath = sys_get_temp_dir() . '/apex_missing_' . uniqid() . '.jpg';
        $result = $this->optimizer->optimizeFile($missingPath, 'image/jpeg');
        $this->assertInstanceOf('WP_Error', $result);
        $this->assertEquals('source_file_missing', $result->get_error_code());
        $errData = $result->get_error_data();
        $this->assertEquals(404, $errData['status'] ?? 0);
    }

    // Scenario 4: Unsupported MIME type
    public function testUnsupportedMimeTypeReturnsWpError() {
        $tempFile = $this->createTempImage();
        $result = $this->optimizer->optimizeFile($tempFile, 'application/pdf');
        $this->assertInstanceOf('WP_Error', $result);
        $this->assertEquals('unsupported_mime_type', $result->get_error_code());
        $errData = $result->get_error_data();
        $this->assertEquals(415, $errData['status'] ?? 0);
    }

    // Scenario 5: Empty / 0-byte source file
    public function testEmptySourceFileReturnsWpError() {
        $tempFile = $this->createTempImage('');
        $result = $this->optimizer->optimizeFile($tempFile, 'image/jpeg');
        $this->assertInstanceOf('WP_Error', $result);
        $this->assertEquals('invalid_source_file', $result->get_error_code());
        $errData = $result->get_error_data();
        $this->assertEquals(400, $errData['status'] ?? 0);
    }

    // Scenario 6: Missing binary / optimizer unavailable
    public function testMissingBinaryReturnsWpError() {
        $tempFile = $this->createTempImage('VALID_BYTES');
        $this->optimizer->setBinaryPath('jpegoptim', null);
        $result = $this->optimizer->optimizeFile($tempFile, 'image/jpeg');
        $this->assertInstanceOf('WP_Error', $result);
        $this->assertEquals('optimizer_unavailable', $result->get_error_code());
        $errData = $result->get_error_data();
        $this->assertEquals(503, $errData['status'] ?? 0);
    }

    // Scenario 7: Process execution failure (non-zero exit code)
    public function testProcessExecutionFailureReturnsWpError() {
        $tempFile = $this->createTempImage('VALID_BYTES');
        $this->optimizer->setBinaryPath('jpegoptim', '/fake/bin/jpegoptim');
        $this->optimizer->setProcessExecutor(function($cmd, $tempOutput) {
            return ['exit_code' => 1, 'stdout' => '', 'stderr' => 'corrupted stream'];
        });

        $result = $this->optimizer->optimizeFile($tempFile, 'image/jpeg');
        $this->assertInstanceOf('WP_Error', $result);
        $this->assertEquals('process_execution_failed', $result->get_error_code());
        $errData = $result->get_error_data();
        $this->assertEquals(500, $errData['status'] ?? 0);
    }

    // Scenario 8: Output file missing after process execution
    public function testOutputFileMissingReturnsWpError() {
        $tempFile = $this->createTempImage('VALID_BYTES');
        $this->optimizer->setBinaryPath('jpegoptim', '/fake/bin/jpegoptim');
        $this->optimizer->setProcessExecutor(function($cmd, $tempOutput) {
            // Simulated process finishes with 0 but unlinks output
            if (file_exists($tempOutput)) {
                @unlink($tempOutput);
            }
            return ['exit_code' => 0, 'stdout' => '', 'stderr' => ''];
        });

        $result = $this->optimizer->optimizeFile($tempFile, 'image/jpeg');
        $this->assertInstanceOf('WP_Error', $result);
        $this->assertEquals('output_file_missing', $result->get_error_code());
        $errData = $result->get_error_data();
        $this->assertEquals(500, $errData['status'] ?? 0);
    }

    // Scenario 9: Output file 0-byte or invalid
    public function testZeroByteOutputFileReturnsWpError() {
        $tempFile = $this->createTempImage('VALID_BYTES');
        $this->optimizer->setBinaryPath('jpegoptim', '/fake/bin/jpegoptim');
        $this->optimizer->setProcessExecutor(function($cmd, $tempOutput) {
            file_put_contents($tempOutput, ''); // 0-byte output
            return ['exit_code' => 0, 'stdout' => '', 'stderr' => ''];
        });

        $result = $this->optimizer->optimizeFile($tempFile, 'image/jpeg');
        $this->assertInstanceOf('WP_Error', $result);
        $this->assertEquals('invalid_output_file', $result->get_error_code());
        $errData = $result->get_error_data();
        $this->assertEquals(500, $errData['status'] ?? 0);
    }

    // Scenario 10: Atomic replacement failure
    public function testAtomicReplacementFailureReturnsWpError() {
        $tempFile = $this->createTempImage('ORIGINAL_DATA_LONGER');
        $this->optimizer->setBinaryPath('jpegoptim', '/fake/bin/jpegoptim');
        $this->optimizer->setProcessExecutor(function($cmd, $tempOutput) {
            file_put_contents($tempOutput, 'SMALL');
            return ['exit_code' => 0, 'stdout' => '', 'stderr' => ''];
        });
        $this->optimizer->setFilesystemSimulator(function($src, $dst) {
            return false; // Simulate permission denied during atomic replace
        });

        $result = $this->optimizer->optimizeFile($tempFile, 'image/jpeg');
        $this->assertInstanceOf('WP_Error', $result);
        $this->assertEquals('replacement_failed', $result->get_error_code());
        $errData = $result->get_error_data();
        $this->assertEquals(500, $errData['status'] ?? 0);
    }

    // REST Controller Mapping: Single Attachment Failure
    public function testRestOptimizeSingleErrorHandling() {
        $request = new \WP_REST_Request('POST', '/apexseo/v1/media/optimize');
        $request->set_param('attachment_id', -5);

        $response = $this->controller->optimizeSingle($request);
        $this->assertInstanceOf('WP_REST_Response', $response);
        $this->assertEquals(400, $response->get_status());
        $data = $response->get_data();
        $this->assertFalse($data['success']);
        $this->assertEquals('invalid_attachment_id', $data['code']);
    }

    // REST Controller Mapping: Bulk Optimize Partial/Full Errors
    public function testRestBulkOptimizeErrorHandling() {
        $request = new \WP_REST_Request('POST', '/apexseo/v1/media/bulk-optimize');
        $request->set_param('attachment_ids', [999991, 999992]);

        $response = $this->controller->bulkOptimize($request);
        $this->assertInstanceOf('WP_REST_Response', $response);
        $this->assertEquals(207, $response->get_status());
        $data = $response->get_data();
        $this->assertFalse($data['success']);
        $this->assertEquals(2, $data['failure_count']);
        $this->assertEquals('attachment_not_found', $data['items'][0]['code']);
    }
}
