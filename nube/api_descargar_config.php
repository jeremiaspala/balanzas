<?php
// api/descargar_config.php
require_once "db.php";

$token = $_GET['token'] ?? '';

if (!$token) {
    http_response_code(400);
    exit("Token faltante");
}

try {
    $pdo = conectarDB();
    $stmt = $pdo->prepare("SELECT id FROM equipos WHERE token=? AND activo=1 LIMIT 1");
    $stmt->execute([$token]);
    $equipo = $stmt->fetch();

    if (!$equipo) {
        http_response_code(403);
        exit("Token inválido");
    }

    // Obtener escalas y cámaras
    $stmt = $pdo->query("SELECT * FROM scales");
    $scales = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($scales as &$scale) {
        $stmt = $pdo->prepare("SELECT * FROM cameras WHERE scale_id=?");
        $stmt->execute([$scale['id']]);
        $scale['cameras'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    header("Content-Type: application/json");
    echo json_encode($scales);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
