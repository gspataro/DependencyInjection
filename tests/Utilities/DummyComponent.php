<?php

namespace GSpataro\Test\Utilities;

use GSpataro\DependencyInjection\Component;

final class DummyComponent extends Component
{
    public function register(): void
    {
        $this->container->add("test", function ($c): object {
            return new \stdClass();
        });
    }

    public function boot(): void
    {
        print("booted");
    }
}
