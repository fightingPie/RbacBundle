<?php

namespace SymfonyRbac\Tests\DependencyInjection;

use SymfonyRbac\DependencyInjection\SymfonyRbacExtension;
use SymfonyRbac\Tests\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;

class SymfonyRbacExtensionTest extends TestCase {
  public function testHasServices(): void {
    $extension = new SymfonyRbacExtension();
    $container = new ContainerBuilder();

    $this->assertInstanceOf(Extension::class, $extension);

    $extension->load([] , $container);
    $this->assertTrue($container->has(''));
  }

  public function testAlias(): void {
    $extension = new SymfonyRbacExtension();

    $this->assertStringEndsWith('rbac', $extension->getAlias());
  }
}
