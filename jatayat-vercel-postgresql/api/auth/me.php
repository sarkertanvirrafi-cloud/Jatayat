<?php
require dirname(__DIR__) . '/_shared/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'GET') fail('Method not allowed',405);
ok(['user'=>auth_user()]);
