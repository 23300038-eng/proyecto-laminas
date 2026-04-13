<?php

declare(strict_types=1);

namespace Dashboard\Factory;

use Psr\Container\ContainerInterface;
use Dashboard\Controller\DashboardController;
use Laminas\Db\Adapter\AdapterInterface;

class DashboardControllerFactory
{
    public function __invoke(ContainerInterface $container): DashboardController
    {
        $db = $container->get(AdapterInterface::class);
        return new DashboardController($db);
    }
}
