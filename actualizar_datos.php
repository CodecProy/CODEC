<?php
// Usar PDO en lugar de MySQLi
$servername = "sql302.infinityfree.com";
$username = "if0_39495455";
$password = "bSf0NYLYcmfaFZf";
$dbname = "if0_39495455_altaltium";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Verificar datos POST
    if(empty($_POST['tipo']) || empty($_POST['minimo']) || empty($_POST['pendiente']) || empty($_POST['punto'])) {
        die("Datos incompletos");
    }

    $tipos = $_POST['tipo'];
    $minimos = $_POST['minimo'];
    $pendientes = $_POST['pendiente'];
    $puntos = $_POST['punto'];

    for ($i = 0; $i < count($tipos); $i++) {
        $tipo = $tipos[$i];
        $min = floatval($minimos[$i]);
        $pen = floatval($pendientes[$i]);
        $punto = floatval($puntos[$i]);

        $sql = "UPDATE calculadora_codec 
                SET cal_honorario_minimo = :min, 
                    cal_pendiente = :pen, 
                    cal_punto_cambio = :punto 
                WHERE cal_tipo = :tipo";
                    
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':min', $min, PDO::PARAM_STR);
        $stmt->bindParam(':pen', $pen, PDO::PARAM_STR);
        $stmt->bindParam(':punto', $punto, PDO::PARAM_STR);
        $stmt->bindParam(':tipo', $tipo, PDO::PARAM_STR);
        $stmt->execute();
    }
    
    echo "Cambios guardados correctamente";
    
} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>