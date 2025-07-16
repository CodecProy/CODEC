<?php
// Encabezados CORS para permitir acceso
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');

// CONFIGURACIÓN PARA INFINITY FREE
$servername = "localhost";
$username = "if0_39478618";
$password = "C3rrEiwVe81HS1";
$dbname = "if0_39478618_altaltium";

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die(json_encode(['error' => "Connection failed: " . $conn->connect_error]));
}

// Consulta para obtener los tipos de cálculo
$sql = "SELECT id_calculadora, cal_tipo, cal_honorario_minimo, cal_pendiente, cal_punto_cambio FROM calculadora_CODEC";
$result = $conn->query($sql);

if (!$result) {
    die(json_encode(['error' => "Query failed: " . $conn->error]));
}

$tipos = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        // Normalizar los nombres de tipos
        $tipoKey = strtolower($row['cal_tipo']);
        if (strpos($tipoKey, 'sentencia') !== false) {
            $tipoKey = 'sentencia';
        } elseif (strpos($tipoKey, 'adjudicada') !== false) {
            $tipoKey = 'adjudicadas';
        }
        
        $tipos[$tipoKey] = [
            'nombre' => $row['cal_tipo'],
            'honorarioMinimo' => (float)$row['cal_honorario_minimo'],
            'pendiente' => (float)$row['cal_pendiente'],
            'puntoCambio' => (float)$row['cal_punto_cambio'],
            'valorComercialExtrajudicial' => null
        ];
    }
}

$conn->close();

if (empty($tipos)) {
    echo json_encode(['error' => 'No se encontraron tipos de cálculo']);
} else {
    echo json_encode($tipos);
}
?>