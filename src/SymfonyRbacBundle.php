<?php
declare(strict_types=1);


namespace SymfonyRbac;


use Symfony\Component\HttpKernel\Bundle\Bundle;
use SymfonyRbac\DependencyInjection\SymfonyRbacExtension;

class SymfonyRbacBundle extends Bundle
{
    public function getContainerExtension()
    {
        if (null === $this->extension) {
            $this->extension = new SymfonyRbacExtension();
        }

        return $this->extension;
    }
}