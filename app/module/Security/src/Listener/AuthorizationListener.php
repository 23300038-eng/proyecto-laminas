<?php

declare(strict_types=1);

namespace Security\Listener;

use Laminas\Mvc\MvcEvent;
use Laminas\Http\Response;

class AuthorizationListener
{
    /**
     * Verifica autorización usando bit_administrador y permisos de sesión.
     */
    public function onRoute(MvcEvent $event): ?Response
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $routeMatch = $event->getRouteMatch();
        if (!$routeMatch) {
            return null;
        }

        $routeName = $routeMatch->getMatchedRouteName();

        // Solo verificar rutas de seguridad
        if ($routeName !== 'security') {
            return null;
        }

        // Si el usuario es administrador, acceso total
        if (!empty($_SESSION['bit_administrador'])) {
            return null;
        }

        // Usuario sin sesión: el AuthenticationListener ya lo redirige
        if (empty($_SESSION['usuario_id'])) {
            return null;
        }

        // Usuario no-admin intentando acceder a security: denegar
        $response = $event->getResponse();
        $response->setStatusCode(302);
        $response->getHeaders()->addHeaderLine('Location', '/dashboard');
        $event->setResponse($response);
        return $response;
    }
}
