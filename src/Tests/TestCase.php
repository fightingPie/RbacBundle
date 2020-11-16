<?php

namespace SymfonyRbac\Tests;

use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        $this->container = Kernel::make()->getContainer();
    }
}
