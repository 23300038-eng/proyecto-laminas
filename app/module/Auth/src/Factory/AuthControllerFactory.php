<?php

declare(strict_types=1);

namespace Auth\Factory;

use Auth\Controller\AuthController;
use Laminas\Db\Adapter\AdapterInterface;
use Psr\Container\ContainerInterface;

class AuthControllerFactory
{
    public function __invoke(ContainerInterface $container): AuthController
    {
        $db = $container->get(AdapterInterface::class);
        $config = $container->get('config');

        return new AuthController(
            $db,
            $config['hcaptcha'] ?? []
        );
    }
}
