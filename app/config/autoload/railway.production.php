<?php
/**
 * Configuration para Railway (Production)
 * Desactiva caches que requieren permisos de escritura
 */

return [
    'module_listener_options' => [
        'config_cache_enabled' => false,
        'module_map_cache_enabled' => false,
    ],
];
