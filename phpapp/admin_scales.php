<?php
require_once 'config.php';
$db = db_connect();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $db->prepare('INSERT INTO scales (name, ip, port) VALUES (?,?,?)');
    $stmt->bind_param('ssi', $_POST['name'], $_POST['ip'], $_POST['port']);
    $stmt->execute();
    $stmt->close();
    header('Location: admin_scales.php');
    exit;
}
$scales = [];
if ($result = $db->query('SELECT * FROM scales')) {
    while ($row = $result->fetch_assoc()) {
        $scales[] = $row;
    }
    $result->free();
}
$db->close();
?>
<!doctype html>
<html>
<head><title>Balanzas</title></head>
<body>
<h1>Balanzas</h1>
<form method="post">
Nombre: <input type="text" name="name"/>
IP: <input type="text" name="ip"/>
Puerto: <input type="text" name="port"/>
<input type="submit" value="Agregar"/>
</form>
<ul>
<?php foreach ($scales as $s): ?>
<li><?php echo htmlspecialchars($s['name']); ?> - <a href="admin_cameras.php?scale=<?php echo $s['id']; ?>">Camaras</a></li>
<?php endforeach; ?>
</ul>
<a href="index.php">Volver</a>
</body>
</html>
