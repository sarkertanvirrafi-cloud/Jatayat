<?php
require dirname(__DIR__) . '/_shared/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('Method not allowed',405);
$token=bearer_token();
if($token!=='') db()->prepare("DELETE FROM auth_tokens WHERE token_hash=?")->execute([hash('sha256',$token)]);
ok();
