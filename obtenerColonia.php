<?php
require 'conexion.php';

$coloniaId = isset($_GET['id']) ? $_GET['id'] : '';

if (empty($coloniaId)) {
    echo json_encode(['error' => 'Se requiere ID de colonia']);
    exit;
}

$sql = "SELECT c.*, a.alcaldia_nombre, e.estado_nombre 
        FROM colonia_CODEC c
        JOIN alcaldia_CODEC a ON c.colonia_alcaldia = a.id_alcaldia
        JOIN estado_CODEC e ON a.alcaldia_estado = e.id_estado
        WHERE c.id_colonia = :coloniaId";

$stmt = $conn->prepare($sql);
$stmt->bindParam(':coloniaId', $coloniaId);
$stmt->execute();

$result = $stmt->fetch(PDO::FETCH_ASSOC);

if ($result) {
    // Agregar coordenadas aproximadas (deberías tener esto en tu BD)
    $result['coordenadas'] = "19.4326,-99.1332"; // Ejemplo para CDMX
    echo json_encode($result);
} else {
    echo json_encode(['error' => 'Colonia no encontrada']);
}
?>