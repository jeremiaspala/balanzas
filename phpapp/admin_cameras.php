<?php
require_once 'config.php';
$db = db_connect();
$scale_id = intval($_GET['scale']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $db->prepare('INSERT INTO cameras (scale_id, name, url) VALUES (?,?,?)');
    $stmt->bind_param('iss', $scale_id, $_POST['name'], $_POST['url']);
    $stmt->execute();
    $stmt->close();
    header('Location: admin_cameras.php?scale=' . $scale_id);
    exit;
}
$cameras = [];
$stmt = $db->prepare('SELECT * FROM cameras WHERE scale_id=?');
$stmt->bind_param('i', $scale_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $cameras[] = $row;
}
$stmt->close();
$db->close();
?>
<!doctype html>
<html>
<head><title>Camaras</title></head>
<body>
<h1>Camaras</h1>
<form method="post">
Nombre: <input type="text" name="name"/>
URL: <input type="text" name="url"/>
<input type="submit" value="Agregar"/>
</form>
<ul>
<?php foreach ($cameras as $c): ?>
<li><?php echo htmlspecialchars($c['name']); ?> - <?php echo htmlspecialchars($c['url']); ?></li>
<?php endforeach; ?>
</ul>
<a href="admin_scales.php">Volver</a>
</body>
</html>
