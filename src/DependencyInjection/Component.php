<?php

namespace GSpataro\DependencyInjection;

abstract class Component
{
    /**
     * Initialize Component object
     *
     * @param Container $container
     */

    public function __construct(
        protected Container $container
    ) {
    }

    /**
     * Register services
     *
     * @return void
     */

    abstract public function register(): void;

    /**
     * Boot services
     *
     * @return void
     */

    abstract public function boot(): void;
}
