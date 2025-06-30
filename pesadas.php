<?php
$mysqli = new mysqli("localhost", "balanzas", "balanzas", "balanzas");
$balanza_id = (int)$_GET['balanza'];
$balanza = $mysqli->query("SELECT name FROM scales WHERE id=$balanza_id")->fetch_assoc();

// Obtener grupos manualmente
$pesadas = [];
$res = $mysqli->query("SELECT * FROM weights WHERE scale_id=$balanza_id AND weight >= 100 ORDER BY timestamp ASC");

$grupo_actual = [];
$ultima_fecha = null;

while($r = $res->fetch_assoc()) {
  $ts = strtotime($r['timestamp']);
  if (!$ultima_fecha || $ts - $ultima_fecha > 120) { // más de 2 minutos entre registros
    if ($grupo_actual) $pesadas[] = $grupo_actual;
    $grupo_actual = [];
  }
  $grupo_actual[] = $r;
  $ultima_fecha = $ts;
}
if ($grupo_actual) $pesadas[] = $grupo_actual;
?>

<h2>Pesadas registradas – <?=$balanza['name']?></h2>
<ul>
<?php foreach ($pesadas as $grupo): ?>
<?php
$id_ini = $grupo[0]['id'];
$id_fin = end($grupo)['id'];
$inicio = date("d/m/Y H:i:s", strtotime($grupo[0]['timestamp']));
?>
<li>
<a href="detalle.php?inicio=<?=$id_ini?>&fin=<?=$id_fin?>&balanza=<?=$balanza_id?>"><?=$inicio?></a>
</li>
<?php endforeach; ?>
</ul>
<a href="index.php">⬅ Volver</a>
