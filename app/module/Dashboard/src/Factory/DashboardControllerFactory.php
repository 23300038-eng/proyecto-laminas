<?php

declare(strict_types=1);

namespace Dashboard\Factory;

use Psr\Container\ContainerInterface;
use Dashboard\Controller\DashboardController;
use Laminas\ServiceManager\Factory\InvokableFactory;

class DashboardControllerFactory
{
    public function __invoke(ContainerInterface $container): DashboardController
    {
        return new DashboardController();
    }
}
