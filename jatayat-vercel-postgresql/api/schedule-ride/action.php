<?php
require dirname(__DIR__) . '/_shared/bootstrap.php';
if($_SERVER['REQUEST_METHOD']!=='POST') fail('Method not allowed',405);
$user=auth_user();
$d=body();
$id=(int)($d['schedule_id']??0);
$action=(string)($d['action']??'');
$viewRole=(string)($d['view_role']??'');
if(!$id || !in_array($action,['accept','decline'],true) || !in_array($viewRole,['rider','customer'],true)) fail('Invalid action');
$pdo=db();
$st=$pdo->prepare("SELECT *, destinations_json::text AS destinations_text FROM schedule_rides WHERE id=? AND status='active' LIMIT 1");
$st->execute([$id]);
$row=$st->fetch();
if(!$row) fail('Schedule ride not available',404);
try {
    $pdo->beginTransaction();
    $sql="INSERT INTO schedule_responses(schedule_ride_id,responder_id,action)
          VALUES(?,?,?)
          ON CONFLICT (schedule_ride_id,responder_id)
          DO UPDATE SET action=EXCLUDED.action, created_at=CURRENT_TIMESTAMP";
    $pdo->prepare($sql)->execute([$id,(int)$user['id'],$action]);
    if($action==='accept') $pdo->prepare("UPDATE schedule_rides SET status='accepted',accepted_by=? WHERE id=?")->execute([(int)$user['id'],$id]);
    $details=['pickup'=>$row['pickup'],'destinations'=>json_decode($row['destinations_text'],true)?:[]];
    add_history((int)$user['id'],$viewRole,'schedule_ride',$id,'Schedule Ride',ucfirst($action),$details);
    add_history((int)$row['owner_id'],$row['origin_role'],'schedule_ride',$id,'Schedule Ride',ucfirst($action),$details);
    $pdo->commit();
    ok();
} catch(Throwable $e) {
    if($pdo->inTransaction()) $pdo->rollBack();
    fail('Action failed',500);
}
