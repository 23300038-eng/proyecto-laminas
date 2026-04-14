<?php

declare(strict_types=1);

namespace Security;

use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Mvc\MvcEvent;
use Security\Listener\AuthorizationListener;
use Security\Support\SchemaManager;

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
        $container = $e->getApplication()->getServiceManager();
        $db = $container->get(AdapterInterface::class);

        SchemaManager::ensure($db);

        $listener = new AuthorizationListener($db);
        $eventManager->attach(MvcEvent::EVENT_ROUTE, [$listener, 'onRoute'], 900);
    }
}
