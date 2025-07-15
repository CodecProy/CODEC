<?php
session_start();

// Configuración de la base de datos
$servername = "sql302.infinityfree.com";
$username = "if0_39478618";
$password = "C3rrEiwVe81HS1";
$dbname = "if0_39478618_altaltium";

// Establecer conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    // Registrar el error en un archivo de log en producción
    error_log("Error de conexión a la base de datos: " . $conn->connect_error);
    header("Location: index.html?error=db");
    exit();
}

// Validar que se hayan enviado los datos
if (empty($_POST['username']) || empty($_POST['password'])) {
    header("Location: index.html?error=empty");
    exit();
}

// Obtener y sanitizar los datos
$user = trim($_POST['username']);
$pass = trim($_POST['password']);

// Preparar la consulta con sentencias preparadas para seguridad
$stmt = $conn->prepare("SELECT estatus FROM usuarios_codec WHERE usuario = ? AND contrasena = ?");
if (!$stmt) {
    error_log("Error al preparar la consulta: " . $conn->error);
    header("Location: index.html?error=db");
    exit();
}

// Vincular parámetros y ejecutar
$stmt->bind_param("ss", $user, $pass);
$stmt->execute();

// Obtener resultados
$result = $stmt->get_result();

// Verificar si se encontró el usuario
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $estatus = strtolower($row['estatus']);
    
    // Establecer sesión
    $_SESSION['usuario'] = $user;
    $_SESSION['estatus'] = $estatus;
    
    // Redirigir según el estatus
    if ($estatus == "super") {
        header("Location: inicio.html");
    } else {
        header("Location: menu.html");
    }
    exit();
} else {
    // Usuario no encontrado o contraseña incorrecta
    header("Location: index.html?error=auth");
    exit();
}

// Cerrar conexiones
$stmt->close();
$conn->close();
?>