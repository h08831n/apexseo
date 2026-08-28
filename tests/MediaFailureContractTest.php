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

    // Minimal valid fixtures
    private $validJpeg;
    private $validPng;
    private $validWebp;
    private $validGif;

    public function setUp(): void {
        $this->optimizer = new ImageOptimizer(null);
        $this->security = new SecurityManager();
        $this->controller = new MediaRestController($this->security, $this->optimizer);

        // Genuine minimal valid 1x1 image fixtures
        $this->validJpeg = base64_decode('/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////wgALCAABAAEBAREA/8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPxA=');
        $this->validPng = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
        $this->validWebp = base64_decode('UklGRiQAAABXRUJQVlA4IBgAAAAwAQCdASoBAAEAAQAcJaQAA3AA/v3AgAA=');
        $this->validGif = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
    }

    public function tearDown(): void {
        foreach ($this->tempFiles as $f) {
            if (file_exists($f)) {
                @unlink($f);
            }
        }
    }

    private function createTempImage(?string $content = null): string {
        $file = tempnam(sys_get_temp_dir(), 'apex_test_img_');
        $data = $content !== null ? $content : $this->validJpeg;
        file_put_contents($file, $data);
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
        $tempFile = $this->createTempImage($this->validJpeg);
        $this->optimizer->setBinaryPath('jpegoptim', null);
        $result = $this->optimizer->optimizeFile($tempFile, 'image/jpeg');
        $this->assertInstanceOf('WP_Error', $result);
        $this->assertEquals('optimizer_unavailable', $result->get_error_code());
        $errData = $result->get_error_data();
        $this->assertEquals(503, $errData['status'] ?? 0);
    }

    // Scenario 7: Process execution failure (non-zero exit code)
    public function testProcessExecutionFailureReturnsWpError() {
        $tempFile = $this->createTempImage($this->validJpeg);
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
        $tempFile = $this->createTempImage($this->validJpeg);
        $this->optimizer->setBinaryPath('jpegoptim', '/fake/bin/jpegoptim');
        $this->optimizer->setProcessExecutor(function($cmd, $tempOutput) {
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

    // Scenario 9: Output file 0-byte or invalid returns invalid_output_file
    public function testZeroByteOutputFileReturnsWpError() {
        $tempFile = $this->createTempImage($this->validJpeg);
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

    // Scenario 9b: Invalid non-image output returns invalid_output_file
    public function testInvalidNonImageOutputReturnsInvalidOutputFile() {
        $tempFile = $this->createTempImage($this->validJpeg);
        $this->optimizer->setBinaryPath('jpegoptim', '/fake/bin/jpegoptim');
        $this->optimizer->setProcessExecutor(function($cmd, $tempOutput) {
            file_put_contents($tempOutput, 'INVALID_NON_IMAGE_CORRUPT_BYTES');
            return ['exit_code' => 0, 'stdout' => '', 'stderr' => ''];
        });

        $result = $this->optimizer->optimizeFile($tempFile, 'image/jpeg');
        $this->assertInstanceOf('WP_Error', $result);
        $this->assertEquals('invalid_output_file', $result->get_error_code());
        $errData = $result->get_error_data();
        $this->assertEquals(500, $errData['status'] ?? 0);
    }

    // Scenario 10: Atomic replacement failure with valid image output preserves original byte-for-byte
    public function testAtomicReplacementFailureReturnsWpError() {
        $originalContent = $this->validJpeg . "\nORIGINAL_PADDING_BYTES";
        $tempFile = $this->createTempImage($originalContent);

        $this->optimizer->setBinaryPath('jpegoptim', '/fake/bin/jpegoptim');
        $this->optimizer->setProcessExecutor(function($cmd, $tempOutput) {
            // Write a genuine minimal valid JPEG image as optimizer output
            $validOutput = base64_decode('/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////wgALCAABAAEBAREA/8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPxA=');
            file_put_contents($tempOutput, $validOutput);
            return ['exit_code' => 0, 'stdout' => '', 'stderr' => ''];
        });

        $this->optimizer->setFilesystemSimulator(function($src, $dst) {
            return false; // Simulate permission denied or atomic rename failure
        });

        $result = $this->optimizer->optimizeFile($tempFile, 'image/jpeg');
        $this->assertInstanceOf('WP_Error', $result);
        $this->assertEquals('replacement_failed', $result->get_error_code());
        $errData = $result->get_error_data();
        $this->assertEquals(500, $errData['status'] ?? 0);

        // Original file must remain completely byte-for-byte unchanged
        $this->assertEquals($originalContent, file_get_contents($tempFile));
    }

    // Scenario 11: Temporary files are cleaned up on failure
    public function testTemporaryFilesAreCleanedUpOnFailure() {
        $tempFile = $this->createTempImage($this->validJpeg);
        $createdTempOutput = null;

        $this->optimizer->setBinaryPath('jpegoptim', '/fake/bin/jpegoptim');
        $this->optimizer->setProcessExecutor(function($cmd, $tempOutput) use (&$createdTempOutput) {
            $createdTempOutput = $tempOutput;
            file_put_contents($tempOutput, 'INVALID_OUTPUT');
            return ['exit_code' => 0, 'stdout' => '', 'stderr' => ''];
        });

        $result = $this->optimizer->optimizeFile($tempFile, 'image/jpeg');
        $this->assertInstanceOf('WP_Error', $result);
        $this->assertNotNull($createdTempOutput);
        $this->assertFalse(file_exists($createdTempOutput), 'Temporary working file must be removed on failure.');
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

    // REST Controller Mapping: Bulk Optimize Partial/Full Errors (Status 207 Multi-Status)
    public function testRestBulkOptimizeErrorHandling() {
        $request = new \WP_REST_Request('POST', '/apexseo/v1/media/bulk-optimize');
        $request->set_param('attachment_ids', [999991, 999992]);

        $response = $this->controller->bulkOptimize($request);
        $this->assertInstanceOf('WP_REST_Response', $response);
        $this->assertEquals(207, $response->get_status());
        $data = $response->get_data();
        $this->assertFalse($data['success']);
        $this->assertEquals(2, $data['failure_count']);
        $this->assertEquals(0, $data['success_count']);
        $this->assertEquals('attachment_not_found', $data['items'][0]['code']);
    }
}
