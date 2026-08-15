<?php
namespace ApexSEO\Core\Exceptions;

use Exception;

/**
 * Base Exception for Apex SEO Platform.
 */
class ApexException extends Exception {
    /**
     * Contextual metadata for error diagnostics.
     *
     * @var array
     */
    protected $context = [];

    /**
     * Constructor.
     *
     * @param string $message Exception message.
     * @param int $code Error code.
     * @param Exception|null $previous Previous exception.
     * @param array $context Contextual data.
     */
    public function __construct($message = "", $code = 0, $previous = null, $context = []) {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    /**
     * Get contextual error metadata.
     *
     * @return array
     */
    public function getContext() {
        return $this->context;
    }

    /**
     * Convert exception to a WP_Error instance.
     *
     * @return \WP_Error
     */
    public function toWpError() {
        if (class_exists('\\WP_Error')) {
            return new \WP_Error(
                'apexseo_error_' . $this->getCode(),
                $this->getMessage(),
                array_merge(['code' => $this->getCode()], $this->context)
            );
        }
        return null;
    }
}
