<?php
header('Content-Type: application/json');
$mysqli = new mysqli("localhost", "balanzas", "balanzas", "balanzas");

// Obtener últimas lecturas por balanza
$sql = "
SELECT w.scale_id, s.name, w.weight, w.timestamp, w.stable
FROM weights w
JOIN (
    SELECT scale_id, MAX(timestamp) AS latest FROM weights GROUP BY scale_id
    ) AS ult
    ON w.scale_id = ult.scale_id AND w.timestamp = ult.latest
    JOIN scales s ON s.id = w.scale_id
    ORDER BY s.name ASC
    ";

    $res = $mysqli->query($sql);
    $salida = [];
    while ($r = $res->fetch_assoc()) {
        $salida[] = [
            "name" => $r['name'],
            "weight" => (float)$r['weight'],
            "timestamp" => $r['timestamp'],
            "stable" => (bool)$r['stable']
        ];
    }
    echo json_encode($salida);
