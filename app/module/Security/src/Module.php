<?php

declare(strict_types=1);

namespace Security;

use Laminas\Mvc\MvcEvent;
use Security\Listener\AuthorizationListener;

class Module
{
    public function getConfig(): array
    {
        /** @var array $config */
        $config = include __DIR__ . '/../config/module.config.php';
        return $config;
    }

    public function onBootstrap(MvcEvent $e): void
    {
        $eventManager = $e->getApplication()->getEventManager();
        $authListener = new AuthorizationListener();
        
        // Ejecutar el listener de autorización después de que se resuelva la ruta
        // Con prioridad 900 para ejecutarse después de autenticación
        $eventManager->attach(MvcEvent::EVENT_ROUTE, [$authListener, 'onRoute'], 900);
    }
}
