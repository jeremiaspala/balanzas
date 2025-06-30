<?php
require_once 'config.php';

function load_scales($db) {
    $scales = [];
    if ($result = $db->query('SELECT * FROM scales')) {
        while ($row = $result->fetch_assoc()) {
            $scales[] = $row;
        }
        $result->free();
    }
    return $scales;
}

function store_weight($db, $scale_id, $weight, $stable) {
    $stmt = $db->prepare('INSERT INTO weights (scale_id, weight, timestamp, stable) VALUES (?,?,NOW(),?)');
    $stmt->bind_param('idi', $scale_id, $weight, $stable);
    $stmt->execute();
    $stmt->close();
}

function capture_images($db, $scale_id, $image_dir) {
    $stmt = $db->prepare('SELECT * FROM cameras WHERE scale_id=?');
    $stmt->bind_param('i', $scale_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($cam = $res->fetch_assoc()) {
        try {
            $data = file_get_contents($cam['url']);
            if ($data !== false) {
                $datePath = date('Ymd');
                $dir = $image_dir . '/' . $scale_id . '/' . $datePath;
                if (!is_dir($dir)) {
                    mkdir($dir, 0777, true);
                }
                $filename = date('His') . '_cam' . $cam['id'] . '.jpg';
                file_put_contents($dir . '/' . $filename, $data);
            }
        } catch (Exception $e) {
            echo "Failed to capture image from {$cam['name']}: {$e->getMessage()}\n";
        }
    }
    $stmt->close();
}

function read_weight($conn) {
    $line = fgets($conn);
    if ($line === false) {
        return 0.0;
    }
    return floatval(trim($line));
}

$db = db_connect();
$scales = load_scales($db);
$image_dir = '/var/www/html/balanzas';

$connections = [];
foreach ($scales as $scale) {
    $conn = @fsockopen($scale['ip'], $scale['port']);
    if ($conn) {
        stream_set_timeout($conn, 1);
        $connections[$scale['id']] = ['conn' => $conn, 'last' => null, 'stable' => 0];
    } else {
        echo "Failed to connect to {$scale['name']}\n";
    }
}

while (true) {
    foreach ($connections as $id => &$info) {
        $weight = read_weight($info['conn']);
        if ($info['last'] !== null && abs($weight - $info['last']) < 0.01) {
            $info['stable'] += 1;
        } else {
            $info['stable'] = 0;
        }
        store_weight($db, $id, $weight, $info['stable'] >= 5 ? 1 : 0);
        if ($info['stable'] == 5) {
            capture_images($db, $id, $image_dir);
        }
        $info['last'] = $weight;
    }
    sleep(1);
}
?>
