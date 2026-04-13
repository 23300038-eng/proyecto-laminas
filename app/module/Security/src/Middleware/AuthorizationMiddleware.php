<?php

declare(strict_types=1);

namespace Security\Middleware;

use Laminas\Http\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AuthorizationMiddleware implements MiddlewareInterface
{
    /**
     * Verificar si el usuario tiene permisos para acceder al módulo
     * Si no tiene permisos, retornar error 403 Forbidden
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Iniciar sesión si no está iniciada
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Routes que no requieren verificación de permisos específicos
        $publicRoutes = [
            '/auth/login',
            '/auth/logout',
            '/login',
            '/dashboard',
            '/',
        ];

        $uri = $request->getUri()->getPath();
        
        // Si es ruta pública, permitir acceso
        if ($this->isPublicRoute($uri, $publicRoutes)) {
            return $handler->handle($request);
        }

        // Obtener el módulo que intenta acceder (basado en la ruta)
        $moduloRequerido = $this->getModuloFromRoute($uri);

        // Si el usuario está autenticado pero no es admin, verificar permisos
        if (!empty($_SESSION['usuario_id'])) {
            $usuarioPerfil = $_SESSION['usuario_perfil'] ?? null;
            
            // Admin puede acceder a todo
            if (strtolower($usuarioPerfil) === 'administrador') {
                return $handler->handle($request);
            }

            // Verificar si el usuario tiene permiso para este módulo
            if (!empty($moduloRequerido) && !$this->usuarioTienePermiso($moduloRequerido, $usuarioPerfil)) {
                // Usuario sin permisos
                $response = new Response();
                $response = $response->withStatus(403);
                $response->getBody()->write('Acceso denegado: No tienes permisos para acceder a este módulo.');
                return $response;
            }
        }

        // Si llegó aquí, permitir acceso
        return $handler->handle($request);
    }

    /**
     * Verificar si la ruta es pública
     */
    private function isPublicRoute(string $uri, array $publicRoutes): bool
    {
        foreach ($publicRoutes as $route) {
            if ($uri === $route || strpos($uri, $route) === 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * Extraer el módulo de la ruta
     * Ej: /security/usuario → usuario, /dashboard/principal1 → principal1
     */
    private function getModuloFromRoute(string $uri): ?string
    {
        $parts = array_filter(explode('/', $uri));
        
        if (count($parts) >= 2) {
            // El segundo segmento es el módulo/acción
            return $parts[1] ?? null;
        }
        
        return null;
    }

    /**
     * Verificar si el usuario tiene permiso para acceder al módulo
     * En una implementación real, esto debería consultar la base de datos
     */
    private function usuarioTienePermiso(string $modulo, ?string $perfil): bool
    {
        // Aquí debería implementarse la lógica real de permisos
        // Por ahora, solo los admin y usuarios con perfil específico pueden acceder
        
        $perfilesConAcceso = [
            'usuario' => ['Administrador', 'Usuario'],
            'security' => ['Administrador'],
            'perfil' => ['Administrador'],
            'modulo' => ['Administrador'],
            'permiso-perfil' => ['Administrador'],
        ];

        if (isset($perfilesConAcceso[$modulo])) {
            return in_array($perfil, $perfilesConAcceso[$modulo], true);
        }

        // Si el módulo no está en la lista, permitir por defecto
        return true;
    }
}
