<?php
header('Content-Type: application/json');
$servername = "sql302.infinityfree.com";
$username = "if0_39478618";
$password = "C3rrEiwVe81HS1";
$dbname = "if0_39478618_altaltium";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die(json_encode(['error' => "Connection failed: " . $conn->connect_error]));
}

$sql = "SELECT id_calculadora, cal_tipo, cal_honorario_minimo, cal_pendiente, cal_punto_cambio FROM calculadora_CODEC";
$result = $conn->query($sql);

if (!$result) {
    die(json_encode(['error' => "Query failed: " . $conn->error]));
}

$tipos = [];
while($row = $result->fetch_assoc()) {
    $tipoKey = strtolower($row['cal_tipo']);
    if (strpos($tipoKey, 'sentencia') !== false) $tipoKey = 'sentencia';
    elseif (strpos($tipoKey, 'adjudicada') !== false) $tipoKey = 'adjudicadas';

    $tipos[$tipoKey] = [
        'nombre' => $row['cal_tipo'],
        'honorarioMinimo' => (float)$row['cal_honorario_minimo'],
        'pendiente' => (float)$row['cal_pendiente'],
        'puntoCambio' => (float)$row['cal_punto_cambio']
    ];
}
$conn->close();
echo json_encode($tipos);
?>
