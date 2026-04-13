<?php

declare(strict_types=1);

namespace Application\Listener;

use Laminas\Mvc\MvcEvent;
use Laminas\Http\Response;

class AuthenticationListener
{
    /**
     * Listener para verificar autenticación en todas las rutas
     */
    public function onRoute(MvcEvent $event): ?Response
    {
        // Iniciar sesión si no está iniciada
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $request = $event->getRequest();
        $uri = $request->getUri()->getPath();

        // Routes públicas que no requieren autenticación
        $publicRoutes = [
            '/auth/login',
            '/auth/logout',
            '/login',
        ];

        // Si la ruta es pública, permitir acceso
        foreach ($publicRoutes as $route) {
            if (strpos($uri, $route) !== false) {
                return null;
            }
        }

        // Verificar si existe sesión de usuario
        if (empty($_SESSION['usuario_id']) || empty($_SESSION['usuario_nombre'])) {
            // Usuario no autenticado, redirigir a login
            $response = $event->getResponse();
            $response->setStatusCode(302);
            $response->getHeaders()->addHeaderLine('Location', '/auth/login');
            $event->setResponse($response);
            return $response;
        }

        return null;
    }
}
