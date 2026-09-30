<?php
require dirname(__DIR__) . '/_shared/bootstrap.php';
$user=auth_user();
$pdo=db();
if($_SERVER['REQUEST_METHOD']==='POST') {
    $d=body();
    $pickup=trim((string)($d['pickup']??''));
    $dest=$d['destinations']??[];
    $fare=(float)($d['fare']??0);
    if($pickup==='' || !is_array($dest) || count($dest)<1 || $fare<0) fail('Invalid ride request');
    $st=$pdo->prepare("INSERT INTO ride_requests(customer_id,pickup,destinations_json,fare,status) VALUES(?,?,CAST(? AS jsonb),?,'pending') RETURNING id");
    $st->execute([(int)$user['id'],$pickup,json_encode(array_values($dest),JSON_UNESCAPED_UNICODE),$fare]);
    $id=(int)$st->fetchColumn();
    add_history((int)$user['id'],'customer','ride_request',$id,'Ride Request','Created',['pickup'=>$pickup,'destinations'=>$dest]);
    ok(['id'=>$id]);
}
if($_SERVER['REQUEST_METHOD']!=='GET') fail('Method not allowed',405);
$sql="SELECT r.id,r.pickup,r.destinations_json::text AS destinations,r.fare
      FROM ride_requests r
      WHERE r.status='pending' AND r.customer_id<>?
        AND NOT EXISTS (
          SELECT 1 FROM ride_request_responses rr
          WHERE rr.ride_request_id=r.id AND rr.rider_id=?
        )
      ORDER BY r.id DESC";
$st=$pdo->prepare($sql);
$st->execute([(int)$user['id'],(int)$user['id']]);
$rows=$st->fetchAll();
foreach($rows as &$r){ $r['destinations']=json_decode($r['destinations'],true)?:[]; }
ok(['requests'=>$rows]);
