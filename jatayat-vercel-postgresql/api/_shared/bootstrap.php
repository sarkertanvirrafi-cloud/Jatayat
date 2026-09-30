<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Auth-Token');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

function json_out(array $data, int $status = 200): never
{
    http_response_code($status);

    echo json_encode(
        $data,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

    exit;
}

function ok(array $data = []): never
{
    json_out(['ok' => true] + $data);
}

function fail(string $message, int $status = 400): never
{
    json_out(
        [
            'ok' => false,
            'message' => $message
        ],
        $status
    );
}

function body(): array
{
    $raw = file_get_contents('php://input') ?: '';
    $data = json_decode($raw, true);

    return is_array($data) ? $data : [];
}

function uiu_email(string $email): bool
{
    return (bool) preg_match(
        '/^[^@\s]+@bscse\.uiu\.ac\.bd$/i',
        trim($email)
    );
}


/* =====================================
   DATABASE
   ===================================== */

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $config = require __DIR__ . '/config.php';

    if (!($config['valid'] ?? false)) {

        error_log(
            'PostgreSQL configuration error: no valid database configuration found.'
        );

        fail(
            'PostgreSQL configuration missing',
            500
        );
    }

    $host = (string)$config['host'];
    $port = (int)$config['port'];
    $name = (string)$config['database'];
    $user = (string)$config['username'];
    $pass = (string)$config['password'];

    $sslmode = (string)(
        $config['sslmode']
        ?? 'require'
    );

    $endpointId = (string)(
        $config['endpoint_id']
        ?? ''
    );

    try {

        $dsn =
            "pgsql:" .
            "host={$host};" .
            "port={$port};" .
            "dbname={$name};" .
            "sslmode={$sslmode}";

        /*
         * Neon workaround for older libpq clients
         * that do not support the required SNI behavior.
         */
        if ($endpointId !== '') {
            $dsn .= ";options=endpoint={$endpointId}";
        }

        $pdo = new PDO(
            $dsn,
            $user,
            $pass,
            [
                PDO::ATTR_ERRMODE =>
                    PDO::ERRMODE_EXCEPTION,

                PDO::ATTR_DEFAULT_FETCH_MODE =>
                    PDO::FETCH_ASSOC,

                PDO::ATTR_EMULATE_PREPARES =>
                    false,
            ]
        );

        return $pdo;

    } catch (Throwable $e) {

        error_log(
            'PostgreSQL connection error: '
            . $e->getMessage()
        );

        fail(
            'Database connection failed',
            500
        );
    }
}


/* =====================================
   AUTH TOKEN
   ===================================== */

function bearer_token(): string
{
    $custom = trim(
        (string)(
            $_SERVER['HTTP_X_AUTH_TOKEN']
            ?? ''
        )
    );

    if ($custom !== '') {
        return $custom;
    }

    $header =
        $_SERVER['HTTP_AUTHORIZATION']
        ?? '';

    if (
        $header === ''
        &&
        function_exists('getallheaders')
    ) {

        $headers = getallheaders();

        $header =
            $headers['Authorization']
            ??
            $headers['authorization']
            ??
            '';
    }

    if (
        preg_match(
            '/Bearer\s+(.+)/i',
            $header,
            $m
        )
    ) {
        return trim($m[1]);
    }

    return '';
}


function auth_user(): array
{
    $token = bearer_token();

    if ($token === '') {
        fail(
            'Login required',
            401
        );
    }

    $hash = hash(
        'sha256',
        $token
    );

    $sql = "
        SELECT
            u.id,
            u.name,
            u.student_id,
            u.email

        FROM auth_tokens t

        JOIN users u
            ON u.id = t.user_id

        WHERE
            t.token_hash = ?
            AND
            t.expires_at > CURRENT_TIMESTAMP

        LIMIT 1
    ";

    $st = db()->prepare($sql);

    $st->execute([
        $hash
    ]);

    $user = $st->fetch();

    if (!$user) {
        fail(
            'Invalid or expired login',
            401
        );
    }

    return $user;
}


function new_auth_token(int $userId): string
{
    $plain = bin2hex(
        random_bytes(32)
    );

    $hash = hash(
        'sha256',
        $plain
    );

    $st = db()->prepare(
        "
        INSERT INTO auth_tokens(
            user_id,
            token_hash,
            expires_at
        )

        VALUES(
            ?,
            ?,
            CURRENT_TIMESTAMP + INTERVAL '30 days'
        )
        "
    );

    $st->execute([
        $userId,
        $hash
    ]);

    return $plain;
}


/* =====================================
   REQUEST HISTORY
   ===================================== */

function add_history(
    int $userId,
    string $viewRole,
    string $type,
    int $requestId,
    string $title,
    string $action,
    array $details
): void {

    $st = db()->prepare(
        "
        INSERT INTO request_history(
            user_id,
            view_role,
            request_type,
            request_id,
            title,
            action,
            details_json
        )

        VALUES(
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            CAST(? AS jsonb)
        )
        "
    );

    $st->execute([
        $userId,
        $viewRole,
        $type,
        $requestId,
        $title,
        $action,

        json_encode(
            $details,
            JSON_UNESCAPED_UNICODE
        )
    ]);
}