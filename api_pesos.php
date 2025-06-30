<?php
header("Content-Type: application/json");
$mysqli = new mysqli("localhost", "balanzas", "balanzas", "balanzas");
$sid = (int)$_GET['session'];
$res = $mysqli->query("SELECT weight, timestamp FROM weights WHERE session_id=$sid ORDER BY timestamp ASC");
$out = [];
while($r = $res->fetch_assoc()) {
  $out[] = [
    'timestamp' => date("H:i:s", strtotime($r['timestamp'])),
    'weight' => floatval($r['weight'])
  ];
}
echo json_encode($out);
