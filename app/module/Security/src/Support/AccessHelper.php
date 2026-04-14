<?php

declare(strict_types=1);

namespace Security\Support;

use Laminas\Db\Adapter\AdapterInterface;

class AccessHelper
{
    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function defaultBits(): array
    {
        return [
            'agregar' => false,
            'editar' => false,
            'consulta' => false,
            'eliminar' => false,
            'detalle' => false,
        ];
    }

    public static function normalizeBits(?array $bits): array
    {
        $bits = is_array($bits) ? $bits : [];
        return [
            'agregar' => !empty($bits['agregar']) || !empty($bits['bit_agregar']),
            'editar' => !empty($bits['editar']) || !empty($bits['bit_editar']),
            'consulta' => !empty($bits['consulta']) || !empty($bits['bit_consulta']),
            'eliminar' => !empty($bits['eliminar']) || !empty($bits['bit_eliminar']),
            'detalle' => !empty($bits['detalle']) || !empty($bits['bit_detalle']),
        ];
    }

    public static function isAuthenticated(): bool
    {
        self::startSession();
        return !empty($_SESSION['usuario_id']) && !empty($_SESSION['usuario_nombre']);
    }

    public static function isAdmin(): bool
    {
        self::startSession();
        return !empty($_SESSION['es_admin']) || !empty($_SESSION['bit_administrador']);
    }

    public static function getSessionModulePermissions(): array
    {
        self::startSession();
        return is_array($_SESSION['permisos_modulos'] ?? null) ? $_SESSION['permisos_modulos'] : [];
    }

    public static function getSessionSubmodulePermissions(): array
    {
        self::startSession();
        return is_array($_SESSION['permisos_submodulos'] ?? null) ? $_SESSION['permisos_submodulos'] : [];
    }

    public static function hasAnyPermission(array $bits): bool
    {
        $bits = self::normalizeBits($bits);
        foreach ($bits as $value) {
            if ($value) {
                return true;
            }
        }
        return false;
    }

    public static function hasModulePermissionById(int $moduleId, string $permission = 'consulta'): bool
    {
        if (self::isAdmin()) {
            return true;
        }

        $bits = self::normalizeBits(self::getSessionModulePermissions()[$moduleId] ?? []);

        if ($permission === 'consulta') {
            return self::hasAnyPermission($bits);
        }

        if ($permission === 'detalle') {
            return !empty($bits['detalle']) || !empty($bits['consulta']);
        }

        return !empty($bits[$permission]);
    }

    public static function hasSubmodulePermissionById(int $submoduleId, string $permission = 'consulta'): bool
    {
        if (self::isAdmin()) {
            return true;
        }

        $bits = self::normalizeBits(self::getSessionSubmodulePermissions()[$submoduleId] ?? []);

        if ($permission === 'consulta') {
            return self::hasAnyPermission($bits);
        }

        if ($permission === 'detalle') {
            return !empty($bits['detalle']) || !empty($bits['consulta']);
        }

        return !empty($bits[$permission]);
    }

    public static function getModulePermissionsById(int $moduleId): array
    {
        if (self::isAdmin()) {
            return [
                'agregar' => true,
                'editar' => true,
                'consulta' => true,
                'eliminar' => true,
                'detalle' => true,
            ];
        }

        return self::normalizeBits(self::getSessionModulePermissions()[$moduleId] ?? []);
    }

    public static function getSubmodulePermissionsById(int $submoduleId): array
    {
        if (self::isAdmin()) {
            return [
                'agregar' => true,
                'editar' => true,
                'consulta' => true,
                'eliminar' => true,
                'detalle' => true,
            ];
        }

        return self::normalizeBits(self::getSessionSubmodulePermissions()[$submoduleId] ?? []);
    }

    public static function getModuleIdByName(AdapterInterface $db, string $moduleName): ?int
    {
        try {
            $row = $db->query(
                'SELECT id FROM modulo WHERE LOWER(str_nombre_modulo) = LOWER(?) LIMIT 1',
                [$moduleName]
            )->current();

            return $row ? (int) $row['id'] : null;
        } catch (\Throwable $exception) {
            return null;
        }
    }

    public static function slugify(string $text): string
    {
        $text = trim(mb_strtolower($text));
        $map = [
            'á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a',
            'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e',
            'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o',
            'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u',
            'ñ' => 'n',
        ];
        $text = strtr($text, $map);
        $text = preg_replace('/[^a-z0-9]+/u', '-', $text) ?? '';
        $text = trim($text, '-');
        return $text !== '' ? $text : 'item';
    }

    public static function resolveModuleLandingUrl(string $moduleName, array $items): string
    {
        $normalizedName = mb_strtolower(trim($moduleName));

        if ($normalizedName === 'seguridad') {
            return $items[0]['url'] ?? '/security/perfil';
        }

        if ($normalizedName === 'panel de control') {
            return '/dashboard';
        }

        return '/modulos/' . self::slugify($moduleName);
    }

    public static function normalizeSubmoduleRoute(string $moduleName, string $submoduleName, ?string $route): string
    {
        $route = trim((string) $route);

        if ($route !== '') {
            $route = '/' . ltrim($route, '/');
            $route = preg_replace('#/+#', '/', $route) ?: $route;

            if ($route === '/security/permisos-perfil') {
                return '/security/permiso-perfil';
            }
            return $route;
        }

        if (mb_strtolower($moduleName) === 'seguridad') {
            $normalized = self::slugify($submoduleName);
            return match ($normalized) {
                'perfil' => '/security/perfil',
                'modulo' => '/security/modulo',
                'submodulo' => '/security/submodulo',
                'permisos-perfil', 'permiso-perfil' => '/security/permiso-perfil',
                'usuario' => '/security/usuario',
                default => '/security/' . $normalized,
            };
        }

        return '/modulos/' . self::slugify($moduleName) . '/' . self::slugify($submoduleName);
    }

    public static function requiredPermissionForPath(string $path): string
    {
        $path = mb_strtolower(rtrim($path, '/'));

        foreach ([
            '-add' => 'agregar',
            '/add' => 'agregar',
            '-edit' => 'editar',
            '/edit' => 'editar',
            '-delete' => 'eliminar',
            '/delete' => 'eliminar',
            '-detalle' => 'detalle',
            '/detalle' => 'detalle',
        ] as $fragment => $permission) {
            if (str_contains($path, $fragment)) {
                return $permission;
            }
        }

        return 'consulta';
    }


    public static function hasAnyAccessibleSubmoduleForModule(AdapterInterface $db, int $moduleId): bool
    {
        if (self::isAdmin()) {
            return true;
        }

        try {
            $rows = $db->query(
                'SELECT id FROM submodulo WHERE id_modulo = ? AND COALESCE(bit_activo, true) = true',
                [$moduleId]
            );

            foreach ($rows as $row) {
                if (self::hasSubmodulePermissionById((int) $row['id'], 'consulta')) {
                    return true;
                }
            }
        } catch (\Throwable $exception) {
            return false;
        }

        return false;
    }

    public static function resolveRouteResource(AdapterInterface $db, string $path): array
    {
        $path = '/' . ltrim(rtrim($path, '/'), '/');
        if ($path === '//') {
            $path = '/';
        }

        if (in_array($path, ['/auth/login', '/auth/logout', '/login'], true)) {
            return ['type' => 'public'];
        }

        if ($path === '/' || $path === '/mi-perfil' || str_starts_with($path, '/application')) {
            return ['type' => 'public-auth'];
        }

        if ($path === '/dashboard' || str_starts_with($path, '/dashboard/')) {
            $dashboardModuleId = self::getModuleIdByName($db, 'Panel de Control');
            if ($dashboardModuleId !== null) {
                return ['type' => 'module', 'id' => $dashboardModuleId];
            }

            return ['type' => 'dashboard'];
        }

        try {
            $submodules = $db->query(
                'SELECT s.id, s.id_modulo, s.str_ruta
                 FROM submodulo s
                 INNER JOIN modulo m ON m.id = s.id_modulo
                 WHERE COALESCE(s.bit_activo, true) = true
                   AND COALESCE(m.bit_activo, true) = true',
                []
            );

            $bestMatch = null;
            $bestLength = -1;

            foreach ($submodules as $row) {
                $route = '/' . ltrim((string) ($row['str_ruta'] ?? ''), '/');
                $route = rtrim($route, '/');
                if ($route === '') {
                    continue;
                }

                if ($path === $route || str_starts_with($path, $route . '/')) {
                    if (mb_strlen($route) > $bestLength) {
                        $bestLength = mb_strlen($route);
                        $bestMatch = $row;
                    }
                }
            }

            if ($bestMatch) {
                return [
                    'type' => 'submodule',
                    'id' => (int) $bestMatch['id'],
                    'module_id' => (int) $bestMatch['id_modulo'],
                ];
            }
        } catch (\Throwable $exception) {
            // Continuar con resolución dinámica.
        }

        if (str_starts_with($path, '/modulos/')) {
            $parts = array_values(array_filter(explode('/', trim($path, '/'))));
            $moduleSlug = $parts[1] ?? '';
            $submoduleSlug = $parts[2] ?? '';

            try {
                $rows = $db->query(
                    'SELECT 
                        m.id AS modulo_id,
                        m.str_nombre_modulo,
                        s.id AS submodulo_id,
                        s.str_nombre_submodulo
                     FROM modulo m
                     LEFT JOIN submodulo s ON s.id_modulo = m.id AND COALESCE(s.bit_activo, true) = true
                     WHERE COALESCE(m.bit_activo, true) = true',
                    []
                );

                $matchedModule = null;
                $matchedSubmodule = null;

                foreach ($rows as $row) {
                    if (self::slugify((string) $row['str_nombre_modulo']) === $moduleSlug) {
                        $matchedModule = $row;
                        if ($submoduleSlug !== '' && !empty($row['submodulo_id']) && self::slugify((string) $row['str_nombre_submodulo']) === $submoduleSlug) {
                            $matchedSubmodule = $row;
                            break;
                        }
                    }
                }

                if ($matchedSubmodule) {
                    return [
                        'type' => 'submodule',
                        'id' => (int) $matchedSubmodule['submodulo_id'],
                        'module_id' => (int) $matchedSubmodule['modulo_id'],
                    ];
                }

                if ($matchedModule) {
                    return [
                        'type' => 'module',
                        'id' => (int) $matchedModule['modulo_id'],
                    ];
                }
            } catch (\Throwable $exception) {
                return ['type' => 'unknown'];
            }
        }

        if (str_starts_with($path, '/security')) {
            $moduleId = self::getModuleIdByName($db, 'Seguridad');
            return $moduleId ? ['type' => 'module', 'id' => $moduleId] : ['type' => 'unknown'];
        }

        return ['type' => 'unknown'];
    }

    public static function isPathAllowed(AdapterInterface $db, string $path): bool
    {
        $resource = self::resolveRouteResource($db, $path);

        if (($resource['type'] ?? '') === 'public') {
            return true;
        }

        if (!self::isAuthenticated()) {
            return false;
        }

        if (($resource['type'] ?? '') === 'public-auth') {
            return true;
        }

        if (($resource['type'] ?? '') === 'dashboard') {
            return self::isAdmin();
        }

        $permission = self::requiredPermissionForPath($path);

        if (($resource['type'] ?? '') === 'submodule') {
            return self::hasSubmodulePermissionById((int) $resource['id'], $permission);
        }

        if (($resource['type'] ?? '') === 'module') {
            return self::hasModulePermissionById((int) $resource['id'], $permission)
                || ($permission === 'consulta' && self::hasAnyAccessibleSubmoduleForModule($db, (int) $resource['id']));
        }

        return true;
    }

    public static function getPathPermissions(AdapterInterface $db, string $path): array
    {
        $resource = self::resolveRouteResource($db, $path);

        if (($resource['type'] ?? '') === 'submodule') {
            return self::getSubmodulePermissionsById((int) $resource['id']);
        }

        if (($resource['type'] ?? '') === 'module') {
            return self::getModulePermissionsById((int) $resource['id']);
        }

        return self::defaultBits();
    }

    public static function buildMenu(AdapterInterface $db): array
    {
        self::startSession();

        try {
            $moduleRows = $db->query(
                'SELECT id, str_nombre_modulo, str_icono, int_orden
                 FROM modulo
                 WHERE COALESCE(bit_activo, true) = true
                 ORDER BY int_orden ASC, str_nombre_modulo ASC',
                []
            );

            $submoduleRows = $db->query(
                'SELECT id, id_modulo, str_nombre_submodulo, str_ruta, int_orden
                 FROM submodulo
                 WHERE COALESCE(bit_activo, true) = true
                 ORDER BY int_orden ASC, str_nombre_submodulo ASC',
                []
            );

            $submodulesByModule = [];
            foreach ($submoduleRows as $row) {
                $submodulesByModule[(int) $row['id_modulo']][] = $row;
            }

            $menu = [];
            foreach ($moduleRows as $module) {
                $moduleId = (int) $module['id'];
                $moduleName = (string) ($module['str_nombre_modulo'] ?? '');
                if ($moduleName === '') {
                    continue;
                }

                $items = [];

                foreach ($submodulesByModule[$moduleId] ?? [] as $submodule) {
                    $subId = (int) ($submodule['id'] ?? 0);
                    if ($subId <= 0 || !self::hasSubmodulePermissionById($subId, 'consulta')) {
                        continue;
                    }

                    $items[] = [
                        'id' => $subId,
                        'nombre' => (string) ($submodule['str_nombre_submodulo'] ?? 'Submódulo'),
                        'url' => self::normalizeSubmoduleRoute(
                            $moduleName,
                            (string) ($submodule['str_nombre_submodulo'] ?? ''),
                            (string) ($submodule['str_ruta'] ?? '')
                        ),
                    ];
                }

                $canSeeModule = self::hasModulePermissionById($moduleId, 'consulta') || !empty($items);
                if (!$canSeeModule) {
                    continue;
                }

                $menu[] = [
                    'id' => $moduleId,
                    'nombre' => $moduleName,
                    'icono' => (string) ($module['str_icono'] ?? ''),
                    'url' => !empty($items) && mb_strtolower($moduleName) === 'seguridad'
                        ? ($items[0]['url'] ?? '/security/perfil')
                        : self::resolveModuleLandingUrl($moduleName, $items),
                    'items' => $items,
                ];
            }

            $_SESSION['menu_modulos_cache'] = $menu;
            return $menu;
        } catch (\Throwable $exception) {
            return is_array($_SESSION['menu_modulos_cache'] ?? null) ? $_SESSION['menu_modulos_cache'] : [];
        }
    }
}
