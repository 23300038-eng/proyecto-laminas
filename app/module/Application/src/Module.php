<?php

declare(strict_types=1);

namespace Application;

use Application\Listener\AuthenticationListener;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Mvc\MvcEvent;
use Security\Support\AccessHelper;

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
        $application = $e->getApplication();
        $eventManager = $application->getEventManager();
        $serviceManager = $application->getServiceManager();

        $authListener = new AuthenticationListener();
        $eventManager->attach(MvcEvent::EVENT_ROUTE, [$authListener, 'onRoute'], 1000);

        $eventManager->attach(MvcEvent::EVENT_RENDER, function (MvcEvent $event) use ($serviceManager): void {
            $viewModel = $event->getViewModel();
            if (!$viewModel) {
                return;
            }

            $routeName = $event->getRouteMatch() ? (string) $event->getRouteMatch()->getMatchedRouteName() : '';
            if (in_array($routeName, ['auth', 'auth-login-fallback', 'auth-logout-fallback', 'auth-root-fallback'], true)) {
                return;
            }

            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            if (!$serviceManager->has(AdapterInterface::class)) {
                return;
            }

            try {
                /** @var AdapterInterface $db */
                $db = $serviceManager->get(AdapterInterface::class);
                $menu = AccessHelper::buildMenu($db);
                $viewModel->setVariable('menu_modulos', $menu);
                $viewModel->setVariable('modulos', $menu);
            } catch (\Throwable $exception) {
                if (!isset($_SESSION['menu_modulos_cache']) || !is_array($_SESSION['menu_modulos_cache'])) {
                    $_SESSION['menu_modulos_cache'] = [];
                }
            }
        }, -100);
    }
}
