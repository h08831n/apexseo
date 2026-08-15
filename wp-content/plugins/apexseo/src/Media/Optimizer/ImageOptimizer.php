<?php
namespace ApexSEO\Media\Optimizer;

use ApexSEO\Core\Contracts\ServiceContractInterface;
use ApexSEO\Core\Environment\EnvironmentDetector;

/**
 * High-Performance Image Optimization & WebP/AVIF Converter.
 */
class ImageOptimizer implements ServiceContractInterface {
    /**
     * @var EnvironmentDetector
     */
    protected $env;

    /**
     * Compression quality (1-100).
     *
     * @var int
     */
    protected $quality = 82;

    /**
     * Constructor.
     *
     * @param EnvironmentDetector $env
     */
    public function __construct(EnvironmentDetector $env) {
        $this->env = $env;
    }

    /**
     * Check if conversion to WebP format is supported in current PHP environment.
     *
     * @return bool
     */
    public function supportsWebP() {
        if ($this->env->hasExtension('imagick')) {
            return true;
        }
        if ($this->env->hasExtension('gd') && function_exists('imagewebp')) {
            return true;
        }
        return false;
    }

    /**
     * Check if conversion to AVIF format is supported.
     *
     * @return bool
     */
    public function supportsAvif() {
        if ($this->env->hasExtension('gd') && function_exists('imageavif')) {
            return true;
        }
        return false;
    }

    /**
     * Convert an image file on disk to WebP.
     *
     * @param string $sourcePath Path to JPEG/PNG.
     * @param string|null $destPath Destination WebP path (defaults to same name with .webp).
     * @return bool
     */
    public function convertToWebP($sourcePath, $destPath = null) {
        if (!file_exists($sourcePath)) {
            return false;
        }

        if ($destPath === null) {
            $destPath = preg_replace('/\.(jpe?g|png)$/i', '.webp', $sourcePath);
        }

        // 1. Try Imagick
        if ($this->env->hasExtension('imagick') && class_exists('Imagick')) {
            try {
                $img = new \Imagick($sourcePath);
                $img->setImageFormat('webp');
                $img->setImageCompressionQuality($this->quality);
                $img->writeImage($destPath);
                $img->clear();
                $img->destroy();
                return true;
            } catch (\Exception $e) {
                // Fallback to GD
            }
        }

        // 2. Try GD
        if ($this->env->hasExtension('gd') && function_exists('imagewebp')) {
            $info = @getimagesize($sourcePath);
            if (!$info) return false;

            $im = null;
            if ($info[2] === IMAGETYPE_JPEG && function_exists('imagecreatefromjpeg')) {
                $im = @imagecreatefromjpeg($sourcePath);
            } elseif ($info[2] === IMAGETYPE_PNG && function_exists('imagecreatefrompng')) {
                $im = @imagecreatefrompng($sourcePath);
                if ($im) {
                    imagepalettetotruecolor($im);
                    imagealphablending($im, true);
                    imagesavealpha($im, true);
                }
            }

            if ($im) {
                $res = @imagewebp($im, $destPath, $this->quality);
                @imagedestroy($im);
                return (bool) $res;
            }
        }

        return false;
    }
}
