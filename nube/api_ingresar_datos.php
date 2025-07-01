<?php
// api/ingresar_datos.php
require_once "db.php";

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['token'], $data['scale_id'], $data['weight'])) {
    http_response_code(400);
    exit("Faltan datos obligatorios");
}

try {
    $pdo = conectarDB();
    
    // Validar token
    $stmt = $pdo->prepare("SELECT id FROM equipos WHERE token = ? AND activo = 1 LIMIT 1");
    $stmt->execute([$data['token']]);
    if (!$stmt->fetch()) {
        http_response_code(403);
        exit("Token no autorizado");
    }

    $stmt = $pdo->prepare("INSERT INTO weights (scale_id, weight, timestamp, stable) VALUES (?, ?, ?, ?)");
    $stmt->execute([
        $data['scale_id'],
        $data['weight'],
        $data['timestamp'] ?? date("Y-m-d H:i:s"),
        $data['stable'] ?? 0
    ]);

    echo "OK";
} catch (Exception $e) {
    http_response_code(500);
    echo "Error del servidor: " . $e->getMessage();
}
