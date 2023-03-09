# GSpataro/DependencyInjection

A dependency injection container component made to handle the booting process of your application easier.

---

## Installation

> **Requires [PHP 8.0+]([PHP: Releases](https://www.php.net/releases/))**

To install this package you have to require it with [Composer](https://getcomposer.org):

```
composer require gspataro/dependencyinjection
```

---

## Quick start

The fastest way to use the container is to initialize it and provide the services that you need:

```php
<?php

use GSpataro\DependencyInjection\Container;

$container = new Container();

/**
 * Register a new service
 * 
 * @param string   $tag                The name that will identify this service
 * @param callable $factory            The factory that will create the instance of the class
 * @param bool     $isSingleton = true If true, the service will be instanciated once the first time
 */

$container->add("example", function (Container $c, array $params) {
    return new stdClass();
});

/**
 * Get a service
 * Note: if the service is a singleton, the $params array will be used only the first time this method is called
 * 
 * @param string $tag        The identifier of the service
 * @param array $params = [] An array of arguments used by the factory to instanciate the class
 */

$container->get("example");
```

---

## Container

The Container class is the heart of this component.

### Service dependencies

When defining a service, the Container class and params array are passed to the factory to have access to the class dependencies.

```php
/**
 * Let's assume the class of this service will need the "example" class created in the quick start guide
 * and some other informations
 */

$container->add("second_example", function (Container $c, array $params) {
    return new stdClass($c->get("example"), $params['someinfo']);
});
```

### Variables

Sometimes some configurations or other variables will be needed globally across services. In this case, you can define variables accessible by the container.

```php
/**
 * Set a variable
 * 
 * @param string $key          The name of the variable
 * @param mixed  $value = null The value of the variable (if null, the method will retrieve the value instead of setting it)
 */

$container->variable("foo", "bar");

/**
 * Get a variable
 * 
 * Output: bar
 */

$container->variable("foo");
```

### Direct instance

If you need to instanciate a new class but you don't need to register it as a service, you can directly instanciate it. This is not recomended, but you'll still need it in some cases:

```php
/**
 * Directly instanciate a class
 * 
 * @param callable $factory  The factory that will instanciate the class
 * @param array $params = [] Arguments needed by the factory
 */

Container::instanciate(function (Container $c, array $params) {
    return new stdClass();
});
```

---

## Components

The components are another feature of the container. The issue is: if you define your services one by one, the risk of booting a service before its dependency is higher and you have to remember what you booted first.

To simplify this process, the components are "archives" of services that will be booted in the order you specify.

### Defining a component

```php
<?php

use GSpataro\DependencyInjection\Component;
use GSpataro\DependencyInjection\Container;

/**
 * A component must extend the Component abstract class
 */

final class MyComponent extends Component
{
    /**
     * Register the services that you need
     */

    public function register(): void
    {
        $this->container->add("myservice", function (Container $c, array $params) {
            return new stdClass();
        });
    }

    /**
     * If your services need to be booted with the application, call them inside this method
     * Here you can handle everything your service needs in order to boot
     */

    public function boot(): void
    {
        $this->container->get("myservice");
    }
}
```

### Boot components

To boot your components you need to register them into the container and then call the boot method.

```php
/**
 * Register components
 */

$container->loadComponents([
    MyComponent::class
]);

/**
 * Boot components
 */

$container->boot();
```

Let's assume you have two components: ComponentA and ComponentB. If ComponentA depends on some services provided by ComponentB, you can change the order of them in the loadComponents method:

```php
$container->loadComponents([
    ComponentB::class,
    ComponentA::class
]);
```
