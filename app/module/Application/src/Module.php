<?php

declare(strict_types=1);

namespace Application;

use Laminas\Mvc\MvcEvent;
use Application\Listener\AuthenticationListener;

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
        $authListener = new AuthenticationListener();
        
        // Ejecutar el listener de autenticación después de que se resuelva la ruta
        $eventManager->attach(MvcEvent::EVENT_ROUTE, [$authListener, 'onRoute'], 1000);
    }
}
