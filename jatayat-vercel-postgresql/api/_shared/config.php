<?php
declare(strict_types=1);

/**
 * PostgreSQL configuration for Vercel.
 * Primary source: DATABASE_URL (recommended for Neon/Supabase/Railway Postgres).
 * Fallback: POSTGRES_URL or PG* environment variables.
 */

$databaseUrl = trim((string)(
    getenv('DATABASE_URL')
    ?: getenv('DATABASE_URL_UNPOOLED')
    ?: getenv('POSTGRES_URL')
    ?: ''
));

if ($databaseUrl !== '') {
    $parts = parse_url($databaseUrl);
    if ($parts === false || empty($parts['host']) || empty($parts['path'])) {
        return ['valid' => false];
    }

    $query = [];
    if (!empty($parts['query'])) {
        parse_str($parts['query'], $query);
    }

    return [
        'valid' => true,
        'host' => (string)$parts['host'],
        'port' => (int)($parts['port'] ?? 5432),
        'database' => ltrim((string)$parts['path'], '/'),
        'username' => rawurldecode((string)($parts['user'] ?? '')),
        'password' => rawurldecode((string)($parts['pass'] ?? '')),
        'sslmode' => (string)($query['sslmode'] ?? 'require'),
    ];
}

$host = trim((string)(
    getenv('PGHOST')
    ?: getenv('DATABASE_PGHOST')
    ?: ''
));

$name = trim((string)(
    getenv('PGDATABASE')
    ?: getenv('DATABASE_PGDATABASE')
    ?: ''
));

$user = trim((string)(
    getenv('PGUSER')
    ?: getenv('DATABASE_PGUSER')
    ?: ''
));

return [
    'valid' => ($host !== '' && $name !== '' && $user !== ''),
    'host' => $host,
    'port' => (int)(
        getenv('PGPORT')
        ?: getenv('DATABASE_PGPORT')
        ?: 5432
    ),
    'database' => $name,
    'username' => $user,
    'password' => (string)(
        getenv('PGPASSWORD')
        ?: getenv('DATABASE_PGPASSWORD')
        ?: ''
    ),
    'sslmode' => 'require',
];