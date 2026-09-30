<?php
require dirname(__DIR__) . '/_shared/bootstrap.php';
if($_SERVER['REQUEST_METHOD']!=='POST') fail('Method not allowed',405);
$user=auth_user();
$d=body();
$id=(int)($d['request_id']??0);
$action=(string)($d['action']??'');
if(!$id || !in_array($action,['accept','decline'],true)) fail('Invalid action');
$pdo=db();
$st=$pdo->prepare("SELECT *, destinations_json::text AS destinations_text FROM ride_requests WHERE id=? AND status='pending' LIMIT 1");
$st->execute([$id]);
$req=$st->fetch();
if(!$req) fail('Request not available',404);
try {
    $pdo->beginTransaction();
    $sql="INSERT INTO ride_request_responses(ride_request_id,rider_id,action)
          VALUES(?,?,?)
          ON CONFLICT (ride_request_id,rider_id)
          DO UPDATE SET action=EXCLUDED.action, created_at=CURRENT_TIMESTAMP";
    $pdo->prepare($sql)->execute([$id,(int)$user['id'],$action]);
    if($action==='accept') $pdo->prepare("UPDATE ride_requests SET status='accepted',accepted_by=? WHERE id=?")->execute([(int)$user['id'],$id]);
    $details=['pickup'=>$req['pickup'],'destinations'=>json_decode($req['destinations_text'],true)?:[]];
    add_history((int)$user['id'],'rider','ride_request',$id,'Ride Request',ucfirst($action),$details);
    add_history((int)$req['customer_id'],'customer','ride_request',$id,'Ride Request',ucfirst($action),$details);
    $pdo->commit();
    ok();
} catch(Throwable $e) {
    if($pdo->inTransaction()) $pdo->rollBack();
    fail('Action failed',500);
}
