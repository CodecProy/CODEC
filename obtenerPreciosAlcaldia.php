<?php
require 'conexion.php';

// Establecer el tipo de contenido primero para evitar errores
header('Content-Type: application/json');

// Obtener y validar el ID de alcaldía
$alcaldiaId = isset($_GET['id_alcaldia']) ? (int)$_GET['id_alcaldia'] : 0;

if ($alcaldiaId <= 0) {
    echo json_encode(['error' => 'ID de alcaldía no válido']);
    exit;
}

try {
    // Verificar si existe la alcaldía en la tabla alcaldia_CODEC
    $stmtCheck = $conn->prepare("SELECT 1 FROM alcaldia_CODEC WHERE id_alcaldia = ?");
    $stmtCheck->execute([$alcaldiaId]);
    
    if (!$stmtCheck->fetch()) {
        echo json_encode(['error' => 'Alcaldía no encontrada en el sistema']);
        exit;
    }

    // Verificar si ya existen precios para esta alcaldía
    $stmt = $conn->prepare("SELECT * FROM precios_alcaldias_CODEC WHERE id_alcaldia = ?");
    $stmt->execute([$alcaldiaId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        // Si existen datos, devolverlos
        echo json_encode($result);
    } else {
        // Si no existen, crear un nuevo registro con valores por defecto
        $insert = $conn->prepare("
            INSERT INTO precios_alcaldias_CODEC 
            (id_alcaldia, promedio_residencial, prop_residencial, hey_residencial, 
             clau_residencial, mud_residencial, altal_residencial,
             promedio_comercial, prop_comercial, hey_comercial,
             clau_comercial, mud_comercial, altal_comercial)
            VALUES (?, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0)
        ");
        
        if ($insert->execute([$alcaldiaId])) {
            // Volver a obtener el registro recién creado
            $stmt->execute([$alcaldiaId]);
            echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
        } else {
            echo json_encode(['error' => 'No se pudo crear el registro de precios']);
        }
    }
} catch (PDOException $e) {
    // Registrar el error y devolver mensaje genérico
    error_log("Error en obtenerPreciosAlcaldia.php: " . $e->getMessage());
    echo json_encode(['error' => 'Error en el servidor al obtener los precios']);
}
?>