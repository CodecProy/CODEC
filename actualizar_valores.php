<?php
header('Content-Type: application/json');

$servername = "sql302.infinityfree.com";
$username = "if0_39478618";
$password = "C3rrEiwVe81HS1";
$dbname = "if0_39478618_altaltium";

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die(json_encode(['error' => "Connection failed: " . $conn->connect_error]));
}

// Obtener datos del cuerpo de la solicitud
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['tipo'], $data['campo'], $data['valor'])) {
    die(json_encode(['error' => 'Datos incompletos']));
}

// Mapear tipos a los nombres en la base de datos
$tiposDB = [
    'sentencia' => 'Sentencia',
    'adjudicadas' => 'Adjudicada'
];

if (!isset($tiposDB[$data['tipo']])) {
    die(json_encode(['error' => 'Tipo no válido']));
}

// Mapear campos a columnas de la base de datos
$camposDB = [
    'honorarioMinimo' => 'cal_honorario_minimo',
    'pendiente' => 'cal_pendiente',
    'puntoCambio' => 'cal_punto_cambio'
];

if (!isset($camposDB[$data['campo']])) {
    die(json_encode(['error' => 'Campo no válido']));
}

// Preparar la consulta SQL
$sql = "UPDATE calculadora_CODEC SET {$camposDB[$data['campo']]} = ? WHERE cal_tipo = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    die(json_encode(['error' => "Prepare failed: " . $conn->error]));
}

$stmt->bind_param("ds", $data['valor'], $tiposDB[$data['tipo']]);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['error' => "Execute failed: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>