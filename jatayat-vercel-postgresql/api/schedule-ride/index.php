<?php
require dirname(__DIR__) . '/_shared/bootstrap.php';
$user=auth_user();
$pdo=db();
if($_SERVER['REQUEST_METHOD']==='POST') {
    $d=body();
    $role=(string)($d['role']??'');
    $pickup=trim((string)($d['pickup']??''));
    $dest=$d['destinations']??[];
    $date=(string)($d['ride_date']??'');
    $pt=(string)($d['pickup_time']??'');
    $dt=(string)($d['drop_time']??'');
    $fare=(float)($d['fare']??0);
    $seat=isset($d['available_seat'])?(int)$d['available_seat']:null;
    if(!in_array($role,['rider','customer'],true) || $pickup==='' || !is_array($dest) || count($dest)<1 || $date==='' || $pt==='' || $dt==='' || $fare<0) fail('Invalid schedule ride');
    if($role==='rider' && (!$seat || $seat<1)) fail('Available seat is required');
    $st=$pdo->prepare("INSERT INTO schedule_rides(owner_id,origin_role,pickup,destinations_json,available_seat,fare,ride_date,pickup_time,drop_time,status) VALUES(?,?,?,CAST(? AS jsonb),?,?,?,?,?,'active') RETURNING id");
    $st->execute([(int)$user['id'],$role,$pickup,json_encode(array_values($dest),JSON_UNESCAPED_UNICODE),$seat,$fare,$date,$pt,$dt]);
    $id=(int)$st->fetchColumn();
    add_history((int)$user['id'],$role,'schedule_ride',$id,'Schedule Ride','Created',['pickup'=>$pickup,'destinations'=>$dest]);
    ok(['id'=>$id]);
}
if($_SERVER['REQUEST_METHOD']!=='GET') fail('Method not allowed',405);
$scope=$_GET['scope']??'available';
$origin=$_GET['origin_role']??'';
if(!in_array($origin,['rider','customer'],true)) fail('Invalid schedule role');
$base="SELECT id,origin_role,pickup,destinations_json::text AS destinations,available_seat,fare,
              TO_CHAR(ride_date,'YYYY-MM-DD') AS ride_date,
              TO_CHAR(pickup_time,'HH24:MI') AS pickup_time,
              TO_CHAR(drop_time,'HH24:MI') AS drop_time,status
       FROM schedule_rides";
if($scope==='mine') {
    $st=$pdo->prepare($base." WHERE owner_id=? AND origin_role=? AND status='active' ORDER BY id DESC");
    $st->execute([(int)$user['id'],$origin]);
} else {
    $sql=$base." s
        WHERE s.origin_role=?
        AND s.status='active'
        AND NOT EXISTS(
            SELECT 1
            FROM schedule_responses x
            WHERE x.schedule_ride_id=s.id
            AND x.responder_id=?
        )
        ORDER BY s.id DESC";
    $st=$pdo->prepare($sql);
    $st->execute([$origin,(int)$user['id']]);
}
$rows=$st->fetchAll();
foreach($rows as &$r){ $r['destinations']=json_decode($r['destinations'],true)?:[]; }
ok(['schedules'=>$rows]);
