<?php
require_once 'config.php';
$db = db_connect();
$cam_id = intval($_GET['id']);
$camera = null;
$weights = [];
$images = [];
if ($stmt = $db->prepare('SELECT * FROM cameras WHERE id=?')) {
    $stmt->bind_param('i', $cam_id);
    $stmt->execute();
    $camera = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
if ($camera) {
    if ($stmt = $db->prepare('SELECT * FROM weights WHERE scale_id=? ORDER BY timestamp')) {
        $stmt->bind_param('i', $camera['scale_id']);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $weights[] = $row;
        }
        $stmt->close();
    }
    $datePath = date('Ymd');
    $imgDir = '/var/www/html/balanzas/' . $camera['scale_id'] . '/' . $datePath;
    if (is_dir($imgDir)) {
        foreach (scandir($imgDir) as $file) {
            if (preg_match('/_cam' . $cam_id . '\\.(jpg|png)$/', $file)) {
                $images[] = '/balanzas/' . $camera['scale_id'] . '/' . $datePath . '/' . $file;
            }
        }
    }
}
$db->close();
?>
<!doctype html>
<html>
<head>
    <title><?php echo htmlspecialchars($camera['name']); ?></title>
</head>
<body>
<h1><?php echo htmlspecialchars($camera['name']); ?></h1>
<table border="1">
<tr><th>Fecha</th><th>Peso</th><th>Estable</th></tr>
<?php foreach ($weights as $w): ?>
<tr><td><?php echo $w['timestamp']; ?></td><td><?php echo $w['weight']; ?></td><td><?php echo $w['stable']; ?></td></tr>
<?php endforeach; ?>
</table>
<h2>Imagenes</h2>
<?php foreach ($images as $img): ?>
    <img src="<?php echo $img; ?>" width="300"/>
<?php endforeach; ?>
</body>
</html>
