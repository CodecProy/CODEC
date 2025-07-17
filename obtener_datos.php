<?php
header('Content-Type: application/json');

$servername = "sql302.infinityfree.com";
$username = "if0_39495455";
$password = "bSf0NYLYcmfaFZf";
$dbname = "if0_39495455_altaltium";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $sql = "SELECT id_calculadora, cal_tipo, cal_honorario_minimo, cal_pendiente, cal_punto_cambio FROM calculadora_codec";
    $stmt = $conn->query($sql);
    
    $tipos = [];
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $tipos[] = [
            'nombre' => $row['cal_tipo'],
            'honorarioMinimo' => (float)$row['cal_honorario_minimo'],
            'pendiente' => (float)$row['cal_pendiente'],
            'puntoCambio' => (float)$row['cal_punto_cambio']
        ];
    }
    
    echo json_encode($tipos);
    
} catch(PDOException $e) {
    die(json_encode(['error' => "Database error: " . $e->getMessage()]));
}
?>