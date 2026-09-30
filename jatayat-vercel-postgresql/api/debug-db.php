<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

$url = trim((string)(
    getenv('DATABASE_URL')
    ?: getenv('DATABASE_URL_UNPOOLED')
    ?: getenv('POSTGRES_URL')
    ?: ''
));

$result = [
    'php_version' => PHP_VERSION,
    'pdo_drivers' => PDO::getAvailableDrivers(),

    'DATABASE_URL_exists' =>
        getenv('DATABASE_URL') !== false,

    'DATABASE_URL_UNPOOLED_exists' =>
        getenv('DATABASE_URL_UNPOOLED') !== false,

    'url_length' => strlen($url),
];

if ($url === '') {
    $result['error'] = 'No database URL found';
    echo json_encode($result, JSON_PRETTY_PRINT);
    exit;
}

$parts = parse_url($url);

$result['parse_success'] = $parts !== false;
$result['scheme'] = $parts['scheme'] ?? null;
$result['host_exists'] = !empty($parts['host']);
$result['database_exists'] = !empty($parts['path']);
$result['username_exists'] = !empty($parts['user']);
$result['port'] = $parts['port'] ?? 5432;

if ($parts === false) {
    $result['error'] = 'parse_url failed';
    echo json_encode($result, JSON_PRETTY_PRINT);
    exit;
}

$query = [];

if (!empty($parts['query'])) {
    parse_str($parts['query'], $query);
}

$host = (string)($parts['host'] ?? '');
$port = (int)($parts['port'] ?? 5432);
$db   = ltrim((string)($parts['path'] ?? ''), '/');
$user = rawurldecode((string)($parts['user'] ?? ''));
$pass = rawurldecode((string)($parts['pass'] ?? ''));
$ssl  = (string)($query['sslmode'] ?? 'require');

try {

    $dsn =
        "pgsql:host={$host};" .
        "port={$port};" .
        "dbname={$db};" .
        "sslmode={$ssl}";

    $pdo = new PDO(
        $dsn,
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE =>
                PDO::ERRMODE_EXCEPTION
        ]
    );

    $row = $pdo
        ->query(
            "SELECT current_database() AS db, version() AS version"
        )
        ->fetch(PDO::FETCH_ASSOC);

    $result['connected'] = true;
    $result['database'] = $row['db'] ?? '';

} catch (Throwable $e) {

    $result['connected'] = false;
    $result['exception'] = get_class($e);
    $result['error_code'] = $e->getCode();
    $result['error_message'] = $e->getMessage();
}

echo json_encode(
    $result,
    JSON_PRETTY_PRINT |
    JSON_UNESCAPED_SLASHES
);