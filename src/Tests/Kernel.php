<?php

namespace SymfonyRbac\Tests;

use SymfonyRbac\Tests\Fixtures\app\AppKernel;

class Kernel
{
    /**
     * @var AppKernel
     */
    private static $instance;

    /**
     * @return AppKernel
     */
    public static function make(): AppKernel
    {
        if (null === static::$instance) {
            static::$instance = new AppKernel('test', true);

            static::$instance->boot();
        }

        return static::$instance;
    }
}
