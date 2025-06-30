<?php
$mysqli = new mysqli("localhost", "balanzas", "balanzas", "balanzas");
$ini = (int)$_GET['inicio'];
$fin = (int)$_GET['fin'];
$balanza_id = (int)$_GET['balanza'];

$res = $mysqli->query("SELECT * FROM weights WHERE id BETWEEN $ini AND $fin ORDER BY timestamp ASC");
$data = [];
while ($r = $res->fetch_assoc()) {
  $data[] = $r;
}
$inicio = $data[0]['timestamp'];
$fecha_dir = date("Ymd", strtotime($inicio));
$carpeta = "/balanza/{$balanza_id}/{$fecha_dir}";
$archivos = glob(__DIR__ . $carpeta . "/*.jpg");
?>

<h2>Detalle de pesada – <?=date("d/m/Y H:i:s", strtotime($inicio))?></h2>

<h3>Imágenes capturadas</h3>
<?php foreach ($archivos as $img): ?>
<img src="<?=$carpeta.'/'.basename($img)?>" width="300" style="margin:5px">
<?php endforeach; ?>

<h3>Gráfico</h3>
<canvas id="grafico" width="600" height="300"></canvas>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const datos = <?=json_encode(array_map(function($w){
  return [
    'x' => date("H:i:s", strtotime($w['timestamp'])),
                                       'y' => (float)$w['weight']
  ];
}, $data))?>;

new Chart(document.getElementById('grafico'), {
  type: 'line',
  data: {
    datasets: [{
      label: 'Peso (kg)',
          data: datos,
          borderWidth: 2,
          fill: false,
          tension: 0.3
    }]
  },
  options: {
    scales: {
      x: { type: 'category' },
      y: { beginAtZero: true }
    }
  }
});
</script>
<a href="pesadas.php?balanza=<?=$balanza_id?>">⬅ Volver</a>
