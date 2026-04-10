<?php

declare(strict_types=1);

namespace Security\Factory;

use Psr\Container\ContainerInterface;
use Security\Controller\SecurityController;
use Laminas\Db\Adapter\AdapterInterface;
use Security\Model\PerfilModel;
use Security\Model\ModuloModel;
use Security\Model\UsuarioModel;
use Security\Model\PermisoPerfilModel;

class SecurityControllerFactory
{
    public function __invoke(ContainerInterface $container): SecurityController
    {
        $db = $container->get(AdapterInterface::class);
        
        return new SecurityController(
            $db,
            new PerfilModel($db),
            new ModuloModel($db),
            new UsuarioModel($db),
            new PermisoPerfilModel($db)
        );
    }
}
