<?php
function db_connect() {
    $config = [
        'host' => 'localhost',
        'user' => 'balanzas',
        'password' => 'balanzas',
        'database' => 'balanzas'
    ];
    $mysqli = new mysqli($config['host'], $config['user'], $config['password'], $config['database']);
    if ($mysqli->connect_error) {
        die('Connect Error (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error);
    }
    return $mysqli;
}
?>
