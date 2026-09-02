<?php
namespace ApexSEO\Tests;

use ApexSEO\Core\Container\Container;
use ApexSEO\Core\Exceptions\ContainerException;

class DummyDependency {}

class DummyService {
    public $dep;
    public function __construct(DummyDependency $dep) {
        $this->dep = $dep;
    }
}

class CircularA {
    public function __construct(CircularB $b) {}
}

class CircularB {
    public function __construct(CircularA $a) {}
}

/**
 * Service Container and Dependency Injection Test.
 */
class ContainerTest extends TestCase {
    /**
     * @var Container
     */
    protected $container;

    protected function setUp(): void {
        parent::setUp();
        $this->container = new Container();
    }

    public function testSingletonBinding() {
        $this->container->singleton(DummyDependency::class, function() {
            return new DummyDependency();
        });

        $inst1 = $this->container->get(DummyDependency::class);
        $inst2 = $this->container->get(DummyDependency::class);

        $this->assertInstanceOf(DummyDependency::class, $inst1);
        $this->assertSame($inst1, $inst2);
    }

    public function testTransientFactoryBinding() {
        $this->container->factory(DummyDependency::class, function() {
            return new DummyDependency();
        });

        $inst1 = $this->container->get(DummyDependency::class);
        $inst2 = $this->container->get(DummyDependency::class);

        $this->assertInstanceOf(DummyDependency::class, $inst1);
        $this->assertFalse($inst1 === $inst2);
    }

    public function testLazyLoadingResolution() {
        $called = false;
        $this->container->lazy('test_lazy', function() use (&$called) {
            $called = true;
            return new \stdClass();
        });

        $this->assertFalse($called);

        $result = $this->container->get('test_lazy');
        $this->assertTrue($called);
        $this->assertInstanceOf(\stdClass::class, $result);
    }

    public function testAliasResolution() {
        $this->container->singleton('original_service', function() {
            return new \stdClass();
        });
        $this->container->alias('aliased_service', 'original_service');

        $orig = $this->container->get('original_service');
        $alias = $this->container->get('aliased_service');

        $this->assertSame($orig, $alias);
    }

    public function testAutoWiringDependencies() {
        $service = $this->container->get(DummyService::class);
        $this->assertInstanceOf(DummyService::class, $service);
        $this->assertInstanceOf(DummyDependency::class, $service->dep);
    }

    public function testCircularDependencyDetection() {
        $this->expectException(ContainerException::class);
        $this->container->get(CircularA::class);
    }
}
