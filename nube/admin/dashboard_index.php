<?php
// nube/admin/index.php
require_once "../api/db.php";
$pdo = conectarDB();

$balanza = $_GET['balanza'] ?? '';
$fecha = $_GET['fecha'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$countQuery = "SELECT COUNT(*) FROM scales s 
  LEFT JOIN weights w ON w.scale_id = s.id AND w.weight >= 100 AND w.stable = 1 
  WHERE 1=1";
$params = [];

if ($balanza) {
  $countQuery .= " AND s.name LIKE ?";
  $params[] = "%$balanza%";
}
if ($fecha) {
  $countQuery .= " AND DATE(w.timestamp) = ?";
  $params[] = $fecha;
}
$stmt = $pdo->prepare($countQuery);
$stmt->execute($params);
$total = $stmt->fetchColumn();
$totalPages = ceil($total / $perPage);

$query = "SELECT s.id, s.name, w.id as wid, w.timestamp, w.weight FROM scales s 
         LEFT JOIN weights w ON w.scale_id = s.id AND w.weight >= 100 AND w.stable = 1 
         WHERE 1=1";

if ($balanza) {
  $query .= " AND s.name LIKE ?";
}
if ($fecha) {
  $query .= " AND DATE(w.timestamp) = ?";
}

$query .= " GROUP BY s.id, w.id ORDER BY w.timestamp DESC LIMIT $perPage OFFSET $offset";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$pesadas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Exportar CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
  $fecha_nombre = $fecha ?: date('Y-m-d');
  header('Content-Type: text/csv');
  header("Content-Disposition: attachment; filename=pesadas_{$fecha_nombre}.csv");
  echo "balanza,timestamp,weight\n";
  $exportQuery = str_replace("LIMIT $perPage OFFSET $offset", "", $query);
  $stmt = $pdo->prepare($exportQuery);
  $stmt->execute($params);
  while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "{$row['name']},{$row['timestamp']},{$row['weight']}\n";
  }
  exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Panel de Balanzas</title>
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background: #f1f1f1;
      padding: 30px;
    }
    .container {
      max-width: 1000px;
      margin: auto;
      background: #fff;
      padding: 25px;
      border-radius: 10px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    table {
      width: 100%;
      border-collapse: collapse;
    }
    th, td {
      padding: 10px;
      text-align: center;
      border-bottom: 1px solid #ddd;
    }
    th {
      background-color: #007bff;
      color: white;
    }
    h1 {
      text-align: center;
      margin-bottom: 30px;
    }
    form {
      text-align: center;
      margin-bottom: 20px;
    }
    input, button {
      padding: 8px;
      font-size: 14px;
      margin: 0 5px;
    }
    a {
      text-decoration: none;
      color: #007bff;
      font-weight: bold;
    }
    a:hover {
      text-decoration: underline;
    }
    .pagination {
      text-align: center;
      margin-top: 20px;
    }
    .pagination a {
      margin: 0 5px;
      padding: 5px 10px;
      background: #007bff;
      color: white;
      border-radius: 4px;
    }
    .pagination a.active {
      background: #0056b3;
      pointer-events: none;
    }
    .export {
      text-align: center;
      margin-top: 15px;
    }
    .export a {
      background: #28a745;
      color: white;
      padding: 8px 15px;
      border-radius: 5px;
      text-decoration: none;
      font-weight: bold;
    }
    .export a:hover {
      background: #1e7e34;
    }
  </style>
</head>
<body>
<div class="container">
  <h1>Panel de Pesadas Registradas</h1>
  <form method="get">
    <input type="text" name="balanza" placeholder="Nombre de balanza" value="<?= htmlspecialchars($balanza) ?>">
    <input type="date" name="fecha" value="<?= htmlspecialchars($fecha) ?>">
    <button type="submit">Filtrar</button>
    <a href="index.php">Limpiar</a>
  </form>
  <div class="export">
    <a href="?<?= http_build_query(array_merge($_GET, ['export' => 'csv'])) ?>">⬇ Exportar filtrado a CSV</a>
  </div>
  <table>
    <thead>
      <tr>
        <th>Balanza</th>
        <th>Fecha y Hora</th>
        <th>Peso</th>
        <th>Detalle</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($pesadas as $p): ?>
        <tr>
          <td><?= htmlspecialchars($p['name']) ?></td>
          <td><?= htmlspecialchars($p['timestamp']) ?></td>
          <td><?= number_format($p['weight'], 2) ?> kg</td>
          <td><a href="ver.php?id=<?= $p['wid'] ?>">Ver</a></td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($pesadas)): ?>
        <tr><td colspan="4">No hay registros</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
  <?php if ($totalPages > 1): ?>
    <div class="pagination">
      <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" class="<?= $i === $page ? 'active' : '' ?>"> <?= $i ?> </a>
      <?php endfor; ?>
    </div>
  <?php endif; ?>
</div>
</body>
</html>
