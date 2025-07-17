<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$servername = "sql302.infinityfree.com";
$username = "if0_39495455";
$password = "bSf0NYLYcmfaFZf";
$dbname = "if0_39495455_altaltium";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->exec("SET NAMES utf8mb4");
} catch(PDOException $e) {
    echo json_encode(['error' => 'Error de conexión: ' . $e->getMessage()]);
    exit;
}
?>