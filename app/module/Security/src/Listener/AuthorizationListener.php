<?php

declare(strict_types=1);

namespace Security\Listener;

use Laminas\Mvc\MvcEvent;
use Laminas\Http\Response;

class AuthorizationListener
{
    /**
     * Listener para verificar autorización en las rutas del módulo Security
     */
    public function onRoute(MvcEvent $event): ?Response
    {
        // Iniciar sesión si no está iniciada
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $request = $event->getRequest();
        $uri = $request->getUri()->getPath();
        $routeMatch = $event->getRouteMatch();

        // Routes públicas que no requieren verificación de permisos
        $publicRoutes = [
            '/auth/login',
            '/auth/logout',
            '/login',
            '/dashboard',
            '/',
        ];

        // Si es ruta pública, permitir acceso
        foreach ($publicRoutes as $route) {
            if (strpos($uri, $route) !== false) {
                return null;
            }
        }

        // Si no es ruta de seguridad, no verificar autorización
        if (!$routeMatch || $routeMatch->getMatchedRouteName() !== 'security') {
            return null;
        }

        // Obtener el perfil del usuario de la sesión
        $usuarioPerfil = $_SESSION['usuario_perfil'] ?? null;

        // Admin puede acceder a todo
        if (strtolower($usuarioPerfil) === 'administrador') {
            return null;
        }

        // Obtener la acción que intenta acceder
        $action = $routeMatch->getParam('action', 'index');

        // Acciones restringidas solo para administrador
        $adminOnlyActions = [
            'perfil',
            'perfil-add',
            'perfil-edit',
            'perfil-delete',
            'perfil-detalle',
            'modulo',
            'modulo-add',
            'modulo-edit',
            'modulo-delete',
            'modulo-detalle',
            'permiso-perfil',
            'permiso-perfil-add',
            'permiso-perfil-edit',
            'permiso-perfil-delete',
            'permiso-perfil-detalle',
        ];

        if (in_array($action, $adminOnlyActions, true)) {
            // Usuario sin permisos para esta acción
            $response = new Response();
            $response->setStatusCode(403);
            $response->getBody()->write('<h1>Acceso Denegado (403)</h1><p>No tienes permisos para acceder a este módulo. Solo administradores pueden acceder.</p>');
            $event->setResponse($response);
            return $response;
        }

        return null;
    }
}
