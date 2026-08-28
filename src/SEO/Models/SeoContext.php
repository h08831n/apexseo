<?php
namespace ApexSEO\SEO\Models;

class SeoContext {
    private $data = [];

    public function __construct(array $data = []) {
        $this->data = $data;
    }

    public function get(string $key, $default = null) {
        return $this->data[$key] ?? $default;
    }

    public function set(string $key, $value): void {
        $this->data[$key] = $value;
    }

    public function all(): array {
        return $this->data;
    }
}
