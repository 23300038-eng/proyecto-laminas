<?php

declare(strict_types=1);

namespace Auth\Middleware;

use Laminas\Http\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AuthenticationMiddleware implements MiddlewareInterface
{
    /**
     * Verificar si el usuario está autenticado
     * Si no lo está, redirigir a la página de login
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Iniciar sesión si no está iniciada
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Obtener la ruta actual
        $uri = $request->getUri()->getPath();
        
        // Routes públicas que no requieren autenticación
        $publicRoutes = [
            '/auth/login',
            '/auth/logout',
            '/login',
        ];

        // Si la ruta es pública, permitir acceso
        if ($this->isPublicRoute($uri, $publicRoutes)) {
            return $handler->handle($request);
        }

        // Verificar si existe sesión de usuario
        if (empty($_SESSION['usuario_id']) || empty($_SESSION['usuario_nombre'])) {
            // Usuario no autenticado, redirigir a login
            $response = new Response();
            $response = $response->withStatus(302);
            $response = $response->withHeader('Location', '/auth/login');
            return $response;
        }

        // Usuario autenticado, continuar con la solicitud
        return $handler->handle($request);
    }

    /**
     * Verificar si la ruta es pública
     */
    private function isPublicRoute(string $uri, array $publicRoutes): bool
    {
        foreach ($publicRoutes as $route) {
            if (strpos($uri, $route) !== false) {
                return true;
            }
        }
        return false;
    }
}
