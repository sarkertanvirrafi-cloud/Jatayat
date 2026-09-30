<?php
require dirname(__DIR__) . '/_shared/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('Method not allowed',405);
$d=body();
$student=trim((string)($d['student_id']??''));
$email=strtolower(trim((string)($d['email']??'')));
$password=(string)($d['password']??'');
if ($student==='' || $email==='' || $password==='') fail('All fields are required');
if (!uiu_email($email)) fail('Email must end with @bscse.uiu.ac.bd');
$st=db()->prepare("SELECT id,password_hash FROM users WHERE student_id=? AND email=? LIMIT 1");
$st->execute([$student,$email]);
$user=$st->fetch();
if(!$user || !password_verify($password,$user['password_hash'])) fail('Invalid login',401);
ok(['token'=>new_auth_token((int)$user['id'])]);
