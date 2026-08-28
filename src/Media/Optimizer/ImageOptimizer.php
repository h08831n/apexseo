<?php
namespace ApexSEO\Media\Optimizer;

use ApexSEO\Core\Database\DatabaseManager;

/**
 * Image Optimizer Engine
 * Strict Part A Implementation: Real binaries, atomic replacements, WP_Error failure contracts.
 * Never returns fabricated savings, fake paths, or fake success.
 */
class ImageOptimizer {
    protected $db;
    protected $binaryPaths = [];
    protected $processExecutor = null;
    protected $filesystemSimulator = null;

    const SUPPORTED_MIMES = [
        'image/jpeg' => ['jpg', 'jpeg'],
        'image/jpg'  => ['jpg'],
        'image/png'  => ['png'],
        'image/webp' => ['webp'],
        'image/avif' => ['avif'],
        'image/gif'  => ['gif'],
    ];

    public function __construct(?DatabaseManager $db = null) {
        $this->db = $db;
        $this->detectAvailableBinaries();
    }

    public function setProcessExecutor(callable $executor): void {
        $this->processExecutor = $executor;
    }

    public function setFilesystemSimulator(callable $simulator): void {
        $this->filesystemSimulator = $simulator;
    }

    public function setBinaryPath(string $binary, ?string $path): void {
        $this->binaryPaths[$binary] = $path;
    }

    public function isBinaryAvailable(string $binary): bool {
        return !empty($this->binaryPaths[$binary]);
    }

    public function getAvailableBinaries(): array {
        return array_filter($this->binaryPaths);
    }

    private function detectAvailableBinaries(): void {
        $binaries = ['cwebp', 'optipng', 'jpegoptim', 'pngquant', 'avifenc', 'gifsicle'];
        foreach ($binaries as $bin) {
            $this->binaryPaths[$bin] = $this->findBinary($bin);
        }
    }

    private function findBinary(string $binary): ?string {
        if (function_exists('exec')) {
            $out = [];
            $code = 1;
            @exec("which " . escapeshellarg($binary) . " 2>/dev/null", $out, $code);
            if ($code === 0 && !empty($out[0]) && is_executable($out[0])) {
                return $out[0];
            }
        }
        $commonPaths = [
            "/usr/bin/{$binary}",
            "/usr/local/bin/{$binary}",
            "/opt/homebrew/bin/{$binary}",
        ];
        foreach ($commonPaths as $p) {
            if (file_exists($p) && is_executable($p)) {
                return $p;
            }
        }
        return null;
    }

    /**
     * Optimize an existing WordPress attachment by ID.
     */
    public function optimizeAttachment(int $attachmentId, array $options = []) {
        if ($attachmentId <= 0) {
            return new \WP_Error('invalid_attachment_id', 'Attachment ID must be a positive integer.', ['status' => 400]);
        }

        $post = get_post($attachmentId);
        if (!$post || $post->post_type !== 'attachment') {
            return new \WP_Error('attachment_not_found', 'The requested media attachment does not exist.', ['status' => 404]);
        }

        $filePath = get_attached_file($attachmentId);
        if (empty($filePath) || !file_exists($filePath)) {
            return new \WP_Error('source_file_missing', 'The source file for this attachment is missing or unreadable on disk.', ['status' => 404]);
        }

        $mimeType = get_post_mime_type($attachmentId) ?: (function_exists('mime_content_type') ? @mime_content_type($filePath) : '');
        $result = $this->optimizeFile($filePath, $mimeType, $options);

        if (is_wp_error($result)) {
            return $result;
        }

        // Record into wp_apex_image_history table if available
        if ($this->db && !empty($result['success'])) {
            $tableName = $this->db->getTableName(DatabaseManager::TABLE_IMAGE_HISTORY);
            $this->db->insert($tableName, [
                'attachment_id'  => $attachmentId,
                'original_size'  => $result['original_size'],
                'optimized_size' => $result['optimized_size'],
                'saved_bytes'    => $result['saved_bytes'],
                'mime_type'      => $result['mime_type'],
                'optimizer_used' => $result['optimizer_used'] ?? 'native',
            ]);
        }

        $result['attachment_id'] = $attachmentId;
        return $result;
    }

    /**
     * Optimize a standalone file path.
     */
    public function optimizeFile(string $filePath, string $mimeType, array $options = []) {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return new \WP_Error('source_file_missing', 'Source file does not exist or cannot be read.', ['status' => 404]);
        }

        $normalizedMime = strtolower(trim($mimeType));
        if (!isset(self::SUPPORTED_MIMES[$normalizedMime])) {
            return new \WP_Error('unsupported_mime_type', "Unsupported image MIME type [{$normalizedMime}].", ['status' => 415]);
        }

        $originalSize = filesize($filePath);
        if ($originalSize <= 0) {
            return new \WP_Error('invalid_source_file', 'Source image file is empty or corrupted (0 bytes).', ['status' => 400]);
        }

        // Determine tool
        $optimizerTool = $this->getToolForMime($normalizedMime);
        if (!$optimizerTool || empty($this->binaryPaths[$optimizerTool])) {
            return new \WP_Error('optimizer_unavailable', "No optimizer binary available for MIME type [{$normalizedMime}].", ['status' => 503]);
        }

        $tempOutput = tempnam(sys_get_temp_dir(), 'apex_opt_');
        if (!$tempOutput) {
            return new \WP_Error('temp_file_creation_failed', 'Could not create temporary working file.', ['status' => 500]);
        }

        $binary = $this->binaryPaths[$optimizerTool];
        $quality = $options['quality'] ?? 85;

        // Build command
        $cmd = $this->buildCommand($optimizerTool, $binary, $filePath, $tempOutput, $quality);

        // Execute process
        $procResult = $this->runProcess($cmd, $tempOutput);
        if ($procResult['exit_code'] !== 0) {
            $this->cleanFile($tempOutput);
            return new \WP_Error('process_execution_failed', 'Image optimizer process returned a non-zero exit code.', [
                'status' => 500,
                'exit_code' => $procResult['exit_code']
            ]);
        }

        // Validate output existence
        if (!file_exists($tempOutput) || !is_readable($tempOutput)) {
            $this->cleanFile($tempOutput);
            return new \WP_Error('output_file_missing', 'Optimizer process did not produce the expected output file.', ['status' => 500]);
        }

        // Validate output size & image integrity
        $optimizedSize = filesize($tempOutput);
        if ($optimizedSize <= 0) {
            $this->cleanFile($tempOutput);
            return new \WP_Error('invalid_output_file', 'Optimized output file is 0 bytes or corrupted.', ['status' => 500]);
        }

        // Validate image content
        if (!$this->validateImageFile($tempOutput, $normalizedMime)) {
            $this->cleanFile($tempOutput);
            return new \WP_Error('invalid_output_file', 'Optimized output failed image validation checks.', ['status' => 500]);
        }

        // Atomic replacement with safety backup
        $savedBytes = max(0, $originalSize - $optimizedSize);
        $savingsPercent = $originalSize > 0 ? round(($savedBytes / $originalSize) * 100, 2) : 0;

        $replaceSuccess = $this->atomicReplace($tempOutput, $filePath);
        if (!$replaceSuccess) {
            $this->cleanFile($tempOutput);
            return new \WP_Error('replacement_failed', 'Failed to safely replace original image with optimized output.', ['status' => 500]);
        }

        return [
            'success'         => true,
            'original_size'   => $originalSize,
            'optimized_size'  => $optimizedSize,
            'saved_bytes'     => $savedBytes,
            'savings_percent' => $savingsPercent,
            'mime_type'       => $normalizedMime,
            'optimizer_used'  => $optimizerTool,
            'output_path'     => $filePath,
        ];
    }

    /**
     * Convert an image file to WebP.
     */
    public function convertToWebp(string $filePath, array $options = []) {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return new \WP_Error('source_file_missing', 'Source file does not exist or cannot be read.', ['status' => 404]);
        }

        if (empty($this->binaryPaths['cwebp'])) {
            return new \WP_Error('optimizer_unavailable', 'The cwebp binary is not installed or available on this system.', ['status' => 503]);
        }

        $tempOutput = tempnam(sys_get_temp_dir(), 'apex_webp_');
        $destPath = preg_replace('/\.[a-zA-Z0-9]+$/', '.webp', $filePath);
        $quality = $options['quality'] ?? 80;

        $cmd = escapeshellarg($this->binaryPaths['cwebp']) . " -q " . intval($quality) . " " . escapeshellarg($filePath) . " -o " . escapeshellarg($tempOutput);

        $procResult = $this->runProcess($cmd, $tempOutput);
        if ($procResult['exit_code'] !== 0 || !file_exists($tempOutput) || filesize($tempOutput) <= 0) {
            $this->cleanFile($tempOutput);
            return new \WP_Error('conversion_failed', 'WebP conversion process failed to generate valid output.', ['status' => 500]);
        }

        $optimizedSize = filesize($tempOutput);
        $originalSize = filesize($filePath);

        $moveSuccess = @rename($tempOutput, $destPath);
        if (!$moveSuccess) {
            $this->cleanFile($tempOutput);
            return new \WP_Error('replacement_failed', 'Failed to move converted WebP file to destination.', ['status' => 500]);
        }

        return [
            'success'         => true,
            'original_size'   => $originalSize,
            'optimized_size'  => $optimizedSize,
            'saved_bytes'     => max(0, $originalSize - $optimizedSize),
            'savings_percent' => $originalSize > 0 ? round((max(0, $originalSize - $optimizedSize) / $originalSize) * 100, 2) : 0,
            'mime_type'       => 'image/webp',
            'optimizer_used'  => 'cwebp',
            'output_path'     => $destPath,
        ];
    }

    /**
     * Convert an image file to AVIF.
     */
    public function convertToAvif(string $filePath, array $options = []) {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return new \WP_Error('source_file_missing', 'Source file does not exist or cannot be read.', ['status' => 404]);
        }

        if (empty($this->binaryPaths['avifenc'])) {
            return new \WP_Error('optimizer_unavailable', 'The avifenc binary is not installed or available on this system.', ['status' => 503]);
        }

        $tempOutput = tempnam(sys_get_temp_dir(), 'apex_avif_');
        $destPath = preg_replace('/\.[a-zA-Z0-9]+$/', '.avif', $filePath);
        $quality = $options['quality'] ?? 65;

        $cmd = escapeshellarg($this->binaryPaths['avifenc']) . " -s 6 -q " . intval($quality) . " " . escapeshellarg($filePath) . " " . escapeshellarg($tempOutput);

        $procResult = $this->runProcess($cmd, $tempOutput);
        if ($procResult['exit_code'] !== 0 || !file_exists($tempOutput) || filesize($tempOutput) <= 0) {
            $this->cleanFile($tempOutput);
            return new \WP_Error('conversion_failed', 'AVIF conversion process failed to generate valid output.', ['status' => 500]);
        }

        $optimizedSize = filesize($tempOutput);
        $originalSize = filesize($filePath);

        $moveSuccess = @rename($tempOutput, $destPath);
        if (!$moveSuccess) {
            $this->cleanFile($tempOutput);
            return new \WP_Error('replacement_failed', 'Failed to move converted AVIF file to destination.', ['status' => 500]);
        }

        return [
            'success'         => true,
            'original_size'   => $originalSize,
            'optimized_size'  => $optimizedSize,
            'saved_bytes'     => max(0, $originalSize - $optimizedSize),
            'savings_percent' => $originalSize > 0 ? round((max(0, $originalSize - $optimizedSize) / $originalSize) * 100, 2) : 0,
            'mime_type'       => 'image/avif',
            'optimizer_used'  => 'avifenc',
            'output_path'     => $destPath,
        ];
    }

    private function getToolForMime(string $mime): ?string {
        switch ($mime) {
            case 'image/jpeg':
            case 'image/jpg':
                return 'jpegoptim';
            case 'image/png':
                return !empty($this->binaryPaths['optipng']) ? 'optipng' : 'pngquant';
            case 'image/webp':
                return 'cwebp';
            case 'image/avif':
                return 'avifenc';
            case 'image/gif':
                return 'gifsicle';
            default:
                return null;
        }
    }

    private function buildCommand(string $tool, string $binary, string $src, string $dst, int $quality): string {
        $b = escapeshellarg($binary);
        $s = escapeshellarg($src);
        $d = escapeshellarg($dst);

        switch ($tool) {
            case 'jpegoptim':
                // copy to dst first, then optimize in-place on dst
                @copy($src, $dst);
                return "{$b} --max={$quality} --strip-all {$d}";
            case 'optipng':
                @copy($src, $dst);
                return "{$b} -o5 -strip all {$d}";
            case 'pngquant':
                return "{$b} --quality={$quality}-90 --output {$d} --force {$s}";
            case 'cwebp':
                return "{$b} -q {$quality} {$s} -o {$d}";
            case 'avifenc':
                return "{$b} -q {$quality} {$s} {$d}";
            case 'gifsicle':
                return "{$b} -O3 {$s} -o {$d}";
            default:
                return "{$b} {$s} {$d}";
        }
    }

    private function runProcess(string $cmd, string $tempOutput): array {
        if ($this->processExecutor !== null) {
            return call_user_func($this->processExecutor, $cmd, $tempOutput);
        }

        if (!function_exists('exec')) {
            return ['exit_code' => 127, 'stdout' => '', 'stderr' => 'exec disabled'];
        }

        $out = [];
        $code = 0;
        @exec($cmd . ' 2>&1', $out, $code);
        return [
            'exit_code' => $code,
            'stdout'    => implode("
", $out),
            'stderr'    => '',
        ];
    }

    private function validateImageFile(string $file, string $expectedMime): bool {
        if (!file_exists($file) || filesize($file) === 0) {
            return false;
        }
        // Basic header checks or getimagesize
        if (function_exists('getimagesize')) {
            $info = @getimagesize($file);
            return ($info !== false && !empty($info[0]) && !empty($info[1]));
        }
        return true;
    }

    private function atomicReplace(string $sourceFile, string $targetFile): bool {
        if ($this->filesystemSimulator !== null) {
            return call_user_func($this->filesystemSimulator, $sourceFile, $targetFile);
        }

        $backup = $targetFile . '.apex_bak_' . uniqid();
        $backedUp = @copy($targetFile, $backup);

        // Attempt rename
        if (@rename($sourceFile, $targetFile)) {
            if ($backedUp && file_exists($backup)) {
                @unlink($backup);
            }
            return true;
        }

        // If rename failed, try copy + unlink
        if (@copy($sourceFile, $targetFile)) {
            @unlink($sourceFile);
            if ($backedUp && file_exists($backup)) {
                @unlink($backup);
            }
            return true;
        }

        // Restore backup if copy/rename failed
        if ($backedUp && file_exists($backup)) {
            @copy($backup, $targetFile);
            @unlink($backup);
        }

        return false;
    }

    private function cleanFile(string $file): void {
        if (file_exists($file)) {
            @unlink($file);
        }
    }
}
