<?php

namespace SymfonyRbac\Tests\Fixtures\app;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Knp\Bundle\PaginatorBundle\KnpPaginatorBundle;
use SymfonyRbac\SymfonyRbacBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\Kernel;

/**
 * Class AppKernel
 * @package SymfonyRbac\Tests\Fixtures\app
 */
class AppKernel extends Kernel
{
    /**
     * AppKernel constructor.
     * @param $environment
     * @param $debug
     */
    public function __construct($environment = 'test', $debug = true)
    {
        parent::__construct($environment, $debug);

        (new Filesystem())->remove($this->getCacheDir());
    }

    /**
     * @return array|iterable|BundleInterface[]
     */
    public function registerBundles()
    {
        return [
            new FrameworkBundle(),
            new DoctrineBundle(),
            new KnpPaginatorBundle(),
            new SymfonyRbacBundle(),
        ];
    }

    /**
     * @return string
     */
    public function getRootDir()
    {
        return __DIR__;
    }

    /**
     * @return string
     */
    public function getCacheDir()
    {
        return '/tmp/symfony-cache';
    }

    /**
     * @return string
     */
    public function getLogDir()
    {
        return '/tmp/symfony-cache';
    }

    /**
     * @param LoaderInterface $loader
     * @throws \Exception
     */
    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        /**
         * 加载test环境测试配置
         */
        $res = $this->getRootDir() . '/config/config_test.yml';
        $loader->load($res);

        /**
         * 加载数据库配置信息
         */
        $doctrine = $this->getRootDir() . '/config/doctrine.yaml';
        $loader->load($doctrine);

        /**
         * 加载路由配置
         */
        $routing = $this->getRootDir() . '/config/routing.yaml';
        $loader->load($routing);


        /**
         * 加载分页配置
         */
        $paginator = $this->getRootDir() . '/config/knp_paginator.yaml';
        $loader->load($paginator);
    }

}
