<?php
require dirname(__DIR__) . '/_shared/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('Method not allowed', 405);

$d = body();
$name = trim((string)($d['name'] ?? ''));
$student = trim((string)($d['student_id'] ?? ''));
$email = strtolower(trim((string)($d['email'] ?? '')));
$password = (string)($d['password'] ?? '');

if ($name === '' || $student === '' || $email === '' || strlen($password) < 6) {
    fail('All fields are required');
}
if (!uiu_email($email)) fail('Email must end with @bscse.uiu.ac.bd');

$pdo = db();
$st = $pdo->prepare("SELECT id FROM users WHERE email=? OR student_id=? LIMIT 1");
$st->execute([$email, $student]);
if ($st->fetch()) fail('Email or Student ID already registered', 409);

try {
    $pdo->beginTransaction();
    $st = $pdo->prepare("INSERT INTO users(name,student_id,email,password_hash) VALUES(?,?,?,?) RETURNING id");
    $st->execute([$name, $student, $email, password_hash($password, PASSWORD_DEFAULT)]);
    $userId = (int)$st->fetchColumn();
    $plain = new_auth_token($userId);
    $pdo->commit();
    ok(['token' => $plain]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    fail('Sign up failed', 500);
}
