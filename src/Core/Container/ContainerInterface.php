<?php
namespace ApexSEO\Core\Container;

interface ContainerInterface {
    public function get(string $id);
    public function has(string $id): bool;
    public function set(string $id, $concrete): void;
    public function singleton(string $id, $concrete): void;
    public function factory(string $id, $concrete): void;
    public function lazy(string $id, callable $factory): void;
    public function alias(string $alias, string $target): void;
}
