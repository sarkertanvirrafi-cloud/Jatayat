<?php
require __DIR__ . '/_shared/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'GET') fail('Method not allowed', 405);
$pdo = db();
$row = $pdo->query("SELECT current_database() AS database_name, version() AS postgres_version")->fetch();
ok([
    'message' => 'PostgreSQL connected',
    'database' => $row['database_name'] ?? '',
    'postgres_version' => $row['postgres_version'] ?? '',
]);
