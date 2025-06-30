<?php
require_once 'config.php';
$db = db_connect();
$cameras = [];
if ($result = $db->query('SELECT * FROM cameras')) {
    while ($row = $result->fetch_assoc()) {
        $cameras[] = $row;
    }
    $result->free();
}
$db->close();
?>
<!doctype html>
<html>
<head>
    <title>Camaras</title>
</head>
<body>
<h1>Listado de Camaras</h1>
<ul>
<?php foreach ($cameras as $cam): ?>
    <li><a href="camera.php?id=<?php echo $cam['id']; ?>"><?php echo htmlspecialchars($cam['name']); ?></a></li>
<?php endforeach; ?>
</ul>
<a href="admin_scales.php">Administrar Balanzas</a>
</body>
</html>
