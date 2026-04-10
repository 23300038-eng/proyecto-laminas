<?php

declare(strict_types=1);

namespace Auth\Factory;

use Psr\Container\ContainerInterface;
use Auth\Controller\AuthController;
use Laminas\Db\Adapter\AdapterInterface;

class AuthControllerFactory
{
    public function __invoke(ContainerInterface $container): AuthController
    {
        $db = $container->get(AdapterInterface::class);
        return new AuthController($db);
    }
}
