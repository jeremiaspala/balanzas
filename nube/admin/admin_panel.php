<?php
// nube/admin/index.php
require_once "../api/db.php";
$pdo = conectarDB();
$scales = $pdo->query("SELECT id, name FROM scales ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Panel de Balanzas</title>
  <style>
    body { font-family: sans-serif; background: #f0f0f0; padding: 20px; }
    h1 { text-align: center; }
    .balanza { background: white; padding: 15px; margin-bottom: 20px; border-radius: 10px; box-shadow: 0 0 5px #ccc; }
    .pesadas { margin-top: 10px; }
    .pesadas a { display: block; text-decoration: none; color: #333; padding: 5px; border-bottom: 1px solid #ddd; }
  </style>
</head>
<body>
<h1>Panel de Pesadas por Balanza</h1>
<?php foreach ($scales as $scale): ?>
  <div class="balanza">
    <h2><?= htmlspecialchars($scale['name']) ?></h2>
    <div class="pesadas">
      <?php
        $stmt = $pdo->prepare("SELECT id, timestamp FROM weights WHERE scale_id=? AND weight>=100 AND stable=1 ORDER BY timestamp DESC LIMIT 20");
        $stmt->execute([$scale['id']]);
        foreach ($stmt as $row):
      ?>
        <a href="ver.php?id=<?= $row['id'] ?>">🕒 <?= $row['timestamp'] ?></a>
      <?php endforeach; ?>
    </div>
  </div>
<?php endforeach; ?>
</body>
</html>
