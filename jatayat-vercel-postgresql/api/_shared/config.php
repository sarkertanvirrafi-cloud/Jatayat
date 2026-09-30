<?php
declare(strict_types=1);

$databaseUrl = trim((string)(
    getenv('DATABASE_URL')
    ?: getenv('DATABASE_URL_UNPOOLED')
    ?: getenv('POSTGRES_URL')
    ?: ''
));

if ($databaseUrl !== '') {

    $parts = parse_url($databaseUrl);

    if (
        $parts === false ||
        empty($parts['host']) ||
        empty($parts['path'])
    ) {
        return [
            'valid' => false
        ];
    }

    $query = [];

    if (!empty($parts['query'])) {
        parse_str(
            $parts['query'],
            $query
        );
    }

    $host = (string)$parts['host'];

    /*
     * Neon endpoint ID
     *
     * Example host:
     * ep-example-123456.us-east-2.aws.neon.tech
     *
     * Endpoint:
     * ep-example-123456
     */

    $endpointId = explode(
        '.',
        $host
    )[0];

    /*
     * Pooled hostname example:
     * ep-example-123456-pooler
     *
     * Endpoint must be:
     * ep-example-123456
     */
    $endpointId = preg_replace(
        '/-pooler$/',
        '',
        $endpointId
    );

    return [
        'valid' => true,

        'host' => $host,

        'port' => (int)(
            $parts['port']
            ?? 5432
        ),

        'database' => ltrim(
            (string)$parts['path'],
            '/'
        ),

        'username' => rawurldecode(
            (string)(
                $parts['user']
                ?? ''
            )
        ),

        'password' => rawurldecode(
            (string)(
                $parts['pass']
                ?? ''
            )
        ),

        'sslmode' => (string)(
            $query['sslmode']
            ?? 'require'
        ),

        'endpoint_id' => $endpointId
    ];
}


/* =====================================
   ENVIRONMENT VARIABLE FALLBACK
   ===================================== */

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

$password = (string)(
    getenv('PGPASSWORD')
    ?: getenv('DATABASE_PGPASSWORD')
    ?: ''
);

$port = (int)(
    getenv('PGPORT')
    ?: getenv('DATABASE_PGPORT')
    ?: 5432
);

$endpointId = '';

if ($host !== '') {

    $endpointId = explode(
        '.',
        $host
    )[0];

    $endpointId = preg_replace(
        '/-pooler$/',
        '',
        $endpointId
    );
}

return [
    'valid' => (
        $host !== ''
        &&
        $name !== ''
        &&
        $user !== ''
    ),

    'host' => $host,

    'port' => $port,

    'database' => $name,

    'username' => $user,

    'password' => $password,

    'sslmode' => 'require',

    'endpoint_id' => $endpointId
];