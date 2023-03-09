<?php

namespace GSpataro\Test;

use ReflectionClass;
use PHPUnit\Framework\TestCase;
use GSpataro\DependencyInjection;

final class ContainerTest extends TestCase
{
    /**
     * Get an instance of Container
     *
     * @return DependencyInjection\Container
     */

    public function getContainer(): DependencyInjection\Container
    {
        return new DependencyInjection\Container();
    }

    /**
     * Read a private property from an object
     *
     * @param string $property
     * @param object $object
     * @return mixed
     */

    public function readProperty(string $property, object $object): mixed
    {
        $reflectionClass = new ReflectionClass($object);
        $reflectionProperty = $reflectionClass->getProperty($property);
        $reflectionProperty->setAccessible(true);

        return $reflectionProperty->getValue($object);
    }

    /**
     * Set a private property of an object
     *
     * @param string $property
     * @param mixed $value
     * @param object $object
     * @return void
     */

    public function setProperty(string $property, mixed $value, object $object): void
    {
        $reflectionClass = new ReflectionClass($object);
        $reflectionProperty = $reflectionClass->getProperty($property);
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($object, $value);
    }

    /**
     * @testdox Test Container::has() method
     * @covers Container::has
     * @return void
     */

    public function testHas(): void
    {
        $container = $this->getContainer();

        $this->assertFalse($container->has("test"));
        $this->setProperty("services", ["test" => ""], $container);
        $this->assertTrue($container->has("test"));
    }

    /**
     * @testdox Test Container::add() method
     * @covers Container::add
     * @return void
     */

    public function testAdd(): void
    {
        $container = $this->getContainer();
        $services = $this->readProperty("services", $container);

        $this->assertFalse(isset($services['test']));

        $factory = function ($c): object {
            return new \stdClass();
        };

        $container->add("test", $factory, false);

        $services = $this->readProperty("services", $container);
        $this->assertTrue(isset($services['test']));
        $this->assertEquals([
            "test" => [
                "factory" => $factory,
                "singleton" => false
            ]
        ], $services);
    }

    /**
     * @testdox Test Container::add() method with already existing service
     * @covers Container::add
     * @return void
     */

    public function testAddWithExistingService(): void
    {
        $this->expectException(DependencyInjection\Exception\ServiceFoundException::class);

        $container = $this->getContainer();
        $this->setProperty("services", ["test" => ""], $container);

        $container->add("test", function ($c): object {
            return new \stdClass();
        });
    }

    /**
     * @testdox Test Container::add() method with invalid factory return type
     * @covers Container::add
     * @return void
     */

    public function testAddWithInvalidFactoryReturnType(): void
    {
        $this->expectException(DependencyInjection\Exception\InvalidFactoryReturnTypeException::class);

        $container = $this->getContainer();
        $container->add("test", function ($c): void {
        });
    }

    /**
     * @testdox Test Container::get() method
     * @covers Container::get
     * @return void
     */

    public function testGet(): void
    {
        $container = $this->getContainer();
        $this->setProperty("services", [
            "test" => [
                "factory" => function ($c): object {
                    return new \stdClass();
                },
                "singleton" => true
            ]
        ], $container);

        $this->assertInstanceOf(\stdClass::class, $container->get("test"));
    }

    /**
     * @testdox Test Container::get() and make sure a singleton is instanciated once
     * @covers Container::get
     * @return void
     */

    public function testGetSingleton(): void
    {
        $container = $this->getContainer();
        $this->setProperty("services", [
            "test" => [
                "factory" => function ($c): object {
                    return new \stdClass();
                },
                "singleton" => true
            ]
        ], $container);

        $this->assertInstanceOf(\stdClass::class, $container->get("test"));
        $this->assertSame($container->get("test"), $container->get("test"));
    }

    /**
     * @testdox Test Container::get() and make sure a non singleton is instanciated every time
     * @covers Container::get
     * @return void
     */

    public function testGetNonSingleton(): void
    {
        $container = $this->getContainer();
        $this->setProperty("services", [
            "test" => [
                "factory" => function ($c): object {
                    return new \stdClass();
                },
                "singleton" => false
            ]
        ], $container);

        $this->assertInstanceOf(\stdClass::class, $container->get("test"));
        $this->assertNotSame($container->get("test"), $container->get("test"));
    }

    /**
     * @testdox Test Container::instanciate() method
     * @covers Container::instanciate
     * @return void
     */

    public function testInstanciate(): void
    {
        $container = $this->getContainer();
        $this->setProperty("services", [
            "test" => [
                "factory" => function ($c): object {
                    return new \stdClass();
                },
                "singleton" => true
            ]
        ], $container);

        $stdClass = DependencyInjection\Container::instanciate(function ($c): object {
            if ($c->get("test") instanceof \stdClass) {
                echo "success";
            }

            return new \stdClass();
        });

        $this->expectOutputString("success");
        $this->assertInstanceOf(\stdClass::class, $stdClass);
    }

    /**
     * @testdox Test Container::instanciate() method with invalid factory return type
     * @covers Container::instanciate
     * @return void
     */

    public function testInstanciateWithInvalidReturnType(): void
    {
        $this->expectException(DependencyInjection\Exception\InvalidFactoryReturnTypeException::class);
        $container = $this->getContainer();

        DependencyInjection\Container::instanciate(function ($c): void {
        });
    }

    /**
     * @testdox Test Container::variable() method to set a variable
     * @covers Container::variable
     * @return void
     */

    public function testVariableSet(): void
    {
        $container = $this->getContainer();

        $variables = $this->readProperty("variables", $container);
        $this->assertFalse(isset($variables['test']));

        $container->variable("foo", "bar");

        $variables = $this->readProperty("variables", $container);
        $this->assertTrue(isset($variables['foo']));
        $this->assertEquals("bar", $variables['foo']);
    }

    /**
     * @testdox Test Container::variable() method to get a variable
     * @covers Container::variable
     * @return void
     */

    public function testVariableGet(): void
    {
        $container = $this->getContainer();
        $this->setProperty("variables", [
            "foo" => "bar"
        ], $container);

        $this->assertEquals("bar", $container->variable("foo"));
        $this->assertNull($container->variable("nonexisting"));
    }

    /**
     * @testdox Test Container::loadComponents() method
     * @covers Container::loadComponents
     * @return void
     */

    public function testLoadComponents(): void
    {
        $container = $this->getContainer();
        $container->loadComponents([
            \GSpataro\Test\Utilities\DummyComponent::class
        ]);

        $services = $this->readProperty("services", $container);
        $this->assertTrue(isset($services['test']));
    }

    /**
     * @testdox Test Container::loadComponents() method with object given
     * @covers Container::loadComponents
     * @return void
     */

    public function testLoadComponentsWithObjectGiven(): void
    {
        $this->expectException(DependencyInjection\Exception\InvalidComponentException::class);

        $container = $this->getContainer();
        $container->loadComponents([
            new \GSpataro\Test\Utilities\DummyComponent($container)
        ]);
    }

    /**
     * @testdox Test Container::loadComponents() method with non existing class
     * @covers Container::loadComponents
     * @return void
     */

    public function testLoadComponentsWithNonExistingClass(): void
    {
        $this->expectException(DependencyInjection\Exception\ComponentNotFoundException::class);

        $container = $this->getContainer();
        $container->loadComponents([
            nonexisting::class
        ]);
    }

    /**
     * @testdox Test Container::loadComponents() method with invalid component parent
     * @covers Container::loadComponents
     * @return void
     */

    public function testLoadComponentsWithInvalidComponentParent(): void
    {
        $this->expectException(DependencyInjection\Exception\InvalidComponentException::class);

        $container = $this->getContainer();
        $container->loadComponents([
            \GSpataro\Test\Utilities\DummyInvalidComponent::class
        ]);
    }

    /**
     * @testdox Test Container::boot() method
     * @covers Container::boot
     * @return void
     */

    public function testBoot(): void
    {
        $container = $this->getContainer();
        $this->setProperty("components", [
            new \GSpataro\Test\Utilities\DummyComponent($container)
        ], $container);

        $container->boot();

        $this->expectOutputString("booted");
    }
}
