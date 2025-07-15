<?php
$servername = "sql302.infinityfree.com";
$username = "if0_39478618";
$password = "C3rrEiwVe81HS1";
$dbname = "if0_39478618_altaltium";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

$tipos = $_POST['tipo'];
$minimos = $_POST['minimo'];
$pendientes = $_POST['pendiente'];
$puntos = $_POST['punto'];

for ($i = 0; $i < count($tipos); $i++) {
    $tipo = $conn->real_escape_string($tipos[$i]);
    $min = floatval($minimos[$i]);
    $pen = floatval($pendientes[$i]);
    $punto = floatval($puntos[$i]);

    $sql = "UPDATE calculadora_CODEC 
            SET cal_honorario_minimo = $min, 
                cal_pendiente = $pen, 
                cal_punto_cambio = $punto 
            WHERE cal_tipo = '$tipo'";
    $conn->query($sql);
}

$conn->close();
echo "Cambios guardados correctamente";
?>
