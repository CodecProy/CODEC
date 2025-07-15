<?php
require 'conexion.php';

// Establecer el tipo de contenido primero
header('Content-Type: application/json');

// Validar y obtener el ID de alcaldía
$alcaldiaId = isset($_POST['id_alcaldia']) ? (int)$_POST['id_alcaldia'] : 0;

if ($alcaldiaId <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID de alcaldía no válido']);
    exit;
}

// Lista de campos permitidos para actualización
$allowedFields = [
    'promedio_residencial', 'prop_residencial', 'hey_residencial', 
    'clau_residencial', 'mud_residencial', 'altal_residencial',
    'promedio_comercial', 'prop_comercial', 'hey_comercial',
    'clau_comercial', 'mud_comercial', 'altal_comercial'
];

// Preparar los datos para la actualización
$updateData = ['id_alcaldia' => $alcaldiaId];
$updateFields = [];

foreach ($allowedFields as $field) {
    if (isset($_POST[$field])) {
        // Sanitizar y validar el valor como número
        $value = str_replace(',', '', $_POST[$field]); // Eliminar comas
        if (is_numeric($value)) {
            $updateData[$field] = (float)$value;
            $updateFields[] = "$field = :$field";
        }
    }
}

// Si no hay campos válidos para actualizar
if (empty($updateFields)) {
    echo json_encode(['success' => false, 'error' => 'No se proporcionaron datos válidos para actualizar']);
    exit;
}

try {
    // Construir la consulta SQL dinámica
    $sql = "UPDATE precios_alcaldias_CODEC SET " . implode(', ', $updateFields) . " WHERE id_alcaldia = :id_alcaldia";
    $stmt = $conn->prepare($sql);
    
    // Ejecutar la actualización
    if ($stmt->execute($updateData)) {
        // Verificar si se actualizó algún registro
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'No se encontró el registro para actualizar']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Error al ejecutar la actualización']);
    }
} catch (PDOException $e) {
    // Registrar el error y devolver mensaje genérico
    error_log("Error en actualizarPreciosAlcaldia.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error en el servidor al actualizar los precios']);
}
?>