<?php
/**
 * Health Check Endpoint para Railway
 * Verifica que la aplicación esté funcionando correctamente
 */

header('Content-Type: application/json');

$health = [
    'status' => 'OK',
    'timestamp' => date('Y-m-d H:i:s'),
    'checks' => []
];

// Verificar PHP
$health['checks']['php'] = [
    'version' => phpversion(),
    'status' => 'OK'
];

// Verificar extensiones críticas
$required_extensions = ['pdo', 'pdo_pgsql', 'json'];
foreach ($required_extensions as $ext) {
    if (!extension_loaded($ext)) {
        $health['status'] = 'ERROR';
        $health['checks']['extensions'][$ext] = 'MISSING';
    }
}

if (!isset($health['checks']['extensions'])) {
    $health['checks']['extensions'] = [];
    foreach ($required_extensions as $ext) {
        $health['checks']['extensions'][$ext] = 'OK';
    }
}

// Verificar directorios de escritura
$writeable_dirs = [
    '/var/www/html/app/data/cache' => 'cache_dir',
    '/var/www/html/public/uploads' => 'uploads_dir'
];

foreach ($writeable_dirs as $dir => $key) {
    if (is_writable($dir)) {
        $health['checks'][$key] = 'OK';
    } else {
        $health['checks'][$key] = 'NOT_WRITABLE';
        $health['status'] = 'WARNING';
    }
}

// Verificar conexión a BD si están configuradas las variables
if (getenv('PGHOST') && getenv('PGUSER')) {
    try {
        $dsn = 'pgsql:host=' . getenv('PGHOST')
            . ';port=' . (getenv('PGPORT') ?: '5432')
            . ';dbname=' . getenv('PGDATABASE');
        
        $pdo = new PDO(
            $dsn,
            getenv('PGUSER'),
            getenv('PGPASSWORD'),
            [PDO::ATTR_TIMEOUT => 5]
        );
        
        $result = $pdo->query('SELECT 1');
        $health['checks']['database'] = 'OK';
    } catch (PDOException $e) {
        $health['checks']['database'] = 'ERROR: ' . $e->getMessage();
        $health['status'] = 'ERROR';
    }
} else {
    $health['checks']['database'] = 'SKIPPED (no config)';
}

// Determinar HTTP status code
$http_status = ($health['status'] === 'OK') ? 200 : (($health['status'] === 'WARNING') ? 200 : 503);

http_response_code($http_status);
echo json_encode($health, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>
