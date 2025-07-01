<?php
// nube/admin/ver.php
require_once "../api/db.php";
$pdo = conectarDB();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$stmt = $pdo->prepare("SELECT * FROM weights WHERE id=?");
$stmt->execute([$id]);
$peso = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$peso) {
  echo "<h1>No encontrado</h1>";
  exit;
}

$inicio = $pdo->prepare("SELECT timestamp FROM weights WHERE scale_id=? AND id<=? ORDER BY id DESC LIMIT 1");
$inicio->execute([$peso['scale_id'], $peso['id']]);
$t0 = $inicio->fetchColumn();

$fin = $pdo->prepare("SELECT timestamp FROM weights WHERE scale_id=? AND id>? AND weight=0 ORDER BY id ASC LIMIT 1");
$fin->execute([$peso['scale_id'], $peso['id']]);
$t1 = $fin->fetchColumn();

$grafico = $pdo->prepare("SELECT timestamp, weight FROM weights WHERE scale_id=? AND timestamp BETWEEN ? AND ? ORDER BY timestamp");
$grafico->execute([$peso['scale_id'], $t0, $t1 ?: date('Y-m-d H:i:s')]);
$datos = $grafico->fetchAll(PDO::FETCH_ASSOC);

$camdir = "/var/www/html/balanza/" . $peso['scale_id'] . "/" . date("Ymd", strtotime($peso['timestamp']));
$fotos = [];
if (is_dir($camdir)) {
  foreach (glob("$camdir/*_cam*.jpg") as $f) {
    if (strpos($f, date("His", strtotime($peso['timestamp']))) !== false) {
      $fotos[] = str_replace("/var/www/html", "", $f);
    }
  }
}

// Exportar CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
  header('Content-Type: text/csv');
  header('Content-Disposition: attachment; filename="pesada_' . $peso['id'] . '.csv"');
  echo "timestamp,weight\n";
  foreach ($datos as $row) {
    echo $row['timestamp'] . "," . $row['weight'] . "\n";
  }
  exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Detalle de Pesada</title>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      padding: 40px;
      background: #f8f9fa;
      color: #333;
    }
    h1, h2 {
      text-align: center;
    }
    .container {
      max-width: 900px;
      margin: auto;
      background: white;
      padding: 30px;
      box-shadow: 0 0 15px rgba(0,0,0,0.1);
      border-radius: 10px;
    }
    .img {
      text-align: center;
      margin: 15px 0;
    }
    img {
      max-width: 100%;
      border-radius: 6px;
      box-shadow: 0 0 5px rgba(0,0,0,0.2);
    }
    canvas {
      margin-top: 20px;
    }
    .botones {
      text-align: center;
      margin-top: 20px;
    }
    .boton {
      display: inline-block;
      margin: 5px;
      background: #007bff;
      color: white;
      padding: 10px 20px;
      border-radius: 5px;
      text-decoration: none;
      font-weight: bold;
    }
    .boton:hover {
      background: #0056b3;
    }
  </style>
</head>
<body>
<div class="container">
<h1>Pesada <?= $peso['id'] ?> <br><small>(<?= $peso['timestamp'] ?>)</small></h1>
<h2>Imágenes capturadas</h2>
<?php foreach ($fotos as $f): ?>
  <div class="img"><img src="<?= htmlspecialchars($f) ?>"></div>
<?php endforeach; ?>
<h2>Gráfico de peso</h2>
<canvas id="grafico" width="800" height="300"></canvas>
<div class="botones">
  <a href="index.php" class="boton">&larr; Volver al panel</a>
  <a href="?id=<?= $peso['id'] ?>&export=csv" class="boton">⬇ Exportar CSV</a>
</div>
</div>
<script>
const ctx = document.getElementById('grafico').getContext('2d');
const chart = new Chart(ctx, {
  type: 'line',
  data: {
    labels: <?= json_encode(array_column($datos, 'timestamp')) ?>,
    datasets: [{
      label: 'Peso (kg)',
      data: <?= json_encode(array_column($datos, 'weight')) ?>,
      fill: false,
      borderColor: 'rgba(75, 192, 192, 1)',
      backgroundColor: 'rgba(75, 192, 192, 0.2)',
      tension: 0.4,
      pointRadius: 3,
      pointHoverRadius: 6
    }]
  },
  options: {
    scales: {
      x: {
        title: { display: true, text: 'Hora' },
        ticks: { autoSkip: true, maxTicksLimit: 20 }
      },
      y: {
        title: { display: true, text: 'Peso (kg)' },
        beginAtZero: true
      }
    }
  }
});
</script>
</body>
</html>
