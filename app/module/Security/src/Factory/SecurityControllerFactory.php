<?php

declare(strict_types=1);

namespace Security\Factory;

use Laminas\Db\Adapter\AdapterInterface;
use Psr\Container\ContainerInterface;
use Security\Controller\SecurityController;
use Security\Model\ModuloModel;
use Security\Model\PerfilModel;
use Security\Model\PermisoPerfilModel;
use Security\Model\SubmoduloModel;
use Security\Model\UsuarioModel;

class SecurityControllerFactory
{
    public function __invoke(ContainerInterface $container): SecurityController
    {
        $db = $container->get(AdapterInterface::class);

        return new SecurityController(
            $db,
            new PerfilModel($db),
            new ModuloModel($db),
            new SubmoduloModel($db),
            new UsuarioModel($db),
            new PermisoPerfilModel($db)
        );
    }
}
