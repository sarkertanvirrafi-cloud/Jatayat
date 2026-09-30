<?php
require dirname(__DIR__) . '/_shared/bootstrap.php';
if($_SERVER['REQUEST_METHOD']!=='GET') fail('Method not allowed',405);
$user=auth_user();
$role=$_GET['role']??'';
if(!in_array($role,['rider','customer'],true)) fail('Invalid role');
$st=db()->prepare("SELECT title,action,details_json::text AS details_json,TO_CHAR(created_at,'YYYY-MM-DD HH24:MI') AS created_at FROM request_history WHERE user_id=? AND view_role=? ORDER BY id DESC LIMIT 100");
$st->execute([(int)$user['id'],$role]);
$rows=$st->fetchAll();
foreach($rows as &$r){
    $d=json_decode($r['details_json'],true)?:[];
    $r['pickup']=$d['pickup']??'';
    $r['destinations']=$d['destinations']??[];
    unset($r['details_json']);
}
ok(['history'=>$rows]);
