<?php
// api/subir_foto.php
require_once "db.php";

if (!isset($_POST['token'], $_POST['scale_id'], $_POST['cam_id']) || !isset($_FILES['foto'])) {
    http_response_code(400);
    exit("Faltan parámetros");
}

try {
    $pdo = conectarDB();
    $stmt = $pdo->prepare("SELECT id FROM equipos WHERE token=? AND activo=1 LIMIT 1");
    $stmt->execute([$_POST['token']]);
    if (!$stmt->fetch()) {
        http_response_code(403);
        exit("Token inválido");
    }

    $scale_id = intval($_POST['scale_id']);
    $cam_id = intval($_POST['cam_id']);
    $fecha = date("Ymd");
    $hora = date("His");
    $dir = "/var/www/html/balanza/{$scale_id}/{$fecha}";
    if (!is_dir($dir)) mkdir($dir, 0775, true);

    $destino = "$dir/{$hora}_cam{$cam_id}.jpg";
    if (move_uploaded_file($_FILES['foto']['tmp_name'], $destino)) {
        echo "OK";
    } else {
        http_response_code(500);
        echo "Error al guardar";
    }
} catch (Exception $e) {
    http_response_code(500);
    echo "Error del servidor: " . $e->getMessage();
}
