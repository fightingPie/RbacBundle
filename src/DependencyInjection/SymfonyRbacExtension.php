<?php
declare(strict_types=1);


namespace SymfonyRbac\DependencyInjection;


use SymfonyRbac\Utils\CircularReferenceHandler;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * Class SymfonyRbacExtension
 * @package SymfonyRbac\DependencyInjection
 */
class SymfonyRbacExtension extends Extension implements PrependExtensionInterface
{

    /**
     * @var string
     */
    private $bundleAlias = 'symfony_rbac';

    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');
    }

    /**
     * @param ContainerBuilder $container
     */
    public function prepend(ContainerBuilder $container)
    {
        $bundles = $container->getParameter('kernel.bundles');
        if (isset($bundles['FrameworkBundle'])) {
            $config = ['serializer' => ['circular_reference_handler' => CircularReferenceHandler::class]];
            foreach ($container->getExtensions() as $name => $extension) {
                switch ($name) {
                    case 'framework':
                        $container->prependExtensionConfig($name, $config);
                        break;
                }
            }
        }
    }

    public function getAlias()
    {
        return $this->bundleAlias;
    }
}